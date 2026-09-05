from dataclasses import dataclass
from typing import Protocol

from app.core.config import Settings
from app.core.exceptions import ApplicationException
from app.processing.base import ProcessingProfile
from app.processing.indexing import (
    resolve_qdrant_collection,
)


CLOUD_RERANK_CANDIDATE_MULTIPLIER = 2


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
    def embed(
        self,
        question: str,
    ) -> list[float]:
        ...


class CloudRetriever(Protocol):
    def retrieve(
        self,
        *,
        collection_name: str,
        user_id: int,
        target: CloudRetrievalTarget,
        question: str,
        query_vector: list[float],
        limit: int,
    ) -> list[CloudRetrievalResult]:
        ...


class CloudReranker(Protocol):
    def rerank(
        self,
        *,
        question: str,
        candidates: list[
            CloudRetrievalResult
        ],
        limit: int,
    ) -> list[CloudRetrievalResult]:
        ...


class CloudRetrievalService:
    def __init__(
        self,
        *,
        settings: Settings,
        query_embedder: CloudQueryEmbedder,
        dense_retriever: CloudRetriever,
        reranker: CloudReranker,
    ) -> None:
        self._settings = settings
        self._query_embedder = query_embedder
        self._retriever = dense_retriever
        self._reranker = reranker

    def retrieve(
        self,
        *,
        user_id: int,
        target: CloudRetrievalTarget,
        question: str,
        limit: int,
    ) -> list[CloudRetrievalResult]:
        if (
            target.processing_profile
            is not ProcessingProfile.CLOUD
        ):
            raise ApplicationException(
                code=(
                    "cloud_retrieval_target_invalid"
                ),
                message=(
                    "Cloud retrieval requires "
                    "a cloud processing target."
                ),
            )

        collection_name = (
            resolve_qdrant_collection(
                profile=(
                    target.processing_profile
                ),
                settings=self._settings,
            )
        )

        query_vector = (
            self._query_embedder.embed(
                question
            )
        )

        candidate_limit = (
            limit
            * CLOUD_RERANK_CANDIDATE_MULTIPLIER
        )

        candidates = (
            self._retriever.retrieve(
                collection_name=(
                    collection_name
                ),
                user_id=user_id,
                target=target,
                question=question,
                query_vector=query_vector,
                limit=candidate_limit,
            )
        )

        if not candidates:
            return []

        return self._reranker.rerank(
            question=question,
            candidates=candidates,
            limit=limit,
        )
