<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/password-reset-common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}

$email = strtolower(trim($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@tip.edu.ph')) {
    jsonResponse(false, 'Please use your TIP institutional email.', [], 422);
}

$genericMessage = 'If an account exists for that email, a reset code will be sent.';

try {
    ensurePasswordResetTable($pdo);

    $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $userStmt->execute([$email]);
    $userId = $userStmt->fetchColumn();

    if (!$userId) {
        jsonResponse(true, $genericMessage);
    }

    $resetStmt = $pdo->prepare(
        'SELECT requested_at FROM password_resets WHERE user_id = ? LIMIT 1'
    );
    $resetStmt->execute([(int) $userId]);
    $lastRequestedAt = $resetStmt->fetchColumn();

    if ($lastRequestedAt !== false && time() - (int) $lastRequestedAt < 60) {
        jsonResponse(true, $genericMessage);
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $codeHash = password_hash($code, PASSWORD_DEFAULT);
    $now = time();

    $saveStmt = $pdo->prepare(
        'INSERT INTO password_resets (user_id, code_hash, expires_at, requested_at, attempts)
         VALUES (?, ?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE
            code_hash = VALUES(code_hash),
            expires_at = VALUES(expires_at),
            requested_at = VALUES(requested_at),
            attempts = 0'
    );
    $saveStmt->execute([(int) $userId, $codeHash, $now + 600, $now]);

    if (!sendPasswordResetCode($email, $code)) {
        $deleteStmt = $pdo->prepare(
            'DELETE FROM password_resets WHERE user_id = ? AND code_hash = ?'
        );
        $deleteStmt->execute([(int) $userId, $codeHash]);
    }

    jsonResponse(true, $genericMessage);
} catch (Throwable $error) {
    error_log('Password reset request failed: ' . $error->getMessage());
    jsonResponse(false, 'Unable to process the reset request right now.', [], 500);
}