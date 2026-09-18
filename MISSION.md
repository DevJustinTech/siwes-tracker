# Mission: Understand every line of the SIWES Tracker

## Why
Gbenga has the SIWES Tracker project in front of him but didn't build it line-by-line — right now it's someone else's magic to him. He wants to own it completely: read any `.php` file in this folder and know exactly what every line does and why it's there, so he can defend it in a viva/demo, change it safely, and speak about it as the engineer who understands it.

## Success looks like
- Trace what happens, step by step, when a browser requests `http://localhost/siwes%20tracker/login.php` — from Apache handing the file to PHP, to the HTML arriving back
- Read any line of the 12 core PHP files and name the language feature it uses (or know where in the reference sheet to look it up)
- Explain the security trio the app leans on — prepared statements, `e()` escaping, CSRF tokens — including what attack each one stops
- Modify something (add a field, change a stat, alter a redirect) without guessing

## Constraints
- **Core PHP only — the `backend/` Python/FastAPI side is explicitly out of scope.** Don't read it, don't reference it, don't let it confuse the story: this app never calls it.
- Sandbox: XAMPP on Windows (Apache + MySQL + phpMyAdmin); PHP built-in server (`php -S`) as an alternative
- ~20–25 min per lesson; every lesson is grounded in files that exist in this folder — no toy examples
- Assume: programming fundamentals from CS coursework, but a beginner at PHP and at how web requests work

## Out of scope
- The Python backend (`backend/`) entirely
- The CSS design system and `assets/js/app.js` beyond what's needed to understand how PHP pages reference them
- Deployment, Composer, frameworks
