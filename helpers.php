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

/** PDO connection (lazy, shared) */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
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
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}
