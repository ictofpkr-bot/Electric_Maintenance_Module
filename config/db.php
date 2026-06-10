<?php
function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $sqlitePath = getenv('EMS_DB_PATH') ?: __DIR__ . '/../data/ems.sqlite';
    $directory = dirname($sqlitePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $dsn = getenv('EMS_DB_DSN') ?: 'sqlite:' . $sqlitePath;

    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    return $pdo;
}
