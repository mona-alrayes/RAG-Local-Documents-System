from pathlib import Path

import pytest

from app.artifacts.exceptions import (
    ArtifactNotFoundError,
    ArtifactStorageError,
    InvalidArtifactReferenceError,
)
from app.artifacts.filesystem import FilesystemArtifactStore
from app.artifacts.models import ArtifactManifest


def make_manifest() -> ArtifactManifest:
    return ArtifactManifest(
        schema_version=1,
        user_id=7,
        document_id=12,
        processing_run_id=34,
        processing_profile="cloud",
    )


def test_artifact_store_round_trip(tmp_path: Path) -> None:
    store = FilesystemArtifactStore(root=tmp_path / "artifacts")
    manifest = make_manifest()
    payload = b'{"chunks":[{"text":"example"}]}'

    artifact_ref = store.create(
        manifest=manifest,
        payload=payload,
    )

    stored = store.read(artifact_ref)

    assert stored.manifest == manifest
    assert stored.payload == payload


def test_artifact_reference_is_opaque_and_unique(tmp_path: Path) -> None:
    store = FilesystemArtifactStore(root=tmp_path / "artifacts")
    manifest = make_manifest()

    first_ref = store.create(
        manifest=manifest,
        payload=b"first",
    )
    second_ref = store.create(
        manifest=manifest,
        payload=b"second",
    )

    assert first_ref != second_ref

    for artifact_ref in (first_ref, second_ref):
        assert len(artifact_ref) == 64
        assert artifact_ref.isascii()
        assert artifact_ref.isalnum()
        assert "/" not in artifact_ref
        assert "\\" not in artifact_ref
        assert manifest.processing_profile not in artifact_ref
        assert str(tmp_path) not in artifact_ref


@pytest.mark.parametrize(
    "artifact_ref",
    [
        "../outside",
        "../../etc/passwd",
        "/tmp/artifact",
        "not-a-valid-reference",
        "",
        "a" * 63,
        "a" * 65,
    ],
)
def test_artifact_store_rejects_invalid_reference(
    tmp_path: Path,
    artifact_ref: str,
) -> None:
    store = FilesystemArtifactStore(root=tmp_path / "artifacts")

    with pytest.raises(InvalidArtifactReferenceError) as exc_info:
        store.read(artifact_ref)

    assert exc_info.value.code == "invalid_artifact_reference"


def test_artifact_store_reports_missing_artifact_safely(
    tmp_path: Path,
) -> None:
    store = FilesystemArtifactStore(root=tmp_path / "artifacts")
    missing_ref = "a" * 64

    with pytest.raises(ArtifactNotFoundError) as exc_info:
        store.read(missing_ref)

    assert exc_info.value.code == "artifact_not_found"
    assert str(tmp_path) not in exc_info.value.message


def test_artifact_store_reports_corrupt_manifest_safely(
    tmp_path: Path,
) -> None:
    root = tmp_path / "artifacts"
    store = FilesystemArtifactStore(root=root)

    artifact_ref = store.create(
        manifest=make_manifest(),
        payload=b"payload",
    )

    manifest_path = root / artifact_ref / "manifest.json"
    manifest_path.write_text(
        "{not-valid-json",
        encoding="utf-8",
    )

    with pytest.raises(ArtifactStorageError) as exc_info:
        store.read(artifact_ref)

    assert exc_info.value.code == "artifact_storage_error"
    assert str(tmp_path) not in exc_info.value.message


def test_artifact_store_does_not_delete_existing_artifact_on_ref_collision(
    tmp_path: Path,
    monkeypatch,
) -> None:
    root = tmp_path / "artifacts"
    store = FilesystemArtifactStore(root=root)
    fixed_ref = "b" * 64

    monkeypatch.setattr(
        "app.artifacts.filesystem.secrets.token_hex",
        lambda _size: fixed_ref,
    )

    store.create(
        manifest=make_manifest(),
        payload=b"original",
    )

    with pytest.raises(ArtifactStorageError) as exc_info:
        store.create(
            manifest=make_manifest(),
            payload=b"replacement",
        )

    assert exc_info.value.code == "artifact_storage_error"
    assert store.read(fixed_ref).payload == b"original"
