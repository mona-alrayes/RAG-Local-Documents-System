from llama_index.embeddings.jinaai import JinaEmbedding

from app.core.exceptions import ApplicationException
from app.processing.cloud_chunking import NormalizedChunk


JINA_PASSAGE_TASK = "retrieval.passage"
CLOUD_EMBEDDING_DIMENSION = 1024


class CloudJinaEmbedder:
    def __init__(self, api_key: str, model: str) -> None:
        self._embedder = JinaEmbedding(
            api_key=api_key,
            model=model,
            task=JINA_PASSAGE_TASK,
            dimensions=CLOUD_EMBEDDING_DIMENSION,
        )

    def embed(self, chunks: list[NormalizedChunk]) -> list[list[float]]:
        texts = [chunk.text for chunk in chunks]

        try:
            vectors = self._embedder.get_text_embedding_batch(texts)
        except Exception as exc:
            raise ApplicationException(
                code="cloud_embedding_provider_failed",
                message="Cloud embedding provider failed.",
            ) from exc

        if len(vectors) != len(chunks):
            raise ApplicationException(
                code="cloud_embedding_result_invalid",
                message="Cloud embedding result count does not match chunk count.",
            )

        if any(len(vector) != CLOUD_EMBEDDING_DIMENSION for vector in vectors):
            raise ApplicationException(
                code="cloud_embedding_result_invalid",
                message=(
                    "Cloud embedding vector dimension must be "
                    f"{CLOUD_EMBEDDING_DIMENSION}."
                ),
            )

        return vectors
