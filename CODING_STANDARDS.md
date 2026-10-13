# Coding standards

The rules a change to this repo has to hold to. Read at review time.

## Enforced, not just stated

`check.ps1` runs these four and fails the commit. They are mechanical, so they are a check rather than a rule for a reviewer to eyeball:

| Rule | Check |
|---|---|
| No `->query()`; every statement is `prepare()` with bound parameters | `no ->query()` |
| No `mysqli_*` — PDO only | `no mysqli_*` |
| Every page that reads `$_POST` calls `csrf_verify()` | `POST handlers verify CSRF` |
| Every page that reads `$_POST` renders `csrf_field()` | `POST forms carry csrf_field()` |

If a rule needs to change, change it in `check.ps1` — that is the single source of truth. Don't restate it here beyond this table.

## Conventions a reviewer has to judge

**Where code lives.** Shared machinery has one home. Cross-page functions go in `helpers.php`; a domain's own maths goes in its own file (`weeks.php`, `stats.php`, `validation.php`), which `helpers.php` then requires. A page holds its own request handling and its own HTML. If a page grows a reusable function, it moves down one of those, it does not stay local.

**Page shape.** `require_once helpers.php`, then `boot()`, then PHP, then HTML — no output before `boot()`, because `boot()` owns the session and the `Content-Type` header. Files that are pure HTML fragments (`partials/layout-bottom.php`) correctly start with markup instead.

**Strict types.** Logic files (`config.php`, `helpers.php`, `weeks.php`, `stats.php`, `validation.php`, `seed.php`, `tests/*`) carry `declare(strict_types=1)`. Pages and partials do not; that is the existing convention, not an oversight to fix in passing.

**Escaping.** Every dynamic value reaches the browser through `e()`. The exception is a value `e()` has already escaped, like `csrf_field()`'s own output.

**Write path.** Validate → act → `flash()` → `redirect()`. A successful POST never renders its own response; it redirects, so a refresh cannot repeat the write.

**CRUD shape.** `log_form.php` serves create and update, switching on `?id=`. Deletes go through a confirmation page (`log_delete.php`), never a bare link.

**Vocabulary.** Use the domain terms in `CONTEXT.md` — *daily entry*, *first logged day*, *SIWES week*, *week span*, *logged day*. Not the synonyms it rules out.

## Before you finish a change

```powershell
& .\check.ps1          # lint, standards, tests
& .\check.ps1 -Smoke   # plus the live app, if Apache is up
```

Add a test for new logic in `tests/run.php`; a week-numbering or date-validation change belongs in `tests/weeks.php` too. If a change touches what Apache serves, remember the app is one tree — the junction in `RUNNING.md` — and verify through `check.ps1 -Smoke`, not by reading the diff.