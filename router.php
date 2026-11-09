<?php
/**
 * Dev server router — SIWES Tracker
 * Run:  php -S localhost:8000 router.php
 * Needs DATABASE_URL or the DB_* variables pointing at PostgreSQL.
 */

declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Serve existing static files (CSS, JS, images) directly.
if ($uri !== '/' && is_file(__DIR__ . $uri) && !str_ends_with($uri, '.php')) {
    return false;
}

$page = $uri === '/' ? 'dashboard.php' : ltrim($uri, '/');
if (!str_ends_with($page, '.php')) {
    $page .= '.php';
}

$publicPages = [
    'login.php', 'register.php', 'logout.php', 'dashboard.php',
    'logs.php', 'log_form.php', 'log_delete.php', 'profile.php', 'health.php',
];

if (in_array($page, $publicPages, true) && is_file(__DIR__ . '/api/' . $page)) {
    require __DIR__ . '/api/' . $page;
    return true;
}

http_response_code(404);
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Not found</title></head>'
   . '<body><h1>404</h1><p>Page not found. <a href="/">Go home</a></p></body></html>';
return true;
