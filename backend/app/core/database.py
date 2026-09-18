"""
Database session management.

Uses SQLAlchemy Core against the SAME MySQL database the PHP app writes to —
the `students` and `log_entries` tables come from database.sql (shared schema).
"""

from collections.abc import Generator

from sqlalchemy import create_engine
from sqlalchemy.orm import Session, sessionmaker

from app.core.config import settings

engine = create_engine(
    "mysql+pymysql://{user}:{password}@{host}:{port}/{name}".format(
        user=settings.db_user,
        password=settings.db_pass,
        host=settings.db_host,
        port=settings.db_port,
        name=settings.db_name,
    ),
    pool_pre_ping=True,
    pool_recycle=3600,
)

SessionLocal = sessionmaker(bind=engine, autoflush=False, expire_on_commit=False)


def get_db() -> Generator[Session, None, None]:
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
