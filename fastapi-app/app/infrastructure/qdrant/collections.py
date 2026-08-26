from qdrant_client import QdrantClient


def ensure_collection_exists(
    client: QdrantClient,
    collection_name: str,
) -> None:
    if client.collection_exists(collection_name=collection_name):
        return

    client.create_collection(
        collection_name=collection_name,
        vectors_config={},
    )
