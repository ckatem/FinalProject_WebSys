<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

$id = (int)($_POST['listing_id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id < 1 || !in_array($status, ['active', 'hidden'], true)) {
    jsonResponse(false, 'Invalid listing update.', [], 422);
}

$stmt = $pdo->prepare("UPDATE listings SET status = ? WHERE id = ? AND status = 'draft'");
$stmt->execute([$status, $id]);

if ($stmt->rowCount() === 0) {
    jsonResponse(false, 'Listing is no longer pending.', [], 409);
}

jsonResponse(true, $status === 'active' ? 'Listing approved.' : 'Listing hidden.');
