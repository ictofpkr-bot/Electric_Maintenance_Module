<?php
// Copy this file to db.php and fill in your credentials.
// db.php is listed in .gitignore and should never be committed.
function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host    = 'your_db_host';
    $port    = '3306';
    $dbname  = 'your_db_name';
    $user    = 'your_db_user';
    $pass    = 'your_db_password';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('EMS database connection failed: ' . $e->getMessage());
        http_response_code(503);
        echo '<p style="font-family:sans-serif;padding:2rem;color:#900;">Database connection failed. Please try again later.</p>';
        exit;
    }

    return $pdo;
}