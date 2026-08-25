import os

from fastapi.testclient import TestClient

from app.core.config import get_settings
from app.main import create_app


TEST_API_KEY = "d4-test-internal-key"


def create_test_client() -> TestClient:
    os.environ["INTERNAL_API_KEY"] = TEST_API_KEY
    get_settings.cache_clear()

    return TestClient(create_app())


def test_missing_internal_api_key_is_rejected() -> None:
    client = create_test_client()

    response = client.get("/")

    assert response.status_code == 401


def test_invalid_internal_api_key_is_rejected() -> None:
    client = create_test_client()

    response = client.get(
        "/",
        headers={"X-Internal-API-Key": "wrong-key"},
    )

    assert response.status_code == 401


def test_valid_internal_api_key_is_allowed() -> None:
    client = create_test_client()

    response = client.get(
        "/",
        headers={"X-Internal-API-Key": TEST_API_KEY},
    )

    assert response.status_code == 404
