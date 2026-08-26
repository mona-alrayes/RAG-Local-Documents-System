from app.core.config import DeploymentMode
from app.schemas.capabilities import (
    DeploymentCapabilitiesResponse,
    ProviderCapability,
    ProviderStatus,
)


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
    ) -> DeploymentCapabilitiesResponse:
        if deployment_mode is DeploymentMode.CLOUD:
            return DeploymentCapabilitiesResponse(
                deployment_mode=deployment_mode,
                supported_profiles=["cloud"],
                available_profiles=[],
                compare_available=False,
                providers=self._providers(
                    SHARED_PROVIDERS + CLOUD_PROFILE_PROVIDERS,
                ),
            )

        return DeploymentCapabilitiesResponse(
            deployment_mode=deployment_mode,
            supported_profiles=["cloud", "hybrid_local"],
            available_profiles=[],
            compare_available=False,
            providers=self._providers(
                SHARED_PROVIDERS
                + CLOUD_PROFILE_PROVIDERS
                + HYBRID_LOCAL_PROFILE_PROVIDERS,
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
