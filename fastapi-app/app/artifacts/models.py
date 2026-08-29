from dataclasses import dataclass


@dataclass(frozen=True, slots=True)
class ArtifactManifest:
    schema_version: int
    user_id: int
    document_id: int
    processing_run_id: int
    processing_profile: str


@dataclass(frozen=True, slots=True)
class StoredArtifact:
    manifest: ArtifactManifest
    payload: bytes
