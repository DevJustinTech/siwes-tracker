<?php
require_once __DIR__ . '/lib/helpers.php';
boot();
require_login();

$student = current_student();
$q       = trim($_GET['q'] ?? '');

// ---- List (Read) with optional search -------------------------------------
if ($q !== '') {
    $stmt = db()->prepare(
        'SELECT * FROM log_entries
         WHERE student_id = ? AND (work_done ILIKE ? OR challenges ILIKE ? OR entry_date::text ILIKE ?)
         ORDER BY entry_date DESC'
    );
    $like = '%' . $q . '%';
    $stmt->execute([$student['id'], $like, $like, $like . '%']);
} else {
    $stmt = db()->prepare(
        'SELECT * FROM log_entries WHERE student_id = ? ORDER BY entry_date DESC'
    );
    $stmt->execute([$student['id']]);
}
$entries = $stmt->fetchAll();
$firstDay = siwes_first_entry_date(db(), (int)$student['id']);

$page_title = 'My Log Book';
$active     = 'logs';
require __DIR__ . '/lib/partials/layout-top.php';
?>

<section class="page-head">
    <div>
        <h2>Daily log entries</h2>
        <p class="muted">Everything you've logged, newest first.</p>
    </div>
    <a class="btn btn--primary" href="log_form.php"><?= icon('plus') ?><span>New entry</span></a>
</section>

<section class="card">
    <header class="card__head card__head--split">
        <h3><?= icon('book') ?> <?= count($entries) ?> entr<?= count($entries) === 1 ? 'y' : 'ies' ?></h3>
        <form class="search" method="get" role="search">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search work done…" aria-label="Search entries">
            <button class="btn btn--ghost btn--sm" type="submit">Search</button>
            <?php if ($q !== ''): ?><a class="link" href="logs.php">Clear</a><?php endif; ?>
        </form>
    </header>

    <?php if ($entries): ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th><th>Hours</th><th>Work done</th><th>Challenges</th><th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e(day_label($row['entry_date'])) ?></strong>
                            <small class="muted block">Week <?= siwes_week_number($firstDay, $row['entry_date']) ?></small>
                        </td>
                        <td><?= (float)$row['hours_worked'] ?></td>
                        <td class="td-truncate"><?= e(mb_strimwidth($row['work_done'], 0, 110, '…')) ?></td>
                        <td class="td-truncate"><?= $row['challenges'] ? e(mb_strimwidth($row['challenges'], 0, 80, '…')) : '—' ?></td>
                        <td class="td-actions">
                            <a class="icon-btn" href="log_form.php?id=<?= (int)$row['id'] ?>" aria-label="Edit entry"><?= icon('edit') ?></a>
                            <a class="icon-btn icon-btn--danger" href="log_delete.php?id=<?= (int)$row['id'] ?>" aria-label="Delete entry"><?= icon('trash') ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty">
            <?= icon('book') ?>
            <?php if ($q !== ''): ?>
                <p><strong>No matches for "<?= e($q) ?>".</strong><br>Try a different keyword.</p>
                <a class="btn btn--ghost" href="logs.php">Show all entries</a>
            <?php else: ?>
                <p><strong>Nothing logged yet.</strong><br>Add your first daily entry.</p>
                <a class="btn btn--primary" href="log_form.php"><?= icon('plus') ?><span>New entry</span></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/lib/partials/layout-bottom.php'; ?>
