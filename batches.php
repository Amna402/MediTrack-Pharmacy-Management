<?php
/* Batch management (pharmacist): record every incoming batch with quantity and expiry date. */
require __DIR__ . '/includes/bootstrap.php';

$user = require_login(['pharmacist']);
$medicineRepo = new MedicineRepository();
$batchRepo = new BatchRepository();
$medicines = $medicineRepo->allWithStock();
$errors = [];
$old = [];

if (is_post()) {
    verify_csrf();
    $old = $_POST;
    try {
        $clean = (new Validator($_POST))
            ->choice('medicine_id', 'a medicine', array_column($medicines, 'id'))
            ->pattern('batch_no', 'Batch number', '/^[A-Za-z0-9-]{3,30}$/',
                      'Batch number must be 3 to 30 characters: letters, digits and - only.')
            ->integer('quantity', 'Quantity', 1, 100000)
            ->date('expiry_date', 'Expiry date')
            ->pattern('supplier', 'Supplier', "/^[A-Za-z0-9][A-Za-z0-9 .,&'()-]{1,99}$/",
                      'Supplier must be 2 to 100 characters: letters, digits, spaces and . , & \' ( ) -')
            ->validate();
        $batchRepo->create($clean);
        flash_set('ok', 'Batch ' . strtoupper($clean['batch_no']) . ' added.');
        redirect('batches.php');
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
    }
}

$batches = $batchRepo->allWithMedicine();
render_header('Batches', 'batches.php', ['batches'], $user);
?>
<div class="page-head">
    <div>
        <h1>Batches</h1>
        <p>Every incoming batch is stored with its expiry date. Sales always use the earliest-expiring valid batch first.</p>
    </div>
</div>

<div class="split">
    <section class="panel">
        <h2>Add batch</h2>
        <?php if (!$medicines): ?>
            <p class="empty">Add a medicine first on the Medicines page.</p>
        <?php else: ?>
        <p class="alert alert-error" data-alert role="alert" hidden></p>
        <form method="post" data-form="batch" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="medicine_id">Medicine</label>
                <select id="medicine_id" name="medicine_id">
                    <option value="">Select medicine</option>
                    <?php foreach ($medicines as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"<?= (string) ($old['medicine_id'] ?? '') === (string) $m['id'] ? ' selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="field-error" data-error-for="medicine_id"><?= e($errors['medicine_id'] ?? '') ?></p>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="batch_no">Batch number</label>
                    <input type="text" id="batch_no" name="batch_no" maxlength="30" placeholder="B-2401" value="<?= old($old, 'batch_no') ?>">
                    <p class="field-error" data-error-for="batch_no"><?= e($errors['batch_no'] ?? '') ?></p>
                </div>
                <div class="field">
                    <label for="quantity">Quantity</label>
                    <input type="text" id="quantity" name="quantity" inputmode="numeric" maxlength="6" value="<?= old($old, 'quantity') ?>">
                    <p class="field-error" data-error-for="quantity"><?= e($errors['quantity'] ?? '') ?></p>
                </div>
            </div>
            <div class="field">
                <label for="expiry_date">Expiry date</label>
                <input type="date" id="expiry_date" name="expiry_date" value="<?= old($old, 'expiry_date') ?>">
                <p class="field-error" data-error-for="expiry_date"><?= e($errors['expiry_date'] ?? '') ?></p>
            </div>
            <div class="field">
                <label for="supplier">Supplier</label>
                <input type="text" id="supplier" name="supplier" maxlength="100" value="<?= old($old, 'supplier') ?>">
                <p class="field-error" data-error-for="supplier"><?= e($errors['supplier'] ?? '') ?></p>
            </div>
            <button type="submit" class="btn btn-block">Add batch</button>
        </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>All batches</h2>
        <p class="sub">The batch that expires first is at the top.</p>
        <?php if (!$batches): ?>
            <p class="empty">No batches yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Medicine</th><th>Batch</th><th>Qty</th><th>Expiry</th><th>Days left</th><th>Supplier</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($batches as $b): ?>
                    <?php $st = expiry_status($b['expiry_date']); ?>
                    <tr>
                        <td><?= e($b['medicine']) ?></td>
                        <td><?= e($b['batch_no']) ?></td>
                        <td><?= (int) $b['quantity'] ?></td>
                        <td><?= e($b['expiry_date']) ?></td>
                        <td><?= days_until($b['expiry_date']) ?></td>
                        <td><?= e($b['supplier']) ?></td>
                        <td><span class="tag <?= e($st[1]) ?>"><?= e($st[0]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>
<?php render_footer(['batches']); ?>
