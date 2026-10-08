<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();

$stmt = $pdo->prepare(
    "SELECT u.id,u.first_name,u.middle_name,u.last_name,u.course,u.campus,u.profile_photo,
            MAX(m.created_at) AS last_message_at
     FROM messages m
     JOIN users u ON u.id = CASE WHEN m.sender_id=? THEN m.receiver_id ELSE m.sender_id END
     WHERE m.sender_id=? OR m.receiver_id=?
     GROUP BY u.id
     ORDER BY last_message_at DESC"
);
$stmt->execute([$userId,$userId,$userId]);

jsonResponse(true, 'Conversations loaded.', ['users'=>$stmt->fetchAll()]);
