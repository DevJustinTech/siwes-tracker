<?php
/**
 * Seeds a demo student + a few weeks of daily log entries so the dashboard
 * isn't empty. Run once from the command line, with DATABASE_URL set:
 *   DATABASE_URL=postgresql://... php scripts/seed.php
 */

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../api/lib/helpers.php';

try {
    $pdo = db();

    // Wipe demo data and re-create. TRUNCATE also resets the identity columns.
    $pdo->exec('TRUNCATE TABLE log_entries, students RESTART IDENTITY CASCADE');
    $pdo->exec('DELETE FROM sessions');

    $stmt = $pdo->prepare(
        'INSERT INTO students (matric_no, full_name, email, password_hash, institution, course_of_study, placement_org, industry_supervisor)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         RETURNING id'
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
    $studentId = (int)$stmt->fetchColumn();

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

    echo "Demo data created.\n";
    echo "Log in with: demo@siwes.test / demo1234\n";
} catch (Throwable $ex) {
    http_response_code(500);
    echo 'Seeding failed: ' . htmlspecialchars($ex->getMessage());
}
