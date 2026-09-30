<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(true, 'Not logged in.', ['logged_in' => false, 'user' => null]);
}

$stmt = $pdo->prepare(
    "SELECT id,first_name,middle_name,last_name,student_id,email,course,campus,role,
            profile_photo,notify_messages,notify_listings,privacy_photo,privacy_course,created_at
     FROM users WHERE id=? LIMIT 1"
);
$stmt->execute([(int)$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION = [];
    jsonResponse(true, 'Not logged in.', ['logged_in' => false, 'user' => null]);
}

jsonResponse(true, 'Session active.', ['logged_in' => true, 'user' => $user]);
