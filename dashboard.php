<?php
require_once __DIR__ . '/helpers.php';
boot();
require_login();

$student = current_student();

// ---- Stats ---------------------------------------------------------------
$stats = student_stats(db(), (int)$student['id']);
$entryCount  = $stats['entries'];
$totalHours  = $stats['hours'];
$avgHours    = $stats['avg_hours'];
$weekCount   = $stats['week_span'];
$chartLabels = $stats['chart_labels'];
$chartData   = $stats['chart_data'];
$recent     = $stats['recent'];

$page_title = 'Dashboard';
$active     = 'dashboard';
require __DIR__ . '/partials/layout-top.php';
?>

<section class="page-head">
    <div>
        <h2>Hello, <?= e(explode(' ', trim((string)$student['full_name']))[0]) ?></h2>
        <p class="muted">Here's how your SIWES log book is coming along.</p>
    </div>
    <a class="btn btn--primary" href="log_form.php">
        <?= icon('plus') ?><span>New daily entry</span>
    </a>
</section>

<section class="stats-grid">
    <article class="stat-card stat-card--cyan">
        <span class="stat-icon"><?= icon('book') ?></span>
        <div>
            <span class="stat-value"><?= $entryCount ?></span>
            <span class="stat-label">Day<?= $entryCount === 1 ? '' : 's' ?> logged</span>
        </div>
    </article>
    <article class="stat-card stat-card--green">
        <span class="stat-icon"><?= icon('clock') ?></span>
        <div>
            <span class="stat-value"><?= number_format($totalHours, 1) ?></span>
            <span class="stat-label">Total hours</span>
        </div>
    </article>
    <article class="stat-card stat-card--amber">
        <span class="stat-icon"><?= icon('brief') ?></span>
        <div>
            <span class="stat-value"><?= number_format($avgHours, 1) ?></span>
            <span class="stat-label">Avg hours / day</span>
        </div>
    </article>
    <article class="stat-card stat-card--violet">
        <span class="stat-icon"><?= icon('cal') ?></span>
        <div>
            <span class="stat-value"><?= $weekCount ?></span>
            <span class="stat-label">Week span</span>
        </div>
    </article>
</section>

<section class="panel-grid">
    <div class="card card--chart">
        <header class="card__head">
            <h3><?= icon('chart') ?> Hours per day</h3>
            <span class="badge">Last <?= max(count($chartData), 1) ?> days</span>
        </header>
        <?php if ($chartData): ?>
            <div class="chart" role="img"
                 aria-label="Bar chart of hours worked per day: <?= e(implode(', ', array_map(fn($l, $v) => $l . ': ' . $v, $chartLabels, $chartData))) ?>">
                <?php
                $max = max($chartData) ?: 1;
                foreach ($chartData as $i => $val):
                    $pct = round($val / $max * 100);
                ?>
                <div class="chart__col" title="<?= e($chartLabels[$i]) ?> — <?= (float)$val ?> hours">
                    <span class="chart__val"><?= (float)$val ?></span>
                    <div class="chart__track"><div class="chart__bar" style="--h: <?= $pct ?>%"></div></div>
                    <span class="chart__label"><?= e($chartLabels[$i]) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty empty--sm">
                <?= icon('chart') ?>
                <p>No data yet — log your first day to see the chart.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card card--placement">
        <header class="card__head">
            <h3><?= icon('brief') ?> Placement</h3>
        </header>
        <dl class="kv">
            <div><dt>Organisation</dt><dd><?= e($student['placement_org'] ?: 'Not set') ?></dd></div>
            <div><dt>Industry supervisor</dt><dd><?= e($student['industry_supervisor'] ?: 'Not set') ?></dd></div>
            <div><dt>Institution</dt><dd><?= e($student['institution'] ?: 'Not set') ?></dd></div>
            <div><dt>Course</dt><dd><?= e($student['course_of_study'] ?: 'Not set') ?></dd></div>
        </dl>
        <a class="btn btn--ghost btn--sm" href="profile.php">Update placement <?= icon('edit') ?></a>
    </div>
</section>

<section class="card">
    <header class="card__head">
        <h3><?= icon('clock') ?> Recent entries</h3>
        <a class="link" href="logs.php">View all <?= icon('arrow') ?></a>
    </header>

    <?php if ($recent): ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th><th>Hours</th><th>Work done</th><th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e(day_label($row['entry_date'])) ?></strong>
                            <small class="muted block">Week <?= siwes_week_number($stats['first_day'], $row['entry_date']) ?></small>
                        </td>
                        <td><?= (float)$row['hours_worked'] ?></td>
                        <td class="td-truncate"><?= e(mb_strimwidth($row['work_done'], 0, 90, '…')) ?></td>
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
            <p><strong>Your log book is empty.</strong><br>Log your first day of work to get started.</p>
            <a class="btn btn--primary" href="log_form.php"><?= icon('plus') ?><span>Log your first day</span></a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/layout-bottom.php'; ?>
