<?php
require_once __DIR__ . '/../config/database.php';
$userId = requireLogin();

$stmt = $pdo->prepare(
    "SELECT l.*, CONCAT_WS(' ',u.first_name,u.middle_name,u.last_name) AS seller_name
     FROM favorites f JOIN listings l ON l.id=f.listing_id
     JOIN users u ON u.id=l.seller_id
     WHERE f.user_id=? AND l.status <> 'hidden'
     ORDER BY f.created_at DESC"
);
$stmt->execute([$userId]);
$listings = $stmt->fetchAll();

foreach ($listings as &$l) {
    $img = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id=? ORDER BY sort_order,id");
    $img->execute([(int)$l['id']]);
    $l['images'] = array_column($img->fetchAll(),'image_path');
    $l['image'] = $l['images'][0] ?? '';
    $l['sellerName'] = $l['seller_name'];
    $l['sellerId'] = (int)$l['seller_id'];
    $l['courseCode'] = $l['course_code'];
    $l['condition'] = $l['item_condition'];
    $l['price'] = (float)$l['price'];
}
jsonResponse(true, 'Saved items loaded.', ['listings'=>$listings]);
