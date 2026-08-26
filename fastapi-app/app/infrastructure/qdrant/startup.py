from app.core.config import Settings
from app.infrastructure.qdrant.client import build_qdrant_client
from app.infrastructure.qdrant.collections import ensure_collection_exists


def initialize_qdrant(settings: Settings) -> None:
    client = build_qdrant_client(settings)

    try:
        ensure_collection_exists(
            client=client,
            collection_name=settings.qdrant_cloud_collection,
        )
    finally:
        client.close()
