<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

$stmt = $pdo->query(
    "SELECT l.id,l.title,l.price,l.category,l.status,l.created_at,
            CONCAT_WS(' ',u.first_name,u.last_name) AS seller_name
     FROM listings l JOIN users u ON u.id=l.seller_id
     ORDER BY l.created_at DESC"
);
jsonResponse(true,'Listings loaded.',['listings'=>$stmt->fetchAll()]);
