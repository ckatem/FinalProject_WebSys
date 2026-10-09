<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/rating-common.php';

ensureUserRatingsTable($pdo);

$currentUserId = requireLogin();
$profileUserId = (int) ($_GET['user_id'] ?? 0);

if ($profileUserId <= 0) {
    jsonResponse(false, 'Invalid profile.', [], 422);
}

$summaryStmt = $pdo->prepare(
    'SELECT COUNT(*) AS rating_count, COALESCE(AVG(rating), 0) AS average_rating
     FROM user_ratings WHERE reviewee_id = ?'
);
$summaryStmt->execute([$profileUserId]);
$summary = $summaryStmt->fetch();

$eligiblePurchases = [];
if ($currentUserId !== $profileUserId) {
    $eligibleStmt = $pdo->prepare(
        'SELECT p.id, p.title, p.purchased_at
         FROM purchases p
         WHERE ((p.buyer_id = ? AND p.seller_id = ?)
             OR (p.seller_id = ? AND p.buyer_id = ?))
           AND NOT EXISTS (
               SELECT 1 FROM user_ratings r
               WHERE r.purchase_id = p.id AND r.reviewer_id = ?
           )
         ORDER BY p.purchased_at DESC'
    );
    $eligibleStmt->execute([
        $currentUserId,
        $profileUserId,
        $currentUserId,
        $profileUserId,
        $currentUserId,
    ]);
    $eligiblePurchases = $eligibleStmt->fetchAll();
}

jsonResponse(true, 'Ratings loaded.', [
    'rating_count' => (int) $summary['rating_count'],
    'average_rating' => (float) $summary['average_rating'],
    'eligible_purchases' => $eligiblePurchases,
]);