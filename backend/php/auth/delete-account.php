<?php

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(
        false,
        'POST request required.',
        [],
        405
    );
}

$userId = requireLogin();

try {

    $pdo->beginTransaction();


    /*
     * 1. Remove user's cart items
     */
    $stmt = $pdo->prepare(
        "DELETE FROM cart_items
         WHERE user_id = ?"
    );
    $stmt->execute([$userId]);


    /*
     * 2. Remove user's favorites
     */
    $stmt = $pdo->prepare(
        "DELETE FROM favorites
         WHERE user_id = ?"
    );
    $stmt->execute([$userId]);


    /*
     * 3. Remove messages sent or received by the user
     */
    $stmt = $pdo->prepare(
        "DELETE FROM messages
         WHERE sender_id = ?
         OR receiver_id = ?"
    );
    $stmt->execute([
        $userId,
        $userId
    ]);


    /*
     * 4. Remove reports made by the user
     */
    $stmt = $pdo->prepare(
        "DELETE FROM reports
         WHERE reporter_id = ?"
    );
    $stmt->execute([$userId]);


    /*
     * 5. Remove purchases involving the user
     */
    $stmt = $pdo->prepare(
        "DELETE FROM purchases
         WHERE buyer_id = ?
         OR seller_id = ?"
    );
    $stmt->execute([
        $userId,
        $userId
    ]);


    /*
     * 6. If the user is a buyer on another listing,
     * simply remove the buyer reference.
     */
    $stmt = $pdo->prepare(
        "UPDATE listings
         SET buyer_id = NULL
         WHERE buyer_id = ?"
    );
    $stmt->execute([$userId]);


    /*
     * 7. Delete images belonging to the user's listings.
     *
     * This must happen BEFORE deleting the listings.
     */
    $stmt = $pdo->prepare(
        "DELETE FROM listing_images
         WHERE listing_id IN (
             SELECT id
             FROM listings
             WHERE seller_id = ?
         )"
    );
    $stmt->execute([$userId]);


    /*
     * 8. Delete listings owned by the user.
     */
    $stmt = $pdo->prepare(
        "DELETE FROM listings
         WHERE seller_id = ?"
    );
    $stmt->execute([$userId]);


    /*
     * 9. Finally delete the user account.
     */
    $stmt = $pdo->prepare(
        "DELETE FROM users
         WHERE id = ?"
    );
    $stmt->execute([$userId]);


    if ($stmt->rowCount() !== 1) {

        $pdo->rollBack();

        jsonResponse(
            false,
            'Unable to delete account.',
            [],
            500
        );
    }


    /*
     * 10. Save all changes.
     */
    $pdo->commit();


    /*
     * 11. Destroy the PHP session.
     */
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();


    /*
     * 12. Tell JavaScript the deletion succeeded.
     */
    jsonResponse(
        true,
        'Account deleted successfully.'
    );


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(
        false,
        'Unable to delete account. Please try again.',
        [],
        500
    );
}