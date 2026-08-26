from collections.abc import AsyncIterator
from contextlib import asynccontextmanager

from fastapi import FastAPI

from app.api.exception_handler import application_exception_handler
from app.api.v1.capabilities_routes import router as capabilities_router
from app.api.v1.health import router as health_router
from app.core.config import (
    get_settings,
    validate_startup_configuration,
)
from app.core.exceptions import ApplicationException
from app.core.logging import configure_logging
from app.infrastructure.qdrant.startup import initialize_qdrant
from app.middleware.correlation_id import CorrelationIdMiddleware
from app.middleware.internal_api_auth import InternalApiAuthMiddleware
from app.runtime.startup import initialize_local_runtime


def create_app() -> FastAPI:
    settings = get_settings()

    configure_logging()

    @asynccontextmanager
    async def lifespan(_: FastAPI) -> AsyncIterator[None]:
        validate_startup_configuration(settings)
        initialize_qdrant(settings)
        initialize_local_runtime(settings)
        yield

    app = FastAPI(
        title=settings.app_name,
        version=settings.app_version,
        lifespan=lifespan,
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
