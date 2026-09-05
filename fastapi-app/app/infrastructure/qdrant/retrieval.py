from typing import Any, Protocol

from qdrant_client import QdrantClient, models

from app.core.exceptions import ApplicationException
from app.infrastructure.qdrant.schema import (
    DENSE_VECTOR_NAME,
    SPARSE_VECTOR_NAME,
)
from app.processing.base import ProcessingProfile
from app.services.cloud_retrieval import (
    CloudRetrievalResult,
    CloudRetrievalTarget,
)
from app.services.hybrid_local_retrieval import (
    HybridLocalRetrievalResult,
    HybridLocalRetrievalTarget,
)
from app.services.retrieval_scope import RetrievalScope


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

RRF_CANDIDATE_MULTIPLIER = 2

SparseQuery = (
    models.Document
    | models.SparseVector
)


class SparseQueryRepresenter(Protocol):
    def represent_query(
        self,
        question: str,
    ) -> SparseQuery:
        ...


def _scope_values(
    scope: RetrievalScope,
) -> dict[str, int | str]:
    return {
        "user_id": scope.user_id,
        "document_id": scope.document_id,
        "processing_run_id": scope.processing_run_id,
        "processing_profile": (
            scope.processing_profile.value
        ),
    }


def _build_scope_filter(
    *,
    scope: RetrievalScope,
) -> models.Filter:
    return models.Filter(
        must=[
            models.FieldCondition(
                key=key,
                match=models.MatchValue(
                    value=value,
                ),
            )
            for key, value
            in _scope_values(scope).items()
        ]
    )


def _validate_scope(
    *,
    payload: dict[str, Any],
    scope: RetrievalScope,
    error_code: str,
    error_message: str,
) -> None:
    expected_scope = _scope_values(scope)

    if any(
        payload.get(key) != expected_value
        for key, expected_value
        in expected_scope.items()
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
            "document_id": int(
                payload["document_id"]
            ),
            "processing_run_id": int(
                payload["processing_run_id"]
            ),
            "processing_profile": (
                ProcessingProfile(
                    payload["processing_profile"]
                )
            ),
            "chunk_index": int(
                payload["chunk_index"]
            ),
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
    except (
        KeyError,
        TypeError,
        ValueError,
    ) as exc:
        raise ApplicationException(
            code=invalid_code,
            message=invalid_message,
        ) from exc


def _query_rrf_points(
    *,
    client: QdrantClient,
    collection_name: str,
    scope: RetrievalScope,
    dense_query: list[float],
    sparse_query: SparseQuery,
    limit: int,
) -> list[Any]:
    scope_filter = _build_scope_filter(
        scope=scope,
    )

    candidate_limit = (
        limit * RRF_CANDIDATE_MULTIPLIER
    )

    response = client.query_points(
        collection_name=collection_name,
        prefetch=[
            models.Prefetch(
                query=dense_query,
                using=DENSE_VECTOR_NAME,
                filter=scope_filter,
                limit=candidate_limit,
            ),
            models.Prefetch(
                query=sparse_query,
                using=SPARSE_VECTOR_NAME,
                filter=scope_filter,
                limit=candidate_limit,
            ),
        ],
        query=models.FusionQuery(
            fusion=models.Fusion.RRF,
        ),
        query_filter=scope_filter,
        limit=limit,
        with_payload=RETRIEVAL_PAYLOAD_FIELDS,
        with_vectors=False,
    )

    return list(response.points)


def _map_cloud_result(
    *,
    point: Any,
    scope: RetrievalScope,
) -> CloudRetrievalResult:
    payload = point.payload

    if not isinstance(payload, dict):
        raise ApplicationException(
            code="cloud_retrieval_result_invalid",
            message=(
                "Cloud retrieval result payload "
                "is invalid."
            ),
        )

    _validate_scope(
        payload=payload,
        scope=scope,
        error_code=(
            "cloud_retrieval_result_scope_invalid"
        ),
        error_message=(
            "Cloud retrieval result does not belong "
            "to the trusted retrieval scope."
        ),
    )

    values = _result_values(
        point=point,
        payload=payload,
        invalid_code=(
            "cloud_retrieval_result_invalid"
        ),
        invalid_message=(
            "Cloud retrieval result payload "
            "is invalid."
        ),
    )

    return CloudRetrievalResult(**values)


def _map_hybrid_local_result(
    *,
    point: Any,
    scope: RetrievalScope,
) -> HybridLocalRetrievalResult:
    payload = point.payload

    if not isinstance(payload, dict):
        raise ApplicationException(
            code=(
                "hybrid_local_retrieval_result_invalid"
            ),
            message=(
                "Hybrid Local retrieval result "
                "payload is invalid."
            ),
        )

    _validate_scope(
        payload=payload,
        scope=scope,
        error_code=(
            "hybrid_local_retrieval_result_"
            "scope_invalid"
        ),
        error_message=(
            "Hybrid Local retrieval result "
            "does not belong to the trusted "
            "retrieval scope."
        ),
    )

    values = _result_values(
        point=point,
        payload=payload,
        invalid_code=(
            "hybrid_local_retrieval_result_invalid"
        ),
        invalid_message=(
            "Hybrid Local retrieval result "
            "payload is invalid."
        ),
    )

    return HybridLocalRetrievalResult(
        **values
    )


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
        question: str | None = None,
    ) -> list[CloudRetrievalResult]:
        scope = RetrievalScope(
            user_id=user_id,
            document_id=target.document_id,
            processing_run_id=(
                target.processing_run_id
            ),
            processing_profile=(
                target.processing_profile
            ),
        )

        query_filter = _build_scope_filter(
            scope=scope,
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
            _map_cloud_result(
                point=point,
                scope=scope,
            )
            for point in response.points
        ]


class QdrantCloudRrfRetriever:
    def __init__(
        self,
        *,
        client: QdrantClient,
        sparse_query_representer: (
            SparseQueryRepresenter
        ),
    ) -> None:
        self._client = client
        self._sparse_query_representer = (
            sparse_query_representer
        )

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
        scope = RetrievalScope(
            user_id=user_id,
            document_id=target.document_id,
            processing_run_id=(
                target.processing_run_id
            ),
            processing_profile=(
                target.processing_profile
            ),
        )

        sparse_query = (
            self._sparse_query_representer
            .represent_query(question)
        )

        points = _query_rrf_points(
            client=self._client,
            collection_name=collection_name,
            scope=scope,
            dense_query=query_vector,
            sparse_query=sparse_query,
            limit=limit,
        )

        return [
            _map_cloud_result(
                point=point,
                scope=scope,
            )
            for point in points
        ]


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
        question: str | None = None,
    ) -> list[HybridLocalRetrievalResult]:
        scope = RetrievalScope(
            user_id=user_id,
            document_id=target.document_id,
            processing_run_id=(
                target.processing_run_id
            ),
            processing_profile=(
                target.processing_profile
            ),
        )

        query_filter = _build_scope_filter(
            scope=scope,
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
            _map_hybrid_local_result(
                point=point,
                scope=scope,
            )
            for point in response.points
        ]


class QdrantHybridLocalRrfRetriever:
    def __init__(
        self,
        *,
        client: QdrantClient,
        sparse_query_representer: (
            SparseQueryRepresenter
        ),
    ) -> None:
        self._client = client
        self._sparse_query_representer = (
            sparse_query_representer
        )

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
        scope = RetrievalScope(
            user_id=user_id,
            document_id=target.document_id,
            processing_run_id=(
                target.processing_run_id
            ),
            processing_profile=(
                target.processing_profile
            ),
        )

        sparse_query = (
            self._sparse_query_representer
            .represent_query(question)
        )

        points = _query_rrf_points(
            client=self._client,
            collection_name=collection_name,
            scope=scope,
            dense_query=query_vector,
            sparse_query=sparse_query,
            limit=limit,
        )

        return [
            _map_hybrid_local_result(
                point=point,
                scope=scope,
            )
            for point in points
        ]
