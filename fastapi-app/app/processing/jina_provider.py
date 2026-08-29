import json
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


JINA_EMBEDDINGS_URL = "https://api.jina.ai/v1/embeddings"

RETRYABLE_STATUS_CODES = {
    429,
    500,
    502,
    503,
    504,
}


class JinaProviderError(Exception):
    def __init__(self, *, retryable: bool) -> None:
        super().__init__("Jina embedding provider request failed.")
        self.retryable = retryable


class JinaEmbeddingProvider:
    def __init__(
        self,
        *,
        api_key: str,
        model: str,
        task: str,
        dimensions: int,
    ) -> None:
        self._api_key = api_key
        self._model = model
        self._task = task
        self._dimensions = dimensions

    def embed(self, texts: list[str]) -> list[list[float]]:
        payload = json.dumps(
            {
                "input": texts,
                "model": self._model,
                "encoding_type": "float",
                "task": self._task,
                "dimensions": self._dimensions,
            }
        ).encode("utf-8")

        request = Request(
            JINA_EMBEDDINGS_URL,
            data=payload,
            headers={
                "Authorization": f"Bearer {self._api_key}",
                "Content-Type": "application/json",
                "Accept": "application/json",
            },
            method="POST",
        )

        try:
            with urlopen(request) as response:
                body = json.loads(response.read().decode("utf-8"))
        except HTTPError as exc:
            raise JinaProviderError(
                retryable=exc.code in RETRYABLE_STATUS_CODES,
            ) from exc
        except (URLError, TimeoutError) as exc:
            raise JinaProviderError(retryable=True) from exc
        except (json.JSONDecodeError, UnicodeDecodeError) as exc:
            raise JinaProviderError(retryable=False) from exc

        try:
            embeddings = sorted(
                body["data"],
                key=lambda item: item["index"],
            )

            return [
                [float(value) for value in item["embedding"]]
                for item in embeddings
            ]
        except (KeyError, TypeError, ValueError) as exc:
            raise JinaProviderError(retryable=False) from exc
