from app.core.config import DeploymentMode, Settings
from app.runtime.state import (
    local_model_coordinator_state,
    local_runtime_state,
)


def initialize_local_runtime(settings: Settings) -> None:
    local_runtime_state.clear()
    local_model_coordinator_state.clear()

    if settings.rag_deployment_mode is not DeploymentMode.LOCAL:
        return

    from app.runtime.resolver import LocalRuntimeResolver
    from app.runtime.telemetry import PsutilResourceTelemetry
    from app.runtime.torch_runtime import TorchRuntimeAdapter

    runtime = TorchRuntimeAdapter()
    telemetry = PsutilResourceTelemetry()

    resolver = LocalRuntimeResolver(
        runtime=runtime,
        telemetry=telemetry,
    )

    snapshot = resolver.resolve(settings.local_device)

    local_runtime_state.set(snapshot)

    if (
        not snapshot.ready
        or snapshot.selected_backend is None
        or snapshot.selected_dtype is None
    ):
        return

    from app.runtime.model_coordinator import LocalModelCoordinator

    coordinator = LocalModelCoordinator(
        runtime_snapshot=snapshot,
        telemetry=telemetry,
        runtime=runtime,
        min_available_memory_ratio=(
            settings.local_min_available_memory_ratio
        ),
        max_concurrency=settings.local_ai_max_concurrency,
    )

    local_model_coordinator_state.set(coordinator)
