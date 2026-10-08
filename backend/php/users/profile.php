<?php
require_once __DIR__ . '/../config/database.php';

$current = requireLogin();
$id = (int)($_GET['id'] ?? $current);

$stmt = $pdo->prepare(
    "SELECT id,first_name,middle_name,last_name,student_id,email,course,campus,role,profile_photo,
            notify_messages,notify_listings,privacy_photo,privacy_course,created_at
     FROM users WHERE id=?"
);
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) jsonResponse(false, 'User not found.', [], 404);

if ($id !== $current) {
    $user['student_id'] = null;
    $user['email'] = null;
    if (!(int)$user['privacy_photo']) $user['profile_photo'] = null;
    if (!(int)$user['privacy_course']) $user['course'] = null;
}

jsonResponse(true, 'Profile loaded.', ['user'=>$user]);
