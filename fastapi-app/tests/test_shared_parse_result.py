from pathlib import Path

from app.parsing.normalized import NormalizedDocument
from app.parsing.pdf import PdfDocumentLoader
from app.parsing.providers.base import BaseParsingProvider
from app.parsing.providers.llamaparse import LlamaParsePage
from app.parsing.shared import load_normalized_documents


class CountingParsingProvider(BaseParsingProvider[LlamaParsePage]):
    def __init__(self) -> None:
        self.parse_calls = 0

    def parse(self, file_path: Path) -> list[LlamaParsePage]:
        self.parse_calls += 1

        assert file_path == Path("example.pdf")

        return [
            LlamaParsePage(
                page_number=1,
                markdown="النص المشترك بعد التحليل",
            )
        ]


class FakeConsumer:
    def __init__(self) -> None:
        self.received: list[NormalizedDocument] | None = None

    def consume(self, documents: list[NormalizedDocument]) -> None:
        self.received = documents


def test_normalized_parse_result_can_be_reused_without_reparsing() -> None:
    provider = CountingParsingProvider()
    loader = PdfDocumentLoader(provider)

    normalized = load_normalized_documents(
        loader,
        Path("example.pdf"),
        preserve_page_numbers=True,
    )

    cloud_consumer = FakeConsumer()
    local_consumer = FakeConsumer()

    cloud_consumer.consume(normalized)
    local_consumer.consume(normalized)

    assert provider.parse_calls == 1
    assert normalized == [
        NormalizedDocument(
            text="النص المشترك بعد التحليل",
            page=1,
            section=None,
        )
    ]
    assert cloud_consumer.received == normalized
    assert local_consumer.received == normalized
