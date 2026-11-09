# SIWES Tracker 📘

> **Deployment branch:** this branch runs the same app on **Vercel + Neon
> PostgreSQL**. See [`DEPLOYMENT.md`](DEPLOYMENT.md) for the live URL, schema
> and deploy steps. The XAMPP instructions below describe the original
> `baseline` branch.

A simple, clean web app where students log their daily SIWES work — built with **plain PHP + MySQL (PDO)** and hand-rolled CSS. No frameworks, no Composer, no build step.

## Features

- **Register / Login** — secure password hashing, sessions, CSRF protection on every form
- **Daily log CRUD** — create, read, update, delete daily entries (date, hours, work done, challenges)
- **One entry per day** — duplicates are blocked with a friendly message (and at the DB level)
- **Dashboard** — stat cards (days logged, total hours, avg hours/day, week span), a pure-CSS hours-per-day bar chart, recent entries
- **Search** — filter log entries by keyword
- **Profile** — update details/placement info + change password
- **Dark mode** — toggle in the top bar, remembered in `localStorage`
- **Responsive** — sidebar collapses to an off-canvas menu on mobile

## Project structure

```
siwes tracker/
├── assets/
│   ├── css/style.css      ← design system (light + dark)
│   ├── js/app.js          ← theme toggle, mobile sidebar, password eye
│   └── teach/             ← course-player CSS/JS, not part of the app
├── partials/
│   ├── layout-top.php     ← shared shell (sidebar, topbar, flashes)
│   ├── layout-bottom.php
│   └── icons.php          ← inline SVG icon set
├── tests/
│   ├── run.php            ← unit suite, no database needed
│   ├── weeks.php          ← week maths, runs standalone
│   └── mysql.php          ← integration tests, opt-in via --mysql
├── config.php             ← DB credentials + app constants
├── helpers.php            ← requires config, weeks, stats, validation, icons;
│                             adds db(), e(), flash, CSRF, auth helpers
├── weeks.php              ← siwes_week_number(), siwes_week_span()
├── stats.php              ← the dashboard's aggregate queries + chart data
├── validation.php         ← validate_log_fields(), validate_registration_fields()
├── database.sql           ← schema — import this first
├── login.php  register.php  logout.php
├── dashboard.php          ← stats + chart + recent entries
├── logs.php               ← list + search (Read)
├── log_form.php           ← create + update (Create/Update)
├── log_delete.php         ← delete with confirmation (Delete)
├── profile.php            ← details + password change
├── seed.php               ← optional demo data (delete after use)
├── router.php             ← only for `php -S` dev server
├── .htaccess              ← keeps .git, docs, tests, *.md and *.sql off HTTP
├── check.ps1              ← lint + standards + tests: the repo's check command
├── AGENTS.md              ← agent guidance for this repo
├── CODING_STANDARDS.md    ← the rules a change has to hold to
└── RUNNING.md             ← starting it on this machine, and proving it works
```

## Setup (XAMPP — the usual way)

`RUNNING.md` has the exact commands, including the fact that PHP and MySQL are **not** on this machine's PATH.

1. **Serve the working tree** — `C:\xampp\htdocs\siwes tracker` is a *junction* to this folder, so Apache already serves this code. Do not copy files into `htdocs`; that is how the two trees drifted apart before.
2. **Start Apache + MySQL** — `C:\xampp\apache_start.bat` and `C:\xampp\mysql_start.bat`, or the XAMPP control panel.
3. **Import the database** — `& C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS siwes_tracker;"` then pipe `database.sql` in. Or use [phpMyAdmin](http://localhost/phpmyadmin) → *Import*.
4. **Check credentials** — `config.php`. Defaults are XAMPP's: host `127.0.0.1`, user `root`, empty password.
5. **Open the app** — <http://localhost/siwes%20tracker/> and register an account. `/` resolves to `dashboard.php`, which redirects to `login.php` when signed out.
   - Want sample data first? Visit <http://localhost/siwes%20tracker/seed.php>, log in with `demo@siwes.test` / `demo1234`, then **delete `seed.php`**.
6. **Verify** — `RUNNING.md` has a route sweep. A single `200` from `login.php` does not prove the app works.

## Setup (PHP's built-in server — no Apache)

`router.php` exists for this. MySQL still needs to be running.

```powershell
& C:\xampp\php\php.exe -S localhost:8000 router.php
```

Then open http://localhost:8000.

## Checks

```powershell
& .\check.ps1              # php -l over every file, then the test suite
& .\check.ps1 -Smoke       # also probe the running app over HTTP
& .\check.ps1 -Mysql       # include integration tests (needs SIWES_TEST_DB=siwes_tracker_test)
```

The suite runs on every commit via `.githooks/pre-commit`. After cloning, enable it once with `git config core.hooksPath .githooks`.

## Notes for the demo/viva

- All DB access uses **PDO prepared statements** (SQL-injection safe); all output is escaped with `e()` (XSS-safe); forms carry **CSRF tokens**.
- `log_form.php` handles both **create** (`log_form.php`) and **update** (`log_form.php?id=5`) — a classic single-form CRUD pattern.
- Deletes go through a **confirmation page** (`log_delete.php`) rather than a bare link.
- The unique key `uq_student_day` in the schema guarantees one entry per student per day at the DB level, not just in PHP.
