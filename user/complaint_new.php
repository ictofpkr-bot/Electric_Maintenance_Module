<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('user');

$description = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim((string) ($_POST['description'] ?? ''));
    if ($description === '') {
        $errorMessage = 'Complaint description is required.';
    } elseif (text_length($description) > 500) {
        $errorMessage = 'Complaint description must be 500 characters or fewer.';
    } else {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('INSERT INTO complaints (user_id, description, status) VALUES (?, ?, ?)');
        $stmt->execute([(int) $_SESSION['user_id'], $description, 'open']);
        header('Location: /user/dashboard.php?success=' . urlencode('Complaint submitted successfully.'));
        exit;
    }
}

$pageTitle = 'New Complaint';
$showNavigation = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <div>
        <h1>Submit New Complaint</h1>
        <p>Tell us what issue you are facing. The maintenance team will review and follow up soon.</p>
    </div>
</section>
<section class="card">
    <?php if ($errorMessage !== ''): ?>
        <p class="message message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <form method="post" action="" class="stacked-form">
        <label>
            Complaint description
            <textarea name="description" rows="6" maxlength="500"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
        <p class="hint">Maximum 500 characters.</p>
        <button class="button button-primary" type="submit">Submit Complaint</button>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
