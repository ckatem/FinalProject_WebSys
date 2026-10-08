<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'No profile photo was uploaded.', [], 422);
}
if ($_FILES['photo']['size'] > 1572864) {
    jsonResponse(false, 'Profile photo must be 1.5 MB or smaller.', [], 422);
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['photo']['tmp_name']);
$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
if (!isset($allowed[$mime])) jsonResponse(false, 'Only JPG, PNG, and WEBP are allowed.', [], 422);

$dir = dirname(__DIR__,2).'/uploads/profiles/';
if (!is_dir($dir)) mkdir($dir,0755,true);

$filename = 'profile_'.$userId.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];
$relative = 'uploads/profiles/'.$filename;

if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dir.$filename)) {
    jsonResponse(false, 'Could not save profile photo.', [], 500);
}

$stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id=?");
$stmt->execute([$userId]);
$old = $stmt->fetchColumn();

$pdo->prepare("UPDATE users SET profile_photo=? WHERE id=?")->execute([$relative,$userId]);

if ($old && is_file(dirname(__DIR__,2).'/'.$old)) @unlink(dirname(__DIR__,2).'/'.$old);

jsonResponse(true, 'Profile photo updated.', ['profile_photo'=>$relative]);
