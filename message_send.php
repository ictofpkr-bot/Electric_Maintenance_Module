<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$complaintId = isset($_POST['complaint_id']) ? (int) $_POST['complaint_id'] : 0;
$messageText = trim((string) ($_POST['message_text'] ?? ''));
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['role'] ?? '';

if ($complaintId <= 0 || $messageText === '') {
    header('Location: /index.php?error=' . urlencode('Message cannot be empty.'));
    exit;
}

$pdo = get_db_connection();
$stmt = $pdo->prepare('SELECT complaint_id, user_id, status FROM complaints WHERE complaint_id = ? LIMIT 1');
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    header('Location: /index.php?error=' . urlencode('Complaint not found.'));
    exit;
}

if ($userRole === 'user' && $complaint['user_id'] !== $userId) {
    header('Location: /user/dashboard.php?error=' . urlencode('Unauthorized access.'));
    exit;
}

if (!in_array($userRole, ['user', 'em'], true)) {
    header('Location: /index.php');
    exit;
}

if ($complaint['status'] !== 'pending') {
    $redirect = $userRole === 'em' ? '/em/complaint_view.php?id=' . urlencode($complaintId) : '/user/complaint_view.php?id=' . urlencode($complaintId);
    header('Location: ' . $redirect . '?error=' . urlencode('Messages can only be added when the complaint is pending.'));
    exit;
}

$insert = $pdo->prepare('INSERT INTO messages (complaint_id, sender_id, message_text) VALUES (?, ?, ?)');
$insert->execute([$complaintId, $userId, $messageText]);

$redirect = $userRole === 'em' ? '/em/complaint_view.php?id=' . urlencode($complaintId) : '/user/complaint_view.php?id=' . urlencode($complaintId);
header('Location: ' . $redirect . '?success=' . urlencode('Message sent successfully.'));
exit;
