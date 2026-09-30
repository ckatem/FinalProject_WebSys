<?php
require_once __DIR__ . '/../config/database.php';
$userId = requireLogin();

$stmt = $pdo->prepare(
    "SELECT c.id,c.listing_id,c.quantity,l.title,l.price,l.status,
            CONCAT_WS(' ',u.first_name,u.middle_name,u.last_name) AS seller_name,
            (SELECT image_path FROM listing_images li WHERE li.listing_id=l.id ORDER BY li.sort_order,li.id LIMIT 1) AS image
     FROM cart_items c JOIN listings l ON l.id=c.listing_id
     JOIN users u ON u.id=l.seller_id
     WHERE c.user_id=? ORDER BY c.added_at DESC"
);
$stmt->execute([$userId]);
jsonResponse(true, 'Cart loaded.', ['cart'=>$stmt->fetchAll()]);
