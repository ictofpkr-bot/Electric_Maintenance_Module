<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('user');
require_once __DIR__ . '/../includes/header.php';
echo '<h1>User Dashboard</h1>';
require_once __DIR__ . '/../includes/footer.php';
