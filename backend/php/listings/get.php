<?php
require_once __DIR__ . '/../config/database.php';

$q = trim($_GET['q'] ?? '');
$campus = trim($_GET['campus'] ?? '');
$department = trim($_GET['department'] ?? '');
$course = trim($_GET['course_code'] ?? '');
$category = trim($_GET['category'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$min = $_GET['min'] !== '' && isset($_GET['min']) ? (float)$_GET['min'] : null;
$max = $_GET['max'] !== '' && isset($_GET['max']) ? (float)$_GET['max'] : null;
$sort = $_GET['sort'] ?? 'newest';

$sql = "SELECT l.*, 
               u.first_name,u.middle_name,u.last_name,u.email,u.course AS seller_course,u.profile_photo,
               CONCAT_WS(' ',u.first_name,u.middle_name,u.last_name) AS seller_name
        FROM listings l
        JOIN users u ON u.id=l.seller_id
        WHERE l.status <> 'hidden'";
$params = [];

if ($q !== '') {
    $sql .= " AND (l.title LIKE ? OR l.description LIKE ? OR l.category LIKE ? OR l.course_code LIKE ? OR l.department LIKE ? OR CONCAT_WS(' ',u.first_name,u.last_name) LIKE ?)";
    $like = "%{$q}%";
    array_push($params,$like,$like,$like,$like,$like,$like);
}
if ($campus !== '') { $sql .= " AND l.campus=?"; $params[]=$campus; }
if ($department !== '') { $sql .= " AND LOWER(l.department)=LOWER(?)"; $params[]=$department; }
if ($course !== '') { $sql .= " AND l.course_code LIKE ?"; $params[]="%{$course}%"; }
if ($category !== '') { $sql .= " AND l.category=?"; $params[]=$category; }
if ($condition !== '') { $sql .= " AND l.item_condition=?"; $params[]=$condition; }
if ($min !== null) { $sql .= " AND l.price>=?"; $params[]=$min; }
if ($max !== null) { $sql .= " AND l.price<=?"; $params[]=$max; }

$order = match ($sort) {
    'price-low' => 'l.price ASC',
    'price-high' => 'l.price DESC',
    'title' => 'l.title ASC',
    default => 'l.created_at DESC'
};
$sql .= " ORDER BY {$order}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

if ($listings) {
    $ids = array_column($listings, 'id');
    $placeholders = implode(',', array_fill(0,count($ids),'?'));
    $imgStmt = $pdo->prepare("SELECT listing_id,image_path FROM listing_images WHERE listing_id IN ($placeholders) ORDER BY sort_order,id");
    $imgStmt->execute($ids);
    $images = [];
    foreach ($imgStmt as $img) {
        $images[$img['listing_id']][] = $img['image_path'];
    }
    foreach ($listings as &$listing) {
        $listing['images'] = $images[$listing['id']] ?? [];
        $listing['image'] = $listing['images'][0] ?? '';
        $listing['sellerId'] = (int)$listing['seller_id'];
        $listing['courseCode'] = $listing['course_code'];
        $listing['sellerName'] = $listing['seller_name'];
        $listing['createdAt'] = $listing['created_at'];
        $listing['status'] = $listing['status'] === 'active' ? 'active' : $listing['status'];
        $listing['condition'] = $listing['item_condition'];
        $listing['price'] = (float)$listing['price'];
        unset($listing['password']);
    }
}

jsonResponse(true, 'Listings loaded.', ['listings' => $listings]);
