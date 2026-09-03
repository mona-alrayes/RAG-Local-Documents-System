from collections.abc import Mapping
from typing import Any

import pytest

from app.core.exceptions import ApplicationException
from app.processing.base import ProcessingProfile
from app.schemas.documents import DocumentFileType, ProcessDocumentRequest
from app.services.processing_progress import (
    CALLBACK_SECRET_HEADER,
    CallbackResponse,
    LaravelProcessingProgressClient,
)


class FakeTransport:
    def __init__(self, outcomes: list[CallbackResponse | Exception]) -> None:
        self.outcomes = outcomes
        self.calls: list[dict[str, Any]] = []

    def post_json(
        self,
        *,
        url: str,
        headers: Mapping[str, str],
        payload: Mapping[str, Any],
        timeout_seconds: float,
    ) -> CallbackResponse:
        self.calls.append(
            {
                "url": url,
                "headers": dict(headers),
                "payload": dict(payload),
                "timeout_seconds": timeout_seconds,
            }
        )
        outcome = self.outcomes.pop(0)

        if isinstance(outcome, Exception):
            raise outcome

        return outcome


def processing_request() -> ProcessDocumentRequest:
    return ProcessDocumentRequest(
        user_id=10,
        document_id=20,
        processing_run_id=30,
        processing_profile=ProcessingProfile.CLOUD,
        file_type=DocumentFileType.PDF,
    )


def valid_ack() -> CallbackResponse:
    return CallbackResponse(
        status_code=200,
        payload={
            "event": "indexing_started",
            "processing_run_id": 30,
            "status": "indexing",
        },
    )


def build_client(
    transport: FakeTransport,
    *,
    max_attempts: int = 3,
    sleeper=lambda _delay: None,
) -> LaravelProcessingProgressClient:
    return LaravelProcessingProgressClient(
        base_url="http://laravel.internal/",
        callback_secret="callback-only-secret",
        timeout_seconds=4.0,
        max_attempts=max_attempts,
        retry_delay_seconds=0.1,
        transport=transport,
        sleeper=sleeper,
    )


def test_callback_request_uses_trusted_url_ids_and_authentication_header() -> None:
    transport = FakeTransport([valid_ack()])
    client = build_client(transport)

    client.notify_indexing_started(
        request=processing_request(),
        correlation_id="h9-correlation-id",
    )

    assert len(transport.calls) == 1
    call = transport.calls[0]
    assert call["url"] == (
        "http://laravel.internal/internal/api/v1/processing-runs/30/events"
    )
    assert call["headers"][CALLBACK_SECRET_HEADER] == "callback-only-secret"
    assert call["payload"] == {
        "event": "indexing_started",
        "user_id": 10,
        "document_id": 20,
        "processing_run_id": 30,
        "correlation_id": "h9-correlation-id",
    }
    assert call["timeout_seconds"] == 4.0


def test_callback_retries_are_bounded() -> None:
    transport = FakeTransport(
        [
            CallbackResponse(status_code=503, payload=None),
            RuntimeError("connection unavailable"),
            valid_ack(),
        ]
    )
    delays: list[float] = []
    client = build_client(transport, sleeper=delays.append)

    client.notify_indexing_started(
        request=processing_request(),
        correlation_id=None,
    )

    assert len(transport.calls) == 3
    assert delays == [0.1, 0.1]


def test_callback_final_failure_is_safe_and_does_not_leak_secret(caplog) -> None:
    transport = FakeTransport(
        [CallbackResponse(status_code=503, payload=None)] * 3
    )
    client = build_client(transport)

    with pytest.raises(ApplicationException) as exc_info:
        client.notify_indexing_started(
            request=processing_request(),
            correlation_id=None,
        )

    assert len(transport.calls) == 3
    assert exc_info.value.code == "processing_progress_callback_failed"
    assert "callback-only-secret" not in str(exc_info.value)
    assert "callback-only-secret" not in repr(exc_info.value)
    assert "callback-only-secret" not in caplog.text


def test_non_retryable_callback_rejection_stops_immediately() -> None:
    transport = FakeTransport(
        [CallbackResponse(status_code=401, payload={"message": "Unauthorized"})]
    )
    client = build_client(transport)

    with pytest.raises(ApplicationException):
        client.notify_indexing_started(
            request=processing_request(),
            correlation_id=None,
        )

    assert len(transport.calls) == 1
