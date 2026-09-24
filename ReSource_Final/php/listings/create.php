<?php
require_once __DIR__ . '/../config/database.php';

$userId = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}

$title = trim($_POST['title'] ?? '');
$courseCode = trim($_POST['course_code'] ?? '');
$department = trim($_POST['department'] ?? '');
$category = trim($_POST['category'] ?? '');
$condition = trim($_POST['condition'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$description = trim($_POST['description'] ?? '');
$campus = trim($_POST['campus'] ?? 'Manila');

if (!$title || !$category || !$condition || !$description || $price <= 0) {
    jsonResponse(false, 'Please complete all required listing fields.', [], 422);
}

$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
$files = $_FILES['images'] ?? null;
$savedFiles = [];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO listings (seller_id,title,course_code,department,category,item_condition,price,description,campus,status)
         VALUES (?,?,?,?,?,?,?,?,?,'active')"
    );
    $stmt->execute([$userId,$title,$courseCode ?: null,$department ?: null,$category,$condition,$price,$description,$campus]);
    $listingId = (int)$pdo->lastInsertId();

    $uploadDir = dirname(__DIR__,2) . '/uploads/listings/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir,0755,true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Could not create the listing image directory.');
    }

    $paths = [];
    if ($files && isset($files['name'])) {
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
        $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

        foreach ($names as $i => $name) {
            if (count($paths) >= 3) break;
            if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if (($sizes[$i] ?? 0) > 1572864) {
                throw new RuntimeException('Each listing image must be 1.5 MB or smaller.');
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpNames[$i]);
            if (!isset($allowed[$mime])) {
                throw new RuntimeException('Only JPG, PNG, and WEBP listing images are allowed.');
            }
            $filename = 'listing_' . $listingId . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            $target = $uploadDir . $filename;
            if (!move_uploaded_file($tmpNames[$i], $target)) {
                throw new RuntimeException('Could not save an uploaded image.');
            }
            $savedFiles[] = $target;
            $relative = 'uploads/listings/' . $filename;
            $imgStmt = $pdo->prepare("INSERT INTO listing_images (listing_id,image_path,sort_order) VALUES (?,?,?)");
            $imgStmt->execute([$listingId,$relative,count($paths)+1]);
            $paths[] = $relative;
        }
    }

    $pdo->commit();

    jsonResponse(true, 'Listing published successfully!', [
        'listing_id'=>$listingId,
        'images'=>$paths
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($savedFiles as $savedFile) {
        if (is_file($savedFile)) @unlink($savedFile);
    }
    jsonResponse(false, $e->getMessage(), [], 422);
}
