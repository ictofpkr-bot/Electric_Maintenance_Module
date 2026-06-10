<?php
require_once __DIR__ . '/../config/db.php';

$pdo = get_db_connection();

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    user_id INTEGER PRIMARY KEY AUTOINCREMENT,
    login_id TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL,
    full_name TEXT NOT NULL,
    contact_number TEXT,
    personal_number TEXT,
    address TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'open',
    submitted_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    closed_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS messages (
    message_id INTEGER PRIMARY KEY AUTOINCREMENT,
    complaint_id INTEGER NOT NULL,
    sender_id INTEGER NOT NULL,
    message_text TEXT NOT NULL,
    sent_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id),
    FOREIGN KEY (sender_id) REFERENCES users(user_id)
);
SQL
);

$adminPassword = getenv('EMS_ADMIN_PASSWORD') ?: 'admin123';
$sampleAccounts = [
    ['ADM-001', $adminPassword, 'admin', 'System Administrator', '', '', ''],
    ['USR-2026-0001', 'Password123', 'user', 'Demo User', '', '', ''],
    ['EM-0001', 'Password123', 'em', 'Demo EM', '', '', ''],
];

$insert = $pdo->prepare('INSERT OR IGNORE INTO users (login_id, password_hash, role, full_name, contact_number, personal_number, address) VALUES (?, ?, ?, ?, ?, ?, ?)');

foreach ($sampleAccounts as [$loginId, $password, $role, $fullName, $contactNumber, $personalNumber, $address]) {
    $insert->execute([
        $loginId,
        password_hash($password, PASSWORD_DEFAULT),
        $role,
        $fullName,
        $contactNumber,
        $personalNumber,
        $address,
    ]);
}

echo "SQLite database initialized successfully.\n";
echo "Database file: " . (getenv('EMS_DB_PATH') ?: realpath(__DIR__ . '/../data/ems.sqlite')) . "\n";
echo "Default accounts created (if they did not already exist):\n";
echo "  Admin: ADM-001 / {$adminPassword}\n";
echo "  User: USR-2026-0001 / Password123\n";
echo "  EM: EM-0001 / Password123\n";
