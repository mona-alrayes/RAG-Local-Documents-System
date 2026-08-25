from fastapi import FastAPI

from app.core.config import get_settings
from app.core.logging import configure_logging
from app.middleware.correlation_id import CorrelationIdMiddleware


def create_app() -> FastAPI:
    settings = get_settings()

    configure_logging()

    app = FastAPI(
        title=settings.app_name,
        version=settings.app_version,
    )

    app.add_middleware(CorrelationIdMiddleware)

    return app


app = create_app()
