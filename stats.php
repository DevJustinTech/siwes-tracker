<?php
/** Dashboard statistics: all-time totals, last ten logged days, latest five entries. */
declare(strict_types=1);
require_once __DIR__ . '/weeks.php';

/** Returns every key even when empty; callers retain responsibility for HTML escaping. */
function student_stats(PDO $pdo, int $studentId): array
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS entries, COALESCE(SUM(hours_worked), 0) AS hours,
                MIN(entry_date) AS first_day, MAX(entry_date) AS last_day
         FROM log_entries WHERE student_id = ?'
    );
    $stmt->execute([$studentId]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    $entryCount = (int)$totals['entries'];
    $hours = (float)$totals['hours'];

    $stmt = $pdo->prepare(
        'SELECT * FROM log_entries WHERE student_id = ? ORDER BY entry_date DESC LIMIT 10'
    );
    $stmt->execute([$studentId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $chartRows = array_reverse($rows);

    return [
        'entries' => $entryCount,
        'hours' => $hours,
        'avg_hours' => $entryCount > 0 ? $hours / $entryCount : 0.0,
        'first_day' => $totals['first_day'],
        'last_day' => $totals['last_day'],
        'week_span' => siwes_week_span($totals['first_day'], $totals['last_day']),
        'chart_labels' => array_map(fn($r) => date('d M', strtotime($r['entry_date'])), $chartRows),
        'chart_data' => array_map(fn($r) => (float)$r['hours_worked'], $chartRows),
        'recent' => array_slice($rows, 0, 5),
    ];
}
