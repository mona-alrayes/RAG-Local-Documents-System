from collections.abc import Iterable

from app.core.exceptions import ApplicationException
from app.processing.base import BaseProcessingProfile, ProcessingProfile


class ProcessingProfileRegistry:
    def __init__(self, profiles: Iterable[BaseProcessingProfile]) -> None:
        self._profiles = {profile.profile: profile for profile in profiles}

    def resolve(self, profile: ProcessingProfile) -> BaseProcessingProfile:
        if not isinstance(profile, ProcessingProfile):
            raise ApplicationException(
                code="invalid_processing_profile",
                message="Processing profile must be a trusted ProcessingProfile value.",
            )

        try:
            return self._profiles[profile]
        except KeyError:
            raise ApplicationException(
                code="processing_profile_not_registered",
                message=f"Processing profile '{profile.value}' is not registered.",
            ) from None
