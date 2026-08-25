from fastapi import FastAPI

from app.core.config import get_settings


def create_app() -> FastAPI:
    settings = get_settings()

    return FastAPI(
        title=settings.app_name,
        version=settings.app_version,
    )


app = create_app()
