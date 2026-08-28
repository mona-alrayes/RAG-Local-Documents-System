from enum import StrEnum
from functools import lru_cache

from pydantic import SecretStr
from pydantic_settings import BaseSettings, SettingsConfigDict


class DeploymentMode(StrEnum):
    CLOUD = "cloud"
    LOCAL = "local"


class LocalAiTopology(StrEnum):
    HOST_NATIVE = "host_native"


class LocalDevice(StrEnum):
    AUTO = "auto"
    CUDA = "cuda"
    ROCM = "rocm"
    XPU = "xpu"
    MPS = "mps"
    CPU = "cpu"


class LocalDtype(StrEnum):
    AUTO = "auto"


class StartupConfigurationError(RuntimeError):
    pass


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    app_name: str = "RAG AI Service"
    app_version: str = "0.1.0"

    rag_deployment_mode: DeploymentMode = DeploymentMode.LOCAL
    local_ai_topology: LocalAiTopology | None = None
    local_device: LocalDevice = LocalDevice.AUTO
    local_dtype: LocalDtype = LocalDtype.AUTO

    internal_api_key: SecretStr | None = None
    llama_cloud_api_key: SecretStr | None = None

    qdrant_url: str = "http://127.0.0.1:6333"
    qdrant_cloud_collection: str = "rag_documents_cloud"
    qdrant_hybrid_local_collection: str = "rag_documents_hybrid_local"


def validate_startup_configuration(settings: Settings) -> None:
    if "rag_deployment_mode" not in settings.model_fields_set:
        raise StartupConfigurationError(
            "RAG_DEPLOYMENT_MODE must be explicitly configured."
        )

    if (
        settings.internal_api_key is None
        or not settings.internal_api_key.get_secret_value().strip()
    ):
        raise StartupConfigurationError(
            "INTERNAL_API_KEY is required and must not be blank."
        )

    if settings.rag_deployment_mode is DeploymentMode.CLOUD:
        if settings.local_ai_topology is not None:
            raise StartupConfigurationError(
                "LOCAL_AI_TOPOLOGY must not be configured "
                "when RAG_DEPLOYMENT_MODE=cloud."
            )

        return

    if settings.local_ai_topology is None:
        raise StartupConfigurationError(
            "LOCAL_AI_TOPOLOGY is required "
            "when RAG_DEPLOYMENT_MODE=local."
        )


@lru_cache
def get_settings() -> Settings:
    return Settings()

