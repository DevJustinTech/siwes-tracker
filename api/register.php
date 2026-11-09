<?php
require_once __DIR__ . '/lib/helpers.php';
boot();

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$old    = fn(string $k) => e($_POST[$k] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $matric      = strtoupper(trim($_POST['matric_no'] ?? ''));
    $fullName    = trim($_POST['full_name'] ?? '');
    $email       = strtolower(trim($_POST['email'] ?? ''));
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['password_confirm'] ?? '';
    $institution = trim($_POST['institution'] ?? '');
    $course      = trim($_POST['course_of_study'] ?? '');

    $errors = validate_registration_fields($matric, $fullName, $email, $password, $confirm);
    if (!$errors) {
        $errors = validate_registration(db(), $matric, $fullName, $email, $password, $confirm);
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO students (matric_no, full_name, email, password_hash, institution, course_of_study)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$matric, $fullName, $email, password_hash($password, PASSWORD_DEFAULT), $institution, $course]);

        flash('success', 'Account created — you can now log in.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create account · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
<main class="auth-wrap auth-wrap--wide">
    <div class="auth-card auth-card--wide">
        <div class="auth-brand">
            <span class="auth-logo"><?= icon('cap') ?></span>
            <span><?= e(APP_NAME) ?></span>
        </div>

        <h1>Create your account</h1>
        <p class="auth-sub">One account, one log book — your entries stay yours.</p>

        <?php if ($errors): ?>
            <div class="alert alert--error" role="alert"><?= icon('alert') ?> Please fix the highlighted fields below.</div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="matric_no">Matric number <span class="req">*</span></label>
                    <input type="text" id="matric_no" name="matric_no" value="<?= $old('matric_no') ?>"
                           placeholder="e.g. CSC/2021/0123" required>
                    <?php if (isset($errors['matric_no'])): ?><p class="field-error"><?= e($errors['matric_no']) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label for="full_name">Full name <span class="req">*</span></label>
                    <input type="text" id="full_name" name="full_name" value="<?= $old('full_name') ?>"
                           placeholder="Ada Obi" required>
                    <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="field">
                <label for="email">Email address <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="<?= $old('email') ?>"
                       placeholder="you@school.edu.ng" required>
                <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="institution">Institution</label>
                    <input type="text" id="institution" name="institution" value="<?= $old('institution') ?>"
                           placeholder="University of …">
                </div>
                <div class="field">
                    <label for="course_of_study">Course of study</label>
                    <input type="text" id="course_of_study" name="course_of_study" value="<?= $old('course_of_study') ?>"
                           placeholder="e.g. Computer Science">
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="password">Password <span class="req">*</span></label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder="At least <?= PASSWORD_MIN_LEN ?> characters" minlength="<?= PASSWORD_MIN_LEN ?>"
                               autocomplete="new-password" required>
                        <button type="button" class="icon-btn input-trailing" data-toggle-password="password"
                                aria-label="Show password"><?= icon('eye') ?></button>
                    </div>
                    <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label for="password_confirm">Confirm password <span class="req">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Repeat password"
                           autocomplete="new-password" required>
                    <?php if (isset($errors['password_confirm'])): ?><p class="field-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn--primary btn--block">Create account</button>
        </form>

        <p class="auth-alt">Already registered? <a href="login.php">Log in</a></p>
    </div>
</main>
<script src="assets/js/app.js"></script>
</body>
</html>
