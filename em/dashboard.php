<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('em');

$pdo = get_db_connection();

$allowedSort = [
    'submitted_at' => 'Submitted',
    'status' => 'Status',
    'user_name' => 'User',
    'complaint_id' => 'ID',
];
$sort = $_GET['sort'] ?? 'submitted_at';
if (!array_key_exists($sort, $allowedSort)) {
    $sort = 'submitted_at';
}
$order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'], true) ? $_GET['order'] : 'desc';

$orderClause = $order === 'asc' ? 'ASC' : 'DESC';
$sortClause = $sort === 'user_name' ? 'user_name' : $sort;

$complaintsStmt = $pdo->prepare('SELECT c.complaint_id, c.description, c.status, c.submitted_at, u.full_name AS user_name FROM complaints c JOIN users u ON c.user_id = u.user_id ORDER BY ' . $sortClause . ' ' . $orderClause);
$complaintsStmt->execute();
$complaints = $complaintsStmt->fetchAll();
require_once __DIR__ . '/../includes/helpers.php';

$pageTitle = 'EM Dashboard';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>EM Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>. Review complaints and sort them by the field you need.</p>
    </div>
    <div class="page-actions">
        <form method="get" action="" class="sort-form" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
            <label style="display:flex; flex-direction:column; font-weight:600; font-size:0.95rem;">
                Sort by
                <select name="sort">
                    <?php foreach ($allowedSort as $key => $label): ?>
                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $sort === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button button-primary" type="submit">Sort</button>
        </form>
    </div>
</section>
<section class="card card--wide">
    <h2>All Complaints</h2>
    <?php if (count($complaints) === 0): ?>
        <p>No complaints have been submitted yet.</p>
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
                        <td><a class="button button-secondary button-small" href="/em/complaint_view.php?id=<?php echo urlencode($complaint['complaint_id']); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
