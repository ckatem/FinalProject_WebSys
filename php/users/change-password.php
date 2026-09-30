<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();
$current = (string)($_POST['current_password'] ?? '');
$new = (string)($_POST['new_password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');

$stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
$stmt->execute([$userId]);
$hash = $stmt->fetchColumn();

if (!$hash || !password_verify($current,$hash)) jsonResponse(false,'Your current password is not correct.',[],422);
if (strlen($new) < 6) jsonResponse(false,'Your new password must be at least 6 characters.',[],422);
if ($new !== $confirm) jsonResponse(false,'Your new passwords do not match.',[],422);

$pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$userId]);
jsonResponse(true,'Password updated.');
