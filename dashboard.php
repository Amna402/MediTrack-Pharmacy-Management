<?php
/* Dashboard: what each role sees after signing in. */
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$role = $user['role'];

$stats = null;
$todayTotal = null;
$roleCounts = [];

if (in_array($role, ['pharmacist', 'admin'], true)) {
    $stats = (new AlertService())->summary();
}
if (in_array($role, ['pharmacist', 'cashier'], true)) {
    $todayTotal = (new SaleService())->todayTotal();
}
if ($role === 'admin') {
    $roleCounts = (new UserRepository())->countByRole();
}

render_header('Dashboard', 'dashboard.php', ['dashboard'], $user);
?>
<div class="page-head">
    <div>
        <h1>Welcome, <?= e($user['name']) ?></h1>
        <p>Signed in as <span class="role-pill"><?= e($role) ?></span></p>
    </div>
</div>

<?php if ($stats !== null): ?>
<ul class="stats" aria-label="Stock summary">
    <li><strong><?= (int) $stats['medicines'] ?></strong><span>Medicines</span></li>
    <li><strong><?= (int) $stats['units'] ?></strong><span>Units in stock (not expired)</span></li>
    <li class="warn"><strong><?= (int) $stats['near'] ?></strong><span>Batches expiring in <?= NEAR_EXPIRY_DAYS ?> days</span></li>
    <li class="warn"><strong><?= (int) $stats['soon'] ?></strong><span>Batches expiring in <?= NEAR_EXPIRY_DAYS + 1 ?> to <?= WARNING_EXPIRY_DAYS ?> days</span></li>
    <li class="bad"><strong><?= (int) $stats['expired'] ?></strong><span>Expired batches with stock</span></li>
    <li class="warn"><strong><?= (int) $stats['low'] ?></strong><span>Low stock medicines</span></li>
</ul>
<?php endif; ?>

<?php if ($todayTotal !== null): ?>
<ul class="stats">
    <li><strong><?= e(money($todayTotal)) ?></strong><span>Sales today</span></li>
</ul>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
<ul class="stats" aria-label="Users by role">
    <?php foreach (['pharmacist', 'cashier', 'supplier', 'admin'] as $r): ?>
    <li><strong><?= (int) ($roleCounts[$r] ?? 0) ?></strong><span><?= e(ucfirst($r)) ?> accounts</span></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<section class="panel">
    <h2>What you can do</h2>
    <div class="quick">
    <?php if ($role === 'pharmacist'): ?>
        <a href="medicines.php"><strong>Medicines</strong><span>Add medicines and see sellable stock.</span></a>
        <a href="batches.php"><strong>Batches</strong><span>Record each incoming batch with its expiry date.</span></a>
        <a href="sales.php"><strong>Sales</strong><span>See the sales history and the batches used (FEFO).</span></a>
        <a href="alerts.php"><strong>Alerts</strong><span>Expired, near-expiry and low-stock lists.</span></a>
    <?php elseif ($role === 'cashier'): ?>
        <a href="sales.php"><strong>Make a sale</strong><span>The system picks the earliest-expiring valid batch.</span></a>
    <?php elseif ($role === 'admin'): ?>
        <a href="users.php"><strong>Users</strong><span>See and remove user accounts.</span></a>
        <a href="alerts.php"><strong>Alerts</strong><span>Expired, near-expiry and low-stock lists.</span></a>
    <?php else: ?>
        <p class="empty">Purchase orders and delivery updates for suppliers will be added in the next stage of the project.</p>
    <?php endif; ?>
    </div>
</section>
<?php render_footer(); ?>
