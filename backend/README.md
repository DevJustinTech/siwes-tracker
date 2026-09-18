# SIWES Tracker — FastAPI Backend

REST API covering the same features as the PHP app. It reads and writes the **same MySQL database** (`siwes_tracker`), so entries created in the PHP app show up here and vice-versa.

## Run it

```powershell
# 1. From the backend/ folder — one-time setup
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt

# 2. Make sure MySQL is running and database.sql has been imported
#    (same database the PHP app uses)

# 3. Start the API
uvicorn app.main:app --reload --port 8000
```

- Interactive docs (Swagger): **http://127.0.0.1:8000/docs**
- Alternative docs (ReDoc): http://127.0.0.1:8000/redoc

Credentials come from `.env` (copy `.env.example`). Defaults match XAMPP: `root`, empty password, database `siwes_tracker`.

## Endpoints

| Method | Path | What it does |
|---|---|---|
| POST | `/auth/register` | Create account (matric no, name, email, password) |
| POST | `/auth/login` | Get JWT token (OAuth2 form: username=email, password) |
| GET | `/auth/me` | Current student |
| GET | `/logs?q=keyword` | List own entries, optional search |
| GET | `/logs/day/2026-09-07` | Get entry by date |
| GET | `/logs/{id}` | Get one entry |
| POST | `/logs` | Create entry (409 if day already logged) |
| PUT | `/logs/{id}` | Partial update |
| DELETE | `/logs/{id}` | Delete entry |
| GET | `/dashboard/stats` | Totals: entries, hours, avg hours/day, week span |
| GET | `/dashboard/chart?days=10` | Hours per day for the chart |
| GET/PATCH | `/profile` | View / update details & placement |
| POST | `/profile/password` | Change password |

All `/logs`, `/dashboard`, `/profile` routes require `Authorization: Bearer <token>`.

## Notes

- **Shared schema** — the API maps onto the exact tables from `database.sql`; there's no `create_all()`. The PHP app owns the schema.
- **Password compatibility** — hashing is bcrypt, the same algorithm PHP's `password_hash()` uses by default, so an account registered in the PHP app can log in through the API and vice-versa.
- **One entry per day** — the DB's `uq_student_day` unique key enforces it, and a pre-check returns a friendly **409** with the same wording as the PHP app.
- JWTs expire after 24h (configurable via `ACCESS_TOKEN_EXPIRE_MINUTES`).

## Quick smoke test

```powershell
# register
curl -X POST http://127.0.0.1:8000/auth/register -H "Content-Type: application/json" `
  -d '{\"matric_no\":\"CSC/2021/9999\",\"full_name\":\"Test User\",\"email\":\"test@siwes.test\",\"password\":\"password123\"}'

# login (OAuth2 form style)
curl -X POST http://127.0.0.1:8000/auth/login -H "Content-Type: application/x-www-form-urlencoded" `
  -d "username=test@siwes.test&password=password123"

# then use: -H "Authorization: Bearer <token>"
```

Easiest way to explore: open `/docs` and click "Authorize".
