<?php
require_once __DIR__ . '/../config/db.php';

$pdo = get_db_connection();

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    user_id         INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login_id        VARCHAR(20)      NOT NULL UNIQUE,
    password_hash   VARCHAR(255)     NOT NULL,
    role            ENUM('admin','user','em') NOT NULL,
    full_name       VARCHAR(100)     NOT NULL,
    contact_number  VARCHAR(20)      NULL,
    personal_number VARCHAR(50)      NULL,
    address         VARCHAR(300)     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active       TINYINT(1)       NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaints (
    complaint_id    INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED     NOT NULL,
    description     VARCHAR(500)     NOT NULL,
    status          ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
    submitted_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at       DATETIME         NULL,
    CONSTRAINT fk_complaints_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    message_id      INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
    complaint_id    INT UNSIGNED     NOT NULL,
    sender_id       INT UNSIGNED     NOT NULL,
    message_text    VARCHAR(1000)    NOT NULL,
    sent_at         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id),
    CONSTRAINT fk_messages_sender    FOREIGN KEY (sender_id)    REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
);

$adminPassword = getenv('EMS_ADMIN_PASSWORD') ?: 'admin123';
$sampleAccounts = [
    ['ADM-001',       $adminPassword, 'admin', 'System Administrator', '', '', ''],
    ['USR-2026-0001', 'Password123',  'user',  'Demo User',            '', '', ''],
    ['EM-0001',       'Password123',  'em',    'Demo EM',              '', '', ''],
];

$insert = $pdo->prepare(
    'INSERT IGNORE INTO users
        (login_id, password_hash, role, full_name, contact_number, personal_number, address)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);

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

echo "MySQL database initialised successfully.\n";
echo "Default accounts created (if they did not already exist):\n";
echo "  Admin: ADM-001 / {$adminPassword}\n";
echo "  User:  USR-2026-0001 / Password123\n";
echo "  EM:    EM-0001 / Password123\n";