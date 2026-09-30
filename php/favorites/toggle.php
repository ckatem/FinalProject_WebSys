<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();
$listingId = (int)($_POST['listing_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id=? AND listing_id=?");
$stmt->execute([$userId,$listingId]);

if ($stmt->fetch()) {
    $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND listing_id=?")->execute([$userId,$listingId]);
    jsonResponse(true, 'Removed from Saved Items.', ['saved'=>false]);
}

$pdo->prepare("INSERT INTO favorites (user_id,listing_id) VALUES (?,?)")->execute([$userId,$listingId]);
jsonResponse(true, 'Item saved.', ['saved'=>true]);
