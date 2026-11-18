<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please login to use the wishlist.'], 401);
}

$action = $_GET['action'] ?? '';
$pdo = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrf($token)) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token, please refresh the page.'], 419);
    }
}

$userId = currentUserId();
$productId = (int)($input['product_id'] ?? 0);

switch ($action) {
    case 'add': {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status='active' LIMIT 1");
        $stmt->execute([$productId]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
        }
        $ins = $pdo->prepare('INSERT IGNORE INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())');
        $ins->execute([$userId, $productId]);
        jsonResponse(['success' => true, 'wishlist_count' => getWishlistCount()]);
    }

    case 'remove': {
        $stmt = $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
        jsonResponse(['success' => true, 'wishlist_count' => getWishlistCount()]);
    }

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
