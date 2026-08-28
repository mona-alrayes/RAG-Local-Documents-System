from pathlib import Path

from app.parsing.base import BaseDocumentLoader
from app.parsing.normalization import normalize_llamaparse_pages
from app.parsing.normalized import NormalizedDocument
from app.parsing.providers.llamaparse import LlamaParsePage


def load_normalized_documents(
    loader: BaseDocumentLoader[LlamaParsePage],
    file_path: Path,
    *,
    preserve_page_numbers: bool,
) -> list[NormalizedDocument]:
    pages = loader.load(file_path)

    return normalize_llamaparse_pages(
        pages,
        preserve_page_numbers=preserve_page_numbers,
    )
