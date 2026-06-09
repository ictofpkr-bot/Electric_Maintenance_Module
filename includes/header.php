<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$pageTitle = $pageTitle ?? 'EMS';
$showNavigation = $showNavigation ?? true;
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
        <a href="/index.php">Home</a>
        <a href="/login.php?role=user">User Login</a>
        <a href="/login.php?role=em">EM Login</a>
        <a href="/admin/login.php">Admin Login</a>
        <a href="/logout.php">Logout</a>
    </nav>
    <?php endif; ?>
</header>
<main class="page-shell">
