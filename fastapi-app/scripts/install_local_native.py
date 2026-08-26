from pathlib import Path
import subprocess
import sys


PROJECT_ROOT = Path(__file__).resolve().parents[1]
PYTORCH_XPU_INDEX_URL = "https://download.pytorch.org/whl/xpu"


def _run_pip(*arguments: str) -> None:
    subprocess.run(
        [sys.executable, "-m", "pip", *arguments],
        cwd=PROJECT_ROOT,
        check=True,
    )


def _install_torch() -> None:
    if sys.platform == "win32":
        _run_pip(
            "install",
            "torch",
            "--index-url",
            PYTORCH_XPU_INDEX_URL,
        )
        return

    if sys.platform == "darwin":
        _run_pip("install", "torch")
        return

    raise RuntimeError(
        f"Local-native bootstrap is not supported on platform: {sys.platform}"
    )


def main() -> None:
    _install_torch()
    _run_pip("install", ".[local-native]")


if __name__ == "__main__":
    main()
