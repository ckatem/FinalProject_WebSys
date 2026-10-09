<?php
require_once __DIR__ . '/../config/database.php';

$buyerId = requireLogin();

try {
    $pdo->beginTransaction();

    if (isset($_POST['items'])) {
        $requestedItems = json_decode((string) $_POST['items'], true);
        if (!is_array($requestedItems) || !$requestedItems || count($requestedItems) > 30) {
            $pdo->rollBack();
            jsonResponse(false, 'Your cart contains invalid items.', [], 422);
        }

        $quantities = [];
        foreach ($requestedItems as $item) {
            $listingId = filter_var($item['listing_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if (!$listingId || !$quantity || $quantity > 99) {
                $pdo->rollBack();
                jsonResponse(false, 'Your cart contains invalid items.', [], 422);
            }
            $quantities[$listingId] = min(99, ($quantities[$listingId] ?? 0) + $quantity);
        }

        $listingIds = array_keys($quantities);
        $placeholders = implode(',', array_fill(0, count($listingIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT l.id AS listing_id,l.seller_id,l.title,l.price,l.status,
                    (SELECT image_path FROM listing_images li WHERE li.listing_id=l.id ORDER BY li.sort_order,li.id LIMIT 1) AS image
             FROM listings l WHERE l.id IN ($placeholders) FOR UPDATE"
        );
        $stmt->execute($listingIds);
        $databaseListings = [];
        foreach ($stmt->fetchAll() as $listing) {
            $databaseListings[(int) $listing['listing_id']] = $listing;
        }

        if (count($databaseListings) !== count($quantities)) {
            $pdo->rollBack();
            jsonResponse(false, 'One or more listings could not be found.', [], 404);
        }

        $rows = [];
        foreach ($quantities as $listingId => $quantity) {
            $row = $databaseListings[$listingId];
            if ($row['status'] === 'sold') {
                $pdo->rollBack();
                jsonResponse(false, 'One or more listings have already been sold.', [], 409);
            }
            if ((int) $row['seller_id'] === $buyerId) {
                $pdo->rollBack();
                jsonResponse(false, 'You cannot purchase your own listing.', [], 403);
            }
            $row['quantity'] = $quantity;
            $rows[] = $row;
        }
    } else {
        $stmt = $pdo->prepare(
            "SELECT c.listing_id,c.quantity,l.seller_id,l.title,l.price,l.status,
                    (SELECT image_path FROM listing_images li WHERE li.listing_id=l.id ORDER BY li.sort_order,li.id LIMIT 1) AS image
             FROM cart_items c JOIN listings l ON l.id=c.listing_id
             WHERE c.user_id=? FOR UPDATE"
        );
        $stmt->execute([$buyerId]);
        $rows = $stmt->fetchAll();
    }

    if (!$rows) {
        $pdo->rollBack();
        jsonResponse(false, 'Your cart is empty.', [], 422);
    }

    $purchased = 0;
    $purchasedListingIds = [];
    foreach ($rows as $row) {
        if ($row['status'] === 'sold' || (int)$row['seller_id'] === $buyerId) continue;

        $quantity = max(1, min(99, (int)$row['quantity']));
        $now = date('Y-m-d H:i:s');

        $update = $pdo->prepare(
            "UPDATE listings SET status='sold',buyer_id=?,sold_at=?,sold_quantity=? WHERE id=? AND status <> 'sold'"
        );
        $update->execute([$buyerId,$now,$quantity,(int)$row['listing_id']]);

        if ($update->rowCount() === 0) continue;

        $purchase = $pdo->prepare(
            "INSERT INTO purchases (buyer_id,seller_id,listing_id,title,image,price,quantity) VALUES (?,?,?,?,?,?,?)"
        );
        $purchase->execute([$buyerId,(int)$row['seller_id'],(int)$row['listing_id'],$row['title'],$row['image'],$row['price'],$quantity]);
        $purchased++;
        $purchasedListingIds[] = (int) $row['listing_id'];
    }

    if (isset($_POST['items']) && $purchasedListingIds) {
        $purchasedPlaceholders = implode(',', array_fill(0, count($purchasedListingIds), '?'));
        $deleteCart = $pdo->prepare(
            "DELETE FROM cart_items WHERE user_id = ? AND listing_id IN ($purchasedPlaceholders)"
        );
        $deleteCart->execute(array_merge([$buyerId], $purchasedListingIds));
    } elseif (!isset($_POST['items'])) {
        $pdo->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$buyerId]);
    }
    $pdo->commit();

    jsonResponse(true, $purchased ? 'Purchase completed.' : 'No available items could be purchased.', [
        'purchased' => $purchased,
        'purchased_listing_ids' => $purchasedListingIds,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, 'Checkout failed.', [], 500);
}
