from app.core.exceptions import ApplicationException


class ArtifactNotFoundError(ApplicationException):
    def __init__(self) -> None:
        super().__init__(
            code="artifact_not_found",
            message="The requested temporary artifact was not found.",
        )


class InvalidArtifactReferenceError(ApplicationException):
    def __init__(self) -> None:
        super().__init__(
            code="invalid_artifact_reference",
            message="The temporary artifact reference is invalid.",
        )


class ArtifactStorageError(ApplicationException):
    def __init__(self) -> None:
        super().__init__(
            code="artifact_storage_error",
            message="The temporary artifact could not be processed.",
        )
