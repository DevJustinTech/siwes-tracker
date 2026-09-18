"""
SQLAlchemy models mapped onto the shared PHP schema (database.sql).

No create_all() — the tables already exist; the PHP app owns the schema.
"""

from datetime import date, datetime
from decimal import Decimal

from sqlalchemy import DECIMAL, Date, DateTime, ForeignKey, Integer, String, Text, func
from sqlalchemy.orm import DeclarativeBase, Mapped, mapped_column, relationship


class Base(DeclarativeBase):
    pass


class Student(Base):
    __tablename__ = "students"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    matric_no: Mapped[str] = mapped_column(String(40), unique=True)
    full_name: Mapped[str] = mapped_column(String(120))
    email: Mapped[str] = mapped_column(String(160), unique=True)
    password_hash: Mapped[str] = mapped_column(String(255))
    institution: Mapped[str | None] = mapped_column(String(160))
    course_of_study: Mapped[str | None] = mapped_column(String(120))
    placement_org: Mapped[str | None] = mapped_column(String(160))
    industry_supervisor: Mapped[str | None] = mapped_column(String(160))
    created_at: Mapped[datetime | None] = mapped_column(DateTime, server_default=func.now())
    updated_at: Mapped[datetime | None] = mapped_column(
        DateTime, server_default=func.now(), onupdate=func.now()
    )

    entries: Mapped[list["LogEntry"]] = relationship(
        back_populates="student", cascade="all, delete-orphan"
    )


class LogEntry(Base):
    __tablename__ = "log_entries"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    student_id: Mapped[int] = mapped_column(ForeignKey("students.id", ondelete="CASCADE"))
    entry_date: Mapped[date] = mapped_column(Date)
    hours_worked: Mapped[Decimal] = mapped_column(DECIMAL(4, 1))
    work_done: Mapped[str] = mapped_column(Text)
    challenges: Mapped[str | None] = mapped_column(Text)
    remarks: Mapped[str | None] = mapped_column(Text)
    created_at: Mapped[datetime | None] = mapped_column(DateTime, server_default=func.now())
    updated_at: Mapped[datetime | None] = mapped_column(
        DateTime, server_default=func.now(), onupdate=func.now()
    )

    student: Mapped[Student] = relationship(back_populates="entries")
