<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/password-reset-common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}

$email = strtolower(trim($_POST['email'] ?? ''));
$code = trim($_POST['code'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@tip.edu.ph')) {
    jsonResponse(false, 'Please use your TIP institutional email.', [], 422);
}
if (!preg_match('/^\d{6}$/', $code)) {
    jsonResponse(false, 'Please enter the 6-digit reset code.', [], 422);
}
if (strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters.', [], 422);
}
if ($password !== $confirmPassword) {
    jsonResponse(false, 'Passwords do not match.', [], 422);
}

try {
    ensurePasswordResetTable($pdo);

    $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $userStmt->execute([$email]);
    $userId = $userStmt->fetchColumn();

    if (!$userId) {
        jsonResponse(false, 'The reset code is invalid or expired.', [], 422);
    }

    $pdo->beginTransaction();

    $resetStmt = $pdo->prepare(
        'SELECT code_hash, expires_at, attempts
         FROM password_resets WHERE user_id = ? FOR UPDATE'
    );
    $resetStmt->execute([(int) $userId]);
    $reset = $resetStmt->fetch();

    if (!$reset || (int) $reset['expires_at'] < time() || (int) $reset['attempts'] >= 5) {
        $deleteStmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $deleteStmt->execute([(int) $userId]);
        $pdo->commit();
        jsonResponse(false, 'The reset code is invalid or expired.', [], 422);
    }

    if (!password_verify($code, $reset['code_hash'])) {
        $attempts = (int) $reset['attempts'] + 1;
        if ($attempts >= 5) {
            $deleteStmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
            $deleteStmt->execute([(int) $userId]);
        } else {
            $attemptStmt = $pdo->prepare(
                'UPDATE password_resets SET attempts = ? WHERE user_id = ?'
            );
            $attemptStmt->execute([$attempts, (int) $userId]);
        }
        $pdo->commit();
        jsonResponse(false, 'The reset code is invalid or expired.', [], 422);
    }

    $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $updateStmt->execute([password_hash($password, PASSWORD_DEFAULT), (int) $userId]);

    $deleteStmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
    $deleteStmt->execute([(int) $userId]);
    $pdo->commit();

    jsonResponse(true, 'Password reset successfully. You can now log in.');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Password reset failed: ' . $error->getMessage());
    jsonResponse(false, 'Unable to reset the password right now.', [], 500);
}