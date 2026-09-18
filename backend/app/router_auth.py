"""Auth endpoints — register, login, me."""

from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.security import OAuth2PasswordRequestForm
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.database import get_db
from app.core.security import create_access_token, hash_password, verify_password
from app.dependencies import get_current_student
from app.models import Student
from app.schemas import StudentCreate, StudentOut, Token

router = APIRouter(prefix="/auth", tags=["auth"])


@router.post("/register", response_model=StudentOut, status_code=status.HTTP_201_CREATED)
def register(payload: StudentCreate, db: Session = Depends(get_db)) -> Student:
    matric = payload.matric_no.strip().upper()
    email = payload.email.strip().lower()

    conflicts = db.execute(
        select(Student).where(
            (Student.email == email) | (Student.matric_no == matric)
        )
    ).scalars().all()
    for row in conflicts:
        if row.email.lower() == email:
            raise HTTPException(status.HTTP_409_CONFLICT, "This email is already registered.")
        raise HTTPException(status.HTTP_409_CONFLICT, "This matric number is already registered.")

    student = Student(
        matric_no=matric,
        full_name=payload.full_name.strip(),
        email=email,
        password_hash=hash_password(payload.password),
        institution=payload.institution,
        course_of_study=payload.course_of_study,
    )
    db.add(student)
    db.commit()
    db.refresh(student)
    return student


@router.post("/login", response_model=Token)
def login(
    form: OAuth2PasswordRequestForm = Depends(),
    db: Session = Depends(get_db),
) -> Token:
    """OAuth2 form login (username = email) so /docs Authorize works out of the box."""
    student = db.execute(
        select(Student).where(Student.email == form.username.strip().lower())
    ).scalar_one_or_none()

    # Same message for unknown email / wrong password — no account enumeration.
    if student is None or not verify_password(form.password, student.password_hash):
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Invalid email or password.")

    return Token(access_token=create_access_token(str(student.id)))


@router.get("/me", response_model=StudentOut)
def me(student: Student = Depends(get_current_student)) -> Student:
    return student
