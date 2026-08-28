from app.processing.cloud_chunking import NormalizedChunk
from app.processing.cloud_sparse import CloudSparseRepresenter


def test_cloud_sparse_representation_uses_qdrant_multilingual_bm25() -> None:
    chunks = [
        NormalizedChunk(text="النص الأول"),
        NormalizedChunk(text="النص الثاني"),
    ]

    documents = CloudSparseRepresenter().represent(chunks)

    assert [document.text for document in documents] == [
        "النص الأول",
        "النص الثاني",
    ]
    assert all(document.model == "Qdrant/bm25" for document in documents)
    assert all(
        document.options == {"tokenizer": "multilingual"}
        for document in documents
    )
