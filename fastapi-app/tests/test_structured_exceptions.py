import os

from fastapi.testclient import TestClient

from app.core.config import get_settings
from app.core.exceptions import ApplicationException
from app.main import create_app


TEST_API_KEY = "d7-test-internal-key"
TEST_CORRELATION_ID = "d7-test-correlation-id"


def test_application_exception_returns_structured_error() -> None:
    os.environ["INTERNAL_API_KEY"] = TEST_API_KEY
    get_settings.cache_clear()

    app = create_app()

    @app.get("/__test__/application-error")
    async def raise_application_error() -> None:
        raise ApplicationException(
            code="TEST_ERROR",
            message="Safe test error",
        )

    client = TestClient(app)

    response = client.get(
        "/__test__/application-error",
        headers={
            "X-Internal-API-Key": TEST_API_KEY,
            "X-Correlation-ID": TEST_CORRELATION_ID,
        },
    )

    assert response.status_code == 500
    assert response.headers["x-correlation-id"] == TEST_CORRELATION_ID
    assert response.json() == {
        "error": {
            "code": "TEST_ERROR",
            "message": "Safe test error",
        },
        "correlation_id": TEST_CORRELATION_ID,
    }