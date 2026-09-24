<?php
require_once __DIR__ . '/../config/database.php';
$userId = requireLogin();

$listingId = (int)($_POST['listing_id'] ?? 0);
$quantity = max(0, min(99, (int)($_POST['quantity'] ?? 1)));

if ($quantity === 0) {
    $pdo->prepare("DELETE FROM cart_items WHERE user_id=? AND listing_id=?")->execute([$userId,$listingId]);
} else {
    $pdo->prepare("UPDATE cart_items SET quantity=? WHERE user_id=? AND listing_id=?")->execute([$quantity,$userId,$listingId]);
}
jsonResponse(true, 'Cart updated.');
