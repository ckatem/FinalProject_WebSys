<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

$users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$listings = (int)$pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$active = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='active'")->fetchColumn();
$sold = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='sold'")->fetchColumn();
$reports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();

jsonResponse(true,'Admin dashboard loaded.',[
    'stats'=>compact('users','listings','active','sold','reports')
]);
