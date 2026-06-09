<?php
$role = $_GET['role'] ?? 'user';
echo "Login page for " . htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
