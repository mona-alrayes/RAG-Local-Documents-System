from app.core.config import DeploymentMode
from app.runtime.models import LocalRuntimeSnapshot
from app.schemas.capabilities import (
    DeploymentCapabilitiesResponse,
    ProviderCapability,
    ProviderStatus,
)
from app.schemas.runtime import LocalRuntimeCapability


SHARED_PROVIDERS = (
    "llama_parse",
)

CLOUD_PROFILE_PROVIDERS = (
    "jina_embeddings",
    "jina_reranker",
    "hugging_face_llm",
)

HYBRID_LOCAL_PROFILE_PROVIDERS = (
    "bge_m3_embeddings",
    "bge_reranker",
    "ollama_llm",
)


class CapabilitiesService:
    def build(
        self,
        deployment_mode: DeploymentMode,
        local_runtime_snapshot: LocalRuntimeSnapshot | None = None,
    ) -> DeploymentCapabilitiesResponse:
        if deployment_mode is DeploymentMode.CLOUD:
            return DeploymentCapabilitiesResponse(
                deployment_mode=deployment_mode,
                supported_profiles=["cloud"],
                available_profiles=[],
                providers=self._providers(
                    SHARED_PROVIDERS + CLOUD_PROFILE_PROVIDERS,
                ),
                local_runtime=None,
            )

        return DeploymentCapabilitiesResponse(
            deployment_mode=deployment_mode,
            supported_profiles=["cloud", "hybrid_local"],
            available_profiles=[],
            providers=self._providers(
                SHARED_PROVIDERS
                + CLOUD_PROFILE_PROVIDERS
                + HYBRID_LOCAL_PROFILE_PROVIDERS,
            ),
            local_runtime=self._local_runtime(
                local_runtime_snapshot,
            ),
        )

    def _providers(
        self,
        provider_names: tuple[str, ...],
    ) -> list[ProviderCapability]:
        return [
            ProviderCapability(
                provider=provider_name,
                status=ProviderStatus.NOT_CHECKED,
            )
            for provider_name in provider_names
        ]

    def _local_runtime(
        self,
        snapshot: LocalRuntimeSnapshot | None,
    ) -> LocalRuntimeCapability | None:
        if snapshot is None:
            return None

        return LocalRuntimeCapability(
            ready=snapshot.ready,
            requested_device=snapshot.requested_device,
            selected_backend=snapshot.selected_backend,
            selected_dtype=snapshot.selected_dtype,
            probe_status=snapshot.probe_status,
            failure_reason=snapshot.failure_reason,
        )
