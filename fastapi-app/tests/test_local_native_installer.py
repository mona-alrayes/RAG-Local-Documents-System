from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path


SCRIPT_PATH = (
    Path(__file__).resolve().parents[1]
    / "scripts"
    / "install_local_native.py"
)


def _load_installer():
    spec = spec_from_file_location("install_local_native", SCRIPT_PATH)
    assert spec is not None
    assert spec.loader is not None

    module = module_from_spec(spec)
    spec.loader.exec_module(module)

    return module


def test_windows_installs_torch_from_xpu_index(monkeypatch) -> None:
    installer = _load_installer()
    calls = []

    monkeypatch.setattr(installer.sys, "platform", "win32")
    monkeypatch.setattr(
        installer,
        "_run_pip",
        lambda *arguments: calls.append(arguments),
    )

    installer._install_torch()

    assert calls == [
        (
            "install",
            "torch",
            "--index-url",
            installer.PYTORCH_XPU_INDEX_URL,
        )
    ]


def test_macos_installs_torch_from_default_index(monkeypatch) -> None:
    installer = _load_installer()
    calls = []

    monkeypatch.setattr(installer.sys, "platform", "darwin")
    monkeypatch.setattr(
        installer,
        "_run_pip",
        lambda *arguments: calls.append(arguments),
    )

    installer._install_torch()

    assert calls == [("install", "torch")]
