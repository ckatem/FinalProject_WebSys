<?php
require_once __DIR__ . '/../config/database.php';
$userId = requireLogin();

$stmt = $pdo->prepare("SELECT l.*,p.purchased_at,p.quantity,
    CONCAT_WS(' ',u.first_name,u.middle_name,u.last_name) AS buyer_name
    FROM purchases p JOIN listings l ON l.id=p.listing_id
    JOIN users u ON u.id=p.buyer_id
    WHERE p.seller_id=? ORDER BY p.purchased_at DESC");
$stmt->execute([$userId]);
jsonResponse(true, 'Sold items loaded.', ['items'=>$stmt->fetchAll()]);
