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

$directoryStmt = $pdo->prepare(
    "SELECT id,first_name,middle_name,last_name,course,campus,profile_photo,privacy_photo
     FROM users
     WHERE id<>?
     ORDER BY first_name,last_name"
);
$directoryStmt->execute([$userId]);

$directory = $directoryStmt->fetchAll();
foreach ($directory as &$directoryUser) {
    if (!(int)$directoryUser['privacy_photo']) {
        $directoryUser['profile_photo'] = null;
    }
}
unset($directoryUser);

jsonResponse(true, 'Conversations loaded.', [
    'users'=>$stmt->fetchAll(),
    'directory'=>$directory
]);
