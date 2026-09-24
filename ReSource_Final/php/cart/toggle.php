<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();
$listingId = (int)($_POST['listing_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id,status FROM listings WHERE id=?");
$stmt->execute([$listingId]);
$listing = $stmt->fetch();

if (!$listing || $listing['status'] === 'sold') jsonResponse(false, 'This listing is no longer available.', [], 422);

$stmt = $pdo->prepare("SELECT id FROM cart_items WHERE user_id=? AND listing_id=?");
$stmt->execute([$userId,$listingId]);

if ($stmt->fetch()) {
    $pdo->prepare("DELETE FROM cart_items WHERE user_id=? AND listing_id=?")->execute([$userId,$listingId]);
    jsonResponse(true, 'Removed from cart.', ['in_cart'=>false]);
}

$pdo->prepare("INSERT INTO cart_items (user_id,listing_id,quantity) VALUES (?,?,1)")->execute([$userId,$listingId]);
jsonResponse(true, 'Item added to your cart.', ['in_cart'=>true]);
