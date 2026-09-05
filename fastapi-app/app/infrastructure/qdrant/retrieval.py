from typing import Any

from qdrant_client import QdrantClient, models

from app.core.exceptions import ApplicationException
from app.infrastructure.qdrant.schema import DENSE_VECTOR_NAME
from app.processing.base import ProcessingProfile
from app.services.cloud_retrieval import (
    CloudRetrievalResult,
    CloudRetrievalTarget,
)
from app.services.hybrid_local_retrieval import (
    HybridLocalRetrievalResult,
    HybridLocalRetrievalTarget,
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


def _build_scope_filter(
    *,
    user_id: int,
    document_id: int,
    processing_run_id: int,
    processing_profile: ProcessingProfile,
) -> models.Filter:
    return models.Filter(
        must=[
            models.FieldCondition(
                key="user_id",
                match=models.MatchValue(
                    value=user_id,
                ),
            ),
            models.FieldCondition(
                key="document_id",
                match=models.MatchValue(
                    value=document_id,
                ),
            ),
            models.FieldCondition(
                key="processing_run_id",
                match=models.MatchValue(
                    value=processing_run_id,
                ),
            ),
            models.FieldCondition(
                key="processing_profile",
                match=models.MatchValue(
                    value=processing_profile.value,
                ),
            ),
        ]
    )


def _validate_scope(
    *,
    payload: dict[str, Any],
    user_id: int,
    document_id: int,
    processing_run_id: int,
    processing_profile: ProcessingProfile,
    error_code: str,
    error_message: str,
) -> None:
    expected_scope = {
        "user_id": user_id,
        "document_id": document_id,
        "processing_run_id": processing_run_id,
        "processing_profile": processing_profile.value,
    }

    if any(
        payload.get(key) != expected_value
        for key, expected_value in expected_scope.items()
    ):
        raise ApplicationException(
            code=error_code,
            message=error_message,
        )


def _result_values(
    *,
    point: Any,
    payload: dict[str, Any],
    invalid_code: str,
    invalid_message: str,
) -> dict[str, Any]:
    try:
        page = payload["page"]
        section = payload["section"]

        return {
            "point_id": str(point.id),
            "score": float(point.score),
            "document_id": int(payload["document_id"]),
            "processing_run_id": int(
                payload["processing_run_id"]
            ),
            "processing_profile": ProcessingProfile(
                payload["processing_profile"]
            ),
            "chunk_index": int(payload["chunk_index"]),
            "text": str(payload["text"]),
            "page": (
                int(page)
                if page is not None
                else None
            ),
            "section": (
                str(section)
                if section is not None
                else None
            ),
            "source": str(payload["source"]),
        }
    except (KeyError, TypeError, ValueError) as exc:
        raise ApplicationException(
            code=invalid_code,
            message=invalid_message,
        ) from exc


class QdrantCloudDenseRetriever:
    def __init__(
        self,
        *,
        client: QdrantClient,
    ) -> None:
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
        query_filter = _build_scope_filter(
            user_id=user_id,
            document_id=target.document_id,
            processing_run_id=target.processing_run_id,
            processing_profile=ProcessingProfile.CLOUD,
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

        _validate_scope(
            payload=payload,
            user_id=user_id,
            document_id=target.document_id,
            processing_run_id=target.processing_run_id,
            processing_profile=ProcessingProfile.CLOUD,
            error_code="cloud_retrieval_result_scope_invalid",
            error_message=(
                "Cloud retrieval result does not belong "
                "to the trusted retrieval scope."
            ),
        )

        values = _result_values(
            point=point,
            payload=payload,
            invalid_code="cloud_retrieval_result_invalid",
            invalid_message=(
                "Cloud retrieval result payload is invalid."
            ),
        )

        return CloudRetrievalResult(**values)


class QdrantHybridLocalDenseRetriever:
    def __init__(
        self,
        *,
        client: QdrantClient,
    ) -> None:
        self._client = client

    def retrieve(
        self,
        *,
        collection_name: str,
        user_id: int,
        target: HybridLocalRetrievalTarget,
        query_vector: list[float],
        limit: int,
    ) -> list[HybridLocalRetrievalResult]:
        query_filter = _build_scope_filter(
            user_id=user_id,
            document_id=target.document_id,
            processing_run_id=target.processing_run_id,
            processing_profile=ProcessingProfile.HYBRID_LOCAL,
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
        target: HybridLocalRetrievalTarget,
    ) -> HybridLocalRetrievalResult:
        payload = point.payload

        if not isinstance(payload, dict):
            raise ApplicationException(
                code="hybrid_local_retrieval_result_invalid",
                message=(
                    "Hybrid Local retrieval result payload is invalid."
                ),
            )

        _validate_scope(
            payload=payload,
            user_id=user_id,
            document_id=target.document_id,
            processing_run_id=target.processing_run_id,
            processing_profile=ProcessingProfile.HYBRID_LOCAL,
            error_code=(
                "hybrid_local_retrieval_result_scope_invalid"
            ),
            error_message=(
                "Hybrid Local retrieval result does not belong "
                "to the trusted retrieval scope."
            ),
        )

        values = _result_values(
            point=point,
            payload=payload,
            invalid_code=(
                "hybrid_local_retrieval_result_invalid"
            ),
            invalid_message=(
                "Hybrid Local retrieval result payload is invalid."
            ),
        )

        return HybridLocalRetrievalResult(**values)
