import os

import pytest
from fastapi.testclient import TestClient

from app.core.config import StartupConfigurationError, get_settings
from app.main import create_app


TEST_API_KEY = "d9-test-internal-key"


def clear_configuration() -> None:
    for name in (
        "RAG_DEPLOYMENT_MODE",
        "LOCAL_AI_TOPOLOGY",
        "INTERNAL_API_KEY",
    ):
        os.environ.pop(name, None)

    get_settings.cache_clear()


def test_local_configuration_starts_successfully() -> None:
    clear_configuration()

    os.environ["RAG_DEPLOYMENT_MODE"] = "local"
    os.environ["LOCAL_AI_TOPOLOGY"] = "host_native"
    os.environ["INTERNAL_API_KEY"] = TEST_API_KEY
    get_settings.cache_clear()

    with TestClient(create_app()):
        pass


def test_cloud_configuration_starts_successfully() -> None:
    clear_configuration()

    os.environ["RAG_DEPLOYMENT_MODE"] = "cloud"
    os.environ["INTERNAL_API_KEY"] = TEST_API_KEY
    get_settings.cache_clear()

    with TestClient(create_app()):
        pass


def test_missing_internal_api_key_fails_startup() -> None:
    clear_configuration()

    os.environ["RAG_DEPLOYMENT_MODE"] = "cloud"
    get_settings.cache_clear()

    with pytest.raises(
        StartupConfigurationError,
        match="INTERNAL_API_KEY is required",
    ):
        with TestClient(create_app()):
            pass


def test_cloud_configuration_rejects_local_ai_topology() -> None:
    clear_configuration()

    os.environ["RAG_DEPLOYMENT_MODE"] = "cloud"
    os.environ["LOCAL_AI_TOPOLOGY"] = "host_native"
    os.environ["INTERNAL_API_KEY"] = TEST_API_KEY
    get_settings.cache_clear()

    with pytest.raises(
        StartupConfigurationError,
        match="LOCAL_AI_TOPOLOGY must not be configured",
    ):
        with TestClient(create_app()):
            pass
