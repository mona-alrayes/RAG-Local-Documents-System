from dataclasses import dataclass
from typing import Protocol

from app.core.config import Settings
from app.core.exceptions import ApplicationException
from app.processing.base import ProcessingProfile
from app.processing.indexing import resolve_qdrant_collection


@dataclass(frozen=True, slots=True)
class HybridLocalRetrievalTarget:
    document_id: int
    processing_run_id: int
    processing_profile: ProcessingProfile


@dataclass(frozen=True, slots=True)
class HybridLocalRetrievalResult:
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


class HybridLocalQueryEmbedder(Protocol):
    def embed(
        self,
        question: str,
    ) -> list[float]:
        ...


class HybridLocalRetriever(Protocol):
    def retrieve(
        self,
        *,
        collection_name: str,
        user_id: int,
        target: HybridLocalRetrievalTarget,
        question: str,
        query_vector: list[float],
        limit: int,
    ) -> list[HybridLocalRetrievalResult]:
        ...


class HybridLocalRetrievalService:
    def __init__(
        self,
        *,
        settings: Settings,
        query_embedder: HybridLocalQueryEmbedder,
        dense_retriever: HybridLocalRetriever,
    ) -> None:
        self._settings = settings
        self._query_embedder = query_embedder
        self._retriever = dense_retriever

    def retrieve(
        self,
        *,
        user_id: int,
        target: HybridLocalRetrievalTarget,
        question: str,
        limit: int,
    ) -> list[HybridLocalRetrievalResult]:
        if (
            target.processing_profile
            is not ProcessingProfile.HYBRID_LOCAL
        ):
            raise ApplicationException(
                code="hybrid_local_retrieval_target_invalid",
                message=(
                    "Hybrid Local retrieval requires "
                    "a hybrid_local processing target."
                ),
            )

        collection_name = resolve_qdrant_collection(
            profile=target.processing_profile,
            settings=self._settings,
        )

        query_vector = self._query_embedder.embed(
            question
        )

        return self._retriever.retrieve(
            collection_name=collection_name,
            user_id=user_id,
            target=target,
            question=question,
            query_vector=query_vector,
            limit=limit,
        )
