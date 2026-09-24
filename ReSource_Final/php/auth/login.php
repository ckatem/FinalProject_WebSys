<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}

$email = strtolower(trim($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$requestedRole = $_POST['role'] ?? 'student';

if (!in_array($requestedRole, ['student', 'admin'], true)) {
    jsonResponse(false, 'Please choose a valid account type.', [], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@tip.edu.ph')) {
    jsonResponse(false, 'Please use your TIP institutional email.', [], 422);
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(false, 'Incorrect email or password.', [], 401);
}

if ($user['role'] !== $requestedRole) {
    jsonResponse(false, 'This account does not have the selected account type.', [], 403);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];

unset($user['password']);

jsonResponse(true, 'Login successful.', ['user' => $user]);
