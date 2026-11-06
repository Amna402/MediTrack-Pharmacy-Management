<?php
/* Medicine management (pharmacist): add medicines and see how much sellable stock each one has. */
require __DIR__ . '/includes/bootstrap.php';

$user = require_login(['pharmacist']);
$repo = new MedicineRepository();
$errors = [];
$old = [];

if (is_post()) {
    verify_csrf();
    $old = $_POST;
    try {
        $clean = (new Validator($_POST))
            ->pattern('name', 'Medicine name', '/^[A-Za-z0-9][A-Za-z0-9 .\/()%+-]{1,99}$/',
                      'Use 2 to 100 characters: letters, digits, spaces and . / ( ) % + -')
            ->choice('category', 'a category', CATEGORIES)
            ->money('unit_price', 'Unit price', 100000)
            ->integer('reorder_level', 'Reorder level', 0, 100000)
            ->validate();
        $repo->create($clean);
        flash_set('ok', 'Medicine added: ' . $clean['name']);
        redirect('medicines.php');
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
    }
}

$medicines = $repo->allWithStock();
render_header('Medicines', 'medicines.php', ['medicines'], $user);
?>
<div class="page-head">
    <div>
        <h1>Medicines</h1>
        <p>Add a medicine first, then record its batches on the Batches page.</p>
    </div>
</div>

<div class="split">
    <section class="panel">
        <h2>Add medicine</h2>
        <p class="alert alert-error" data-alert role="alert" hidden></p>
        <form method="post" data-form="medicine" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="name">Medicine name</label>
                <input type="text" id="name" name="name" maxlength="100" value="<?= old($old, 'name') ?>">
                <p class="field-error" data-error-for="name"><?= e($errors['name'] ?? '') ?></p>
            </div>
            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">Select category</option>
                    <?php foreach (CATEGORIES as $c): ?>
                    <option value="<?= e($c) ?>"<?= ($old['category'] ?? '') === $c ? ' selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="field-error" data-error-for="category"><?= e($errors['category'] ?? '') ?></p>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="unit_price">Unit price (Rs)</label>
                    <input type="text" id="unit_price" name="unit_price" inputmode="decimal" maxlength="10" value="<?= old($old, 'unit_price') ?>">
                    <p class="field-error" data-error-for="unit_price"><?= e($errors['unit_price'] ?? '') ?></p>
                </div>
                <div class="field">
                    <label for="reorder_level">Reorder level</label>
                    <input type="text" id="reorder_level" name="reorder_level" inputmode="numeric" maxlength="6" value="<?= old($old, 'reorder_level') ?>">
                    <p class="field-error" data-error-for="reorder_level"><?= e($errors['reorder_level'] ?? '') ?></p>
                </div>
            </div>
            <button type="submit" class="btn btn-block">Add medicine</button>
        </form>
    </section>

    <section class="panel">
        <h2>Medicine list</h2>
        <p class="sub">Sellable stock does not include expired batches.</p>
        <?php if (!$medicines): ?>
            <p class="empty">No medicines yet. Add the first one.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Medicine</th><th>Category</th><th>Price</th><th>Sellable</th><th>Reorder at</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($medicines as $m): ?>
                    <?php
                    $sellable = (int) $m['sellable'];
                    if ($sellable === 0) { $tag = ['Out of stock', 'tag-red']; }
                    elseif ($sellable <= (int) $m['reorder_level']) { $tag = ['Low stock', 'tag-amber']; }
                    else { $tag = ['In stock', 'tag-green']; }
                    ?>
                    <tr>
                        <td><?= e($m['name']) ?></td>
                        <td><?= e($m['category']) ?></td>
                        <td><?= e(money($m['unit_price'])) ?></td>
                        <td><?= $sellable ?></td>
                        <td><?= (int) $m['reorder_level'] ?></td>
                        <td><span class="tag <?= e($tag[1]) ?>"><?= e($tag[0]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>
<?php render_footer(['medicines']); ?>
