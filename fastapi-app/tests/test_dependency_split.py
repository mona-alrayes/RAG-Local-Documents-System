from pathlib import Path
import tomllib


PROJECT_ROOT = Path(__file__).resolve().parents[1]
PYPROJECT_PATH = PROJECT_ROOT / "pyproject.toml"

FORBIDDEN_CLOUD_DEPENDENCIES = {
    "torch",
    "transformers",
    "ollama",
}


def _dependency_name(requirement: str) -> str:
    return (
        requirement.split("[", 1)[0]
        .split("=", 1)[0]
        .split("<", 1)[0]
        .split(">", 1)[0]
        .split("~", 1)[0]
        .strip()
        .lower()
    )


def test_base_dependencies_exclude_local_ai_packages() -> None:
    with PYPROJECT_PATH.open("rb") as file:
        pyproject = tomllib.load(file)

    dependencies = pyproject["project"]["dependencies"]
    dependency_names = {_dependency_name(item) for item in dependencies}

    assert dependency_names.isdisjoint(FORBIDDEN_CLOUD_DEPENDENCIES)


def test_local_native_extra_contains_local_ai_dependencies() -> None:
    with PYPROJECT_PATH.open("rb") as file:
        pyproject = tomllib.load(file)

    local_dependencies = pyproject["project"]["optional-dependencies"]["local-native"]
    dependency_names = {_dependency_name(item) for item in local_dependencies}

    assert "transformers" in dependency_names
