<?php
/**
 * Seeds a demo student + a few weeks of daily log entries so the dashboard
 * isn't empty. Run once, then DELETE this file (it's for demos only):
 *   http://localhost/siwes%20tracker/seed.php
 */

declare(strict_types=1);
require_once __DIR__ . '/helpers.php';
boot();

try {
    $pdo = db();

    // Wipe demo data and re-create
    $pdo->exec('DELETE FROM log_entries');
    $pdo->exec('DELETE FROM students');

    $stmt = $pdo->prepare(
        'INSERT INTO students (matric_no, full_name, email, password_hash, institution, course_of_study, placement_org, industry_supervisor)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        'CSC/2021/0123',
        'Ada Obi',
        'demo@siwes.test',
        password_hash('demo1234', PASSWORD_DEFAULT),
        'University of Nigeria, Nsukka',
        'Computer Science',
        'TechHub Solutions Ltd, Enugu',
        'Mr. Chinedu Eze',
    ]);
    $studentId = (int)$pdo->lastInsertId();

    // Four weeks of working days (Mon–Fri). Each day has its own note.
    $weeks = [
        [
            ['2026-07-06', 8.0, "Set up the dev environment; cloned the company's inventory project and studied the codebase.", 'Getting used to the company workflow took a while.'],
            ['2026-07-07', 8.5, "Attended onboarding sessions and met the team. Walked through the product roadmap with Mr. Eze."],
            ['2026-07-08', 7.5, "Read through the existing database schema and sketched notes on how the modules connect."],
            ['2026-07-09', 8.0, "Practiced with the internal tools; set up Git properly and learned the team's branching workflow."],
            ['2026-07-10', 6.0, "Pair-read the auth module with a senior dev and took notes for next week's tasks."],
        ],
        [
            ['2026-07-13', 8.0, "Built the first HTML/CSS pages for the inventory dashboard.", null],
            ['2026-07-14', 9.0, "Styled the products table and status cards; matched the company design guide."],
            ['2026-07-15', 8.5, "Learned Git rebasing and opened my first pull request — approved on the second review."],
            ['2026-07-16', 7.0, "Fixed feedback on the PR: cleanup on class names and spacing."],
            ['2026-07-17', 8.0, "Wired the dashboard pages to mock JSON data so the team could review flow before the API is ready.", null],
        ],
        [
            ['2026-07-20', 8.0, "Converted the dashboard to a responsive layout; tested on phones.", null],
            ['2026-07-21', 9.5, "Hunted down cross-browser bugs in the flexbox layout; documented fixes in the wiki."],
            ['2026-07-22', 8.0, "Paired with a senior dev on form validation; rewrote the client-side checks."],
            ['2026-07-23', 7.5, "Started PHP backend tasks: set up PDO connection helpers and a test query page.", null],
            ['2026-07-24', 8.5, "Built the first CRUD endpoint for the products module using prepared statements."],
        ],
        [
            ['2026-07-27', 8.0, "Extended the products API: search, pagination, and sorting parameters.", 'Understanding how the existing schema indexes help performance.'],
            ['2026-07-28', 9.0, "Wrote unit tests for the validation helpers; found and fixed an off-by-one bug."],
            ['2026-07-29', 8.5, "Debugged a session timeout issue; documented the fix and the why in the team wiki."],
            ['2026-07-30', 7.0, "Attended the client requirements meeting; took minutes and translated them into tickets."],
            ['2026-07-31', 8.0, "Implemented CSV export for the weekly reports view.", null],
        ],
        [
            ['2026-08-03', 9.0, "Code review session with Mr. Eze on naming conventions; refactored three endpoints.", null],
            ['2026-08-04', 8.0, "Worked on bug backlog: fixed date timezone bugs across the reports module."],
            ['2026-08-05', 7.5, "Fixed an off-by-one in the pagination logic after reproducing user complaints."],
            ['2026-08-06', 8.5, "Built a small internal tool for QA to seed test data faster."],
            ['2026-08-07', 6.5, "Prepared the sprint demo slides; rehearsed with the team."],
        ],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO log_entries (student_id, entry_date, hours_worked, work_done, challenges)
         VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($weeks as $week) {
        foreach ($week as $day) {
            [$date, $hours, $work, $challenges] = array_pad($day, 4, null);
            $stmt->execute([$studentId, $date, $hours, $work, $challenges]);
        }
    }

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Seeded</title></head>';
    echo '<body style="font-family:sans-serif;max-width:480px;margin:80px auto;text-align:center">';
    echo '<h2>✅ Demo data created</h2>';
    echo '<p>Log in with:<br><strong>demo@siwes.test</strong> / <strong>demo1234</strong></p>';
    echo '<p><a href="login.php">Go to login →</a></p>';
    echo '<p style="color:#b91c1c"><small>Now delete seed.php from the project folder.</small></p>';
    echo '</body></html>';
} catch (Throwable $ex) {
    http_response_code(500);
    echo 'Seeding failed: ' . htmlspecialchars($ex->getMessage());
}
