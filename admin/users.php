<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$pdo = get_db_connection();
$usersStmt = $pdo->query('SELECT user_id, login_id, full_name, role, contact_number, is_active FROM users ORDER BY role, full_name');
$users = $usersStmt->fetchAll();

$successMessage = $_GET['success'] ?? '';

$pageTitle = 'Users';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>Users</h1>
        <p>Manage accounts, roles, and access status from one clean user administration view.</p>
        <?php if ($successMessage !== ''): ?>
            <p class="message message-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>
    <div class="page-actions">
        <a class="button button-primary" href="/admin/user_create.php">Create User</a>
        <a class="button button-secondary" href="/admin/import_users.php">Import Users</a>
    </div>
</section>
<section class="card card--wide">
    <?php if (count($users) === 0): ?>
        <p>No users found.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Login ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['login_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['contact_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></td>
                        <td>
                            <a href="/admin/user_edit.php?id=<?php echo urlencode($user['user_id']); ?>">Edit</a>
                            <?php if ($user['is_active']): ?>
                                <form method="post" action="/admin/user_delete.php" style="display:inline; margin:0; padding:0;">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="button button-link" type="submit" onclick="return confirm('Deactivate this user?');">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
