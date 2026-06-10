<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/users.php');
    exit;
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($userId <= 0) {
    header('Location: /admin/users.php');
    exit;
}

$pdo = get_db_connection();
$update = $pdo->prepare('UPDATE users SET is_active = 0 WHERE user_id = ?');
$update->execute([$userId]);

header('Location: /admin/users.php?success=' . urlencode('User has been deactivated.'));
exit;
