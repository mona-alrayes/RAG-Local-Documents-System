from dataclasses import dataclass
from datetime import datetime

@dataclass(frozen=True, slots=True)
class ArtifactManifest:
    schema_version: int
    user_id: int
    document_id: int
    processing_run_id: int
    processing_profile: str
    created_at: datetime | None = None
    expires_at: datetime | None = None


@dataclass(frozen=True, slots=True)
class StoredArtifact:
    manifest: ArtifactManifest
    payload: bytes
