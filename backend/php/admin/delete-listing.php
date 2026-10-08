<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}
requireCsrfToken();

$id = (int)($_POST['listing_id'] ?? 0);
if ($id < 1) {
    jsonResponse(false, 'Invalid listing ID.', [], 422);
}

$listing = $pdo->prepare("SELECT l.id, l.title, l.status, l.seller_id, u.email, CONCAT_WS(' ', u.first_name, u.last_name) AS seller_name FROM listings l JOIN users u ON u.id = l.seller_id WHERE l.id = ? LIMIT 1");
$listing->execute([$id]);
$listingRow = $listing->fetch();

if (!$listingRow) {
    jsonResponse(false, 'Listing not found.', [], 404);
}

$img = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id=?");
$img->execute([$id]);
$paths = $img->fetchAll();

$delete = $pdo->prepare("DELETE FROM listings WHERE id=?");
$delete->execute([$id]);

if ($delete->rowCount() === 0) {
    jsonResponse(false, 'Listing could not be deleted.', [], 409);
}

foreach ($paths as $row) {
    $full = dirname(__DIR__,2).'/'.$row['image_path'];
    if (is_file($full)) @unlink($full);
}

$adminId = requireAdmin($pdo);
writeAuditLog(
    $pdo,
    $adminId,
    'delete_listing',
    'listing',
    (int) $listingRow['id'],
    (int) $listingRow['seller_id'],
    'Administrator permanently deleted listing.',
    'Permanently removed by moderator.'
);

jsonResponse(true,'Listing removed by administrator.');
