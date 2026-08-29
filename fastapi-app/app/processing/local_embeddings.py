from importlib import import_module
from typing import cast

from app.core.exceptions import ApplicationException
from app.processing.chunks import NormalizedChunk
from app.runtime.models import LocalRuntimeSnapshot, RuntimeBackend, RuntimeDtype

LOCAL_EMBEDDING_DIMENSION = 1024


class LocalBgeM3Embedder:
    def __init__(self, model: str, runtime: LocalRuntimeSnapshot) -> None:
        if (
            not runtime.ready
            or runtime.selected_backend is None
            or runtime.selected_dtype is None
        ):
            raise ApplicationException(
                code="local_embedding_runtime_unavailable",
                message="Local embedding runtime is not ready.",
            )

        self._device = self._resolve_torch_device(runtime.selected_backend)

        try:
            torch = import_module("torch")
            transformers = import_module("transformers")

            torch_dtype = (
                torch.float16
                if runtime.selected_dtype is RuntimeDtype.FP16
                else torch.float32
            )

            self._tokenizer = transformers.AutoTokenizer.from_pretrained(model)
            self._model = transformers.AutoModel.from_pretrained(
                model,
                torch_dtype=torch_dtype,
            )
            self._model = self._model.to(self._device)
            self._model.eval()
            self._torch = torch
        except Exception as exc:
            raise ApplicationException(
                code="local_embedding_model_failed",
                message="Local embedding model failed to load.",
            ) from exc

    def embed(self, chunks: list[NormalizedChunk]) -> list[list[float]]:
        if not chunks:
            return []

        texts = [chunk.text for chunk in chunks]

        try:
            inputs = self._tokenizer(
                texts,
                padding=True,
                truncation=True,
                return_tensors="pt",
            )
            inputs = {
                key: value.to(self._device)
                for key, value in inputs.items()
            }

            with self._torch.inference_mode():
                outputs = self._model(**inputs)
                dense_vectors = outputs.last_hidden_state[:, 0]

            vectors = cast(
                list[list[float]],
                dense_vectors.detach().cpu().float().tolist(),
            )
        except Exception as exc:
            raise ApplicationException(
                code="local_embedding_model_failed",
                message="Local embedding model inference failed.",
            ) from exc

        if len(vectors) != len(chunks):
            raise ApplicationException(
                code="local_embedding_result_invalid",
                message="Local embedding result count does not match chunk count.",
            )

        if any(len(vector) != LOCAL_EMBEDDING_DIMENSION for vector in vectors):
            raise ApplicationException(
                code="local_embedding_result_invalid",
                message=(
                    "Local embedding vector dimension must be "
                    f"{LOCAL_EMBEDDING_DIMENSION}."
                ),
            )

        return vectors

    @staticmethod
    def _resolve_torch_device(backend: RuntimeBackend) -> str:
        if backend in {RuntimeBackend.CUDA, RuntimeBackend.ROCM}:
            return "cuda"

        return backend.value
