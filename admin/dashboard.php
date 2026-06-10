<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$pdo = get_db_connection();
$memberStmt = $pdo->query('SELECT role, COUNT(*) AS count FROM users WHERE is_active = 1 GROUP BY role');
$memberCounts = ['user' => 0, 'em' => 0, 'admin' => 0];
foreach ($memberStmt->fetchAll(PDO::FETCH_ASSOC) as $memberRow) {
    $memberCounts[$memberRow['role']] = (int) $memberRow['count'];
}
$totalMembers = array_sum($memberCounts);

$complaintCount = $pdo->query('SELECT COUNT(*) FROM complaints')->fetchColumn();
$statusStmt = $pdo->query('SELECT status, COUNT(*) AS count FROM complaints GROUP BY status');
$statusCounts = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$pendingCount = $statusCounts['pending'] ?? 0;
$openCount = $statusCounts['open'] ?? 0;
$closedCount = $statusCounts['closed'] ?? 0;

$pageTitle = 'Admin Dashboard';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>Dashboard Summary</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>. Today is <?php echo date('F j, Y'); ?>.</p>
    </div>
</section>
<div class="dashboard-summary-grid">
<section class="card">
    <h2>Member Summary</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($totalMembers, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($memberCounts['user'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="stat-label">Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($memberCounts['em'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="stat-label">Providers</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($memberCounts['admin'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="stat-label">Admins</div>
        </div>
    </div>
</section>
<section class="card">
    <h2>Complaint Summary</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($complaintCount, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($pendingCount, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($openCount, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="stat-label">Open</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($closedCount, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="stat-label">Closed</div>
        </div>
    </div>
</section>
</div>
<div class="actions-row dashboard-action-row">
    <a class="button button-primary" href="/admin/users.php">Manage Users</a>
    <a class="button button-secondary" href="/admin/complaints.php">View Complaints</a>
    <a class="button button-secondary" href="/admin/user_create.php">Add Provider</a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php';
