<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}
requireCsrfToken();

$id = (int)($_POST['listing_id'] ?? 0);
$status = $_POST['status'] ?? '';
$reasonValue = $_POST['rejection_reason'] ?? '';
if (!is_string($reasonValue)) {
    jsonResponse(false, 'Invalid rejection reason.', [], 422);
}
$reason = trim($reasonValue);

if ($id < 1 || !in_array($status, ['active', 'hidden'], true)) {
    jsonResponse(false, 'Invalid listing update.', [], 422);
}
if ($status === 'hidden' && ($reason === '' || mb_strlen($reason) > 255)) {
    jsonResponse(false, 'A rejection reason of 1 to 255 characters is required.', [], 422);
}

$listingStmt = $pdo->prepare("SELECT l.id, l.title, l.status, l.seller_id, l.price, u.email, CONCAT_WS(' ', u.first_name, u.last_name) AS seller_name FROM listings l JOIN users u ON u.id = l.seller_id WHERE l.id = ? LIMIT 1");
$listingStmt->execute([$id]);
$listing = $listingStmt->fetch();

if (!$listing) {
    jsonResponse(false, 'Listing not found.', [], 404);
}

$previousStatus = $listing['status'];
if (!in_array($previousStatus, ['draft', 'active', 'hidden'], true)) {
    jsonResponse(false, 'Listing cannot be moderated in its current state.', [], 409);
}

$stmt = $pdo->prepare("UPDATE listings SET status = ?, rejection_reason = ? WHERE id = ?");
$stmt->execute([$status, $status === 'hidden' ? $reason : null, $id]);

$adminId = requireAdmin($pdo);
writeAuditLog(
    $pdo,
    $adminId,
    $status === 'active' ? 'approve_listing' : 'reject_listing',
    'listing',
    (int) $listing['id'],
    (int) $listing['seller_id'],
    'Listing ' . ($status === 'active' ? 'approved' : 'hidden') . ' by administrator.',
    $status === 'hidden' ? $reason : null
);

if ($listing['email'] !== '') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'resource.tip.marketplace@gmail.com';
        $mail->Password = 'qdhu rstf fycb eggj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('resource.tip.marketplace@gmail.com', 'ReSource');
        $mail->addAddress($listing['email']);
        $mail->isHTML(true);
        $mail->Subject = $status === 'active' ? 'Your listing was approved on ReSource' : 'Your listing was updated on ReSource';
        $mail->Body = $status === 'active'
            ? "<p>Hi {$listing['seller_name']},</p><p>Your listing <strong>{$listing['title']}</strong> has been approved and is now live on ReSource.</p><p>Thank you for listing with TIP students.</p>"
            : "<p>Hi {$listing['seller_name']},</p><p>Your listing <strong>{$listing['title']}</strong> was hidden by the administrator.</p><p>Reason: <strong>{$reason}</strong></p><p>Please review your item and update it before resubmitting.</p>";
        $mail->AltBody = $status === 'active'
            ? "Your listing {$listing['title']} has been approved and is now live on ReSource."
            : "Your listing {$listing['title']} was hidden by the administrator. Reason: {$reason}";
        $mail->send();
    } catch (Exception $e) {
        // Keep moderation action intact even if the email fails.
    }
}

jsonResponse(true, $status === 'active' ? 'Listing approved.' : 'Listing rejected and hidden.');
