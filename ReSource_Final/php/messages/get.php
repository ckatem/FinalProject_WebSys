<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();
$partnerId = (int)($_GET['partner_id'] ?? 0);

if ($partnerId <= 0) jsonResponse(false, 'Partner is required.', [], 422);

$stmt = $pdo->prepare(
    "SELECT m.id,m.sender_id,m.receiver_id,m.listing_id,m.message,m.is_read,m.created_at,
            CONCAT_WS(' ',s.first_name,s.middle_name,s.last_name) AS sender_name,
            CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) AS receiver_name
     FROM messages m
     JOIN users s ON s.id=m.sender_id
     JOIN users r ON r.id=m.receiver_id
     WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
     ORDER BY m.created_at ASC"
);
$stmt->execute([$userId,$partnerId,$partnerId,$userId]);

$pdo->prepare("UPDATE messages SET is_read=1 WHERE receiver_id=? AND sender_id=?")->execute([$userId,$partnerId]);

jsonResponse(true, 'Messages loaded.', ['messages'=>$stmt->fetchAll()]);
