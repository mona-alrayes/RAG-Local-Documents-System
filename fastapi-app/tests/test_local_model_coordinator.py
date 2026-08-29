from threading import Event, Thread

from app.core.exceptions import ApplicationException
from app.runtime.model_coordinator import LocalModelCoordinator
from app.runtime.models import (
    LocalRuntimeSnapshot,
    ResourceSnapshot,
    RuntimeBackend,
    RuntimeDtype,
    RuntimeProbeStatus,
)


class FakeTelemetry:
    def __init__(
        self,
        *,
        available: int = 8_000,
        total: int = 10_000,
    ) -> None:
        self.available = available
        self.total = total
        self.calls = 0

    def snapshot(self) -> ResourceSnapshot:
        self.calls += 1

        return ResourceSnapshot(
            process_rss_bytes=1_000,
            system_available_memory_bytes=self.available,
            system_total_memory_bytes=self.total,
        )


class FakeRuntime:
    def __init__(self) -> None:
        self.release_calls: list[RuntimeBackend] = []

    def accelerator_memory(
        self,
        backend: RuntimeBackend,
    ) -> tuple[int | None, int | None]:
        return 200, 300

    def release_cache(self, backend: RuntimeBackend) -> None:
        self.release_calls.append(backend)


def make_runtime_snapshot() -> LocalRuntimeSnapshot:
    return LocalRuntimeSnapshot(
        ready=True,
        requested_device="auto",
        selected_backend=RuntimeBackend.CPU,
        selected_dtype=RuntimeDtype.FP32,
        probe_status=RuntimeProbeStatus.PASSED,
        failure_reason=None,
        resources=ResourceSnapshot(),
    )


def make_coordinator(
    *,
    telemetry: FakeTelemetry | None = None,
    runtime: FakeRuntime | None = None,
) -> tuple[
    LocalModelCoordinator,
    FakeTelemetry,
    FakeRuntime,
]:
    telemetry = telemetry or FakeTelemetry()
    runtime = runtime or FakeRuntime()

    coordinator = LocalModelCoordinator(
        runtime_snapshot=make_runtime_snapshot(),
        telemetry=telemetry,
        runtime=runtime,
        min_available_memory_ratio=0.15,
        max_concurrency=1,
    )

    return coordinator, telemetry, runtime


def test_local_model_coordinator_starts_empty_and_loads_lazily() -> None:
    coordinator, _, _ = make_coordinator()
    load_calls = 0

    def loader() -> object:
        nonlocal load_calls
        load_calls += 1
        return object()

    assert coordinator.active_model is None
    assert coordinator.last_metrics is None
    assert load_calls == 0

    with coordinator.lease(
        model_id="BAAI/bge-m3",
        loader=loader,
    ) as lease:
        assert lease.resource is not None
        assert load_calls == 1
        assert coordinator.active_model == "BAAI/bge-m3"

    assert coordinator.active_model is None


def test_local_model_coordinator_releases_after_success() -> None:
    coordinator, telemetry, runtime = make_coordinator()

    with coordinator.lease(
        model_id="BAAI/bge-m3",
        loader=object,
    ):
        assert coordinator.active_model == "BAAI/bge-m3"

    assert coordinator.active_model is None
    assert runtime.release_calls == [RuntimeBackend.CPU]
    assert telemetry.calls == 3


def test_local_model_coordinator_releases_after_stage_exception() -> None:
    coordinator, _, runtime = make_coordinator()

    try:
        with coordinator.lease(
            model_id="BAAI/bge-m3",
            loader=object,
        ):
            assert coordinator.active_model == "BAAI/bge-m3"
            raise RuntimeError("stage failed")
    except RuntimeError as exc:
        assert str(exc) == "stage failed"
    else:
        raise AssertionError("Expected RuntimeError")

    assert coordinator.active_model is None
    assert runtime.release_calls == [RuntimeBackend.CPU]


def test_local_model_coordinator_cleans_up_after_loader_failure() -> None:
    coordinator, _, runtime = make_coordinator()

    def failing_loader() -> object:
        raise RuntimeError("load failed")

    try:
        with coordinator.lease(
            model_id="BAAI/bge-m3",
            loader=failing_loader,
        ):
            raise AssertionError("Lease body must not execute")
    except RuntimeError as exc:
        assert str(exc) == "load failed"
    else:
        raise AssertionError("Expected RuntimeError")

    assert coordinator.active_model is None
    assert runtime.release_calls == [RuntimeBackend.CPU]


def test_local_model_coordinator_memory_gate_blocks_before_load() -> None:
    telemetry = FakeTelemetry(
        available=1_000,
        total=10_000,
    )
    coordinator, _, runtime = make_coordinator(
        telemetry=telemetry,
    )

    loader_called = False

    def loader() -> object:
        nonlocal loader_called
        loader_called = True
        return object()

    try:
        with coordinator.lease(
            model_id="BAAI/bge-m3",
            loader=loader,
        ):
            raise AssertionError("Lease body must not execute")
    except ApplicationException as exc:
        assert exc.code == "local_resource_exhausted"
    else:
        raise AssertionError("Expected ApplicationException")

    assert loader_called is False
    assert coordinator.active_model is None
    assert coordinator.last_metrics is None
    assert runtime.release_calls == []


def test_local_model_coordinator_records_lifecycle_metrics() -> None:
    coordinator, _, _ = make_coordinator()

    with coordinator.lease(
        model_id="BAAI/bge-m3",
        loader=object,
    ):
        pass

    metrics = coordinator.last_metrics

    assert metrics is not None
    assert metrics.model_id == "BAAI/bge-m3"

    assert metrics.resources_before_load.process_rss_bytes == 1_000
    assert (
        metrics.resources_before_load.system_available_memory_bytes
        == 8_000
    )
    assert metrics.resources_before_load.system_total_memory_bytes == 10_000

    assert metrics.resources_after_load.accelerator_allocated_bytes == 200
    assert (
        metrics.resources_after_load.accelerator_cached_or_reserved_bytes
        == 300
    )

    assert metrics.load_duration_ms >= 0
    assert metrics.release_duration_ms >= 0

    assert metrics.resources_after_release.process_rss_bytes == 1_000


def test_local_model_coordinator_never_has_two_active_models() -> None:
    coordinator, _, _ = make_coordinator()

    first_entered = Event()
    allow_first_release = Event()
    second_entered = Event()

    observed_active_models: list[str | None] = []

    def first_stage() -> None:
        with coordinator.lease(
            model_id="model-a",
            loader=object,
        ):
            observed_active_models.append(coordinator.active_model)
            first_entered.set()
            allow_first_release.wait(timeout=2)

    def second_stage() -> None:
        first_entered.wait(timeout=2)

        with coordinator.lease(
            model_id="model-b",
            loader=object,
        ):
            observed_active_models.append(coordinator.active_model)
            second_entered.set()

    first_thread = Thread(target=first_stage)
    second_thread = Thread(target=second_stage)

    first_thread.start()
    second_thread.start()

    assert first_entered.wait(timeout=2)
    assert coordinator.active_model == "model-a"

    # The second lease must still be blocked by the single-active gate.
    assert second_entered.wait(timeout=0.05) is False
    assert coordinator.active_model == "model-a"

    allow_first_release.set()

    first_thread.join(timeout=2)
    second_thread.join(timeout=2)

    assert first_thread.is_alive() is False
    assert second_thread.is_alive() is False

    assert observed_active_models == [
        "model-a",
        "model-b",
    ]
    assert coordinator.active_model is None


def test_local_model_coordinator_preserves_stage_exception_when_cleanup_fails(
    monkeypatch,
) -> None:
    coordinator, _, runtime = make_coordinator()

    def failing_release_cache(backend: RuntimeBackend) -> None:
        raise RuntimeError("cleanup failed")

    monkeypatch.setattr(
        runtime,
        "release_cache",
        failing_release_cache,
    )

    try:
        with coordinator.lease(
            model_id="BAAI/bge-m3",
            loader=object,
        ):
            raise ValueError("stage failed")
    except ValueError as exc:
        assert str(exc) == "stage failed"
    else:
        raise AssertionError("Expected ValueError")

    assert coordinator.active_model is None


def test_local_model_coordinator_surfaces_cleanup_failure_after_success(
    monkeypatch,
) -> None:
    coordinator, _, runtime = make_coordinator()

    def failing_release_cache(backend: RuntimeBackend) -> None:
        raise RuntimeError("cleanup failed")

    monkeypatch.setattr(
        runtime,
        "release_cache",
        failing_release_cache,
    )

    try:
        with coordinator.lease(
            model_id="BAAI/bge-m3",
            loader=object,
        ):
            pass
    except RuntimeError as exc:
        assert str(exc) == "cleanup failed"
    else:
        raise AssertionError("Expected RuntimeError")

    assert coordinator.active_model is None
