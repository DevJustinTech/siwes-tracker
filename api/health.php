<?php
/** Liveness check used after a deploy: proves the app can reach Neon. */
declare(strict_types=1);
require_once __DIR__ . '/lib/helpers.php';
security_headers();

header('Content-Type: application/json');

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM students');
    $stmt->execute();
    $students = (int)$stmt->fetchColumn();
    echo json_encode([
        'status'   => 'ok',
        'database' => 'connected',
        'students' => $students,
    ]);
} catch (Throwable $ex) {
    http_response_code(503);
    echo json_encode([
        'status'   => 'error',
        'database' => 'unavailable',
    ]);
}
