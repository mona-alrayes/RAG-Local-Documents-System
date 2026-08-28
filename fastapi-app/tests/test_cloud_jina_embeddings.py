from app.processing.cloud_chunking import NormalizedChunk
from app.processing.cloud_embeddings import CloudJinaEmbedder
from app.core.exceptions import ApplicationException
from app.core.config import Settings


def test_cloud_jina_embedder_preserves_chunk_order(monkeypatch) -> None:
    received_texts: list[str] = []

    class FakeJinaEmbedding:
        def __init__(self, **kwargs) -> None:
            pass

        def get_text_embedding_batch(self, texts: list[str]) -> list[list[float]]:
            received_texts.extend(texts)

            return [
                [1.0] * 1024,
                [2.0] * 1024,
            ]

    monkeypatch.setattr(
        "app.processing.cloud_embeddings.JinaEmbedding",
        FakeJinaEmbedding,
    )

    chunks = [
        NormalizedChunk(text="first chunk"),
        NormalizedChunk(text="second chunk"),
    ]

    embedder = CloudJinaEmbedder(
        api_key="test-key",
        model="jina-embeddings-v3",
    )

    vectors = embedder.embed(chunks)

    assert received_texts == [
        "first chunk",
        "second chunk",
    ]
    assert vectors[0] == [1.0] * 1024
    assert vectors[1] == [2.0] * 1024


def test_cloud_jina_embedder_uses_passage_task_and_fixed_dimension(monkeypatch) -> None:
    received_kwargs: dict[str, object] = {}

    class FakeJinaEmbedding:
        def __init__(self, **kwargs) -> None:
            received_kwargs.update(kwargs)

    monkeypatch.setattr(
        "app.processing.cloud_embeddings.JinaEmbedding",
        FakeJinaEmbedding,
    )

    CloudJinaEmbedder(
        api_key="test-key",
        model="jina-embeddings-v3",
    )

    assert received_kwargs["task"] == "retrieval.passage"
    assert received_kwargs["dimensions"] == 1024


def test_cloud_jina_embedder_rejects_vector_count_mismatch(monkeypatch) -> None:
    class FakeJinaEmbedding:
        def __init__(self, **kwargs) -> None:
            pass

        def get_text_embedding_batch(self, texts: list[str]) -> list[list[float]]:
            return [[1.0] * 1024]

    monkeypatch.setattr(
        "app.processing.cloud_embeddings.JinaEmbedding",
        FakeJinaEmbedding,
    )

    chunks = [
        NormalizedChunk(text="first chunk"),
        NormalizedChunk(text="second chunk"),
    ]

    embedder = CloudJinaEmbedder(
        api_key="test-key",
        model="jina-embeddings-v3",
    )

    try:
        embedder.embed(chunks)
    except ApplicationException as exc:
        assert exc.code == "cloud_embedding_result_invalid"
    else:
        raise AssertionError("Expected ApplicationException")


def test_cloud_jina_embedder_rejects_invalid_vector_dimension(monkeypatch) -> None:
    class FakeJinaEmbedding:
        def __init__(self, **kwargs) -> None:
            pass

        def get_text_embedding_batch(self, texts: list[str]) -> list[list[float]]:
            return [[1.0] * 512]

    monkeypatch.setattr(
        "app.processing.cloud_embeddings.JinaEmbedding",
        FakeJinaEmbedding,
    )

    chunks = [
        NormalizedChunk(text="first chunk"),
    ]

    embedder = CloudJinaEmbedder(
        api_key="test-key",
        model="jina-embeddings-v3",
    )

    try:
        embedder.embed(chunks)
    except ApplicationException as exc:
        assert exc.code == "cloud_embedding_result_invalid"
    else:
        raise AssertionError("Expected ApplicationException")


def test_cloud_jina_embedding_settings(monkeypatch) -> None:
    monkeypatch.setenv("JINAAI_API_KEY", "test-jina-key")
    monkeypatch.setenv("CLOUD_EMBED_MODEL", "jina-embeddings-v3")

    settings = Settings()

    assert settings.jinaai_api_key.get_secret_value() == "test-jina-key"
    assert settings.cloud_embed_model == "jina-embeddings-v3"


def test_cloud_jina_embedder_wraps_provider_failure(monkeypatch) -> None:
    class FakeJinaEmbedding:
        def __init__(self, **kwargs) -> None:
            pass

        def get_text_embedding_batch(self, texts: list[str]) -> list[list[float]]:
            raise RuntimeError("Jina unavailable")

    monkeypatch.setattr(
        "app.processing.cloud_embeddings.JinaEmbedding",
        FakeJinaEmbedding,
    )

    embedder = CloudJinaEmbedder(
        api_key="test-key",
        model="jina-embeddings-v3",
    )

    chunks = [
        NormalizedChunk(text="first chunk"),
    ]

    try:
        embedder.embed(chunks)
    except ApplicationException as exc:
        assert exc.code == "cloud_embedding_provider_failed"
    else:
        raise AssertionError("Expected ApplicationException")
