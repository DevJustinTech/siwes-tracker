"""
Smoke test — runs the whole API against an in-memory SQLite DB.
(For CI/no-MySQL environments. Real deployments use MySQL via database.sql.)

Run from backend/:  python smoke_test.py
"""

import io
import sys
from decimal import Decimal

import pytest
from fastapi.testclient import TestClient
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from sqlalchemy.pool import StaticPool

from app.core.database import get_db
from app.models import Base
from app.main import app


@pytest.fixture()
def client(monkeypatch):
    engine = create_engine(
        "sqlite://",
        connect_args={"check_same_thread": False},
        poolclass=StaticPool,
    )
    Base.metadata.create_all(engine)
    TestingSession = sessionmaker(bind=engine, autoflush=False, expire_on_commit=False)

    def override_get_db():
        db = TestingSession()
        try:
            yield db
        finally:
            db.close()

    app.dependency_overrides[get_db] = override_get_db
    with TestClient(app) as c:
        yield c
    app.dependency_overrides.clear()


def test_full_flow(client):
    # --- register ---
    r = client.post("/auth/register", json={
        "matric_no": "csc/2021/0123",
        "full_name": "Ada Obi",
        "email": "ada@example.com",
        "password": "password123",
    })
    assert r.status_code == 201, r.text
    body = r.json()
    assert body["matric_no"] == "CSC/2021/0123"  # upper-cased
    assert "password" not in body and "password_hash" not in body

    # duplicate email rejected
    r = client.post("/auth/register", json={
        "matric_no": "OTHER/1", "full_name": "X Y",
        "email": "ada@example.com", "password": "password123",
    })
    assert r.status_code == 409

    # --- login ---
    r = client.post("/auth/login", data={"username": "ada@example.com", "password": "password123"})
    assert r.status_code == 200, r.text
    token = r.json()["access_token"]
    auth = {"Authorization": f"Bearer {token}"}

    # wrong password
    r = client.post("/auth/login", data={"username": "ada@example.com", "password": "wrong-pass"})
    assert r.status_code == 401

    # --- me ---
    r = client.get("/auth/me", headers=auth)
    assert r.status_code == 200 and r.json()["email"] == "ada@example.com"

    # unauthenticated blocked
    assert client.get("/logs").status_code == 401

    # --- create daily entries ---
    r = client.post("/logs", headers=auth, json={
        "entry_date": "2026-07-06",
        "hours_worked": 8,
        "work_done": "Set up dev environment and studied the codebase.",
    })
    assert r.status_code == 201, r.text
    first = r.json()
    assert first["entry_date"] == "2026-07-06"

    r = client.post("/logs", headers=auth, json={
        "entry_date": "2026-07-07",
        "hours_worked": 7.5,
        "work_done": "Built responsive dashboard pages.",
        "challenges": "Cross-browser quirks.",
    })
    assert r.status_code == 201
    second = r.json()

    # duplicate day -> 409
    r = client.post("/logs", headers=auth, json={
        "entry_date": "2026-07-06",
        "hours_worked": 8, "work_done": "dup",
    })
    assert r.status_code == 409

    # validation: too many hours / bad hours
    r = client.post("/logs", headers=auth, json={
        "entry_date": "2026-07-08", "hours_worked": 30, "work_done": "x",
    })
    assert r.status_code == 422

    # --- list + search ---
    r = client.get("/logs", headers=auth)
    assert r.status_code == 200 and len(r.json()) == 2

    r = client.get("/logs", headers=auth, params={"q": "responsive"})
    assert len(r.json()) == 1 and r.json()[0]["id"] == second["id"]

    # --- update ---
    r = client.put(f"/logs/{second['id']}", headers=auth, json={"hours_worked": 9})
    assert r.status_code == 200 and Decimal(r.json()["hours_worked"]) == Decimal("9.0")

    # --- day lookup ---
    r = client.get("/logs/day/2026-07-06", headers=auth)
    assert r.status_code == 200 and r.json()["id"] == first["id"]

    # --- dashboard ---
    r = client.get("/dashboard/stats", headers=auth)
    stats = r.json()
    assert stats["entries"] == 2
    assert Decimal(stats["total_hours"]) == Decimal("17.0")
    assert Decimal(stats["avg_hours"]) == Decimal("8.5")
    assert stats["week_span"] == 1

    r = client.get("/dashboard/chart", headers=auth)
    assert len(r.json()) == 2 and r.json()[-1]["hours_worked"] == "9.0"

    # --- profile ---
    r = client.patch("/profile", headers=auth, json={"placement_org": "TechHub Ltd"})
    assert r.json()["placement_org"] == "TechHub Ltd"

    r = client.post("/profile/password", headers=auth,
                    json={"current_password": "wrong", "new_password": "newpassword1"})
    assert r.status_code == 400
    r = client.post("/profile/password", headers=auth,
                    json={"current_password": "password123", "new_password": "newpassword1"})
    assert r.status_code == 204
    assert client.post("/auth/login", data={"username": "ada@example.com", "password": "newpassword1"}).status_code == 200

    # --- delete ---
    r = client.delete(f"/logs/{second['id']}", headers=auth)
    assert r.status_code == 204
    assert client.get(f"/logs/{second['id']}", headers=auth).status_code == 404

    # another student's entry is invisible
    client.post("/auth/register", json={
        "matric_no": "EEE/2021/0002", "full_name": "Bola Ade",
        "email": "bola@example.com", "password": "password123",
    })
    r = client.post("/auth/login", data={"username": "bola@example.com", "password": "password123"})
    other_auth = {"Authorization": f"Bearer {r.json()['access_token']}"}
    assert client.get(f"/logs/{first['id']}", headers=other_auth).status_code == 404
    assert client.delete(f"/logs/{first['id']}", headers=other_auth).status_code == 404

    # tampered JWT rejected
    assert client.get("/auth/me", headers={"Authorization": "Bearer fake.token.here"}).status_code == 401


if __name__ == "__main__":
    sys.exit(pytest.main([__file__, "-q", "--no-header"]))
