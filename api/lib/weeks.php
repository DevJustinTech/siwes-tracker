<?php
/** SIWES weeks are seven-day windows starting at the first logged day. */
declare(strict_types=1);

/** Dates are valid Y-m-d calendar dates; no history or an earlier date means week 1. */
function siwes_week_number(?string $firstDay, string $date): int
{
    if ($firstDay === null) return 1;
    $timezone = new DateTimeZone('UTC');
    $first = new DateTimeImmutable($firstDay, $timezone);
    $day = new DateTimeImmutable($date, $timezone);
    $days = (int)$first->diff($day)->format('%r%a');
    return $days < 0 ? 1 : intdiv($days, 7) + 1;
}

/** An empty log book spans zero weeks. */
function siwes_week_span(?string $firstDay, ?string $lastDay): int
{
    return $firstDay === null || $lastDay === null ? 0 : siwes_week_number($firstDay, $lastDay);
}

/** Fetch once before rendering entries, never from inside a row loop. */
function siwes_first_entry_date(PDO $pdo, int $studentId): ?string
{
    $stmt = $pdo->prepare('SELECT MIN(entry_date) FROM log_entries WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $date = $stmt->fetchColumn();
    return $date === null || $date === false ? null : (string)$date;
}
