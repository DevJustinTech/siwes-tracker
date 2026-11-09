<?php
/**
 * SIWES Tracker — configuration
 *
 * Neon supplies DATABASE_URL (a postgresql:// connection string) in
 * production. Local development can set the discrete DB_* variables instead.
 * No credentials belong in this file.
 */

declare(strict_types=1);

// ---- Database -------------------------------------------------------------
define('DB_URL', getenv('DATABASE_URL') ?: '');
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'siwes_tracker');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 5432));
define('DB_SSLMODE', getenv('DB_SSLMODE') ?: 'require');

// ---- App ------------------------------------------------------------------
define('APP_NAME', getenv('APP_NAME') ?: 'SIWES Tracker');

// Request startup is explicit: pages call boot() from helpers.php.
