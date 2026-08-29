from datetime import datetime, timedelta, timezone
from pathlib import Path

import pytest
from pydantic import ValidationError

from app.artifacts.exceptions import (
    ArtifactExpiredError,
    ArtifactNotFoundError,
    ArtifactStorageError,
    InvalidArtifactReferenceError,
)
from app.artifacts.filesystem import FilesystemArtifactStore
from app.artifacts.models import ArtifactManifest
from app.core.config import Settings


FIXED_NOW = datetime(2026, 8, 30, 10, 0, tzinfo=timezone.utc)


def make_manifest() -> ArtifactManifest:
    return ArtifactManifest(
        schema_version=1,
        user_id=7,
        document_id=12,
        processing_run_id=34,
        processing_profile="cloud",
    )


def fixed_clock(now: datetime = FIXED_NOW):
    return lambda: now


def test_temp_artifact_ttl_defaults_to_24_hours(monkeypatch) -> None:
    monkeypatch.delenv("TEMP_ARTIFACT_TTL_HOURS", raising=False)

    settings = Settings(_env_file=None)

    assert settings.temp_artifact_ttl_hours == 24


def test_temp_artifact_ttl_is_configurable(monkeypatch) -> None:
    monkeypatch.setenv("TEMP_ARTIFACT_TTL_HOURS", "12")

    settings = Settings(_env_file=None)

    assert settings.temp_artifact_ttl_hours == 12


@pytest.mark.parametrize("invalid_ttl", ["0", "-1"])
def test_temp_artifact_ttl_rejects_invalid_values(
    monkeypatch,
    invalid_ttl: str,
) -> None:
    monkeypatch.setenv("TEMP_ARTIFACT_TTL_HOURS", invalid_ttl)

    with pytest.raises(ValidationError):
        Settings(_env_file=None)


def test_artifact_store_round_trip(tmp_path: Path) -> None:
    store = FilesystemArtifactStore(
        root=tmp_path / "artifacts",
        clock=fixed_clock(),
    )
    manifest = make_manifest()
    payload = b'{"chunks":[{"text":"example"}]}'

    artifact_ref = store.create(
        manifest=manifest,
        payload=payload,
    )

    stored = store.read(artifact_ref)

    assert stored.manifest.schema_version == manifest.schema_version
    assert stored.manifest.user_id == manifest.user_id
    assert stored.manifest.document_id == manifest.document_id
    assert (
        stored.manifest.processing_run_id
        == manifest.processing_run_id
    )
    assert (
        stored.manifest.processing_profile
        == manifest.processing_profile
    )
    assert stored.manifest.created_at == FIXED_NOW
    assert stored.manifest.expires_at == FIXED_NOW + timedelta(hours=24)
    assert stored.payload == payload


def test_artifact_reference_is_opaque_and_unique(tmp_path: Path) -> None:
    store = FilesystemArtifactStore(
        root=tmp_path / "artifacts",
        clock=fixed_clock(),
    )
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
    store = FilesystemArtifactStore(
        root=tmp_path / "artifacts",
        clock=fixed_clock(),
    )

    with pytest.raises(InvalidArtifactReferenceError) as exc_info:
        store.read(artifact_ref)

    assert exc_info.value.code == "invalid_artifact_reference"


def test_artifact_store_reports_missing_artifact_safely(
    tmp_path: Path,
) -> None:
    store = FilesystemArtifactStore(
        root=tmp_path / "artifacts",
        clock=fixed_clock(),
    )
    missing_ref = "a" * 64

    with pytest.raises(ArtifactNotFoundError) as exc_info:
        store.read(missing_ref)

    assert exc_info.value.code == "artifact_not_found"
    assert str(tmp_path) not in exc_info.value.message


def test_artifact_store_reports_corrupt_manifest_safely(
    tmp_path: Path,
) -> None:
    root = tmp_path / "artifacts"
    store = FilesystemArtifactStore(
        root=root,
        clock=fixed_clock(),
    )

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
    store = FilesystemArtifactStore(
        root=root,
        clock=fixed_clock(),
    )
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


def test_artifact_is_valid_before_expiry(tmp_path: Path) -> None:
    current_time = FIXED_NOW

    def clock() -> datetime:
        return current_time

    store = FilesystemArtifactStore(
        root=tmp_path / "artifacts",
        ttl_hours=2,
        clock=clock,
    )

    artifact_ref = store.create(
        manifest=make_manifest(),
        payload=b"payload",
    )

    current_time = FIXED_NOW + timedelta(
        hours=1,
        minutes=59,
        seconds=59,
    )

    stored = store.read(artifact_ref)

    assert stored.payload == b"payload"


def test_artifact_is_expired_at_exact_boundary(tmp_path: Path) -> None:
    current_time = FIXED_NOW

    def clock() -> datetime:
        return current_time

    store = FilesystemArtifactStore(
        root=tmp_path / "artifacts",
        ttl_hours=2,
        clock=clock,
    )

    artifact_ref = store.create(
        manifest=make_manifest(),
        payload=b"payload",
    )

    current_time = FIXED_NOW + timedelta(hours=2)

    with pytest.raises(ArtifactExpiredError) as exc_info:
        store.read(artifact_ref)

    assert exc_info.value.code == "artifact_expired"


def test_artifact_is_expired_after_ttl(tmp_path: Path) -> None:
    current_time = FIXED_NOW

    def clock() -> datetime:
        return current_time

    store = FilesystemArtifactStore(
        root=tmp_path / "artifacts",
        ttl_hours=1,
        clock=clock,
    )

    artifact_ref = store.create(
        manifest=make_manifest(),
        payload=b"payload",
    )

    current_time = FIXED_NOW + timedelta(hours=2)

    with pytest.raises(ArtifactExpiredError):
        store.read(artifact_ref)


def test_expired_artifact_error_does_not_leak_filesystem_path(
    tmp_path: Path,
) -> None:
    current_time = FIXED_NOW

    def clock() -> datetime:
        return current_time

    store = FilesystemArtifactStore(
        root=tmp_path / "artifacts",
        ttl_hours=1,
        clock=clock,
    )

    artifact_ref = store.create(
        manifest=make_manifest(),
        payload=b"payload",
    )

    current_time = FIXED_NOW + timedelta(hours=1)

    with pytest.raises(ArtifactExpiredError) as exc_info:
        store.read(artifact_ref)

    assert exc_info.value.code == "artifact_expired"
    assert str(tmp_path) not in exc_info.value.message
