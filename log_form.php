<?php
require_once __DIR__ . '/helpers.php';
boot();
require_login();

$student = current_student();

// ---- Load entry for editing (Update) ---------------------------------------
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$entry  = null;

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM log_entries WHERE id = ? AND student_id = ?');
    $stmt->execute([$id, $student['id']]);
    $entry = $stmt->fetch();
    if (!$entry) {
        flash('error', 'Entry not found.');
        redirect('logs.php');
    }
}

$errors = [];

// ---- Defaults --------------------------------------------------------------
$entryDate   = $entry['entry_date']   ?? date('Y-m-d');
$hoursWorked = $entry['hours_worked'] ?? 8.0;
$workDone    = $entry['work_done']    ?? '';
$challenges  = $entry['challenges']   ?? '';
$remarks     = $entry['remarks']      ?? '';

// ---- Create / Update -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $entryDate   = trim($_POST['entry_date'] ?? '');
    $hoursWorked = (float)($_POST['hours_worked'] ?? 0);
    $workDone    = trim($_POST['work_done'] ?? '');
    $challenges  = trim($_POST['challenges'] ?? '');
    $remarks     = trim($_POST['remarks'] ?? '');

    $errors = validate_log_entry(db(), (int)$student['id'], $id, $entryDate, $hoursWorked, $workDone);

    if (!$errors) {
        if ($id > 0) {
            $stmt = db()->prepare(
                'UPDATE log_entries
                 SET entry_date = ?, hours_worked = ?, work_done = ?, challenges = ?, remarks = ?
                 WHERE id = ? AND student_id = ?'
            );
            $stmt->execute([$entryDate, $hoursWorked, $workDone, $challenges, $remarks, $id, $student['id']]);
            flash('success', 'Entry updated.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO log_entries (student_id, entry_date, hours_worked, work_done, challenges, remarks)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$student['id'], $entryDate, $hoursWorked, $workDone, $challenges, $remarks]);
            flash('success', 'Day logged — nice progress!');
        }
        redirect('logs.php');
    }
}

$page_title = $id > 0 ? 'Edit entry' : 'New daily entry';
$active     = 'logs';
require __DIR__ . '/partials/layout-top.php';
?>

<section class="page-head">
    <div>
        <h2><?= $id > 0 ? 'Edit daily entry' : 'Log a day of work' ?></h2>
        <p class="muted"><?= $id > 0 ? 'Update the details below and save.' : 'Pick the day, then summarise what you did.' ?></p>
    </div>
    <a class="btn btn--ghost" href="logs.php">Back to log book</a>
</section>

<section class="card card--form">
    <form method="post" novalidate>
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="field">
                <label for="entry_date">Date worked <span class="req">*</span></label>
                <input type="date" id="entry_date" name="entry_date" value="<?= e($entryDate) ?>" required>
                <?php if (isset($errors['entry_date'])): ?><p class="field-error"><?= e($errors['entry_date']) ?></p><?php endif; ?>
            </div>
            <div class="field">
                <label for="hours_worked">Hours worked <span class="req">*</span></label>
                <input type="number" id="hours_worked" name="hours_worked" min="<?= HOURS_MIN ?>" max="<?= HOURS_MAX ?>" step="<?= HOURS_STEP ?>"
                       value="<?= e((string)$hoursWorked) ?>" required>
                <?php if (isset($errors['hours_worked'])): ?><p class="field-error"><?= e($errors['hours_worked']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="work_done">What did you work on? <span class="req">*</span></label>
            <textarea id="work_done" name="work_done" rows="6" required
                      placeholder="e.g. Built REST endpoints for the inventory module, attended sprint planning, fixed the login bug…"><?= e($workDone) ?></textarea>
            <?php if (isset($errors['work_done'])): ?><p class="field-error"><?= e($errors['work_done']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="challenges">Challenges faced <span class="muted">(optional)</span></label>
            <textarea id="challenges" name="challenges" rows="3"
                      placeholder="Anything that slowed you down or needs the supervisor's attention."><?= e($challenges) ?></textarea>
        </div>

        <div class="field">
            <label for="remarks">Remarks <span class="muted">(optional — reserved for your supervisor)</span></label>
            <textarea id="remarks" name="remarks" rows="2" placeholder="Supervisor's remarks."><?= e($remarks) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary"><?= icon('check') ?><span><?= $id > 0 ? 'Save changes' : 'Log this day' ?></span></button>
            <a class="btn btn--ghost" href="logs.php">Cancel</a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/partials/layout-bottom.php'; ?>
