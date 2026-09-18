"""Dashboard + student profile endpoints."""

from datetime import date
from decimal import Decimal
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.core.database import get_db
from app.core.security import hash_password, verify_password
from app.dependencies import get_current_student
from app.models import LogEntry, Student
from app.schemas import ChartPoint, DashboardStats, PasswordChange, StudentOut, StudentUpdate

router = APIRouter(tags=["dashboard & profile"])


# ---- Dashboard ------------------------------------------------------------
@router.get("/dashboard/stats", response_model=DashboardStats)
def dashboard_stats(
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> DashboardStats:
    row = db.execute(
        select(
            func.count(LogEntry.id),
            func.coalesce(func.sum(LogEntry.hours_worked), 0),
            func.min(LogEntry.entry_date),
            func.max(LogEntry.entry_date),
        ).where(LogEntry.student_id == student.id)
    ).one()

    entries, total_hours, first_day, last_day = row
    entries = int(entries)
    total_hours = Decimal(total_hours)
    avg_hours = (total_hours / entries).quantize(Decimal("0.1")) if entries else Decimal("0")
    week_span = 0
    if first_day is not None and last_day is not None:
        week_span = (last_day - first_day).days // 7 + 1

    return DashboardStats(
        entries=entries,
        total_hours=total_hours,
        avg_hours=avg_hours,
        week_span=int(week_span),
    )


@router.get("/dashboard/chart", response_model=list[ChartPoint])
def dashboard_chart(
    days: Annotated[int, Query(ge=1, le=60)] = 10,
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> list[ChartPoint]:
    rows = db.execute(
        select(LogEntry.entry_date, LogEntry.hours_worked)
        .where(LogEntry.student_id == student.id)
        .order_by(LogEntry.entry_date.desc())
        .limit(days)
    ).all()
    return [ChartPoint(entry_date=d, hours_worked=h) for d, h in reversed(rows)]


# ---- Profile --------------------------------------------------------------
@router.get("/profile", response_model=StudentOut)
def get_profile(student: Student = Depends(get_current_student)) -> Student:
    return student


@router.patch("/profile", response_model=StudentOut)
def update_profile(
    payload: StudentUpdate,
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> Student:
    changes = payload.model_dump(exclude_unset=True)
    for field, value in changes.items():
        setattr(student, field, value.strip() if isinstance(value, str) else value)
    db.commit()
    db.refresh(student)
    return student


@router.post("/profile/password", status_code=status.HTTP_204_NO_CONTENT)
def change_password(
    payload: PasswordChange,
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> None:
    if not verify_password(payload.current_password, student.password_hash):
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Current password is incorrect.")
    student.password_hash = hash_password(payload.new_password)
    db.commit()
