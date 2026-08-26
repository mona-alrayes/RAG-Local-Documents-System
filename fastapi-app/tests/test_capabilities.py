import pytest
from fastapi.testclient import TestClient

from app.core.config import get_settings
from app.main import create_app


TEST_API_KEY = "d8-test-internal-key"


@pytest.mark.parametrize(
    (
        "deployment_mode",
        "expected_profiles",
        "expected_providers",
    ),
    [
        (
            "cloud",
            ["cloud"],
            [
                "llama_parse",
                "jina_embeddings",
                "jina_reranker",
                "hugging_face_llm",
            ],
        ),
        (
            "local",
            ["cloud", "hybrid_local"],
            [
                "llama_parse",
                "jina_embeddings",
                "jina_reranker",
                "hugging_face_llm",
                "bge_m3_embeddings",
                "bge_reranker",
                "ollama_llm",
            ],
        ),
    ],
)
def test_capabilities_endpoint_reflects_deployment_mode(
    monkeypatch: pytest.MonkeyPatch,
    deployment_mode: str,
    expected_profiles: list[str],
    expected_providers: list[str],
) -> None:
    monkeypatch.setenv("INTERNAL_API_KEY", TEST_API_KEY)
    monkeypatch.setenv("RAG_DEPLOYMENT_MODE", deployment_mode)
    get_settings.cache_clear()

    client = TestClient(create_app())

    response = client.get(
        "/api/v1/capabilities",
        headers={"X-Internal-API-Key": TEST_API_KEY},
    )

    assert response.status_code == 200

    payload = response.json()

    assert payload["deployment_mode"] == deployment_mode
    assert payload["supported_profiles"] == expected_profiles
    assert payload["available_profiles"] == []
    assert payload["compare_available"] is False
    assert [
        provider["provider"]
        for provider in payload["providers"]
    ] == expected_providers
    assert all(
        provider["status"] == "not_checked"
        for provider in payload["providers"]
    )
