from pydantic import BaseModel


class ProcessDocumentResponse(BaseModel):
    document_id: int
    processing_run_id: int
    profile: str
    status: str
    total_pages: int | None
    total_chunks: int
    vector_count: int
    vector_dimension: int
    temporary_artifact_ref: str
