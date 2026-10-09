<?php
declare(strict_types=1);

$host = 'localhost';
$db   = 'resource_marketplace';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function jsonResponse(bool $success, string $message = '', array $data = [], int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

function requireLogin(): int {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(false, 'Please log in first.', [], 401);
    }
    return (int) $_SESSION['user_id'];
}

function requireAdmin(PDO $pdo): int {
    $id = requireLogin();
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $role = $stmt->fetchColumn();
    if ($role !== 'admin') {
        jsonResponse(false, 'Administrator access required.', [], 403);
    }
    return $id;
}

function requireCsrfToken(): void {
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['admin_csrf_token'] ?? '';
    if (!is_string($provided) || !is_string($expected) || $expected === '' || !hash_equals($expected, $provided)) {
        jsonResponse(false, 'Invalid or missing security token.', [], 419);
    }
}

function writeAuditLog(PDO $pdo, int $adminId, string $action, string $entityType, ?int $targetId = null, ?int $targetUserId = null, ?string $details = null, ?string $reason = null): void {
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (admin_id, action, entity_type, target_id, target_user_id, details, reason) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $adminId,
        $action,
        $entityType,
        $targetId,
        $targetUserId,
        $details !== null ? $details : null,
        $reason !== null && trim($reason) !== '' ? trim($reason) : null,
    ]);
}