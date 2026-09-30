<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

$stmt = $pdo->query(
    "SELECT a.id, a.action, a.entity_type, a.target_id, a.details, a.reason, a.created_at,
            CONCAT_WS(' ', admin.first_name, admin.last_name) AS admin_name,
            CONCAT_WS(' ', target.first_name, target.last_name) AS target_name
     FROM audit_logs a
     LEFT JOIN users admin ON admin.id = a.admin_id
     LEFT JOIN users target ON target.id = a.target_user_id
     ORDER BY a.created_at DESC
     LIMIT 100"
);

jsonResponse(true, 'Audit log loaded.', ['audit_logs' => $stmt->fetchAll()]);
