<?php
require_once __DIR__ . '/lib/helpers.php';
boot();
require_login();

$student = current_student();
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'details') {
        $fullName    = trim($_POST['full_name'] ?? '');
        $institution = trim($_POST['institution'] ?? '');
        $course      = trim($_POST['course_of_study'] ?? '');
        $org         = trim($_POST['placement_org'] ?? '');
        $supervisor  = trim($_POST['industry_supervisor'] ?? '');

        if ($fullName === '') {
            $errors['full_name'] = 'Full name is required.';
        } else {
            $stmt = db()->prepare(
                'UPDATE students SET full_name = ?, institution = ?, course_of_study = ?, placement_org = ?, industry_supervisor = ?, updated_at = now()
                 WHERE id = ?'
            );
            $stmt->execute([$fullName, $institution, $course, $org, $supervisor, $student['id']]);
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    }

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $errors = validate_password_change($current, $new, $confirm, $student['password_hash']);
        if (!$errors) {
            $stmt = db()->prepare('UPDATE students SET password_hash = ?, updated_at = now() WHERE id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $student['id']]);
            flash('success', 'Password changed.');
            redirect('profile.php');
        }
    }
}

$page_title = 'Profile';
$active     = 'profile';
require __DIR__ . '/lib/partials/layout-top.php';
?>

<section class="page-head">
    <div>
        <h2>Your profile</h2>
        <p class="muted">Keep your details and placement info current.</p>
    </div>
    <div class="profile-id">
        <span class="avatar avatar--lg" aria-hidden="true"><?= e(strtoupper(mb_substr($student['full_name'], 0, 1))) ?></span>
        <div>
            <strong><?= e($student['full_name']) ?></strong>
            <small class="muted block"><?= e($student['matric_no']) ?></small>
        </div>
    </div>
</section>

<div class="panel-grid">
    <!-- ---- Personal + placement details ---- -->
    <section class="card">
        <header class="card__head">
            <h3><?= icon('user') ?> Details &amp; placement</h3>
        </header>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="details">

            <div class="form-grid">
                <div class="field">
                    <label for="full_name">Full name <span class="req">*</span></label>
                    <input type="text" id="full_name" name="full_name" value="<?= e($student['full_name']) ?>" required>
                    <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name']) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label for="matric_no_display">Matric number</label>
                    <input type="text" id="matric_no_display" value="<?= e($student['matric_no']) ?>" disabled>
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="institution">Institution</label>
                    <input type="text" id="institution" name="institution" value="<?= e($student['institution']) ?>">
                </div>
                <div class="field">
                    <label for="course_of_study">Course of study</label>
                    <input type="text" id="course_of_study" name="course_of_study" value="<?= e($student['course_of_study']) ?>">
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="placement_org">Placement organisation</label>
                    <input type="text" id="placement_org" name="placement_org" value="<?= e($student['placement_org']) ?>"
                           placeholder="Where you're doing your SIWES">
                </div>
                <div class="field">
                    <label for="industry_supervisor">Industry supervisor</label>
                    <input type="text" id="industry_supervisor" name="industry_supervisor" value="<?= e($student['industry_supervisor']) ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary"><?= icon('check') ?><span>Save details</span></button>
            </div>
        </form>
    </section>

    <!-- ---- Change password ---- -->
    <section class="card card--narrow">
        <header class="card__head">
            <h3><?= icon('lock') ?> Change password</h3>
        </header>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">

            <div class="field">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                <?php if (isset($errors['current_password'])): ?><p class="field-error"><?= e($errors['current_password']) ?></p><?php endif; ?>
            </div>
            <div class="field">
                <label for="new_password">New password</label>
                <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="<?= PASSWORD_MIN_LEN ?>" required>
                <?php if (isset($errors['new_password'])): ?><p class="field-error"><?= e($errors['new_password']) ?></p><?php endif; ?>
            </div>
            <div class="field">
                <label for="confirm_password">Confirm new password</label>
                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                <?php if (isset($errors['confirm_password'])): ?><p class="field-error"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Update password</button>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/lib/partials/layout-bottom.php'; ?>
