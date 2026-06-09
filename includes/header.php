<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav>
    <a href="/index.php">Home</a>
    <a href="/logout.php">Logout</a>
</nav>
