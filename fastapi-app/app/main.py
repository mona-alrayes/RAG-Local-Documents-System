from fastapi import FastAPI

from app.api.exception_handler import application_exception_handler
from app.api.v1.capabilities_routes import router as capabilities_router
from app.api.v1.health import router as health_router
from app.core.config import get_settings
from app.core.exceptions import ApplicationException
from app.core.logging import configure_logging
from app.middleware.correlation_id import CorrelationIdMiddleware
from app.middleware.internal_api_auth import InternalApiAuthMiddleware


def create_app() -> FastAPI:
    settings = get_settings()

    configure_logging()

    app = FastAPI(
        title=settings.app_name,
        version=settings.app_version,
    )

    app.add_exception_handler(
        ApplicationException,
        application_exception_handler,
    )

    app.include_router(health_router)
    app.include_router(capabilities_router)

    app.add_middleware(
        InternalApiAuthMiddleware,
        internal_api_key=settings.internal_api_key,
    )
    app.add_middleware(CorrelationIdMiddleware)

    return app


app = create_app()
