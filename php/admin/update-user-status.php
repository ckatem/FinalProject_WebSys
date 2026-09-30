<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}
requireCsrfToken();

$userId = (int) ($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';
$reasonValue = $_POST['reason'] ?? '';
$allowedActions = ['activate', 'suspend', 'ban'];

if ($userId < 1 || !in_array($action, $allowedActions, true) || !is_string($reasonValue)) {
    jsonResponse(false, 'Invalid user action.', [], 422);
}
$reason = trim($reasonValue);

if ($action !== 'activate' && ($reason === '' || mb_strlen($reason) > 255)) {
    jsonResponse(false, 'A reason of 1 to 255 characters is required.', [], 422);
}

$userStmt = $pdo->prepare(
    "SELECT id, first_name, middle_name, last_name, email, status, moderation_reason FROM users WHERE id = ? LIMIT 1"
);
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
if (!$user) {
    jsonResponse(false, 'User not found.', [], 404);
}

$status = $action === 'activate' ? 'active' : ($action === 'suspend' ? 'suspended' : 'banned');
$moderationReason = $action === 'activate' ? null : $reason;
$suspendedAt = $action === 'activate' ? null : date('Y-m-d H:i:s');

$update = $pdo->prepare(
    "UPDATE users SET status = ?, moderation_reason = ?, suspended_at = ? WHERE id = ?"
);
$update->execute([$status, $moderationReason, $suspendedAt, $userId]);

$adminId = requireAdmin($pdo);
writeAuditLog(
    $pdo,
    $adminId,
    $action,
    'user',
    null,
    $userId,
    'User account status changed to ' . $status . '.',
    $moderationReason
);

jsonResponse(true, $action === 'activate' ? 'User account reactivated.' : ($action === 'suspend' ? 'User suspended.' : 'User banned.'));
