from abc import ABC, abstractmethod

from app.artifacts.models import ArtifactManifest, StoredArtifact


class ArtifactStore(ABC):
    @abstractmethod
    def create(
        self,
        *,
        manifest: ArtifactManifest,
        payload: bytes,
    ) -> str:
        """Persist a private artifact and return its opaque reference."""
        raise NotImplementedError

    @abstractmethod
    def read(self, artifact_ref: str) -> StoredArtifact:
        """Load an artifact identified only by its opaque reference."""
        raise NotImplementedError
