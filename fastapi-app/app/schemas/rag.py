from pydantic import BaseModel


class DocumentTarget(BaseModel):
    document_id: int
    processing_run_id: int
    processing_profile: str
    qdrant_collection: str


class RecentCompletedTurn(BaseModel):
    user: str
    assistant: str


class RagQueryRequest(BaseModel):
    user_id: int
    conversation_id: int
    message_id: int
    document_targets: list[DocumentTarget]
    question: str
    recent_completed_turns: list[RecentCompletedTurn] | None = None


class RagSource(BaseModel):
    source_number: int
    document_id: int
    processing_run_id: int
    processing_profile: str
    page: int | None = None
    section: str | None = None
    chunk_index: int
    qdrant_point_id: str
    reranker_score: float
    text_preview: str


class RagQueryResponse(BaseModel):
    answer: str
    sources: list[RagSource]
    timings_ms: dict[str, int]
