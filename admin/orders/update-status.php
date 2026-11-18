<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrf($token)) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token, please refresh the page.'], 419);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$orderId = (int)($input['order_id'] ?? 0);
$newOrderStatus = $input['order_status'] ?? '';
$newPaymentStatus = $input['payment_status'] ?? '';

$validOrderStatuses = ['pending','confirmed','processing','shipped','delivered','cancelled'];
$validPaymentStatuses = ['pending','paid','failed','refunded'];

if (!in_array($newOrderStatus, $validOrderStatuses, true) || !in_array($newPaymentStatus, $validPaymentStatuses, true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid status value.'], 422);
}

$pdo = getDB();

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) {
    jsonResponse(['success' => false, 'message' => 'Order not found.'], 404);
}

try {
    $pdo->beginTransaction();

    // If transitioning INTO cancelled from a non-cancelled state, restore stock.
    if ($newOrderStatus === 'cancelled' && $order['order_status'] !== 'cancelled') {
        $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as $item) {
            if ($item['variant_id']) {
                $pdo->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?')
                    ->execute([$item['quantity'], $item['variant_id']]);
            }
            if ($item['product_id']) {
                $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')
                    ->execute([$item['quantity'], $item['product_id']]);
            }
        }
    }

    // If moving OUT of cancelled back into an active status, deduct stock again.
    if ($order['order_status'] === 'cancelled' && $newOrderStatus !== 'cancelled') {
        $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as $item) {
            if ($item['variant_id']) {
                $pdo->prepare('UPDATE product_variants SET stock = GREATEST(0, stock - ?) WHERE id = ?')
                    ->execute([$item['quantity'], $item['variant_id']]);
            }
            if ($item['product_id']) {
                $pdo->prepare('UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?')
                    ->execute([$item['quantity'], $item['product_id']]);
            }
        }
    }

    $pdo->prepare('UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?')
        ->execute([$newOrderStatus, $newPaymentStatus, $orderId]);

    $pdo->commit();
    jsonResponse(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Order status update error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Could not update order status.'], 500);
}
