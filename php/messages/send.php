<?php
require_once __DIR__ . '/../config/database.php';

$senderId = requireLogin();
$receiverId = (int)($_POST['receiver_id'] ?? 0);
$listingId = (int)($_POST['listing_id'] ?? 0) ?: null;
$message = trim($_POST['message'] ?? '');

if ($receiverId <= 0 || !$message) jsonResponse(false, 'Message and recipient are required.', [], 422);
if ($receiverId === $senderId) jsonResponse(false, 'You cannot message yourself.', [], 422);

$stmt = $pdo->prepare("SELECT id FROM users WHERE id=?");
$stmt->execute([$receiverId]);
if (!$stmt->fetch()) jsonResponse(false, 'Recipient not found.', [], 404);

$stmt = $pdo->prepare("INSERT INTO messages (sender_id,receiver_id,listing_id,message) VALUES (?,?,?,?)");
$stmt->execute([$senderId,$receiverId,$listingId,$message]);

jsonResponse(true, 'Message sent.', ['message_id'=>(int)$pdo->lastInsertId()]);
