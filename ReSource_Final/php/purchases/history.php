<?php
require_once __DIR__ . '/../config/database.php';
$userId = requireLogin();

$stmt = $pdo->prepare("SELECT * FROM purchases WHERE buyer_id=? ORDER BY purchased_at DESC");
$stmt->execute([$userId]);
jsonResponse(true, 'Purchase history loaded.', ['purchases'=>$stmt->fetchAll()]);
