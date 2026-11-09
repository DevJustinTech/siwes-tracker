# Deploying SIWES Tracker to Vercel + Neon

This branch turns the XAMPP version of the SIWES Tracker into a live,
serverless deployment:

- **Vercel** runs each public PHP page as a serverless function using the
  `vercel-php` runtime (PHP 8.5).
- **Neon** provides the managed PostgreSQL database.
- **Sessions** are stored in Postgres, because Vercel functions are ephemeral
  and cannot rely on local session files.

Live app: **https://siwes-tracker-six.vercel.app**
Health check: **https://siwes-tracker-six.vercel.app/health**

## Deployment record

| Item | Value |
| --- | --- |
| Vercel project | `siwes-tracker` (`prj_eAoFR4i0W72bIxFHHdv2vpUs6JT4`) |
| Vercel team | `akinbowale-olugbengas-projects` |
| Production URL | https://siwes-tracker-six.vercel.app |
| Neon project | `siwes-tracker` (`wispy-math-71649836`) |
| Neon region | `aws-eu-central-1` (Frankfurt) |
| Database | `neondb`, role `neondb_owner` |
| Deployment branch | `deploy/vercel-neon` |
| Demo account | `demo@siwes.test` / `demo1234` |

## Architecture

```
Browser
   │  HTTPS
   ▼
Vercel (serverless PHP functions in api/)
   │  PDO + SSL (pooled connection)
   ▼
Neon PostgreSQL
   ├── students
   ├── log_entries
   └── sessions
```

Every request is stateless at the Vercel layer: the PHP function opens a PDO
connection to Neon, reads or writes the data it needs, and returns the page.
The `sessions` table keeps a logged-in student signed in across function
instances.

## What changed from the XAMPP version

| Concern | XAMPP version | Vercel + Neon version |
| --- | --- | --- |
| Database | MySQL / MariaDB | PostgreSQL (Neon) |
| Driver | `pdo_mysql` | `pdo_pgsql` |
| Schema | `database.sql` | `database.pg.sql` |
| Sessions | PHP files on disk | `sessions` table in Postgres |
| Config | credentials in `config.php` | `DATABASE_URL` environment variable |
| Public pages | repository root | `api/` (Vercel function convention) |
| Shared code | root + `partials/` | `api/lib/` |
| Web-server rules | `.htaccess` | `.vercelignore` + `vercel.json` routes |
| Search | MySQL `LIKE` | PostgreSQL `ILIKE` |
| Inserts that need the new id | `lastInsertId()` | `INSERT ... RETURNING id` |
| `updated_at` | `ON UPDATE CURRENT_TIMESTAMP` | `updated_at = now()` in updates |

The application logic, UI, CSRF protection, prepared statements and password
hashing are unchanged.

## Layout

```
api/                 public pages (one serverless function each)
  login.php          register.php      logout.php
  dashboard.php      logs.php          log_form.php
  log_delete.php     profile.php       health.php
  lib/               shared code, not directly routable
    config.php       helpers.php       stats.php
    weeks.php        validation.php
    partials/        layout + icons
assets/              static CSS and JS (served by Vercel's CDN)
database.pg.sql      PostgreSQL schema
vercel.json          function runtime + routes + security headers
.vercelignore        keeps docs, tests and teaching files off the deployment
scripts/seed.php     optional demo data (run locally, never deployed)
router.php           local development router for `php -S`
```

## Environment variables

| Name | Required | Purpose |
| --- | --- | --- |
| `DATABASE_URL` | yes | Neon pooled connection string, e.g. `postgresql://user:pass@host/db?sslmode=require` |
| `APP_NAME` | no | Overrides the app name shown in the UI |
| `DB_SSLMODE` | no | Defaults to `require`; use `disable` only for a local Postgres without TLS |

`api/lib/config.php` reads these at runtime. No credentials are committed.

## Deploy from scratch

```sh
# 1. Create the Neon project and database, then apply the schema
psql "$DATABASE_URL" -f database.pg.sql

# 2. Point the Vercel project at Neon
vercel env add DATABASE_URL production

# 3. Deploy
vercel --prod

# 4. Optional demo data (run locally, with the Neon URL)
DATABASE_URL="$DATABASE_URL" php scripts/seed.php
```

## Verify

```sh
curl -s -o /dev/null -w '%{http_code}\n' https://<app>.vercel.app/            # 302 -> login.php
curl -s -o /dev/null -w '%{http_code}\n' https://<app>.vercel.app/login.php   # 200
curl -s https://<app>.vercel.app/health                                        # {"status":"ok",...}
```

Then register an account, log a daily entry, edit it, search for it, and delete
it. The demo account created by `scripts/seed.php` is:

```
demo@siwes.test / demo1234
```

### Verification results (2026-11-09)

| Check | Result |
| --- | --- |
| `/` signed out | `302` → `/login.php` |
| `/login.php`, `/register.php` | `200` |
| `/dashboard.php`, `/logs.php` signed out | `302` → `/login.php` |
| `/assets/css/style.css` | `200` |
| `/health` | `200` `{"status":"ok","database":"connected","students":1}` |
| Demo login | `302` → `/dashboard.php` |
| Dashboard | `200`, "Hello, Ada", 25 days logged |
| Create daily entry | `302` → `/logs.php`, row visible |
| Search (`?q=vercel`) | matching entry returned |
| Update entry | `302`, new text visible |
| Duplicate same-day entry | friendly error, no second row |
| Delete entry | `302`, row removed |
| Private paths (`database.pg.sql`, `README.md`, `api/lib/config.php`, `.git/config`, `tests/`, `scripts/`) | `302`, no source served |
| Security headers | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` present; `X-Powered-By` removed |
| Sessions after testing | 34 rows in `sessions` (DB-backed sessions working) |

### Neon + libpq note

The PHP build used by `vercel-php` ships a libpq without SNI support. Neon's
pooler therefore needs the endpoint ID in the connection options:

```
options=endpoint=ep-...-pooler
```

`api/lib/helpers.php` adds this automatically for `*.neon.tech` hosts. Without
it, the connection fails with "Endpoint ID is not specified".

## Notes for the report

- **Serverless architecture** — Vercel builds one function per public page; no
  Apache or long-running server is required.
- **Managed database** — Neon supplies PostgreSQL with TLS and connection
  pooling; the app only needs `DATABASE_URL`.
- **Stateless sessions** — session data is stored in Postgres so authentication
  works across cold starts and multiple function instances.
- **Security** — PDO prepared statements, CSRF tokens on every form,
  `password_hash()`/`password_verify()`, HttpOnly + SameSite cookies, and secure
  cookies when served over HTTPS.
- **Least exposure** — `.vercelignore` keeps tests, documentation and teaching
  material out of the deployment; `vercel.json` routes only the public pages.

## Trade-offs

- `vercel-php` is a **community runtime**, not an official Vercel runtime. It is
  archived upstream (October 2026) but still builds and runs successfully; Vercel
  recommends containers for new long-term PHP projects.
- Serverless functions cold-start in roughly 250 ms; Neon may also autosuspend on
  the free plan, so the first request after idle is slower.
- Each request opens a pooled PDO connection and reads/writes one session row;
  that is the cost of stateless authentication.
- The app has no file uploads, background jobs or cron work, so it fits the
  serverless model without extra services.

