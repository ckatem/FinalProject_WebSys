<?php
require_once __DIR__ . '/../config/database.php';

$buyerId = requireLogin();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT c.listing_id,c.quantity,l.seller_id,l.title,l.price,l.status,
                (SELECT image_path FROM listing_images li WHERE li.listing_id=l.id ORDER BY li.sort_order,li.id LIMIT 1) AS image
         FROM cart_items c JOIN listings l ON l.id=c.listing_id
         WHERE c.user_id=? FOR UPDATE"
    );
    $stmt->execute([$buyerId]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        $pdo->rollBack();
        jsonResponse(false, 'Your cart is empty.', [], 422);
    }

    $purchased = 0;
    foreach ($rows as $row) {
        if ($row['status'] === 'sold' || (int)$row['seller_id'] === $buyerId) continue;

        $quantity = max(1, min(99, (int)$row['quantity']));
        $now = date('Y-m-d H:i:s');

        $update = $pdo->prepare(
            "UPDATE listings SET status='sold',buyer_id=?,sold_at=?,sold_quantity=? WHERE id=? AND status <> 'sold'"
        );
        $update->execute([$buyerId,$now,$quantity,(int)$row['listing_id']]);

        if ($update->rowCount() === 0) continue;

        $purchase = $pdo->prepare(
            "INSERT INTO purchases (buyer_id,seller_id,listing_id,title,image,price,quantity) VALUES (?,?,?,?,?,?,?)"
        );
        $purchase->execute([$buyerId,(int)$row['seller_id'],(int)$row['listing_id'],$row['title'],$row['image'],$row['price'],$quantity]);
        $purchased++;
    }

    $pdo->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$buyerId]);
    $pdo->commit();

    jsonResponse(true, $purchased ? 'Purchase completed.' : 'No available items could be purchased.', ['purchased'=>$purchased]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, 'Checkout failed.', [], 500);
}
