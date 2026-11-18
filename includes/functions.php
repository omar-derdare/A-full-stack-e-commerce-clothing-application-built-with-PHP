<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/* =========================================================
   SECURITY HELPERS
   ========================================================= */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verifyCsrf($token)) {
        http_response_code(419);
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh the page.']));
    }
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/* =========================================================
   AUTH HELPERS
   ========================================================= */

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}

function currentUser(): ?array
{
    static $user = null;
    if ($user !== null) {
        return $user ?: null;
    }
    if (!isLoggedIn()) {
        $user = false;
        return null;
    }
    $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([currentUserId()]);
    $user = $stmt->fetch() ?: false;
    return $user ?: null;
}

/* =========================================================
   SETTINGS
   ========================================================= */

function getSettings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    $settings = [];
    try {
        $stmt = getDB()->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        error_log('Settings load error: ' . $e->getMessage());
    }
    return $settings;
}

function setting(string $key, $default = '')
{
    $settings = getSettings();
    return $settings[$key] ?? $default;
}

function formatPrice(float $amount): string
{
    return setting('currency_symbol', '$') . number_format($amount, 2);
}

/* =========================================================
   PRODUCT HELPERS
   ========================================================= */

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    return $text ?: 'n-a';
}

function uniqueSlug(string $table, string $baseSlug, ?int $ignoreId = null): string
{
    $pdo   = getDB();
    $slug  = $baseSlug;
    $i     = 1;
    while (true) {
        $sql    = "SELECT id FROM {$table} WHERE slug = ?" . ($ignoreId ? ' AND id != ?' : '');
        $params = $ignoreId ? [$slug, $ignoreId] : [$slug];
        $stmt   = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $i++;
        $slug = $baseSlug . '-' . $i;
    }
}

function productDisplayPrice(array $product): array
{
    $price = (float)$product['price'];
    $sale  = $product['sale_price'] !== null ? (float)$product['sale_price'] : null;
    $hasSale = $sale !== null && $sale > 0 && $sale < $price;
    $discount = $hasSale ? round((($price - $sale) / $price) * 100) : 0;
    return [
        'price'     => $price,
        'sale'      => $hasSale ? $sale : null,
        'final'     => $hasSale ? $sale : $price,
        'has_sale'  => $hasSale,
        'discount'  => $discount,
    ];
}

function productImageUrl(?string $filename): string
{
    if (empty($filename)) {
        return BASE_URL . '/assets/images/placeholder.jpg';
    }
    return UPLOAD_URL . '/products/' . $filename;
}

function categoryImageUrl(?string $filename): string
{
    if (empty($filename)) {
        return BASE_URL . '/assets/images/placeholder.jpg';
    }
    return UPLOAD_URL . '/categories/' . $filename;
}

function getColors(): array
{
    static $colors = null;
    if ($colors !== null) {
        return $colors;
    }
    try {
        $colors = getDB()->query('SELECT * FROM colors ORDER BY name')->fetchAll();
    } catch (Exception $e) {
        $colors = [];
    }
    return $colors;
}

function colorHex(?string $name): string
{
    if (!$name) {
        return '#cccccc';
    }
    foreach (getColors() as $c) {
        if (strcasecmp($c['name'], $name) === 0) {
            return $c['hex_code'];
        }
    }
    return '#cccccc';
}

function getHeroSlides(): array
{
    try {
        return getDB()->query("SELECT * FROM hero_slides WHERE status = 'active' ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function heroSlideImageUrl(?string $filename): string
{
    if (empty($filename)) {
        return BASE_URL . '/assets/images/placeholder.jpg';
    }
    return UPLOAD_URL . '/slides/' . $filename;
}

function getProductVariants(int $productId): array
{
    $stmt = getDB()->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY size, color');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function getProductImages(int $productId): array
{
    $stmt = getDB()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function getProductAverageRating(int $productId): array
{
    $stmt = getDB()->prepare(
        "SELECT COALESCE(AVG(rating),0) AS avg_rating, COUNT(*) AS total
         FROM reviews WHERE product_id = ? AND status = 'approved'"
    );
    $stmt->execute([$productId]);
    $row = $stmt->fetch();
    return [
        'average' => round((float)$row['avg_rating'], 1),
        'total'   => (int)$row['total'],
    ];
}

/* =========================================================
   FILE UPLOAD HELPER
   ========================================================= */

function handleImageUpload(array $file, string $subfolder): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed with error code ' . $file['error']);
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('File is too large. Maximum size is 5MB.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) {
        throw new RuntimeException('Invalid file type. Allowed: jpg, jpeg, png, webp.');
    }

    // Verify it's actually an image (prevents disguised malicious files).
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }

    $targetDir = UPLOAD_PATH . '/' . $subfolder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $target   = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }

    return $filename;
}

/* =========================================================
   CART HELPERS (session/user aware)
   ========================================================= */

function getOrCreateCartId(): int
{
    $pdo = getDB();
    $userId = currentUserId();

    if ($userId) {
        $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();
        if ($cart) {
            // Merge any guest-session cart items into the user cart.
            mergeGuestCartIntoUserCart((int)$cart['id']);
            return (int)$cart['id'];
        }
        // Maybe there's a guest cart tied to this session -> claim it.
        $sessionId = session_id();
        $stmt = $pdo->prepare('SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL LIMIT 1');
        $stmt->execute([$sessionId]);
        $guestCart = $stmt->fetch();
        if ($guestCart) {
            $stmt = $pdo->prepare('UPDATE carts SET user_id = ? WHERE id = ?');
            $stmt->execute([$userId, $guestCart['id']]);
            return (int)$guestCart['id'];
        }
        $stmt = $pdo->prepare('INSERT INTO carts (user_id, session_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
        $stmt->execute([$userId, $sessionId]);
        return (int)$pdo->lastInsertId();
    }

    $sessionId = session_id();
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL LIMIT 1');
    $stmt->execute([$sessionId]);
    $cart = $stmt->fetch();
    if ($cart) {
        return (int)$cart['id'];
    }
    $stmt = $pdo->prepare('INSERT INTO carts (session_id, created_at, updated_at) VALUES (?, NOW(), NOW())');
    $stmt->execute([$sessionId]);
    return (int)$pdo->lastInsertId();
}

function mergeGuestCartIntoUserCart(int $userCartId): void
{
    $pdo = getDB();
    $sessionId = session_id();
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL LIMIT 1');
    $stmt->execute([$sessionId]);
    $guestCart = $stmt->fetch();
    if (!$guestCart || (int)$guestCart['id'] === $userCartId) {
        return;
    }

    $stmt = $pdo->prepare('SELECT * FROM cart_items WHERE cart_id = ?');
    $stmt->execute([$guestCart['id']]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $existsStmt = $pdo->prepare(
            'SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id <=> ?)'
        );
        $existsStmt->execute([$userCartId, $item['product_id'], $item['variant_id']]);
        $existing = $existsStmt->fetch();
        if ($existing) {
            $upd = $pdo->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE id = ?');
            $upd->execute([$item['quantity'], $existing['id']]);
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $ins->execute([$userCartId, $item['product_id'], $item['variant_id'], $item['quantity'], $item['price']]);
        }
    }
    $del = $pdo->prepare('DELETE FROM carts WHERE id = ?');
    $del->execute([$guestCart['id']]);
}

function getCartItems(int $cartId): array
{
    $stmt = getDB()->prepare(
        "SELECT ci.*, p.name AS product_name, p.slug AS product_slug, p.featured_image, p.stock AS product_stock,
                p.status AS product_status, pv.size, pv.color, pv.stock AS variant_stock
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id
         LEFT JOIN product_variants pv ON pv.id = ci.variant_id
         WHERE ci.cart_id = ?
         ORDER BY ci.id DESC"
    );
    $stmt->execute([$cartId]);
    return $stmt->fetchAll();
}

function getCartTotals(array $items, ?array $coupon = null): array
{
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += (float)$item['price'] * (int)$item['quantity'];
    }

    $discount = 0.0;
    if ($coupon) {
        $discount = calculateCouponDiscount($coupon, $subtotal);
    }

    $afterDiscount = max(0, $subtotal - $discount);
    $shippingPrice = (float)setting('shipping_price', 5);
    $freeThreshold = (float)setting('free_shipping_threshold', 75);
    $shipping = ($afterDiscount >= $freeThreshold && $afterDiscount > 0) ? 0.0 : $shippingPrice;
    if ($subtotal <= 0) {
        $shipping = 0.0;
    }

    $total = $afterDiscount + $shipping;

    return [
        'subtotal' => round($subtotal, 2),
        'discount' => round($discount, 2),
        'shipping' => round($shipping, 2),
        'total'    => round($total, 2),
    ];
}

function calculateCouponDiscount(array $coupon, float $subtotal): float
{
    if ($subtotal < (float)$coupon['minimum_amount']) {
        return 0.0;
    }
    if ($coupon['type'] === 'percentage') {
        $discount = $subtotal * ((float)$coupon['value'] / 100);
        if (!empty($coupon['maximum_discount'])) {
            $discount = min($discount, (float)$coupon['maximum_discount']);
        }
    } else {
        $discount = (float)$coupon['value'];
    }
    return min($discount, $subtotal);
}

function validateCoupon(string $code, float $subtotal): array
{
    $stmt = getDB()->prepare('SELECT * FROM coupons WHERE code = ? LIMIT 1');
    $stmt->execute([strtoupper(trim($code))]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return ['valid' => false, 'message' => 'Coupon code not found.'];
    }
    if ($coupon['status'] !== 'active') {
        return ['valid' => false, 'message' => 'This coupon is no longer active.'];
    }
    if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < strtotime(date('Y-m-d'))) {
        return ['valid' => false, 'message' => 'This coupon has expired.'];
    }
    if ($coupon['usage_limit'] !== null && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'This coupon has reached its usage limit.'];
    }
    if ($subtotal < (float)$coupon['minimum_amount']) {
        return ['valid' => false, 'message' => 'Minimum order of ' . formatPrice((float)$coupon['minimum_amount']) . ' required for this coupon.'];
    }

    return ['valid' => true, 'coupon' => $coupon];
}

function getCartCount(): int
{
    $cartId = getOrCreateCartId();
    $stmt = getDB()->prepare('SELECT COALESCE(SUM(quantity),0) AS c FROM cart_items WHERE cart_id = ?');
    $stmt->execute([$cartId]);
    return (int)$stmt->fetchColumn();
}

function getWishlistCount(): int
{
    if (!isLoggedIn()) {
        return 0;
    }
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id = ?');
    $stmt->execute([currentUserId()]);
    return (int)$stmt->fetchColumn();
}

/* =========================================================
   ORDER HELPERS
   ========================================================= */

function generateOrderNumber(): string
{
    $pdo  = getDB();
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_number LIKE ?");
    $stmt->execute(["ORD-{$year}-%"]);
    $count = (int)$stmt->fetchColumn() + 1;
    return sprintf('ORD-%s-%06d', $year, $count);
}

/* =========================================================
   MISC
   ========================================================= */

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}
