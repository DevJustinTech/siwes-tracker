<?php
require_once __DIR__ . '/lib/helpers.php';
boot();

// Already logged in? Straight to the dashboard.
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM students WHERE email = ?');
        $stmt->execute([$email]);
        $student = $stmt->fetch();

        if ($student && password_verify($password, $student['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION = [
                'student_id'   => (int)$student['id'],
                'student_name' => $student['full_name'],
            ];
            redirect('dashboard.php');
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log in · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
</head>
<body class="auth-body">
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="auth-logo"><?= icon('cap') ?></span>
            <span><?= e(APP_NAME) ?></span>
        </div>

        <h1>Welcome back</h1>
        <p class="auth-sub">Log in to keep your log book up to date.</p>

        <?php if ($error): ?>
            <div class="alert alert--error" role="alert"><?= icon('alert') ?> <?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($m = flash('success')): ?>
            <div class="alert alert--success" role="status"><?= icon('check') ?> <?= e($m) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="you@school.edu.ng" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" placeholder="••••••••"
                           autocomplete="current-password" required>
                    <button type="button" class="icon-btn input-trailing" data-toggle-password="password"
                            aria-label="Show password"><?= icon('eye') ?></button>
                </div>
            </div>
            <button type="submit" class="btn btn--primary btn--block">Log in</button>
        </form>

        <p class="auth-alt">New here? <a href="register.php">Create an account</a></p>
    </div>
</main>
<script src="assets/js/app.js"></script>
</body>
</html>
