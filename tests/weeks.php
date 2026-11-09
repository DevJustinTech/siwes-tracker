<?php
/** Run directly: php tests/weeks.php. No database or session required. */
declare(strict_types=1);
require_once __DIR__ . '/../api/lib/weeks.php';

$failures = 0;
$checks = 0;
function check_week(string $name, int $expected, int $actual): void
{
    global $failures, $checks;
    $checks++;
    if ($expected !== $actual) {
        $failures++;
        echo "FAIL $name: expected $expected, got $actual\n";
    } else {
        echo "PASS $name\n";
    }
}

$cases = [
    ['first day', '2026-07-06', '2026-07-06', 1],
    ['last day of week one', '2026-07-06', '2026-07-12', 1],
    ['first day of week two', '2026-07-06', '2026-07-13', 2],
    ['last day of week two', '2026-07-06', '2026-07-19', 2],
    ['first day of week three', '2026-07-06', '2026-07-20', 3],
    ['five-week range', '2026-07-06', '2026-08-07', 5],
    ['before first day', '2026-07-06', '2026-07-05', 1],
    ['no history', null, '2026-07-06', 1],
    ['leap day crossing', '2024-02-26', '2024-03-04', 2],
    ['year crossing', '2025-12-29', '2026-01-05', 2],
];
foreach ($cases as [$name, $first, $date, $expected]) {
    check_week($name, $expected, siwes_week_number($first, $date));
}
check_week('empty span', 0, siwes_week_span(null, null));
check_week('single-day span', 1, siwes_week_span('2026-07-06', '2026-07-06'));
check_week('five-week span', 5, siwes_week_span('2026-07-06', '2026-08-07'));

$originalTimezone = date_default_timezone_get();
try {
    foreach (['Africa/Lagos', 'America/New_York'] as $timezone) {
        date_default_timezone_set($timezone);
        check_week("spring clock change in $timezone", 2, siwes_week_number('2026-03-02', '2026-03-09'));
        check_week("autumn clock change in $timezone", 2, siwes_week_number('2026-10-26', '2026-11-02'));
    }
} finally {
    date_default_timezone_set($originalTimezone);
}
printf("\n%d passed, %d failed\n", $checks - $failures, $failures);
exit($failures > 0 ? 1 : 0);
