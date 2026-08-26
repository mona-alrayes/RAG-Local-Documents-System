from threading import Lock

from app.runtime.models import LocalRuntimeSnapshot


class LocalRuntimeState:
    def __init__(self) -> None:
        self._snapshot: LocalRuntimeSnapshot | None = None
        self._lock = Lock()

    def set(self, snapshot: LocalRuntimeSnapshot) -> None:
        with self._lock:
            self._snapshot = snapshot

    def get(self) -> LocalRuntimeSnapshot | None:
        with self._lock:
            return self._snapshot

    def clear(self) -> None:
        with self._lock:
            self._snapshot = None


local_runtime_state = LocalRuntimeState()
