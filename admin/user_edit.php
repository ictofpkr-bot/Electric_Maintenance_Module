<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($userId <= 0) {
    header('Location: /admin/users.php');
    exit;
}

$pdo = get_db_connection();
$errorMessage = '';
$successMessage = '';
$roles = ['user' => 'User', 'em' => 'EM', 'admin' => 'Admin'];

$stmt = $pdo->prepare('SELECT user_id, login_id, role, full_name, contact_number, personal_number, address, is_active FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /admin/users.php');
    exit;
}

$fullName = $user['full_name'];
$contactNumber = $user['contact_number'];
$personalNumber = $user['personal_number'];
$address = $user['address'];
$selectedRole = $user['role'];
$isActive = (int) $user['is_active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $contactNumber = trim((string) ($_POST['contact_number'] ?? ''));
    $personalNumber = trim((string) ($_POST['personal_number'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $selectedRole = $_POST['role'] ?? $selectedRole;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($fullName === '') {
        $errorMessage = 'Full name is required.';
    } elseif (!array_key_exists($selectedRole, $roles)) {
        $errorMessage = 'Invalid role selected.';
    } else {
        $update = $pdo->prepare('UPDATE users SET role = ?, full_name = ?, contact_number = ?, personal_number = ?, address = ?, is_active = ? WHERE user_id = ?');
        $update->execute([$selectedRole, $fullName, $contactNumber, $personalNumber, $address, $isActive, $userId]);
        $successMessage = 'User updated successfully.';
    }
}

$pageTitle = 'Edit User';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>Edit User</h1>
        <p>Update account information, role, and active status for this user.</p>
    </div>
    <div class="page-actions">
        <a class="button button-secondary" href="/admin/users.php">Back to user list</a>
    </div>
</section>
<section class="card">
    <?php if ($successMessage !== ''): ?>
        <p class="message message-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <p class="message message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="" class="stacked-form">
        <label>
            Login ID
            <input type="text" value="<?php echo htmlspecialchars($user['login_id'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
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
            Full name
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
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
        <label class="checkbox-label">
            <input type="checkbox" name="is_active" value="1" <?php echo $isActive ? 'checked' : ''; ?>> Active
        </label>
        <button class="button button-primary" type="submit">Save Changes</button>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
