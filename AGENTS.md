# SIWES Tracker

Plain PHP/MySQL app for tracking SIWES (industrial training) activity logs. Page-level PHP at the repo root; `backend/` is a separate Python service this app never calls.

## Before you start

- **Running it** — Apache serves *this working tree*, through a junction at `C:\xampp\htdocs\siwes tracker`. Do not copy files into `htdocs`. PHP and MySQL are not on PATH, so `php -v` fails; start, verify and stop commands are in `RUNNING.md`.
- **Checking it** — `.\check.ps1` lints every `.php` file and runs the test suite. Runs on every commit via `.githooks/pre-commit`. Flags: `-Smoke` probes the live app over HTTP, `-Mysql` adds integration tests.
- **Verifying** — one `200` from `login.php` proves nothing. `check.ps1 -Smoke` is the real check; `RUNNING.md` explains what each expected status means.

## Domain language

`CONTEXT.md` defines the terms this app uses: *daily entry*, *first logged day*, *SIWES week*, *week span*, *logged day*. Use those words and not the synonyms it rules out (`weekly entry`, `calendar week`).

## What this project is for

`MISSION.md` and `NOTES.md` explain the goal: the owner must be able to read any PHP file here and say what every line does. `lessons/` is the course built from that, `reference/` holds the file map and glossary, `RESOURCES.md` holds vetted external links.

## Conventions

See `CODING_STANDARDS.md` — the rules a change has to hold to, and which of them are mechanically enforced.

## Agent skills

### Issue tracker

Issues and specs live as local markdown files under `.scratch/<feature-slug>/` (created on first use). See `docs/agents/issue-tracker.md`.

### Triage labels

The five canonical triage roles are used with their default label strings. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: one `CONTEXT.md` and `docs/adr/` at the repo root. See `docs/agents/domain.md`.