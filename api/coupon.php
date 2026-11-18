<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$pdo = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrf($token)) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token, please refresh the page.'], 419);
    }
}

switch ($action) {
    case 'apply': {
        $code = trim($input['code'] ?? '');
        if ($code === '') {
            jsonResponse(['success' => false, 'message' => 'Please enter a coupon code.'], 422);
        }

        $cartId = getOrCreateCartId();
        $items = getCartItems($cartId);
        if (empty($items)) {
            jsonResponse(['success' => false, 'message' => 'Your cart is empty.'], 422);
        }
        $totals = getCartTotals($items);

        $result = validateCoupon($code, $totals['subtotal']);
        if (!$result['valid']) {
            jsonResponse(['success' => false, 'message' => $result['message']], 422);
        }

        $_SESSION['coupon_code'] = $result['coupon']['code'];
        jsonResponse(['success' => true, 'message' => 'Coupon applied.']);
    }

    case 'remove': {
        unset($_SESSION['coupon_code']);
        jsonResponse(['success' => true]);
    }

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
