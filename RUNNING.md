# Running the SIWES Tracker

## The one tree

There is exactly **one** copy of this app, and it is this working tree:

```
C:\Users\HP\Desktop\my projects\siwes tracker     ← the repo, and the source of truth
```

Apache reaches it through a **junction**:

```
C:\xampp\htdocs\siwes tracker  ──junction──▶  the repo
```

So editing a file here changes what the browser serves, immediately. Never copy files into `htdocs` by hand — a stale second copy is exactly the bug that junction now prevents.

An old pre-junction snapshot of the app was parked outside the web root when the junction was made (2026-10-02):

```
C:\xampp\siwes-tracker-fork-backup-20261002
```

It is **older** than this tree and has an incompatible `siwes_week_number()` signature — `helpers.php` there takes `array $student` where `weeks.php` here takes `?string $firstDay`. It is parked outside `htdocs` on purpose, so it is not reachable over HTTP. Delete it once you have no reason to want it.

Check the junction is still intact:

```powershell
(Get-Item "C:\xampp\htdocs\siwes tracker").LinkType   # expect: Junction
```

## Start

PHP and MySQL are **not on your PATH**. Use the full paths.

```powershell
& C:\xampp\apache_start.bat      # Apache, port 80
& C:\xampp\mysql_start.bat       # MySQL,  port 3306
```

Or the XAMPP control panel: `C:\xampp\xampp-control.exe`.

Then open <http://localhost/siwes%20tracker/> — `/` resolves to `dashboard.php`, which redirects you to `login.php` if you are not signed in.

Stop:

```powershell
& C:\xampp\apache_stop.bat
& C:\xampp\mysql_stop.bat
```

## Verify it is actually running

**One `200` on the login page does not mean the app works.** A stale copy, a dead database, or a fatal error can all still serve HTML. Check the routes that exercise the real paths:

```powershell
$routes = @{ '/' = 302; '/login.php' = 200; '/register.php' = 200
             '/dashboard.php' = 302; '/logs.php' = 302
             '/.git/config' = 403; '/database.sql' = 403 }
foreach ($r in $routes.GetEnumerator() | Sort-Object Name) {
    $got = & curl.exe -s -o NUL -w "%{http_code}" "http://localhost/siwes%20tracker$($r.Name)"
    "{0,-16} expect {1} got {2} {3}" -f $r.Name, $r.Value, $got, $(if ($got -eq $r.Value) { 'OK' } else { 'MISMATCH' })
}
```

The `302`s are correct, not a fault: those pages require a session. The `403`s are `.htaccess` doing its job — `.git` and `database.sql` must never be served.

The database must also be reachable. `login.php` renders without it, so check it directly:

```powershell
& C:\xampp\mysql\bin\mysql.exe -u root -e "USE siwes_tracker; SHOW TABLES;"
```

Expect `log_entries` and `students`. No output means MySQL is down or the schema was never imported.

## First-time database setup

```powershell
& C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS siwes_tracker;"
Get-Content database.sql -Raw | & C:\xampp\mysql\bin\mysql.exe -u root siwes_tracker
```

Credentials live in `config.php`; the defaults are XAMPP's (`127.0.0.1`, `root`, empty password).

Demo data: <http://localhost/siwes%20tracker/seed.php>, then sign in as `demo@siwes.test` / `demo1234`. `seed.php` refuses to run twice and is meant to be deleted afterwards.

## Without Apache

`router.php` exists for PHP's built-in server. It dispatches `/` and maps `/dashboard` → `dashboard.php`, so behaviour matches Apache.

```powershell
& C:\xampp\php\php.exe -S localhost:8000 router.php
```

MySQL must still be running. `router.php` is unused under Apache — Apache does that routing itself.

## Checks

```powershell
& .\check.ps1
```

Lints every `.php` file and runs the test suite. See `check.ps1` for what it covers and how to include the MySQL integration tests.