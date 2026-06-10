<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$role = $_GET['role'] ?? 'user';
if (!in_array($role, ['user', 'em', 'admin'], true)) {
    $role = 'user';
}

$roleLabel = $role === 'admin' ? 'Admin Login' : ($role === 'em' ? 'EM Login' : 'User Login');
$errorMessage = '';
$loginId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedRole = $_POST['role'] ?? $role;
    if (!in_array($postedRole, ['user', 'em', 'admin'], true)) {
        $postedRole = $role;
    }
    $role = $postedRole;
    $roleLabel = $role === 'admin' ? 'Admin Login' : ($role === 'em' ? 'EM Login' : 'User Login');

    $loginId = trim((string) ($_POST['login_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($loginId === '' || $password === '') {
        $errorMessage = 'Login ID and password are required.';
    } else {
        try {
            $user = authenticate_login(get_db_connection(), $loginId, $password, $postedRole);
            if ($user === null) {
                $errorMessage = 'Invalid credentials.';
            } else {
                start_auth_session($user);
                header('Location: ' . dashboard_url_for_role($user['role']));
                exit;
            }
        } catch (Throwable $exception) {
            $errorMessage = 'Unable to sign in right now.';
        }
    }
}

$pageTitle = $roleLabel;
$showNavigation = true;
require_once __DIR__ . '/includes/header.php';
?>
<section class="card auth-card">
    <p class="eyebrow"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></p>
    <h1>Sign in</h1>
    <p class="intro">Enter your login ID and password to continue.</p>

    <?php if ($errorMessage !== ''): ?>
        <p class="form-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="" class="stacked-form">
        <input type="hidden" name="role" value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
        <label>
            Login ID
            <input type="text" name="login_id" autocomplete="username" value="<?php echo htmlspecialchars($loginId, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>
            Password
            <input type="password" name="password" autocomplete="current-password">
        </label>
        <button class="button button-primary" type="submit">Login</button>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php';
