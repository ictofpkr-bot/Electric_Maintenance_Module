<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('em');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /em/dashboard.php');
    exit;
}

$complaintId = isset($_POST['complaint_id']) ? (int) $_POST['complaint_id'] : 0;
$newStatus = $_POST['new_status'] ?? '';
if ($complaintId <= 0 || !in_array($newStatus, ['pending', 'closed'], true)) {
    header('Location: /em/dashboard.php?error=' . urlencode('Invalid status update.'));
    exit;
}

$pdo = get_db_connection();
$stmt = $pdo->prepare('SELECT status FROM complaints WHERE complaint_id = ? LIMIT 1');
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    header('Location: /em/dashboard.php?error=' . urlencode('Complaint not found.'));
    exit;
}

$currentStatus = $complaint['status'];
$valid = ($currentStatus === 'open' && $newStatus === 'pending')
      || ($currentStatus === 'pending' && $newStatus === 'closed');

if (!$valid) {
    header('Location: /em/complaint_view.php?id=' . urlencode($complaintId) . '&error=' . urlencode('Invalid status transition.'));
    exit;
}

if ($newStatus === 'closed') {
    $update = $pdo->prepare('UPDATE complaints SET status = ?, updated_at = NOW(), closed_at = NOW() WHERE complaint_id = ?');
} else {
    $update = $pdo->prepare('UPDATE complaints SET status = ?, updated_at = NOW(), closed_at = NULL WHERE complaint_id = ?');
}
$update->execute([$newStatus, $complaintId]);

header('Location: /em/complaint_view.php?id=' . urlencode($complaintId) . '&success=' . urlencode('Complaint status updated.'));
exit;