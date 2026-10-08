<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();
$listingId = (int)($_POST['listing_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!$listingId || !$reason) jsonResponse(false,'Listing and reason are required.',[],422);

$pdo->prepare("INSERT INTO reports (reporter_id,listing_id,reason,description) VALUES (?,?,?,?)")
    ->execute([$userId,$listingId,$reason,$description ?: null]);

jsonResponse(true,'Report submitted.');
