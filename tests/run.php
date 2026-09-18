<?php
/** Plain PHP tests; --mysql opts into the guarded, disposable database checks. */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$failures = 0;
$checks = 0;
$skips = 0;
function same($expected, $actual): void
{
    if ($expected !== $actual) throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}
function test(string $name, callable $check): void
{
    global $checks, $failures;
    $checks++;
    try { $check(); echo "PASS $name\n"; }
    catch (Throwable $e) { $failures++; echo "FAIL $name: {$e->getMessage()}\n"; }
}

$timezone = date_default_timezone_get();
require_once __DIR__ . '/../helpers.php';
test('includes do not start session or change timezone', function () use ($timezone) {
    same(PHP_SESSION_NONE, session_status());
    same($timezone, date_default_timezone_get());
});

foreach ([
    ['2026-07-06', '2026-07-06', 1], ['2026-07-06', '2026-07-12', 1],
    ['2026-07-06', '2026-07-13', 2], ['2026-07-06', '2026-08-07', 5],
    ['2026-07-06', '2026-07-05', 1], [null, '2026-07-06', 1],
    ['2024-02-26', '2024-03-04', 2], ['2025-12-29', '2026-01-05', 2],
] as [$first, $date, $expected]) {
    test("week $first / $date", fn() => same($expected, siwes_week_number($first, $date)));
}
test('empty week span', fn() => same(0, siwes_week_span(null, null)));
test('timezone independent week', function () {
    $before = date_default_timezone_get();
    try {
        date_default_timezone_set('America/New_York');
        same(2, siwes_week_number('2026-03-02', '2026-03-09'));
    } finally { date_default_timezone_set($before); }
});
foreach (['2026-02-30', '2026-02-29', '2026-13-01', '2026-00-10', '2026-2-01', '', 'not a date'] as $date) {
    test("invalid calendar date [$date]", fn() => same('Pick a valid date.', validate_log_fields($date, 8.0, 'Work')['entry_date'] ?? null));
}
foreach (['2024-02-29', '2026-02-28', '2026-12-31'] as $date) {
    test("valid calendar date $date", fn() => same([], validate_log_fields($date, 8.0, 'Work')));
}
foreach ([0.5, 24.0, 1.2] as $hours) {
    test("existing accepted hours $hours", fn() => same([], validate_log_fields('2026-07-06', $hours, 'Work')));
}
foreach ([0.0, 0.4, 24.1] as $hours) {
    test("rejected hours $hours", fn() => same('Hours must be between 0.5 and 24.', validate_log_fields('2026-07-06', $hours, 'Work')['hours_worked'] ?? null));
}
test('empty work description', fn() => same('Describe what you did this day.', validate_log_fields('2026-07-06', 8, '')['work_done'] ?? null));
test('registration all field errors', fn() => same(['matric_no', 'full_name', 'email', 'password', 'password_confirm'], array_keys(validate_registration_fields('', '', '', 'short', 'other'))));
test('no invented matric format', fn() => same([], validate_registration_fields('OTHER-FORMAT', 'Student', 'student@example.test', '12345678', '12345678')));
test('registration minimum password length', fn() => same('Password must be at least 8 characters.', validate_registration_fields('M1', 'Student', 'student@example.test', '1234567', '1234567')['password'] ?? null));
$hash = password_hash('original-password', PASSWORD_DEFAULT);
test('valid password change', fn() => same([], validate_password_change('original-password', '12345678', '12345678', $hash)));
test('password-change errors retain keys', fn() => same(['current_password', 'new_password', 'confirm_password'], array_keys(validate_password_change('wrong', 'short', 'different', $hash))));

if (in_array('--mysql', $argv, true)) {
    require __DIR__ . '/mysql.php';
    test('MySQL integration setup and behaviour', fn() => run_mysql_tests());
} else {
    $skips++;
    echo "SKIP MySQL integration (use --mysql and SIWES_TEST_DB=siwes_tracker_test)\n";
}
printf("\n%d passed, %d failed, %d skipped\n", $checks - $failures, $failures, $skips);
exit($failures > 0 ? 1 : 0);
