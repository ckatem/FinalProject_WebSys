<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

$stmt = $pdo->query("SELECT id,first_name,middle_name,last_name,student_id,email,course,campus,role,created_at FROM users ORDER BY created_at DESC");
jsonResponse(true,'Users loaded.',['users'=>$stmt->fetchAll()]);
