from app.core.config import DeploymentMode, Settings
from app.runtime.state import local_runtime_state


def initialize_local_runtime(settings: Settings) -> None:
    local_runtime_state.clear()

    if settings.rag_deployment_mode is not DeploymentMode.LOCAL:
        return

    from app.runtime.resolver import LocalRuntimeResolver
    from app.runtime.telemetry import PsutilResourceTelemetry
    from app.runtime.torch_runtime import TorchRuntimeAdapter

    resolver = LocalRuntimeResolver(
        runtime=TorchRuntimeAdapter(),
        telemetry=PsutilResourceTelemetry(),
    )

    snapshot = resolver.resolve(settings.local_device)

    local_runtime_state.set(snapshot)
