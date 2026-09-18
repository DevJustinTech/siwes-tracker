<?php
/** Integration tests use real MySQL semantics and only an empty, explicitly selected test database. */
declare(strict_types=1);

function run_mysql_tests(): void
{
    $database = getenv('SIWES_TEST_DB');
    if ($database !== 'siwes_tracker_test' || strcasecmp($database, DB_NAME) === 0) {
        throw new RuntimeException('Set SIWES_TEST_DB=siwes_tracker_test; application database is never allowed.');
    }
    $host = getenv('SIWES_TEST_HOST') ?: DB_HOST;
    $port = getenv('SIWES_TEST_PORT') ?: DB_PORT;
    $user = getenv('SIWES_TEST_USER') ?: DB_USER;
    $password = getenv('SIWES_TEST_PASS');
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $password === false ? DB_PASS : $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    if ((int)$pdo->query("SELECT GET_LOCK('siwes_tracker_test_runner', 0)")->fetchColumn() !== 1) {
        throw new RuntimeException('Another test run holds the database lock.');
    }
    $created = [];
    try {
        $pdo->exec('CREATE DATABASE IF NOT EXISTS siwes_tracker_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE siwes_tracker_test');
        same('siwes_tracker_test', $pdo->query('SELECT DATABASE()')->fetchColumn());
        if ($pdo->query('SHOW TABLES')->fetchAll()) {
            throw new RuntimeException('Test database is not empty. Refusing to alter or clear existing tables.');
        }
        // Read the real table definitions, not CREATE DATABASE/USE or demo instructions.
        $schema = file_get_contents(__DIR__ . '/../database.sql');
        foreach (['students', 'log_entries'] as $table) {
            if (!preg_match('/CREATE TABLE IF NOT EXISTS ' . $table . ' \(.*?\) ENGINE=InnoDB;/s', $schema, $match)) {
                throw new RuntimeException("Cannot locate schema for $table");
            }
            $pdo->exec($match[0]);
            $created[] = $table;
        }
        $pdo->beginTransaction();
        $insertStudent = $pdo->prepare('INSERT INTO students (matric_no, full_name, email, password_hash) VALUES (?, ?, ?, ?)');
        $insertStudent->execute(['TEST/A', 'Fixture A', 'fixture-a@example.test', password_hash('fixture-password', PASSWORD_DEFAULT)]);
        $a = (int)$pdo->lastInsertId();
        $insertStudent->execute(['TEST/B', 'Fixture B', 'fixture-b@example.test', password_hash('fixture-password', PASSWORD_DEFAULT)]);
        $b = (int)$pdo->lastInsertId();
        $insertStudent->execute(['TEST/C', 'Empty Fixture', 'fixture-c@example.test', 'not-used']);
        $empty = (int)$pdo->lastInsertId();
        $insertEntry = $pdo->prepare('INSERT INTO log_entries (student_id, entry_date, hours_worked, work_done) VALUES (?, ?, ?, ?)');
        $firstId = 0;
        for ($day = 1; $day <= 12; $day++) {
            $insertEntry->execute([$a, sprintf('2026-07-%02d', $day), $day, "Work $day"]);
            if ($day === 1) $firstId = (int)$pdo->lastInsertId();
        }
        $insertEntry->execute([$b, '2026-06-01', 24, 'Other student']);

        test('stats preserve lifetime totals beyond ten rows', function () use ($pdo, $a) {
            $stats = student_stats($pdo, $a);
            same(12, $stats['entries']); same(78.0, $stats['hours']); same(6.5, $stats['avg_hours']);
            same(2, $stats['week_span']); same('2026-07-01', $stats['first_day']);
            same(['03 Jul', '04 Jul', '05 Jul', '06 Jul', '07 Jul', '08 Jul', '09 Jul', '10 Jul', '11 Jul', '12 Jul'], $stats['chart_labels']);
            same([3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0], $stats['chart_data']);
            same(['2026-07-12', '2026-07-11', '2026-07-10', '2026-07-09', '2026-07-08'], array_column($stats['recent'], 'entry_date'));
        });
        test('empty student defaults', function () use ($pdo, $empty) {
            $stats = student_stats($pdo, $empty);
            same(0, $stats['entries']); same(0.0, $stats['hours']); same(0.0, $stats['avg_hours']);
            same(0, $stats['week_span']); same([], $stats['chart_labels']); same([], $stats['recent']);
            same(null, siwes_first_entry_date($pdo, $empty));
        });
        test('first-date loader is student scoped', fn() => same('2026-07-01', siwes_first_entry_date($pdo, $a)));
        test('same-day duplicate gives friendly error', fn() => same('You already logged this day — edit that entry instead.', validate_log_entry($pdo, $a, 0, '2026-07-01', 8, 'Work')['entry_date'] ?? null));
        test('edit excludes itself', fn() => same([], validate_log_entry($pdo, $a, $firstId, '2026-07-01', 8, 'Edited')));
        test('other student can use same date', fn() => same([], validate_log_entry($pdo, $b, 0, '2026-07-01', 8, 'Work')));
        test('registration duplicate keys and messages', function () use ($pdo) {
            same([
                'email' => 'This email is already registered.',
                'matric_no' => 'This matric number is already registered.',
            ], validate_registration($pdo, 'TEST/A', 'Name', 'FIXTURE-A@EXAMPLE.TEST', '12345678', '12345678'));
        });
        test('registration unique values accepted', fn() => same([], validate_registration($pdo, 'ANY-FORMAT', 'Name', 'new@example.test', '12345678', '12345678')));
        test('database unique constraint remains active', function () use ($insertEntry, $a) {
            try { $insertEntry->execute([$a, '2026-07-01', 8, 'Duplicate']); }
            catch (PDOException $e) { same(1062, (int)$e->errorInfo[1]); return; }
            throw new RuntimeException('Duplicate unexpectedly inserted');
        });
        test('database foreign key remains active', function () use ($insertEntry) {
            try { $insertEntry->execute([2147483647, '2026-07-01', 8, 'Orphan']); }
            catch (PDOException $e) { same(1452, (int)$e->errorInfo[1]); return; }
            throw new RuntimeException('Orphan unexpectedly inserted');
        });
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
        // Only tables created by this invocation are removed; pre-existing tables are never touched.
        foreach (array_reverse($created) as $table) $pdo->exec("DROP TABLE `$table`");
        $pdo->query("SELECT RELEASE_LOCK('siwes_tracker_test_runner')");
    }
}
