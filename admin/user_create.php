<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$roles = ['user' => 'User', 'em' => 'EM', 'admin' => 'Admin'];
$fullName = '';
$contactNumber = '';
$personalNumber = '';
$address = '';
$selectedRole = 'user';
$errorMessage = '';
$successMessage = '';
$createdCredentials = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $contactNumber = trim((string) ($_POST['contact_number'] ?? ''));
    $personalNumber = trim((string) ($_POST['personal_number'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $selectedRole = $_POST['role'] ?? 'user';

    if ($fullName === '') {
        $errorMessage = 'Full name is required.';
    } elseif (!array_key_exists($selectedRole, $roles)) {
        $errorMessage = 'Invalid role selected.';
    } else {
        $pdo = get_db_connection();
        $loginId = generate_login_id($pdo, $selectedRole);
        $password = generate_password(10);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $pdo->prepare('INSERT INTO users (login_id, password_hash, role, full_name, contact_number, personal_number, address) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$loginId, $passwordHash, $selectedRole, $fullName, $contactNumber, $personalNumber, $address]);

        $createdCredentials = ['login_id' => $loginId, 'password' => $password];
        $successMessage = 'User account created successfully.';

        $fullName = $contactNumber = $personalNumber = $address = '';
        $selectedRole = 'user';
    }
}

$pageTitle = 'Create User';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>Create User</h1>
        <p>Add a new user to the system with an automatically generated login ID and password.</p>
    </div>
</section>
<section class="card">
    <?php if ($successMessage !== ''): ?>
        <p class="message message-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <p class="message message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if ($createdCredentials !== null): ?>
        <div class="notification-card">
            <h2>Credentials</h2>
            <p>Login ID: <strong><?php echo htmlspecialchars($createdCredentials['login_id'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p>Password: <strong><?php echo htmlspecialchars($createdCredentials['password'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p>Please copy the credentials now; the password is only shown once.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="stacked-form">
        <label>
            Full name
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>
            Role
            <select name="role">
                <?php foreach ($roles as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedRole === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Contact number
            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($contactNumber, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>
            Personal number
            <input type="text" name="personal_number" value="<?php echo htmlspecialchars($personalNumber, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>
            Address
            <textarea name="address" rows="3"><?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
        <button class="button button-primary" type="submit">Create User</button>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
