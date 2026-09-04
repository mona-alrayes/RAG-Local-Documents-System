from dataclasses import dataclass
from typing import Protocol

from app.core.config import Settings
from app.core.exceptions import ApplicationException
from app.processing.base import ProcessingProfile
from app.processing.indexing import resolve_qdrant_collection


@dataclass(frozen=True, slots=True)
class CloudRetrievalTarget:
    document_id: int
    processing_run_id: int
    processing_profile: ProcessingProfile


@dataclass(frozen=True, slots=True)
class CloudRetrievalResult:
    point_id: str
    score: float
    document_id: int
    processing_run_id: int
    processing_profile: ProcessingProfile
    chunk_index: int
    text: str
    page: int | None
    section: str | None
    source: str


class CloudQueryEmbedder(Protocol):
    def embed(self, question: str) -> list[float]: ...


class CloudDenseRetriever(Protocol):
    def retrieve(
        self,
        *,
        collection_name: str,
        user_id: int,
        target: CloudRetrievalTarget,
        query_vector: list[float],
        limit: int,
    ) -> list[CloudRetrievalResult]: ...


class CloudRetrievalService:
    def __init__(
        self,
        *,
        settings: Settings,
        query_embedder: CloudQueryEmbedder,
        dense_retriever: CloudDenseRetriever,
    ) -> None:
        self._settings = settings
        self._query_embedder = query_embedder
        self._dense_retriever = dense_retriever

    def retrieve(
        self,
        *,
        user_id: int,
        target: CloudRetrievalTarget,
        question: str,
        limit: int,
    ) -> list[CloudRetrievalResult]:
        if target.processing_profile is not ProcessingProfile.CLOUD:
            raise ApplicationException(
                code="cloud_retrieval_target_invalid",
                message="Cloud retrieval requires a cloud processing target.",
            )

        collection_name = resolve_qdrant_collection(
            profile=target.processing_profile,
            settings=self._settings,
        )

        query_vector = self._query_embedder.embed(question)

        return self._dense_retriever.retrieve(
            collection_name=collection_name,
            user_id=user_id,
            target=target,
            query_vector=query_vector,
            limit=limit,
        )
