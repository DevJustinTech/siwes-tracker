"""Log entry CRUD endpoints — parity with the PHP app.

The DB enforces one entry per student per day (UNIQUE KEY uq_student_day).
"""

from datetime import date
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import String as sqlalchemy_String
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.core.database import get_db
from app.dependencies import get_current_student
from app.models import LogEntry, Student
from app.schemas import LogEntryCreate, LogEntryOut, LogEntryUpdate

router = APIRouter(prefix="/logs", tags=["logs"])


@router.get("", response_model=list[LogEntryOut])
def list_entries(
    q: Annotated[str | None, Query(max_length=100)] = None,
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> list[LogEntry]:
    stmt = select(LogEntry).where(LogEntry.student_id == student.id)
    if q:
        like = f"%{q}%"
        stmt = stmt.where(
            LogEntry.work_done.like(like)
            | LogEntry.challenges.like(like)
            | func.cast(LogEntry.entry_date, sqlalchemy_String(10)).like(f"{q}%")
        )
    stmt = stmt.order_by(LogEntry.entry_date.desc())
    return list(db.execute(stmt).scalars().all())


@router.get("/day/{entry_date}", response_model=LogEntryOut)
def get_entry_by_day(
    entry_date: date,
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> LogEntry:
    entry = db.execute(
        select(LogEntry).where(
            LogEntry.student_id == student.id, LogEntry.entry_date == entry_date
        )
    ).scalar_one_or_none()
    if entry is None:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "No entry for that day.")
    return entry


@router.get("/{entry_id}", response_model=LogEntryOut)
def get_entry(
    entry_id: int,
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> LogEntry:
    entry = db.get(LogEntry, entry_id)
    if entry is None or entry.student_id != student.id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Entry not found.")
    return entry


def _check_duplicate_day(
    db: Session, student_id: int, entry_date: date, exclude_id: int | None = None
) -> None:
    stmt = select(LogEntry.id).where(
        LogEntry.student_id == student_id, LogEntry.entry_date == entry_date
    )
    if exclude_id is not None:
        stmt = stmt.where(LogEntry.id != exclude_id)
    if db.execute(stmt).scalar_one_or_none() is not None:
        raise HTTPException(
            status.HTTP_409_CONFLICT,
            "You already logged this day — update that entry instead.",
        )


@router.post("", response_model=LogEntryOut, status_code=status.HTTP_201_CREATED)
def create_entry(
    payload: LogEntryCreate,
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> LogEntry:
    _check_duplicate_day(db, student.id, payload.entry_date)

    entry = LogEntry(
        student_id=student.id,
        entry_date=payload.entry_date,
        hours_worked=payload.hours_worked,
        work_done=payload.work_done.strip(),
        challenges=payload.challenges,
        remarks=payload.remarks,
    )
    db.add(entry)
    db.commit()
    db.refresh(entry)
    return entry


@router.put("/{entry_id}", response_model=LogEntryOut)
def update_entry(
    entry_id: int,
    payload: LogEntryUpdate,
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> LogEntry:
    entry = db.get(LogEntry, entry_id)
    if entry is None or entry.student_id != student.id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Entry not found.")

    changes = payload.model_dump(exclude_unset=True)

    if "entry_date" in changes:
        _check_duplicate_day(db, student.id, changes["entry_date"], exclude_id=entry.id)
        entry.entry_date = changes["entry_date"]

    for field in ("hours_worked", "work_done", "challenges", "remarks"):
        if field in changes:
            value = changes[field]
            setattr(entry, field, value.strip() if isinstance(value, str) and field == "work_done" else value)

    db.commit()
    db.refresh(entry)
    return entry


@router.delete("/{entry_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_entry(
    entry_id: int,
    student: Student = Depends(get_current_student),
    db: Session = Depends(get_db),
) -> None:
    entry = db.get(LogEntry, entry_id)
    if entry is None or entry.student_id != student.id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Entry not found.")
    db.delete(entry)
    db.commit()
