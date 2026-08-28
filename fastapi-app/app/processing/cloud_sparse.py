from qdrant_client import models

from app.processing.cloud_chunking import NormalizedChunk


CLOUD_SPARSE_MODEL = "Qdrant/bm25"
CLOUD_SPARSE_TOKENIZER = "multilingual"


class CloudSparseRepresenter:
    def represent(self, chunks: list[NormalizedChunk]) -> list[models.Document]:
        return [
            models.Document(
                text=chunk.text,
                model=CLOUD_SPARSE_MODEL,
                options={"tokenizer": CLOUD_SPARSE_TOKENIZER},
            )
            for chunk in chunks
        ]
