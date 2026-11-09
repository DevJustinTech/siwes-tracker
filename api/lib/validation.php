<?php
/** Form rules. Field checks are pure; uniqueness checks receive the connection explicitly. */
declare(strict_types=1);

const HOURS_MIN = 0.5;
const HOURS_MAX = 24;
const HOURS_STEP = 0.5;
const PASSWORD_MIN_LEN = 8;

/** Input is already normalized by the page; returns field-name => message. */
function validate_log_fields(string $date, float $hours, string $workDone): array
{
    $errors = [];
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('UTC'));
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        $errors['entry_date'] = 'Pick a valid date.';
    }
    if ($hours < HOURS_MIN || $hours > HOURS_MAX) {
        $errors['hours_worked'] = 'Hours must be between ' . HOURS_MIN . ' and ' . HOURS_MAX . '.';
    }
    if ($workDone === '') $errors['work_done'] = 'Describe what you did this day.';
    return $errors;
}

function validate_log_entry(PDO $pdo, int $studentId, int $excludeId, string $date, float $hours, string $workDone): array
{
    $errors = validate_log_fields($date, $hours, $workDone);
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM log_entries WHERE student_id = ? AND entry_date = ? AND id <> ?');
        $stmt->execute([$studentId, $date, $excludeId]);
        if ($stmt->fetch()) $errors['entry_date'] = 'You already logged this day — edit that entry instead.';
    }
    return $errors;
}

/** Registration and profile keep their existing normalization and error keys. */
function validate_registration_fields(string $matric, string $fullName, string $email, string $password, string $confirm): array
{
    $errors = [];
    if ($matric === '') $errors['matric_no'] = 'Matric number is required.';
    if ($fullName === '') $errors['full_name'] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (strlen($password) < PASSWORD_MIN_LEN) $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LEN . ' characters.';
    if ($password !== $confirm) $errors['password_confirm'] = 'Passwords do not match.';
    return $errors;
}

function validate_registration(PDO $pdo, string $matric, string $fullName, string $email, string $password, string $confirm): array
{
    $errors = validate_registration_fields($matric, $fullName, $email, $password, $confirm);
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT email, matric_no FROM students WHERE email = ? OR matric_no = ?');
        $stmt->execute([$email, $matric]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (strcasecmp($row['email'], $email) === 0) $errors['email'] = 'This email is already registered.';
            if (strcasecmp($row['matric_no'], $matric) === 0) $errors['matric_no'] = 'This matric number is already registered.';
        }
    }
    return $errors;
}

function validate_password_change(string $current, string $new, string $confirm, string $currentHash): array
{
    $errors = [];
    if (!password_verify($current, $currentHash)) $errors['current_password'] = 'Current password is incorrect.';
    if (strlen($new) < PASSWORD_MIN_LEN) $errors['new_password'] = 'New password must be at least ' . PASSWORD_MIN_LEN . ' characters.';
    if ($new !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
    return $errors;
}
