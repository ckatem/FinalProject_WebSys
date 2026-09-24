<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();

$course = trim($_POST['course'] ?? '');
$notifyMessages = isset($_POST['notify_messages']) ? (int)!!$_POST['notify_messages'] : null;
$notifyListings = isset($_POST['notify_listings']) ? (int)!!$_POST['notify_listings'] : null;
$privacyPhoto = isset($_POST['privacy_photo']) ? (int)!!$_POST['privacy_photo'] : null;
$privacyCourse = isset($_POST['privacy_course']) ? (int)!!$_POST['privacy_course'] : null;

$fields=[]; $params=[];
if ($course !== '') { $fields[]='course=?'; $params[]=$course; }
if ($notifyMessages !== null) { $fields[]='notify_messages=?'; $params[]=$notifyMessages; }
if ($notifyListings !== null) { $fields[]='notify_listings=?'; $params[]=$notifyListings; }
if ($privacyPhoto !== null) { $fields[]='privacy_photo=?'; $params[]=$privacyPhoto; }
if ($privacyCourse !== null) { $fields[]='privacy_course=?'; $params[]=$privacyCourse; }

if (!$fields) jsonResponse(true, 'Nothing to update.');

$params[]=$userId;
$pdo->prepare("UPDATE users SET ".implode(',', $fields)." WHERE id=?")->execute($params);

jsonResponse(true, 'Profile settings updated.');
