<?php
/* Sales module with FEFO. The cashier sells, the pharmacist can see the history. */
require __DIR__ . '/includes/bootstrap.php';

$user = require_login(['cashier', 'pharmacist']);
$canSell = $user['role'] === 'cashier';
$medicineRepo = new MedicineRepository();
$salesService = new SaleService();
$medicines = $medicineRepo->allWithStock();
$errors = [];
$old = [];

if ($canSell && is_post()) {
    verify_csrf();
    $old = $_POST;
    try {
        $clean = (new Validator($_POST))
            ->choice('medicine_id', 'a medicine', array_column($medicines, 'id'))
            ->integer('quantity', 'Quantity', 1, 10000)
            ->validate();
        $result = $salesService->sell($user['id'], (int) $clean['medicine_id'], (int) $clean['quantity']);
        flash_set('ok', $result['summary']);
        redirect('sales.php');
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
    } catch (StockException $e) {
        $errors['quantity'] = $e->getMessage();
    }
}

$recent = $salesService->recent(15);
render_header('Sales', 'sales.php', ['sales'], $user);
?>
<div class="page-head">
    <div>
        <h1>Sales</h1>
        <p>FEFO: each sale takes stock from the batch that expires first. Expired batches are never sold.</p>
    </div>
</div>

<div class="split">
<?php if ($canSell): ?>
    <section class="panel">
        <h2>New sale</h2>
        <p class="alert alert-error" data-alert role="alert" hidden></p>
        <form method="post" data-form="sale" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="medicine_id">Medicine</label>
                <select id="medicine_id" name="medicine_id">
                    <option value="">Select medicine</option>
                    <?php foreach ($medicines as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"<?= (string) ($old['medicine_id'] ?? '') === (string) $m['id'] ? ' selected' : '' ?>>
                        <?= e($m['name']) ?> (stock <?= (int) $m['sellable'] ?>, <?= e(money($m['unit_price'])) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="field-error" data-error-for="medicine_id"><?= e($errors['medicine_id'] ?? '') ?></p>
            </div>
            <div class="field">
                <label for="quantity">Quantity</label>
                <input type="text" id="quantity" name="quantity" inputmode="numeric" maxlength="5" value="<?= old($old, 'quantity') ?>">
                <p class="field-error" data-error-for="quantity"><?= e($errors['quantity'] ?? '') ?></p>
            </div>
            <button type="submit" class="btn btn-block">Complete sale</button>
        </form>
    </section>
<?php endif; ?>

    <section class="panel">
        <h2>Recent sales</h2>
        <p class="sub">The last 15 sales and the batches they were taken from.</p>
        <?php if (!$recent): ?>
            <p class="empty">No sales yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Medicine</th><th>Qty</th><th>Total</th><th>Batches used</th><th>Cashier</th><th>Time</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $s): ?>
                    <tr>
                        <td><?= (int) $s['id'] ?></td>
                        <td><?= e($s['medicine']) ?></td>
                        <td><?= (int) $s['quantity'] ?></td>
                        <td><?= e(money($s['total'])) ?></td>
                        <td><?= e($s['batches'] ?? '') ?></td>
                        <td><?= e($s['cashier']) ?></td>
                        <td><?= e($s['sold_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>
<?php render_footer($canSell ? ['sales'] : []); ?>
