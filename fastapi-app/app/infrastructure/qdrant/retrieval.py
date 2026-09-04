from typing import Any

from qdrant_client import QdrantClient, models

from app.core.exceptions import ApplicationException
from app.infrastructure.qdrant.schema import DENSE_VECTOR_NAME
from app.processing.base import ProcessingProfile
from app.services.cloud_retrieval import (
    CloudRetrievalResult,
    CloudRetrievalTarget,
)


RETRIEVAL_PAYLOAD_FIELDS = [
    "user_id",
    "document_id",
    "processing_run_id",
    "processing_profile",
    "chunk_index",
    "text",
    "page",
    "section",
    "source",
]


class QdrantCloudDenseRetriever:
    def __init__(self, *, client: QdrantClient) -> None:
        self._client = client

    def retrieve(
        self,
        *,
        collection_name: str,
        user_id: int,
        target: CloudRetrievalTarget,
        query_vector: list[float],
        limit: int,
    ) -> list[CloudRetrievalResult]:
        query_filter = models.Filter(
            must=[
                models.FieldCondition(
                    key="user_id",
                    match=models.MatchValue(value=user_id),
                ),
                models.FieldCondition(
                    key="document_id",
                    match=models.MatchValue(value=target.document_id),
                ),
                models.FieldCondition(
                    key="processing_run_id",
                    match=models.MatchValue(
                        value=target.processing_run_id,
                    ),
                ),
                models.FieldCondition(
                    key="processing_profile",
                    match=models.MatchValue(
                        value=ProcessingProfile.CLOUD.value,
                    ),
                ),
            ]
        )

        response = self._client.query_points(
            collection_name=collection_name,
            query=query_vector,
            using=DENSE_VECTOR_NAME,
            query_filter=query_filter,
            limit=limit,
            with_payload=RETRIEVAL_PAYLOAD_FIELDS,
            with_vectors=False,
        )

        return [
            self._map_result(
                point=point,
                user_id=user_id,
                target=target,
            )
            for point in response.points
        ]

    @staticmethod
    def _map_result(
        *,
        point: Any,
        user_id: int,
        target: CloudRetrievalTarget,
    ) -> CloudRetrievalResult:
        payload = point.payload

        if not isinstance(payload, dict):
            raise ApplicationException(
                code="cloud_retrieval_result_invalid",
                message="Cloud retrieval result payload is invalid.",
            )

        expected_scope = {
            "user_id": user_id,
            "document_id": target.document_id,
            "processing_run_id": target.processing_run_id,
            "processing_profile": ProcessingProfile.CLOUD.value,
        }

        if any(
            payload.get(key) != expected_value
            for key, expected_value in expected_scope.items()
        ):
            raise ApplicationException(
                code="cloud_retrieval_result_scope_invalid",
                message=(
                    "Cloud retrieval result does not belong to "
                    "the trusted retrieval scope."
                ),
            )

        try:
            page = payload["page"]
            section = payload["section"]

            return CloudRetrievalResult(
                point_id=str(point.id),
                score=float(point.score),
                document_id=int(payload["document_id"]),
                processing_run_id=int(payload["processing_run_id"]),
                processing_profile=ProcessingProfile(
                    payload["processing_profile"]
                ),
                chunk_index=int(payload["chunk_index"]),
                text=str(payload["text"]),
                page=int(page) if page is not None else None,
                section=str(section) if section is not None else None,
                source=str(payload["source"]),
            )
        except (KeyError, TypeError, ValueError) as exc:
            raise ApplicationException(
                code="cloud_retrieval_result_invalid",
                message="Cloud retrieval result payload is invalid.",
            ) from exc
