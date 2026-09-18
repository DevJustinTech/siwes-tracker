# SIWES Tracker 📘

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
│   └── js/app.js          ← theme toggle, mobile sidebar, password eye
├── partials/
│   ├── layout-top.php     ← shared shell (sidebar, topbar, flashes)
│   ├── layout-bottom.php
│   └── icons.php          ← inline SVG icon set
├── config.php             ← DB credentials + app constants
├── helpers.php            ← db(), e(), flash, CSRF, date helpers
├── database.sql           ← schema — import this first
├── login.php  register.php  logout.php
├── dashboard.php          ← stats + chart + recent entries
├── logs.php               ← list + search (Read)
├── log_form.php           ← create + update (Create/Update)
├── log_delete.php         ← delete with confirmation (Delete)
├── profile.php            ← details + password change
├── seed.php               ← optional demo data (delete after use)
└── router.php             ← only for `php -S` dev server
```

## Setup (XAMPP — the usual way)

1. **Copy the project** into `C:\xampp\htdocs\siwes tracker` (or clone there).
2. **Start Apache + MySQL** in the XAMPP control panel.
3. **Import the database** — open [phpMyAdmin](http://localhost/phpmyadmin) → *Import* → choose `database.sql` → Go.
   (Or from a terminal: `mysql -u root < database.sql`.)
4. **Check credentials** — open `config.php`. Defaults are XAMPP's: host `127.0.0.1`, user `root`, empty password. Change `APP_URL` if you rename the folder.
5. **Open the app** — visit `http://localhost/siwes%20tracker/` and register an account.
   - Want sample data first? Visit `http://localhost/siwes%20tracker/seed.php`, log in with `demo@siwes.test` / `demo1234`, then **delete `seed.php`**.

## Setup (PHP's built-in server — no Apache)

If PHP is on your PATH but you don't want Apache:

```powershell
mysql -u root < database.sql      # still needs MySQL running (XAMPP's MySQL works)
php -S localhost:8000 router.php
```

Then open http://localhost:8000.

## Notes for the demo/viva

- All DB access uses **PDO prepared statements** (SQL-injection safe); all output is escaped with `e()` (XSS-safe); forms carry **CSRF tokens**.
- `log_form.php` handles both **create** (`log_form.php`) and **update** (`log_form.php?id=5`) — a classic single-form CRUD pattern.
- Deletes go through a **confirmation page** (`log_delete.php`) rather than a bare link.
- The unique key `uq_student_day` in the schema guarantees one entry per student per day at the DB level, not just in PHP.
