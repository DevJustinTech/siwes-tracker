<?php
/**
 * SIWES Tracker — helpers
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials/icons.php';
require_once __DIR__ . '/weeks.php';
require_once __DIR__ . '/stats.php';
require_once __DIR__ . '/validation.php';

/** Build PDO connection details from DATABASE_URL or the discrete DB_* values. */
function db_connection_details(): array
{
    if (DB_URL !== '') {
        $parts = parse_url(DB_URL);
        if ($parts === false || !isset($parts['host'])) {
            throw new RuntimeException('DATABASE_URL is not a valid PostgreSQL connection string.');
        }
        $host = $parts['host'];
        $dsn  = 'pgsql:host=' . $host
              . ';port=' . ($parts['port'] ?? 5432)
              . ';dbname=' . ltrim($parts['path'] ?? '/neondb', '/')
              . ';sslmode=' . DB_SSLMODE;

        // Neon's pooler requires the endpoint ID when the client's libpq
        // predates SNI support (the PHP build on Vercel does).
        if (str_ends_with($host, '.neon.tech')) {
            $endpointId = explode('.', $host)[0];
            $dsn .= ';options=endpoint=' . $endpointId;
        }

        return [
            'dsn'  => $dsn,
            'user' => urldecode($parts['user'] ?? ''),
            'pass' => urldecode($parts['pass'] ?? ''),
        ];
    }
    return [
        'dsn'  => 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=' . DB_SSLMODE,
        'user' => DB_USER,
        'pass' => DB_PASS,
    ];
}

/** PDO connection (lazy, shared) */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $connection = db_connection_details();
        $pdo = new PDO($connection['dsn'], $connection['user'], $connection['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/**
 * Sessions live in Postgres because Vercel lambdas are ephemeral: a file-based
 * session would disappear between requests. The table is created by
 * database.pg.sql.
 */
final class DbSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $stmt = db()->prepare('SELECT data FROM sessions WHERE id = ? AND last_activity >= ?');
        $stmt->execute([$id, time() - (int)ini_get('session.gc_maxlifetime')]);
        $data = $stmt->fetchColumn();
        return $data === false ? '' : (string)$data;
    }

    public function write(string $id, string $data): bool
    {
        $stmt = db()->prepare(
            'INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)
             ON CONFLICT (id) DO UPDATE
             SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity'
        );
        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        $stmt = db()->prepare('DELETE FROM sessions WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = db()->prepare('DELETE FROM sessions WHERE last_activity < ?');
        $stmt->execute([time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}

/** Escape for HTML output */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Redirect and stop */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Is the current visitor logged in? */
function is_logged_in(): bool
{
    return isset($_SESSION['student_id']);
}

/** Require login — call at the top of protected pages */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        redirect('login.php');
    }
}

/** Currently logged-in student row (cached) */
function current_student(): ?array
{
    static $student = null;
    static $loaded  = false;
    if (!$loaded && is_logged_in()) {
        $stmt = db()->prepare('SELECT * FROM students WHERE id = ?');
        $stmt->execute([$_SESSION['student_id']]);
        $student = $stmt->fetch() ?: null;
        $loaded  = true;
    }
    return $student;
}

/** One-shot flash messages */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash_' . $key] = $message;
        return null;
    }
    $value = $_SESSION['flash_' . $key] ?? null;
    unset($_SESSION['flash_' . $key]);
    return $value;
}

/** CSRF token (create + return) */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** CSRF hidden input for forms */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verify CSRF on POST requests — call before handling any POST */
function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Page expired — please go back and try again.');
    }
}

/** Friendly "Wed 09 Sep 2026" day label */
function day_label(string $date): string
{
    return date('D d M Y', strtotime($date));
}

/** Explicit request startup; reusable includes do not start sessions. */
function boot(): void
{
    date_default_timezone_set('Africa/Lagos');
    if (session_status() === PHP_SESSION_NONE) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $https,
        ]);
        session_set_save_handler(new DbSessionHandler(), true);
        session_start();
    }
}
