"""SIWES Tracker — FastAPI backend entry point.

Run (from the backend/ folder):
    uvicorn app.main:app --reload --port 8000

Interactive docs: http://127.0.0.1:8000/docs
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app import router_auth, router_logs, router_misc
from app.core.config import settings

app = FastAPI(
    title="SIWES Tracker API",
    description=(
        "REST API for the SIWES Tracker student log book. "
        "Shares the same MySQL database as the PHP app."
    ),
    version="1.0.0",
)

# Permissive CORS for local development (PHP frontend on Apache, Vite dev servers, etc.)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(router_auth.router)
app.include_router(router_logs.router)
app.include_router(router_misc.router)


@app.get("/", tags=["meta"])
def root() -> dict[str, str]:
    return {"name": "SIWES Tracker API", "docs": "/docs", "health": "/health"}


@app.get("/health", tags=["meta"])
def health() -> dict[str, str]:
    return {"status": "ok"}
