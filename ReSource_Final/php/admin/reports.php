<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

$stmt = $pdo->query(
    "SELECT r.*,l.title,
            CONCAT_WS(' ',u.first_name,u.last_name) AS reporter_name
     FROM reports r JOIN listings l ON l.id=r.listing_id
     JOIN users u ON u.id=r.reporter_id
     ORDER BY r.created_at DESC"
);
jsonResponse(true,'Reports loaded.',['reports'=>$stmt->fetchAll()]);
