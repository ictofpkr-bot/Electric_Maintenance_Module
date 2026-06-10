<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$successMessage = '';
$errorMessage = '';
$importedUsers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Please upload a valid CSV file.';
    } else {
        $tmpFile = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($tmpFile, 'r');
        if ($handle === false) {
            $errorMessage = 'Unable to read the uploaded file.';
        } else {
            $pdo = get_db_connection();
            $rowNumber = 0;
            $createdCount = 0;
            $errors = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if ($rowNumber === 1 && isset($row[0]) && preg_match('/full[_ ]name/i', $row[0])) {
                    continue;
                }
                if (count($row) === 0 || trim($row[0]) === '') {
                    continue;
                }

                $fullName = trim($row[0]);
                $role = strtolower(trim($row[1] ?? 'user')) ?: 'user';
                $contactNumber = trim($row[2] ?? '');
                $personalNumber = trim($row[3] ?? '');
                $address = trim($row[4] ?? '');

                if ($fullName === '') {
                    $errors[] = "Row {$rowNumber}: full name is required.";
                    continue;
                }
                if (!in_array($role, ['user', 'em', 'admin'], true)) {
                    $role = 'user';
                }

                try {
                    $loginId = generate_login_id($pdo, $role);
                    $password = generate_password(10);
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $insert = $pdo->prepare('INSERT INTO users (login_id, password_hash, role, full_name, contact_number, personal_number, address) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $insert->execute([$loginId, $passwordHash, $role, $fullName, $contactNumber, $personalNumber, $address]);

                    $importedUsers[] = [
                        'login_id' => $loginId,
                        'password' => $password,
                        'full_name' => $fullName,
                        'role' => $role,
                    ];
                    $createdCount++;
                } catch (Throwable $exception) {
                    $errors[] = "Row {$rowNumber}: could not import user ({$exception->getMessage()}).";
                }
            }

            fclose($handle);
            if ($createdCount > 0) {
                $successMessage = "Imported {$createdCount} user(s) successfully.";
            }
            if (count($errors) > 0) {
                $errorMessage = implode(' ', $errors);
            }
        }
    }
}

$pageTitle = 'Import Users';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>Import Users</h1>
        <p>Upload a CSV file to create multiple users at once. Only the first five columns are used.</p>
    </div>
</section>
<section class="card">
    <div class="info-panel">
        <h2>CSV format</h2>
        <ul>
            <li><strong>full_name</strong></li>
            <li><strong>role</strong> (user, em, admin)</li>
            <li><strong>contact_number</strong></li>
            <li><strong>personal_number</strong></li>
            <li><strong>address</strong></li>
        </ul>
    </div>
    <?php if ($successMessage !== ''): ?>
        <p class="message message-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <p class="message message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data" class="stacked-form">
        <label>
            CSV file
            <input type="file" name="csv_file" accept=".csv">
        </label>
        <button class="button button-primary" type="submit">Import Users</button>
    </form>
</section>

<?php if (count($importedUsers) > 0): ?>
<section class="card card--wide">
    <h2>Imported Users</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Login ID</th>
                <th>Password</th>
                <th>Name</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($importedUsers as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['login_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($user['password'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php';
