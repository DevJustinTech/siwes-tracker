"""
SIWES Tracker — FastAPI backend settings.

Reads .env (see .env.example). Defaults match the PHP app's config.php
(XAMPP: root with empty password, database `siwes_tracker`).
"""

from functools import lru_cache
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    # ---- Database (same MySQL the PHP app uses) ----
    db_host: str = "127.0.0.1"
    db_port: int = 3306
    db_name: str = "siwes_tracker"
    db_user: str = "root"
    db_pass: str = ""

    # ---- Auth ----
    jwt_secret: str = "change-me-in-production"
    jwt_algorithm: str = "HS256"
    access_token_expire_minutes: int = 60 * 24  # 24h

    model_config = {"env_file": ".env", "env_file_encoding": "utf-8"}


@lru_cache
def get_settings() -> Settings:
    return Settings()


settings = get_settings()
