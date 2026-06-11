<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$pageTitle = $pageTitle ?? 'EMS';
$showNavigation = $showNavigation ?? true;
$loggedIn = isset($_SESSION['role']);
$userRole = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/index.php">EMS</a>
    <?php if ($showNavigation): ?>
    <nav class="site-nav">
        <?php if ($loggedIn): ?>
            <?php if ($userRole === 'admin'): ?>
                <a href="/admin/dashboard.php">Admin Dashboard</a>
            <?php elseif ($userRole === 'user'): ?>
                <a href="/user/dashboard.php">My Complaints</a>
            <?php elseif ($userRole === 'em'): ?>
                <a href="/em/dashboard.php">EM Dashboard</a>
            <?php endif; ?>
            <form method="post" action="/logout.php" style="margin:0;">
                <button type="submit" class="button button-secondary" style="min-height:36px;padding:0.4rem 1rem;">Logout</button>
            </form>
        <?php else: ?>
            <a href="/login.php?role=user">User Login</a>
            <a href="/login.php?role=em">EM Login</a>
            <a href="/login.php?role=admin">Admin Login</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</header>
<main class="page-shell">