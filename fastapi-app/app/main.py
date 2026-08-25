from fastapi import FastAPI


def create_app() -> FastAPI:
    return FastAPI(
        title="RAG AI Service",
        version="0.1.0",
    )


app = create_app()
