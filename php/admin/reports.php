<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

$stmt = $pdo->query(
    "SELECT r.*,l.title,l.status AS listing_status,
        CONCAT_WS(' ',seller.first_name,seller.last_name) AS seller_name,
        CONCAT_WS(' ',reporter.first_name,reporter.last_name) AS reporter_name,
        (SELECT image_path FROM listing_images li
         WHERE li.listing_id=l.id ORDER BY li.sort_order,li.id LIMIT 1) AS listing_image
     FROM reports r
     JOIN listings l ON l.id=r.listing_id
     JOIN users reporter ON reporter.id=r.reporter_id
     JOIN users seller ON seller.id=l.seller_id
     ORDER BY CASE r.status WHEN 'pending' THEN 0 ELSE 1 END, r.created_at DESC"
);
jsonResponse(true, 'Reports loaded.', ['reports' => $stmt->fetchAll()]);
