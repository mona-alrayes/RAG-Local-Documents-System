import pytest
from pydantic import ValidationError

from app.processing.base import ProcessingProfile
from app.schemas.documents import (
    DocumentFileType,
    ProcessDocumentRequest,
    ProcessDocumentResponse,
)
from app.schemas.rag import RagQueryRequest, RagQueryResponse


def test_process_document_request_contract() -> None:
    request = ProcessDocumentRequest(
        user_id=7,
        document_id=152,
        processing_run_id=901,
        processing_profile="cloud",
        file_type="pdf",
    )

    assert request.user_id == 7
    assert request.document_id == 152
    assert request.processing_run_id == 901
    assert request.processing_profile is ProcessingProfile.CLOUD
    assert request.file_type is DocumentFileType.PDF


def test_process_document_request_rejects_untrusted_contract_values() -> None:
    with pytest.raises(ValidationError):
        ProcessDocumentRequest(
            user_id=7,
            document_id=152,
            processing_run_id=901,
            processing_profile="both",
            file_type="exe",
        )


def test_process_document_response_contract() -> None:
    response = ProcessDocumentResponse(
        document_id=152,
        processing_run_id=901,
        profile="cloud",
        status="indexed",
        qdrant_collection="rag_documents_cloud",
        profile_snapshot={
            "profile": "cloud",
            "chunking": {
                "chunk_size": 800,
                "chunk_overlap": 120,
            },
            "dense_embedding": {
                "provider": "jina",
                "model": "jina-embeddings-v3",
                "vector_dimension": 1024,
            },
            "sparse_representation": {
                "provider": "qdrant",
                "model": "bm25",
                "tokenizer": "multilingual",
            },
            "batching": {
                "batch_size": 32,
                "wait_between_batches_seconds": 0,
                "rate_limit_retry_wait_seconds": 1,
                "max_retries": 3,
            },
        },
        total_pages=None,
        total_chunks=184,
        vector_count=184,
        vector_dimension=1024,
        stage_timings_ms={
            "parse": 40,
            "chunk": 20,
            "dense_embedding": 120,
            "sparse_representation": 35,
            "total": 215,
        },
        warnings=[],
    )

    assert response.document_id == 152
    assert response.processing_run_id == 901
    assert response.profile is ProcessingProfile.CLOUD
    assert response.status == "indexed"
    assert response.qdrant_collection == "rag_documents_cloud"
    assert response.total_chunks == 184
    assert response.total_pages is None
    assert response.vector_count == 184
    assert response.vector_dimension == 1024


def test_rag_request_and_response_contracts() -> None:
    request = RagQueryRequest(
        user_id=7,
        conversation_id=51,
        message_id=900,
        document_targets=[
            {
                "document_id": 12,
                "processing_run_id": 81,
                "processing_profile": "cloud",
                "qdrant_collection": "rag_documents_cloud",
            }
        ],
        question="ما أهم النتائج؟",
        recent_completed_turns=[
            {
                "user": "لخص المنهجية.",
                "assistant": "تعتمد المنهجية على ...",
            }
        ],
    )

    response = RagQueryResponse(
        answer="أظهرت الدراسة ...",
        llm={
            "provider": "hugging_face",
            "model": "Qwen/Qwen3.5-9B",
        },
        processing_profiles=["cloud"],
        sources=[
            {
                "source_number": 1,
                "document_id": 12,
                "document_title": "study.pdf",
                "processing_run_id": 81,
                "processing_profile": "cloud",
                "page": 15,
                "section": None,
                "chunk_index": 48,
                "qdrant_point_id": "point-1",
                "reranker_score": 0.91,
                "text_preview": "مقتطف من المصدر",
            }
        ],
        timings_ms={
            "query_embedding": 30,
            "retrieval": 80,
            "total": 110,
        },
    )

    assert request.document_targets[0].processing_run_id == 81
    assert request.recent_completed_turns is not None
    assert request.recent_completed_turns[0].user == "لخص المنهجية."

    assert response.sources[0].document_id == 12
    assert response.sources[0].reranker_score == 0.91
    assert response.timings_ms["total"] == 110
    assert response.llm.provider == "hugging_face"
    assert response.llm.model == "Qwen/Qwen3.5-9B"
    assert response.processing_profiles == ["cloud"]
    assert response.sources[0].document_title == "study.pdf"
