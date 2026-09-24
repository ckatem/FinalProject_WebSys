<?php
require_once __DIR__ . '/../config/database.php';

$sellerId = requireLogin();
$listingId = (int)($_POST['listing_id'] ?? 0);
$buyerEmail = strtolower(trim($_POST['buyer_email'] ?? ''));

$stmt = $pdo->prepare("SELECT * FROM listings WHERE id=? AND seller_id=? AND status <> 'sold'");
$stmt->execute([$listingId,$sellerId]);
$listing = $stmt->fetch();

if (!$listing) jsonResponse(false, 'Listing not found or already sold.', [], 404);

$stmt = $pdo->prepare("SELECT id,first_name,middle_name,last_name FROM users WHERE email=?");
$stmt->execute([$buyerEmail]);
$buyer = $stmt->fetch();

if (!$buyer || (int)$buyer['id'] === $sellerId) {
    jsonResponse(false, 'Enter another registered TIP buyer email.', [], 422);
}

try {
    $pdo->beginTransaction();

    $now = date('Y-m-d H:i:s');
    $update = $pdo->prepare("UPDATE listings SET status='sold',buyer_id=?,sold_at=?,sold_quantity=1 WHERE id=? AND seller_id=?");
    $update->execute([(int)$buyer['id'],$now,$listingId,$sellerId]);

    $img = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id=? ORDER BY sort_order,id LIMIT 1");
    $img->execute([$listingId]);
    $image = $img->fetchColumn();

    $purchase = $pdo->prepare(
        "INSERT INTO purchases (buyer_id,seller_id,listing_id,title,image,price,quantity) VALUES (?,?,?,?,?,?,1)"
    );
    $purchase->execute([(int)$buyer['id'],$sellerId,$listingId,$listing['title'],$image,$listing['price']]);

    $pdo->commit();
    jsonResponse(true, 'Listing marked as sold and added to purchase history.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, 'Could not record the sale.', [], 500);
}
