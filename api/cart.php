<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$pdo = getDB();

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (in_array($action, ['add', 'update', 'remove'], true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrf($token)) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token, please refresh the page.'], 419);
    }
}

switch ($action) {

    case 'add': {
        $productId = (int)($input['product_id'] ?? 0);
        $variantId = isset($input['variant_id']) && $input['variant_id'] !== null ? (int)$input['variant_id'] : null;
        $qty = max(1, (int)($input['quantity'] ?? 1));

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $variants = getProductVariants($productId);
        if ($variants && !$variantId) {
            jsonResponse(['success' => false, 'message' => 'Please select a size/color.'], 422);
        }

        $availableStock = (int)$product['stock'];
        $price = productDisplayPrice($product)['final'];

        if ($variantId) {
            $vStmt = $pdo->prepare('SELECT * FROM product_variants WHERE id = ? AND product_id = ? LIMIT 1');
            $vStmt->execute([$variantId, $productId]);
            $variant = $vStmt->fetch();
            if (!$variant) {
                jsonResponse(['success' => false, 'message' => 'Selected variant not found.'], 404);
            }
            $availableStock = (int)$variant['stock'];
            if (!empty($variant['price'])) {
                $price = (float)$variant['price'];
            }
        }

        if ($availableStock <= 0) {
            jsonResponse(['success' => false, 'message' => 'This item is out of stock.'], 422);
        }

        $cartId = getOrCreateCartId();

        $existsStmt = $pdo->prepare('SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id <=> ?)');
        $existsStmt->execute([$cartId, $productId, $variantId]);
        $existing = $existsStmt->fetch();

        $newQty = $qty + ($existing ? (int)$existing['quantity'] : 0);
        if ($newQty > $availableStock) {
            jsonResponse(['success' => false, 'message' => "Only {$availableStock} item(s) available in stock."], 422);
        }

        if ($existing) {
            $upd = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
            $upd->execute([$newQty, $existing['id']]);
        } else {
            $ins = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $ins->execute([$cartId, $productId, $variantId, $qty, $price]);
        }

        jsonResponse(['success' => true, 'cart_count' => getCartCount()]);
    }

    case 'update': {
        $itemId = (int)($input['item_id'] ?? 0);
        $qty = (int)($input['quantity'] ?? 1);
        $cartId = getOrCreateCartId();

        $stmt = $pdo->prepare('SELECT ci.*, p.stock AS product_stock, pv.stock AS variant_stock FROM cart_items ci JOIN products p ON p.id = ci.product_id LEFT JOIN product_variants pv ON pv.id = ci.variant_id WHERE ci.id = ? AND ci.cart_id = ? LIMIT 1');
        $stmt->execute([$itemId, $cartId]);
        $item = $stmt->fetch();
        if (!$item) {
            jsonResponse(['success' => false, 'message' => 'Cart item not found.'], 404);
        }

        $availableStock = $item['variant_stock'] !== null ? (int)$item['variant_stock'] : (int)$item['product_stock'];
        if ($qty < 1) {
            jsonResponse(['success' => false, 'message' => 'Quantity must be at least 1.'], 422);
        }
        if ($qty > $availableStock) {
            jsonResponse(['success' => false, 'message' => "Only {$availableStock} item(s) available."], 422);
        }

        $upd = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
        $upd->execute([$qty, $itemId]);

        jsonResponse(['success' => true, 'cart_count' => getCartCount()]);
    }

    case 'remove': {
        $itemId = (int)($input['item_id'] ?? 0);
        $cartId = getOrCreateCartId();
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?');
        $stmt->execute([$itemId, $cartId]);
        jsonResponse(['success' => true, 'cart_count' => getCartCount()]);
    }

    case 'count': {
        jsonResponse(['success' => true, 'cart_count' => getCartCount()]);
    }

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
