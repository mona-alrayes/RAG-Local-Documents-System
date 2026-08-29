import gc
from collections.abc import Callable, Iterator
from contextlib import contextmanager
from dataclasses import dataclass
from sys import exc_info
from threading import BoundedSemaphore, Lock
from time import perf_counter
from typing import Generic, TypeVar

from app.core.exceptions import ApplicationException
from app.runtime.models import (
    LocalRuntimeSnapshot,
    ResourceSnapshot,
)
from app.runtime.telemetry import ResourceTelemetry
from app.runtime.torch_runtime import TorchRuntimeAdapter


ModelT = TypeVar("ModelT")


class LocalModelLease(Generic[ModelT]):
    def __init__(self, resource: ModelT) -> None:
        self._resource: ModelT | None = resource

    @property
    def resource(self) -> ModelT:
        if self._resource is None:
            raise RuntimeError(
                "Local model lease has already been released."
            )

        return self._resource

    def _release(self) -> None:
        self._resource = None


@dataclass(frozen=True, slots=True)
class LocalModelLifecycleMetrics:
    model_id: str
    resources_before_load: ResourceSnapshot
    load_duration_ms: int
    resources_after_load: ResourceSnapshot
    release_duration_ms: int
    resources_after_release: ResourceSnapshot


class LocalModelCoordinator:
    def __init__(
        self,
        *,
        runtime_snapshot: LocalRuntimeSnapshot,
        telemetry: ResourceTelemetry,
        runtime: TorchRuntimeAdapter,
        min_available_memory_ratio: float,
        max_concurrency: int = 1,
    ) -> None:
        if max_concurrency != 1:
            raise ValueError(
                "LocalModelCoordinator supports max_concurrency=1 only."
            )

        if (
            not runtime_snapshot.ready
            or runtime_snapshot.selected_backend is None
        ):
            raise ApplicationException(
                code="local_model_runtime_unavailable",
                message="Local model runtime is not ready.",
            )

        self._backend = runtime_snapshot.selected_backend
        self._telemetry = telemetry
        self._runtime = runtime
        self._min_available_memory_ratio = min_available_memory_ratio

        self._gate = BoundedSemaphore(max_concurrency)
        self._state_lock = Lock()

        self._active_model: str | None = None
        self._last_metrics: LocalModelLifecycleMetrics | None = None

    @property
    def active_model(self) -> str | None:
        with self._state_lock:
            return self._active_model

    @property
    def last_metrics(self) -> LocalModelLifecycleMetrics | None:
        with self._state_lock:
            return self._last_metrics

    @contextmanager
    def lease(
        self,
        *,
        model_id: str,
        loader: Callable[[], ModelT],
    ) -> Iterator[LocalModelLease[ModelT]]:
        with self._gate:
            resources_before_load = self._resource_snapshot()
            self._ensure_memory_available(resources_before_load)

            load_started = perf_counter()
            lease: LocalModelLease[ModelT] | None = None
            resources_after_load: ResourceSnapshot | None = None

            try:
                resource = loader()
                lease = LocalModelLease(resource)
                resource = None

                load_duration_ms = self._elapsed_ms(load_started)
                resources_after_load = self._resource_snapshot()

                with self._state_lock:
                    self._active_model = model_id

                yield lease
            finally:
                release_started = perf_counter()
                had_active_exception = exc_info()[0] is not None

                with self._state_lock:
                    self._active_model = None

                if lease is not None:
                    lease._release()

                gc.collect()

                try:
                    self._runtime.release_cache(self._backend)
                except Exception:
                    if not had_active_exception:
                        raise

                release_duration_ms = self._elapsed_ms(
                    release_started
                )

                try:
                    resources_after_release = (
                        self._resource_snapshot()
                    )
                except Exception:
                    if not had_active_exception:
                        raise

                    resources_after_release = ResourceSnapshot()

                if resources_after_load is not None:
                    metrics = LocalModelLifecycleMetrics(
                        model_id=model_id,
                        resources_before_load=resources_before_load,
                        load_duration_ms=load_duration_ms,
                        resources_after_load=resources_after_load,
                        release_duration_ms=release_duration_ms,
                        resources_after_release=resources_after_release,
                    )

                    with self._state_lock:
                        self._last_metrics = metrics

    def _ensure_memory_available(
        self,
        resources: ResourceSnapshot,
    ) -> None:
        available = resources.system_available_memory_bytes
        total = resources.system_total_memory_bytes

        if available is None or total is None or total <= 0:
            raise ApplicationException(
                code="local_resource_telemetry_unavailable",
                message="Local memory telemetry is unavailable.",
            )

        available_ratio = available / total

        if available_ratio < self._min_available_memory_ratio:
            raise ApplicationException(
                code="local_resource_exhausted",
                message=(
                    "Available local memory is below "
                    "the required minimum."
                ),
            )

    def _resource_snapshot(self) -> ResourceSnapshot:
        system_resources = self._telemetry.snapshot()

        try:
            allocated, cached_or_reserved = (
                self._runtime.accelerator_memory(
                    self._backend
                )
            )
        except RuntimeError:
            allocated = None
            cached_or_reserved = None

        return ResourceSnapshot(
            process_rss_bytes=system_resources.process_rss_bytes,
            system_available_memory_bytes=(
                system_resources.system_available_memory_bytes
            ),
            system_total_memory_bytes=(
                system_resources.system_total_memory_bytes
            ),
            accelerator_allocated_bytes=allocated,
            accelerator_cached_or_reserved_bytes=cached_or_reserved,
        )

    @staticmethod
    def _elapsed_ms(started: float) -> int:
        return round((perf_counter() - started) * 1000)
