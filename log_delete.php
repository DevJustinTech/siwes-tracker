<?php
require_once __DIR__ . '/helpers.php';
boot();
require_login();

$student = current_student();
$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM log_entries WHERE id = ? AND student_id = ?');
$stmt->execute([$id, $student['id']]);
$entry = $stmt->fetch();

if (!$entry) {
    flash('error', 'Entry not found.');
    redirect('logs.php');
}

// ---- Delete (with confirmation page) ---------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = db()->prepare('DELETE FROM log_entries WHERE id = ? AND student_id = ?');
    $stmt->execute([$id, $student['id']]);
    flash('success', 'Entry deleted.');
    redirect('logs.php');
}

$firstDay = siwes_first_entry_date(db(), (int)$student['id']);
$page_title = 'Delete entry';
$active     = 'logs';
require __DIR__ . '/partials/layout-top.php';
?>

<section class="page-head">
    <div>
        <h2>Delete this entry?</h2>
        <p class="muted">This action can't be undone.</p>
    </div>
</section>

<section class="card card--narrow">
    <div class="delete-preview">
        <h3><?= e(day_label($entry['entry_date'])) ?></h3>
        <p class="muted">Week <?= siwes_week_number($firstDay, $entry['entry_date']) ?>
             · <?= (float)$entry['hours_worked'] ?> hours</p>
        <blockquote class="quote"><?= nl2br(e(mb_strimwidth($entry['work_done'], 0, 300, '…'))) ?></blockquote>
    </div>

    <form method="post" class="form-actions">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--danger"><?= icon('trash') ?><span>Yes, delete it</span></button>
        <a class="btn btn--ghost" href="logs.php">Keep it</a>
    </form>
</section>

<?php require __DIR__ . '/partials/layout-bottom.php'; ?>
