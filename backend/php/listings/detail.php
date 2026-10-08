<?php
require_once __DIR__ . '/../config/database.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid listing ID.', [], 422);

$stmt = $pdo->prepare(
    "SELECT l.*, u.first_name,u.middle_name,u.last_name,u.email,u.course AS seller_course,
            u.campus AS seller_campus,u.profile_photo,
            CONCAT_WS(' ',u.first_name,u.middle_name,u.last_name) AS seller_name
     FROM listings l JOIN users u ON u.id=l.seller_id WHERE l.id=?"
);
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) jsonResponse(false, 'Listing not found.', [], 404);

$img = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id=? ORDER BY sort_order,id");
$img->execute([$id]);
$listing['images'] = array_column($img->fetchAll(),'image_path');
$listing['image'] = $listing['images'][0] ?? '';
$listing['sellerId'] = (int)$listing['seller_id'];
$listing['sellerName'] = $listing['seller_name'];
$listing['courseCode'] = $listing['course_code'];
$listing['condition'] = $listing['item_condition'];
$listing['price'] = (float)$listing['price'];
$listing['createdAt'] = $listing['created_at'];

jsonResponse(true, 'Listing loaded.', ['listing'=>$listing]);
