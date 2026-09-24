<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();
$id = (int)($_POST['listing_id'] ?? 0);

$stmt = $pdo->prepare("SELECT seller_id FROM listings WHERE id=?");
$stmt->execute([$id]);
$seller = $stmt->fetchColumn();

if (!$seller) jsonResponse(false, 'Listing not found.', [], 404);
if ((int)$seller !== $userId) jsonResponse(false, 'You can only delete your own listing.', [], 403);

$img = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id=?");
$img->execute([$id]);
$paths = $img->fetchAll();

$pdo->prepare("DELETE FROM listings WHERE id=?")->execute([$id]);

foreach ($paths as $row) {
    $full = dirname(__DIR__,2) . '/' . $row['image_path'];
    if (is_file($full)) @unlink($full);
}

jsonResponse(true, 'Listing deleted.');
