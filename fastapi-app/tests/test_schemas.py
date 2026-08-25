from app.schemas.documents import ProcessDocumentResponse
from app.schemas.rag import RagQueryRequest, RagQueryResponse


def test_process_document_response_contract() -> None:
    response = ProcessDocumentResponse(
        document_id=152,
        processing_run_id=901,
        profile="cloud",
        status="ready_for_comparison",
        total_pages=None,
        total_chunks=184,
        vector_count=184,
        vector_dimension=1024,
        temporary_artifact_ref="opaque-token",
    )

    assert response.document_id == 152
    assert response.processing_run_id == 901
    assert response.total_chunks == 184
    assert response.total_pages is None


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
        sources=[
            {
                "source_number": 1,
                "document_id": 12,
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
