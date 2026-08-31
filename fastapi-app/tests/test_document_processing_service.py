from pathlib import Path

import pytest
from qdrant_client import models

from app.core.config import Settings
from app.core.exceptions import ApplicationException
from app.infrastructure.qdrant.indexer import IndexingResult
from app.parsing.base import BaseDocumentLoader
from app.parsing.providers.llamaparse import LlamaParsePage
from app.processing.base import ProcessingProfile
from app.processing.chunks import NormalizedChunk
from app.processing.profiles import ExecutableProcessingProfile
from app.processing.registry import ProcessingProfileRegistry
from app.schemas.documents import (
    DocumentFileType,
    ProcessDocumentRequest,
)
from app.services.document_processing import ProcessDocumentService


class FakeLoader(BaseDocumentLoader[LlamaParsePage]):
    def load(self, file_path: Path) -> list[LlamaParsePage]:
        return [
            LlamaParsePage(
                page_number=1,
                markdown="Test document content.",
            )
        ]


class FakeChunker:
    def __init__(self) -> None:
        self.called = False

    def chunk(self, documents):
        self.called = True

        return [
            NormalizedChunk(
                text=documents[0].text,
                page=documents[0].page,
                section=None,
            )
        ]


class FakeDenseEmbedder:
    def __init__(self) -> None:
        self.called = False

    def embed(self, chunks):
        self.called = True
        return [[0.0] * 1024 for _ in chunks]


class FakeSparseRepresenter:
    def __init__(self) -> None:
        self.called = False

    def represent(self, chunks):
        self.called = True

        return [
            models.SparseVector(
                indices=[1],
                values=[1.0],
            )
            for _ in chunks
        ]


class FakeIndexer:
    def __init__(
        self,
        *,
        persisted_count: int | None = None,
        failure: ApplicationException | None = None,
    ) -> None:
        self.persisted_count = persisted_count
        self.failure = failure
        self.context = None

    def index(
        self,
        *,
        context,
        chunks,
        dense_vectors,
        sparse_representations,
    ) -> IndexingResult:
        self.context = context

        if self.failure is not None:
            raise self.failure

        vector_count = (
            len(chunks)
            if self.persisted_count is None
            else self.persisted_count
        )

        return IndexingResult(
            collection_name=context.collection_name,
            vector_count=vector_count,
        )


def build_profile(
    profile: ProcessingProfile,
):
    chunker = FakeChunker()
    dense_embedder = FakeDenseEmbedder()
    sparse_representer = FakeSparseRepresenter()

    executable_profile = ExecutableProcessingProfile(
        profile=profile,
        chunker=chunker,
        dense_embedder=dense_embedder,
        sparse_representer_factory=lambda: sparse_representer,
    )

    return (
        executable_profile,
        chunker,
        dense_embedder,
        sparse_representer,
    )


def build_request(
    *,
    profile: ProcessingProfile,
    file_type: DocumentFileType = DocumentFileType.PDF,
) -> ProcessDocumentRequest:
    return ProcessDocumentRequest(
        user_id=10,
        document_id=20,
        processing_run_id=30,
        processing_profile=profile,
        file_type=file_type,
    )


def test_cloud_processing_returns_indexed_response_after_indexing() -> None:
    settings = Settings(_env_file=None)

    (
        cloud_profile,
        chunker,
        dense_embedder,
        sparse_representer,
    ) = build_profile(ProcessingProfile.CLOUD)

    indexer = FakeIndexer()

    service = ProcessDocumentService(
        settings=settings,
        loaders={
            DocumentFileType.PDF: FakeLoader(),
        },
        profile_registry=ProcessingProfileRegistry(
            [cloud_profile]
        ),
        indexer=indexer,  # type: ignore[arg-type]
    )

    response = service.process(
        request=build_request(
            profile=ProcessingProfile.CLOUD,
        ),
        file_path=Path("/temporary/document.pdf"),
        source="document.pdf",
    )

    assert response.status == "indexed"
    assert response.profile is ProcessingProfile.CLOUD
    assert response.qdrant_collection == settings.qdrant_cloud_collection
    assert response.total_pages == 1
    assert response.total_chunks == 1
    assert response.vector_count == 1
    assert response.vector_dimension == 1024

    assert chunker.called is True
    assert dense_embedder.called is True
    assert sparse_representer.called is True

    assert indexer.context is not None
    assert indexer.context.user_id == 10
    assert indexer.context.document_id == 20
    assert indexer.context.processing_run_id == 30
    assert indexer.context.processing_profile == "cloud"
    assert indexer.context.file_type == "pdf"
    assert indexer.context.source == "document.pdf"


def test_hybrid_local_processing_uses_only_selected_profile() -> None:
    settings = Settings(_env_file=None)

    (
        cloud_profile,
        cloud_chunker,
        cloud_dense,
        cloud_sparse,
    ) = build_profile(ProcessingProfile.CLOUD)

    (
        local_profile,
        local_chunker,
        local_dense,
        local_sparse,
    ) = build_profile(ProcessingProfile.HYBRID_LOCAL)

    service = ProcessDocumentService(
        settings=settings,
        loaders={
            DocumentFileType.DOCX: FakeLoader(),
        },
        profile_registry=ProcessingProfileRegistry(
            [
                cloud_profile,
                local_profile,
            ]
        ),
        indexer=FakeIndexer(),  # type: ignore[arg-type]
    )

    response = service.process(
        request=build_request(
            profile=ProcessingProfile.HYBRID_LOCAL,
            file_type=DocumentFileType.DOCX,
        ),
        file_path=Path("/temporary/document.docx"),
        source="document.docx",
    )

    assert response.status == "indexed"
    assert response.profile is ProcessingProfile.HYBRID_LOCAL
    assert (
        response.qdrant_collection
        == settings.qdrant_hybrid_local_collection
    )

    # DOCX must not expose unreliable physical page numbers.
    assert response.total_pages is None

    assert local_chunker.called is True
    assert local_dense.called is True
    assert local_sparse.called is True

    assert cloud_chunker.called is False
    assert cloud_dense.called is False
    assert cloud_sparse.called is False


def test_indexing_failure_never_returns_indexed_response() -> None:
    settings = Settings(_env_file=None)

    cloud_profile, _, _, _ = build_profile(
        ProcessingProfile.CLOUD
    )

    indexer = FakeIndexer(
        failure=ApplicationException(
            code="qdrant_index_count_mismatch",
            message=(
                "Persisted Qdrant point count does not match "
                "the processing run chunk count."
            ),
        )
    )

    service = ProcessDocumentService(
        settings=settings,
        loaders={
            DocumentFileType.PDF: FakeLoader(),
        },
        profile_registry=ProcessingProfileRegistry(
            [cloud_profile]
        ),
        indexer=indexer,  # type: ignore[arg-type]
    )

    with pytest.raises(ApplicationException) as exc_info:
        service.process(
            request=build_request(
                profile=ProcessingProfile.CLOUD,
            ),
            file_path=Path("/temporary/document.pdf"),
            source="document.pdf",
        )

    assert exc_info.value.code == "qdrant_index_count_mismatch"
