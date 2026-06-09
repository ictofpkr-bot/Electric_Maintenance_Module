<?php
function require_role(string $requiredRole): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        header('Location: /login.php?role=' . urlencode($requiredRole));
        exit;
    }
}
