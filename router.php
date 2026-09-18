<?php
/**
 * Dev server router — SIWES Tracker
 * Run:  php -S localhost:8000 router.php
 * (Requires PHP with pdo_mysql, and MySQL running — e.g. XAMPP's MySQL module.)
 */

require_once __DIR__ . '/helpers.php';

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = __DIR__ . $uri;

// 1. Serve existing files (css, js, images) directly
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// 2. Root -> dashboard for logged-in users, login otherwise
if ($uri === '/' || $uri === '') {
    require __DIR__ . (is_logged_in() ? '/dashboard.php' : '/login.php');
    return true;
}

// 3. Pretty paths: /dashboard -> dashboard.php
$candidate = __DIR__ . rtrim($uri, '/') . '.php';
if (file_exists($candidate)) {
    require $candidate;
    return true;
}

http_response_code(404);
echo '<h1>404</h1><p>Page not found. <a href="/">Go home</a></p>';
return true;
