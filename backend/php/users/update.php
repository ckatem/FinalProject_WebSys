<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	jsonResponse(false, 'POST request required.', [], 405);
}

$fields = [];
$params = [];

foreach (['first_name', 'last_name', 'course', 'campus'] as $field) {
	if (!array_key_exists($field, $_POST)) {
		continue;
	}

	$value = trim((string) $_POST[$field]);
	$maxLengths = [
		'first_name' => 80,
		'last_name' => 80,
		'course' => 100,
		'campus' => 100,
	];

	if ($value === '' || strlen($value) > $maxLengths[$field]) {
		jsonResponse(false, 'Please provide valid profile details.', [], 422);
	}

	if ($field === 'campus' && !in_array($value, ['Manila', 'Quezon City'], true)) {
		jsonResponse(false, 'Please choose a valid campus.', [], 422);
	}

	$fields[] = "{$field} = ?";
	$params[] = $value;
}

if (array_key_exists('middle_name', $_POST)) {
	$middleName = trim((string) $_POST['middle_name']);
	if (strlen($middleName) > 80) {
		jsonResponse(false, 'Please provide valid profile details.', [], 422);
	}
	$fields[] = 'middle_name = ?';
	$params[] = $middleName !== '' ? $middleName : null;
}

foreach ([
	'notify_messages',
	'notify_listings',
	'privacy_photo',
	'privacy_course',
] as $field) {
	if (!array_key_exists($field, $_POST)) {
		continue;
	}

	$value = (string) $_POST[$field];
	if (!in_array($value, ['0', '1'], true)) {
		jsonResponse(false, 'Invalid profile setting.', [], 422);
	}

	$fields[] = "{$field} = ?";
	$params[] = (int) $value;
}

if (!$fields) {
	jsonResponse(true, 'Nothing to update.');
}

$params[] = $userId;
$stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
$stmt->execute($params);

$stmt = $pdo->prepare(
	'SELECT first_name, middle_name, last_name, course, campus
	 FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$userId]);

jsonResponse(true, 'Profile updated.', ['user' => $stmt->fetch()]);
