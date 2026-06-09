<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('em');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /em/dashboard.php');
    exit;
}
echo 'Status update handler placeholder';
