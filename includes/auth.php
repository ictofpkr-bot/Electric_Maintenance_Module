<?php
function login_url_for_role(string $role): string
{
    if ($role === 'admin') {
        return '/admin/login.php';
    }

    return '/login.php?role=' . urlencode($role);
}

function dashboard_url_for_role(string $role): string
{
    if ($role === 'admin') {
        return '/admin/dashboard.php';
    }

    if ($role === 'em') {
        return '/em/dashboard.php';
    }

    return '/user/dashboard.php';
}

function start_auth_session(array $user): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['full_name'] = (string) $user['full_name'];
    $_SESSION['login_id'] = (string) $user['login_id'];
}

function authenticate_login(PDO $pdo, string $loginId, string $password, string $role): ?array
{
    $stmt = $pdo->prepare('SELECT user_id, login_id, password_hash, role, full_name FROM users WHERE login_id = ? AND role = ? AND is_active = 1 FETCH FIRST 1 ROW ONLY');
    $stmt->execute([$loginId, $role]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

function require_role(string $requiredRole): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        header('Location: ' . login_url_for_role($requiredRole));
        exit;
    }
}
