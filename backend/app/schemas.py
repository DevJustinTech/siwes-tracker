"""Pydantic schemas — request/response contracts for the API."""

from datetime import date, datetime
from decimal import Decimal
from typing import Annotated

from pydantic import BaseModel, ConfigDict, EmailStr, Field


# ---- Auth -----------------------------------------------------------------
class StudentCreate(BaseModel):
    matric_no: Annotated[str, Field(min_length=3, max_length=40)]
    full_name: Annotated[str, Field(min_length=2, max_length=120)]
    email: EmailStr
    password: Annotated[str, Field(min_length=8, max_length=128)]
    institution: str | None = None
    course_of_study: str | None = None


class StudentLogin(BaseModel):
    email: EmailStr
    password: str


class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"


# ---- Student / profile ----------------------------------------------------
class StudentOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: int
    matric_no: str
    full_name: str
    email: EmailStr
    institution: str | None
    course_of_study: str | None
    placement_org: str | None
    industry_supervisor: str | None
    created_at: datetime | None


class StudentUpdate(BaseModel):
    full_name: Annotated[str | None, Field(min_length=2, max_length=120)] = None
    institution: str | None = None
    course_of_study: str | None = None
    placement_org: str | None = None
    industry_supervisor: str | None = None


class PasswordChange(BaseModel):
    current_password: str
    new_password: Annotated[str, Field(min_length=8, max_length=128)]


# ---- Log entries ----------------------------------------------------------
class LogEntryBase(BaseModel):
    entry_date: date
    hours_worked: Annotated[Decimal, Field(gt=0, le=24)]
    work_done: Annotated[str, Field(min_length=1, max_length=10_000)]
    challenges: str | None = None
    remarks: str | None = None


class LogEntryCreate(LogEntryBase):
    pass


class LogEntryUpdate(BaseModel):
    entry_date: date | None = None
    hours_worked: Annotated[Decimal | None, Field(gt=0, le=24)] = None
    work_done: Annotated[str | None, Field(min_length=1, max_length=10_000)] = None
    challenges: str | None = None
    remarks: str | None = None


class LogEntryOut(LogEntryBase):
    model_config = ConfigDict(from_attributes=True)

    id: int
    student_id: int
    created_at: datetime | None
    updated_at: datetime | None


# ---- Dashboard ------------------------------------------------------------
class DashboardStats(BaseModel):
    entries: int
    total_hours: Decimal
    avg_hours: Decimal
    week_span: int


class ChartPoint(BaseModel):
    entry_date: date
    hours_worked: Decimal
