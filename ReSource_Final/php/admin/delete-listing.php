<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

$id = (int)($_POST['listing_id'] ?? 0);

$img = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id=?");
$img->execute([$id]);
$paths = $img->fetchAll();

$delete = $pdo->prepare("DELETE FROM listings WHERE id=? AND status='draft'");
$delete->execute([$id]);

if ($delete->rowCount() === 0) {
    jsonResponse(false, 'Listing is no longer pending.', [], 409);
}

foreach ($paths as $row) {
    $full = dirname(__DIR__,2).'/'.$row['image_path'];
    if (is_file($full)) @unlink($full);
}

jsonResponse(true,'Listing removed by administrator.');
