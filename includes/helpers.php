<?php
function generate_login_id(PDO $pdo, string $role): string
{
    $role = strtolower($role);

    if ($role === 'em') {
        $prefix = 'EM';
    } elseif ($role === 'admin') {
        $prefix = 'ADM';
    } else {
        $prefix = 'USR';
    }

    // Use MAX on the numeric suffix rather than COUNT so that deactivated
    // users and any gaps in the sequence never cause a duplicate login_id.
    if ($prefix === 'USR') {
        $pattern = 'USR-' . date('Y') . '-%';
        $stmt = $pdo->prepare(
            "SELECT login_id FROM users WHERE login_id LIKE ? ORDER BY user_id DESC LIMIT 1"
        );
        $stmt->execute([$pattern]);
        $last = $stmt->fetchColumn();

        $nextNumber = 1;
        if ($last !== false) {
            // login_id format: USR-YYYY-NNNN
            $parts = explode('-', $last);
            $nextNumber = (int) end($parts) + 1;
        }

        return sprintf('USR-%s-%04d', date('Y'), $nextNumber);
    }

    $pattern = $prefix . '-%';
    $stmt = $pdo->prepare(
        "SELECT login_id FROM users WHERE login_id LIKE ? ORDER BY user_id DESC LIMIT 1"
    );
    $stmt->execute([$pattern]);
    $last = $stmt->fetchColumn();

    $nextNumber = 1;
    if ($last !== false) {
        // login_id format: EM-NNNN or ADM-NNNN
        $parts = explode('-', $last);
        $nextNumber = (int) end($parts) + 1;
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
    return mb_strlen($text, 'UTF-8');
}

function text_truncate(string $text, int $maxLength, string $suffix = '...'): string
{
    if (mb_strlen($text, 'UTF-8') <= $maxLength) {
        return $text;
    }

    $suffixLen = mb_strlen($suffix, 'UTF-8');
    return mb_substr($text, 0, max(0, $maxLength - $suffixLen), 'UTF-8') . $suffix;
}