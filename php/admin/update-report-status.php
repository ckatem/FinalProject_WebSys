<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}
requireCsrfToken();

$reportId = (int) ($_POST['report_id'] ?? 0);
$action = $_POST['action'] ?? '';
$adminNoteValue = $_POST['admin_note'] ?? '';
$allowedActions = ['dismiss', 'review', 'resolve_hide'];

if ($reportId < 1 || !in_array($action, $allowedActions, true) || !is_string($adminNoteValue)) {
    jsonResponse(false, 'Invalid report action.', [], 422);
}
$adminNote = trim($adminNoteValue);
if (mb_strlen($adminNote) > 5000) {
    jsonResponse(false, 'Admin notes must not exceed 5000 characters.', [], 422);
}

try {
    $pdo->beginTransaction();

    $reportQuery = $pdo->prepare('SELECT id, listing_id, reporter_id, status FROM reports WHERE id = ?');
    $reportQuery->execute([$reportId]);
    $report = $reportQuery->fetch();
    if (!$report) {
        $pdo->rollBack();
        jsonResponse(false, 'Report not found.', [], 404);
    }

    $listingLock = $pdo->prepare('SELECT status, seller_id, title FROM listings WHERE id = ? FOR UPDATE');
    $listingLock->execute([(int) $report['listing_id']]);
    $listing = $listingLock->fetch();
    if (!$listing) {
        $pdo->rollBack();
        jsonResponse(false, 'Reported listing not found.', [], 404);
    }
    $listingPreviousStatus = $listing['status'];

    if ($report['status'] !== 'pending') {
        $pdo->rollBack();
        jsonResponse(false, 'Report has already been handled.', [], 409);
    }

    if ($action === 'resolve_hide') {
        $updateListing = $pdo->prepare("UPDATE listings SET status = 'hidden', rejection_reason = 'Hidden by moderator after report review.' WHERE id = ?");
        $updateListing->execute([(int) $report['listing_id']]);

        $resolveReports = $pdo->prepare(
            "UPDATE reports SET status = 'resolved', admin_note = ?
             WHERE listing_id = ? AND status = 'pending'"
        );
        $resolveReports->execute([$adminNote !== '' ? $adminNote : null, (int) $report['listing_id']]);
        $affectedReports = $resolveReports->rowCount();
        $message = 'Report resolved and listing hidden.';
    } else {
        $newStatus = $action === 'dismiss' ? 'dismissed' : 'reviewed';
        $updateReport = $pdo->prepare(
            "UPDATE reports SET status = ?, admin_note = ? WHERE id = ? AND status = 'pending'"
        );
        $updateReport->execute([$newStatus, $adminNote !== '' ? $adminNote : null, $reportId]);
        if ($updateReport->rowCount() !== 1) {
            $pdo->rollBack();
            jsonResponse(false, 'Report has already been handled.', [], 409);
        }
        $affectedReports = 1;
        $message = $newStatus === 'dismissed' ? 'Report dismissed.' : 'Report marked reviewed.';
    }

    $adminId = requireAdmin($pdo);
    $reason = $action === 'resolve_hide' ? 'Hidden after report review.' : ($action === 'dismiss' ? 'Report dismissed by moderator.' : 'Report reviewed by moderator.');
    writeAuditLog(
        $pdo,
        $adminId,
        $action,
        'report',
        (int) $report['id'],
        (int) $report['reporter_id'],
        'Handled report for listing ' . (int) $report['listing_id'],
        $reason
    );

    $pdo->commit();
    jsonResponse(true, $message, [
        'listing_id' => (int) $report['listing_id'],
        'status' => $action === 'dismiss' ? 'dismissed' : ($action === 'review' ? 'reviewed' : 'resolved'),
        'affected_reports' => $affectedReports,
        'listing_previous_status' => $listingPreviousStatus,
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, 'Unable to update the report.', [], 500);
}