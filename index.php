<?php
$pageTitle = 'EMS';
$showNavigation = false;
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <p class="eyebrow">Electronic Maintenance System</p>
    <h1>Simple complaint tracking for users and maintenance staff.</h1>
    <p class="intro">Choose the role that fits your workflow and continue to a focused login screen.</p>

    <div class="button-row">
        <a class="button button-primary" href="/login.php?role=user">User Login</a>
        <a class="button button-secondary" href="/login.php?role=em">EM Login</a>
    </div>

    <p class="admin-link">
        Admin access is available from the separate <a href="/admin/login.php">admin login</a> page.
    </p>
</section>
<?php require_once __DIR__ . '/includes/footer.php';
