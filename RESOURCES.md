# SIWES Tracker (core PHP) Resources

## Knowledge

- [Manual: PHP.net — basic syntax and tags](https://www.php.net/manual/en/language.basic-syntax.phptags.php)
  Authoritative on `<?php ... ?>` and `<?= ... ?>`. Use for: any "when does PHP run vs when is it just text" question.
- [Manual: PHP.net — `require_once` / `include`](https://www.php.net/manual/en/function.require-once.php)
  Use for: how partials and helpers get pulled into pages, and what happens to variables across the `require` boundary.
- [Manual: PHP.net — session handling](https://www.php.net/manual/en/features.sessions.php)
  Use for: `$_SESSION`, `session_start`, cookies, `session_regenerate_id`.
- [Manual: PHP.net — `password_hash` / `password_verify`](https://www.php.net/manual/en/function.password-hash.php)
  Use for: why login.php can check a password it never sees in plain text.
- [Manual: PHP.net — PDO](https://www.php.net/manual/en/book.pdo.php) + [`prepare()`](https://www.php.net/manual/en/pdo.prepare.php)
  Use for: every `db()->prepare(...)` line in the app.
- [Manual: PHP.net — `htmlspecialchars`](https://www.php.net/manual/en/function.htmlspecialchars.php)
  Use for: what `e()` actually changes about a string.
- [Manual: PHP.net — predefined variables](https://www.php.net/manual/en/language.variables.superglobals.php)
  Use for: `$_SERVER`, `$_GET`, `$_POST`, `$_SESSION` — the app reads all four.
- [Cheat sheet: OWASP — CSRF Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
  Use for: the threat `csrf_verify()` stops, and why `hash_equals` matters.
- [Book (free web edition): _PHP The Right Way_](https://phptherightway.com/)
  Broad best-practice context; the app's style (strict types, PDO exception mode, password_hash) matches its recommendations — useful for "is this app idiomatic?" questions.
- [Docs: MySQL 8.0 Manual — `CREATE TABLE`, constraints](https://dev.mysql.com/doc/refman/8.0/en/create-table.html)
  Use for: reading `database.sql` — PRIMARY KEY, UNIQUE, FOREIGN KEY, ON DELETE CASCADE.

## Wisdom (Communities)

- [r/PHPhelp](https://www.reddit.com/r/PHPhelp/)
  Beginner-friendly, PHP-specific. Use for: "why does this line behave like this" questions after trying the manual.
- [Stack Overflow — questions tagged [php] [mysql] [pdo]](https://stackoverflow.com/questions/tagged/php)
  Use for: error messages — paste the exact text; these patterns are heavily documented there.
- [PHP General mailing list](https://www.php.net/support.php)
  Use for: deeper language questions once comfortable.

## Gaps

- No resource yet for `php -S` built-in server / router-file semantics beyond the manual page — cover when we reach `router.php` (lesson on the dev-server flow).
