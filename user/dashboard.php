<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

$userId = (int) ($_SESSION['user_id'] ?? 0);
$pdo = get_db_connection();
$stmt = $pdo->prepare('SELECT complaint_id, description, status, submitted_at, updated_at FROM complaints WHERE user_id = ? ORDER BY submitted_at DESC');
$stmt->execute([$userId]);
$complaints = $stmt->fetchAll();
require_once __DIR__ . '/../includes/helpers.php';

$successMessage = $_GET['success'] ?? '';
$errorMessage = $_GET['error'] ?? '';

$pageTitle = 'User Dashboard';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>User Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>. View your complaint history and keep track of updates in one place.</p>
    </div>
    <div class="page-actions">
        <a class="button button-primary" href="/user/complaint_new.php">Submit New Complaint</a>
    </div>
    <?php if ($successMessage !== ''): ?>
        <p class="message message-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <p class="message message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
</section>
<section class="card card--wide">
    <h2>Your Complaints</h2>
    <?php if (count($complaints) === 0): ?>
        <p>You have not submitted any complaints yet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $complaint): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($complaint['complaint_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(text_truncate($complaint['description'], 80), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($complaint['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($complaint['submitted_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($complaint['updated_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><a class="button button-secondary button-small" href="/user/complaint_view.php?id=<?php echo urlencode($complaint['complaint_id']); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
