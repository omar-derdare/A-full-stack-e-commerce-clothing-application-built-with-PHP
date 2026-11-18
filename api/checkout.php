<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrf($token)) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token, please refresh the page.'], 419);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$firstName = trim($input['first_name'] ?? '');
$lastName  = trim($input['last_name'] ?? '');
$phone     = trim($input['phone'] ?? '');
$address   = trim($input['address'] ?? '');
$city      = trim($input['city'] ?? '');

$errors = [];
if ($firstName === '' || $lastName === '') $errors[] = 'Full name is required.';
if ($phone === '' || !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) $errors[] = 'A valid phone number is required.';
if ($address === '') $errors[] = 'Delivery address is required.';
if ($city === '') $errors[] = 'City is required.';

if ($errors) {
    jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
}

$pdo = getDB();
$cartId = getOrCreateCartId();
$items = getCartItems($cartId);

if (empty($items)) {
    jsonResponse(['success' => false, 'message' => 'Your cart is empty.'], 422);
}

// Re-validate stock for every item BEFORE starting the transaction.
foreach ($items as $item) {
    if ($item['product_status'] !== 'active') {
        jsonResponse(['success' => false, 'message' => "\"{$item['product_name']}\" is no longer available."], 422);
    }
    $available = $item['variant_id'] ? (int)$item['variant_stock'] : (int)$item['product_stock'];
    if ((int)$item['quantity'] > $available) {
        jsonResponse(['success' => false, 'message' => "Not enough stock for \"{$item['product_name']}\". Only {$available} left."], 422);
    }
}

// Re-fetch coupon from session (never trust client-sent discount).
$couponCode = $_SESSION['coupon_code'] ?? null;
$coupon = null;
$subtotalCheck = 0;
foreach ($items as $item) {
    $subtotalCheck += (float)$item['price'] * (int)$item['quantity'];
}
if ($couponCode) {
    $result = validateCoupon($couponCode, $subtotalCheck);
    if ($result['valid']) {
        $coupon = $result['coupon'];
    } else {
        unset($_SESSION['coupon_code']);
    }
}

// Server-side authoritative totals (frontend total is never trusted).
$totals = getCartTotals($items, $coupon);

try {
    $pdo->beginTransaction();

    // Lock and re-check stock rows within the transaction to prevent race conditions.
    foreach ($items as $item) {
        if ($item['variant_id']) {
            $lockStmt = $pdo->prepare('SELECT stock FROM product_variants WHERE id = ? FOR UPDATE');
            $lockStmt->execute([$item['variant_id']]);
            $stock = (int)$lockStmt->fetchColumn();
        } else {
            $lockStmt = $pdo->prepare('SELECT stock FROM products WHERE id = ? FOR UPDATE');
            $lockStmt->execute([$item['product_id']]);
            $stock = (int)$lockStmt->fetchColumn();
        }
        if ($stock < (int)$item['quantity']) {
            throw new RuntimeException("Not enough stock for \"{$item['product_name']}\".");
        }
    }

    $orderNumber = generateOrderNumber();
    $userId = currentUserId();
    $customerName = $firstName . ' ' . $lastName;

    $insOrder = $pdo->prepare(
        'INSERT INTO orders (user_id, order_number, subtotal, discount, shipping, total, payment_method, payment_status, order_status, coupon_code, customer_name, phone, address, city, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, "cash_on_delivery", "pending", "pending", ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $insOrder->execute([
        $userId, $orderNumber, $totals['subtotal'], $totals['discount'], $totals['shipping'], $totals['total'],
        $coupon ? $coupon['code'] : null, $customerName, $phone, $address, $city,
    ]);
    $orderId = (int)$pdo->lastInsertId();

    $insItem = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, color, quantity, price, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($items as $item) {
        $lineSubtotal = (float)$item['price'] * (int)$item['quantity'];
        $insItem->execute([
            $orderId, $item['product_id'], $item['variant_id'], $item['product_name'],
            $item['size'], $item['color'], $item['quantity'], $item['price'], $lineSubtotal,
        ]);

        // Reduce stock
        if ($item['variant_id']) {
            $pdo->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ?')
                ->execute([$item['quantity'], $item['variant_id']]);
        }
        $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')
            ->execute([$item['quantity'], $item['product_id']]);
    }

    if ($coupon) {
        $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?')->execute([$coupon['id']]);
    }

    // Clear cart
    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);

    $pdo->commit();

    unset($_SESSION['coupon_code']);

    jsonResponse([
        'success'      => true,
        'order_number' => $orderNumber,
        'redirect'     => BASE_URL . '/order-success.php?order=' . urlencode($orderNumber),
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Checkout error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage() ?: 'Could not place your order. Please try again.'], 422);
}
