<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$complaintId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($complaintId <= 0) {
    header('Location: /admin/complaints.php');
    exit;
}

$pdo = get_db_connection();
$stmt = $pdo->prepare('SELECT c.*, u.full_name AS user_name, u.login_id AS user_login FROM complaints c JOIN users u ON c.user_id = u.user_id WHERE c.complaint_id = ? LIMIT 1');
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    header('Location: /admin/complaints.php');
    exit;
}

$messageStmt = $pdo->prepare('SELECT m.message_id, m.message_text, m.sent_at, u.full_name, u.role FROM messages m JOIN users u ON m.sender_id = u.user_id WHERE m.complaint_id = ? ORDER BY m.sent_at ASC');
$messageStmt->execute([$complaintId]);
$messages = $messageStmt->fetchAll();

$successMessage = $_GET['success'] ?? '';
$errorMessage = $_GET['error'] ?? '';

$pageTitle = 'Complaint Detail';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>Complaint Detail</h1>
        <p>Review the complaint details, status updates, and communication thread for this request.</p>
    </div>
    <div class="page-actions">
        <a class="button button-secondary" href="/admin/complaints.php">Back to complaints</a>
    </div>
</section>
<?php if ($successMessage !== ''): ?>
    <p class="message message-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <p class="message message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<section class="card card--wide">
    <div class="detail-grid">
        <div class="detail-card">
            <span class="detail-label">Complaint ID</span>
            <strong><?php echo htmlspecialchars($complaint['complaint_id'], ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
        <div class="detail-card">
            <span class="detail-label">Status</span>
            <span class="status-pill"><?php echo htmlspecialchars(ucfirst($complaint['status']), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="detail-card">
            <span class="detail-label">Submitted</span>
            <strong><?php echo htmlspecialchars($complaint['submitted_at'], ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
        <div class="detail-card">
            <span class="detail-label">Last Updated</span>
            <strong><?php echo htmlspecialchars($complaint['updated_at'], ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
        <div class="detail-card" style="grid-column: span 2;">
            <span class="detail-label">Reported by</span>
            <strong><?php echo htmlspecialchars($complaint['user_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span><?php echo htmlspecialchars($complaint['user_login'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php if ($complaint['closed_at'] !== null): ?>
            <div class="detail-card">
                <span class="detail-label">Closed</span>
                <strong><?php echo htmlspecialchars($complaint['closed_at'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        <?php endif; ?>
    </div>
    <div class="detail-block">
        <h2>Description</h2>
        <p><?php echo nl2br(htmlspecialchars($complaint['description'], ENT_QUOTES, 'UTF-8')); ?></p>
    </div>
</section>
<section class="card">
    <h2>Messages</h2>
    <?php if (count($messages) === 0): ?>
        <p>No messages yet.</p>
    <?php else: ?>
        <div class="message-list">
            <?php foreach ($messages as $message): ?>
                <div class="message-item">
                    <div class="message-meta">
                        <strong><?php echo htmlspecialchars($message['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo htmlspecialchars($message['role'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <em><?php echo htmlspecialchars($message['sent_at'], ENT_QUOTES, 'UTF-8'); ?></em>
                    </div>
                    <div class="message-body"><?php echo nl2br(htmlspecialchars($message['message_text'], ENT_QUOTES, 'UTF-8')); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
