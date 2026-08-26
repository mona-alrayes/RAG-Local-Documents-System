from enum import StrEnum

from pydantic import BaseModel

from app.core.config import DeploymentMode


class ProviderStatus(StrEnum):
    AVAILABLE = "available"
    UNAVAILABLE = "unavailable"
    NOT_CHECKED = "not_checked"


class ProviderCapability(BaseModel):
    provider: str
    status: ProviderStatus


class DeploymentCapabilitiesResponse(BaseModel):
    deployment_mode: DeploymentMode
    supported_profiles: list[str]
    available_profiles: list[str]
    compare_available: bool
    providers: list[ProviderCapability]
