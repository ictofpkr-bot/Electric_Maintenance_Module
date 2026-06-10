<?php
function generate_login_id(PDO $pdo, string $role): string
{
    $role = strtolower($role);
    $prefix = 'USR';
    if ($role === 'em') {
        $prefix = 'EM';
    } elseif ($role === 'admin') {
        $prefix = 'ADM';
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
    $countStmt->execute([$role]);
    $count = (int) $countStmt->fetchColumn();
    $nextNumber = $count + 1;

    if ($prefix === 'USR') {
        return sprintf('%s-%s-%04d', $prefix, date('Y'), $nextNumber);
    }

    return sprintf('%s-%04d', $prefix, $nextNumber);
}

function generate_password(int $length = 12): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    $password = '';

    for ($index = 0; $index < $length; $index++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return $password;
}

function text_length(string $text): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($text, 'UTF-8');
    }

    return strlen($text);
}

function text_truncate(string $text, int $maxLength, string $suffix = '...'): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $maxLength, $suffix, 'UTF-8');
    }

    if (strlen($text) <= $maxLength) {
        return $text;
    }

    return substr($text, 0, max(0, $maxLength - strlen($suffix))) . $suffix;
}
