<?php
/* User management (administrator): see all accounts and remove an account. */
require __DIR__ . '/includes/bootstrap.php';

$user = require_login(['admin']);
$repo = new UserRepository();

if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['user_id'] ?? 0);
    try {
        $target = $repo->find($id);
        if ($target === null) {
            flash_set('error', 'User not found.');
        } elseif ($id === $user['id']) {
            flash_set('error', 'You cannot delete your own account.');
        } else {
            $repo->delete($id);
            flash_set('ok', 'Deleted user: ' . $target['full_name']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {   // the user still has sales records
            flash_set('error', 'This user has sales records and cannot be deleted.');
        } else {
            throw $e;
        }
    }
    redirect('users.php');
}

$users = $repo->all();
render_header('Users', 'users.php', ['users'], $user);
?>
<div class="page-head">
    <div>
        <h1>Users</h1>
        <p>All accounts in the system. Passwords are stored as hashes and are never shown.</p>
    </div>
</div>

<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Mobile</th><th>Role</th><th>Created</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= e($u['full_name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['phone']) ?></td>
                    <td><span class="role-pill role-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                    <td><?= e($u['created_at']) ?></td>
                    <td>
                    <?php if ((int) $u['id'] !== $user['id']): ?>
                        <form method="post" data-confirm="Delete <?= e($u['full_name']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-small">Delete</button>
                        </form>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(['users']); ?>
