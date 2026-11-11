<?php
/* Alert module: expired stock, near-expiry stock and low stock. */
require __DIR__ . '/includes/bootstrap.php';

$user = require_login(['pharmacist', 'admin']);
$service = new AlertService();
$expired = $service->expired();
$near = $service->expiringBetween(0, NEAR_EXPIRY_DAYS);
$soon = $service->expiringBetween(NEAR_EXPIRY_DAYS + 1, WARNING_EXPIRY_DAYS);
$low = $service->lowStock();

// Prints one table of batches (used three times below).
function batch_alert_table(array $rows, string $empty): void
{
    if (!$rows) {
        echo '<p class="empty">' . e($empty) . '</p>';
        return;
    }
    echo '<div class="table-wrap"><table><thead><tr><th>Medicine</th><th>Batch</th><th>Qty</th><th>Expiry</th><th>Days left</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr><td>' . e($r['medicine']) . '</td><td>' . e($r['batch_no']) . '</td><td>' . (int) $r['quantity']
           . '</td><td>' . e($r['expiry_date']) . '</td><td>' . days_until($r['expiry_date']) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

render_header('Alerts', 'alerts.php', ['alerts'], $user);
?>
<div class="page-head">
    <div>
        <h1>Alerts</h1>
        <p>Problems are shown before they turn into a loss.</p>
    </div>
</div>

<section class="panel alert-block dark">
    <h2>Expired stock <span class="count"><?= count($expired) ?></span></h2>
    <p class="sub">These batches can never be sold. Remove them from the shelf.</p>
    <?php batch_alert_table($expired, 'No expired stock.'); ?>
</section>

<section class="panel alert-block red">
    <h2>Expiring within <?= NEAR_EXPIRY_DAYS ?> days <span class="count"><?= count($near) ?></span></h2>
    <p class="sub">Sell these first. FEFO already picks them first at the counter.</p>
    <?php batch_alert_table($near, 'Nothing expires in the next ' . NEAR_EXPIRY_DAYS . ' days.'); ?>
</section>

<section class="panel alert-block amber">
    <h2>Expiring in <?= NEAR_EXPIRY_DAYS + 1 ?> to <?= WARNING_EXPIRY_DAYS ?> days <span class="count"><?= count($soon) ?></span></h2>
    <?php batch_alert_table($soon, 'Nothing in this range.'); ?>
</section>

<section class="panel alert-block amber">
    <h2>Low stock <span class="count"><?= count($low) ?></span></h2>
    <p class="sub">Sellable stock (not expired) is at or below the reorder level.</p>
    <?php if (!$low): ?>
        <p class="empty">All medicines are above their reorder level.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Medicine</th><th>Sellable stock</th><th>Reorder level</th></tr></thead>
            <tbody>
            <?php foreach ($low as $m): ?>
                <tr><td><?= e($m['name']) ?></td><td><?= (int) $m['sellable'] ?></td><td><?= (int) $m['reorder_level'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
