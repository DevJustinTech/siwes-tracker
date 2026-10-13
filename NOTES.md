# NOTES

## Who
Gbenga — CS undergrad (LCU). Has the SIWES Tracker project but didn't write it line-by-line; it currently reads as magic. Wants full ownership: understand every line of the **core PHP** so he can defend it in a viva, modify it safely, and talk about it as someone who truly understands it.

## Hard constraints
- **Python backend (`backend/`) is OUT OF SCOPE.** Never read it, never reference it, never compare to it. It's not part of this app's story.
- Ground every lesson in files that exist in this folder. No toy examples.
- ~20–25 min per lesson. Beginner at PHP and web-request internals, despite CS coursework.

## Course structure
Single lesson (2026-09-14, user asked for "one very long lesson with everything"): lessons/0001-every-line-complete-guide.html — parts: machine/lifecycle → config → helpers → partials → schema → auth → CRUD → dashboard → seed/router → security map → viva drill (14 Q&A) → final exam (12 mixed Q). References: file-map (role table doubles as contents), php-glossary.

## Working notes
- 2026-09-14: Rebuilt workspace — first mission (databases/scalability) rejected; user redirected to "teach me THIS project". Deleted old files, rewrote MISSION/RESOURCES.
- Oddity to mention in a later lesson: `config.php` defines `APP_URL` but nothing uses it; `router.php` is only for `php -S`. Week maths, dashboard aggregates and validation were split out of `helpers.php` into `weeks.php`, `stats.php` and `validation.php` *after* the course was written, so `reference/file-map.html` lists them as not-yet-taught — that split is a good L9 discussion about file organisation.
- The app is genuinely well-built (PDO, CSRF, escaping, session_regenerate_id) — teaching it line-by-line doubles as security-literacy training. Use that.
