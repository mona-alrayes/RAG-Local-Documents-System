import json
import os
import re
import secrets
import shutil
from dataclasses import asdict
from pathlib import Path

from app.artifacts.base import ArtifactStore
from app.artifacts.exceptions import (
    ArtifactNotFoundError,
    ArtifactStorageError,
    InvalidArtifactReferenceError,
)
from app.artifacts.models import ArtifactManifest, StoredArtifact


_ARTIFACT_REF_PATTERN = re.compile(r"^[a-f0-9]{64}$")
_MANIFEST_FILENAME = "manifest.json"
_PAYLOAD_FILENAME = "payload.bin"


class FilesystemArtifactStore(ArtifactStore):
    def __init__(self, *, root: Path) -> None:
        self._root = root.resolve()
        self._root.mkdir(parents=True, exist_ok=True, mode=0o700)

    def create(
        self,
        *,
        manifest: ArtifactManifest,
        payload: bytes,
    ) -> str:
        artifact_ref = secrets.token_hex(32)
        artifact_dir = self._artifact_directory(artifact_ref)
        directory_created = False

        try:
            artifact_dir.mkdir(mode=0o700)
            directory_created = True

            manifest_bytes = json.dumps(
                asdict(manifest),
                ensure_ascii=False,
                separators=(",", ":"),
            ).encode("utf-8")

            self._write_private_file(
                artifact_dir / _MANIFEST_FILENAME,
                manifest_bytes,
            )
            self._write_private_file(
                artifact_dir / _PAYLOAD_FILENAME,
                payload,
            )
        except OSError as exc:
            if directory_created:
                shutil.rmtree(artifact_dir, ignore_errors=True)

            raise ArtifactStorageError() from exc

        return artifact_ref

    def read(self, artifact_ref: str) -> StoredArtifact:
        artifact_dir = self._artifact_directory(artifact_ref)

        if not artifact_dir.is_dir():
            raise ArtifactNotFoundError()

        try:
            manifest_data = json.loads(
                (artifact_dir / _MANIFEST_FILENAME).read_text(
                    encoding="utf-8"
                )
            )
            manifest = ArtifactManifest(**manifest_data)
            payload = (artifact_dir / _PAYLOAD_FILENAME).read_bytes()
        except (OSError, json.JSONDecodeError, TypeError) as exc:
            raise ArtifactStorageError() from exc

        return StoredArtifact(
            manifest=manifest,
            payload=payload,
        )

    def _artifact_directory(self, artifact_ref: str) -> Path:
        if not _ARTIFACT_REF_PATTERN.fullmatch(artifact_ref):
            raise InvalidArtifactReferenceError()

        artifact_dir = (self._root / artifact_ref).resolve()

        if artifact_dir.parent != self._root:
            raise InvalidArtifactReferenceError()

        return artifact_dir

    @staticmethod
    def _write_private_file(path: Path, content: bytes) -> None:
        descriptor = os.open(
            path,
            os.O_WRONLY | os.O_CREAT | os.O_EXCL,
            0o600,
        )

        with os.fdopen(descriptor, "wb") as file:
            file.write(content)
