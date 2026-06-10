<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/helpers.php';

$allowed = ['open', 'pending', 'closed'];
$filter = isset($_GET['status']) && in_array($_GET['status'], $allowed, true) ? $_GET['status'] : '';

$pdo = get_db_connection();
if ($filter !== '') {
    $stmt = $pdo->prepare('SELECT c.complaint_id, c.description, c.status, c.submitted_at, u.full_name AS user_name FROM complaints c JOIN users u ON c.user_id = u.user_id WHERE c.status = ? ORDER BY c.submitted_at DESC');
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query('SELECT c.complaint_id, c.description, c.status, c.submitted_at, u.full_name AS user_name FROM complaints c JOIN users u ON c.user_id = u.user_id ORDER BY c.submitted_at DESC');
}
$complaints = $stmt->fetchAll();

$pageTitle = 'All Complaints';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>All Complaints</h1>
        <p>Browse and filter complaints by status.</p>
    </div>
    <div class="page-actions">
        <form method="get" action="" style="display:flex;gap:0.5rem;align-items:center;">
            <label style="display:flex;gap:0.5rem;align-items:center;">
                Status
                <select name="status">
                    <option value="" <?php echo $filter === '' ? 'selected' : ''; ?>>All</option>
                    <option value="open" <?php echo $filter === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="closed" <?php echo $filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </label>
            <button class="button button-primary" type="submit">Filter</button>
        </form>
    </div>
</section>
<section class="card card--wide">
    <?php if (count($complaints) === 0): ?>
        <p>No complaints found.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $complaint): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($complaint['complaint_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(text_truncate($complaint['description'], 80), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($complaint['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($complaint['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($complaint['submitted_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><a class="button button-secondary button-small" href="/admin/complaint_view.php?id=<?php echo urlencode($complaint['complaint_id']); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
