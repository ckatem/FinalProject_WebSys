<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();
$listingId = (int)($_POST['listing_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}

$allowedReasons = [
    'Prohibited item',
    'Fraud or scam',
    'Misleading or inaccurate listing',
    'Harassment or hateful content',
    'Spam or duplicate listing',
    'Other platform-rule violation',
];

if ($listingId <= 0 || !in_array($reason, $allowedReasons, true)) {
    jsonResponse(false, 'Choose a valid listing and report reason.', [], 422);
}
if (strlen($description) > 1000) {
    jsonResponse(false, 'Report details must be 1000 characters or fewer.', [], 422);
}

$listingStmt = $pdo->prepare('SELECT seller_id FROM listings WHERE id = ? LIMIT 1');
$listingStmt->execute([$listingId]);
$sellerId = $listingStmt->fetchColumn();

if ($sellerId === false) {
    jsonResponse(false, 'Listing not found.', [], 404);
}
if ((int) $sellerId === $userId) {
    jsonResponse(false, 'You cannot report your own listing.', [], 403);
}

$duplicateStmt = $pdo->prepare(
    "SELECT id FROM reports
     WHERE reporter_id = ? AND listing_id = ? AND status = 'pending'
     LIMIT 1"
);
$duplicateStmt->execute([$userId, $listingId]);
if ($duplicateStmt->fetchColumn()) {
    jsonResponse(false, 'You have already reported this listing.', [], 409);
}

$pdo->prepare("INSERT INTO reports (reporter_id,listing_id,reason,description) VALUES (?,?,?,?)")
    ->execute([$userId,$listingId,$reason,$description ?: null]);

jsonResponse(true,'Report submitted.');
