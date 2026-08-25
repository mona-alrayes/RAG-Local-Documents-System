from enum import StrEnum
from functools import lru_cache

from pydantic import SecretStr
from pydantic_settings import BaseSettings, SettingsConfigDict


class DeploymentMode(StrEnum):
    CLOUD = "cloud"
    LOCAL = "local"


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    app_name: str = "RAG AI Service"
    app_version: str = "0.1.0"
    rag_deployment_mode: DeploymentMode = DeploymentMode.LOCAL

    internal_api_key: SecretStr | None = None


@lru_cache
def get_settings() -> Settings:
    return Settings()
