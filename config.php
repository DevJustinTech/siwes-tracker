<?php
/**
 * SIWES Tracker — configuration
 * Adjust the DB credentials below to match your XAMPP setup.
 */

declare(strict_types=1);

// ---- Database -------------------------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'siwes_tracker');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty password
define('DB_PORT', 3306);

// ---- App ------------------------------------------------------------------
define('APP_NAME', 'SIWES Tracker');

// Request startup is explicit: pages call boot() from helpers.php.
