<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$pdo = getDB();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$where = ["p.status = 'active'"];
$params = [];

// Category filter (comma separated ids)
if (!empty($_GET['category'])) {
    $ids = array_filter(array_map('intval', explode(',', $_GET['category'])));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $where[] = "p.category_id IN ($placeholders)";
        $params = array_merge($params, $ids);
    }
}

// Category by slug (from category.php page)
if (!empty($_GET['category_slug'])) {
    $where[] = "c.slug = ?";
    $params[] = $_GET['category_slug'];
}

// Search query
if (!empty($_GET['q'])) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $like = '%' . $_GET['q'] . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}

// Price range
if (isset($_GET['price_min']) && $_GET['price_min'] !== '') {
    $where[] = "COALESCE(p.sale_price, p.price) >= ?";
    $params[] = (float)$_GET['price_min'];
}
if (isset($_GET['price_max']) && $_GET['price_max'] !== '') {
    $where[] = "COALESCE(p.sale_price, p.price) <= ?";
    $params[] = (float)$_GET['price_max'];
}

// On sale only
if (!empty($_GET['on_sale'])) {
    $where[] = "p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price";
}

// Size / Color via variants
$variantJoin = '';
if (!empty($_GET['size']) || !empty($_GET['color'])) {
    $variantJoin = "INNER JOIN product_variants pv ON pv.product_id = p.id";
    if (!empty($_GET['size'])) {
        $where[] = "pv.size = ?";
        $params[] = $_GET['size'];
    }
    if (!empty($_GET['color'])) {
        $where[] = "pv.color = ?";
        $params[] = $_GET['color'];
    }
}

$whereSql = implode(' AND ', $where);

// Sorting
$sort = $_GET['sort'] ?? 'newest';
switch ($sort) {
    case 'price_low':
        $orderBy = 'COALESCE(p.sale_price, p.price) ASC';
        break;
    case 'price_high':
        $orderBy = 'COALESCE(p.sale_price, p.price) DESC';
        break;
    case 'popular':
        $orderBy = 'avg_rating DESC, review_count DESC';
        break;
    case 'best_selling':
        $orderBy = 'units_sold DESC';
        break;
    default:
        $orderBy = 'p.created_at DESC';
}

$countSql = "SELECT COUNT(DISTINCT p.id) FROM products p
             JOIN categories c ON c.id = p.category_id
             $variantJoin
             WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT DISTINCT p.*,
        (SELECT COALESCE(AVG(rating),0) FROM reviews r WHERE r.product_id = p.id AND r.status='approved') AS avg_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status='approved') AS review_count,
        (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.product_id = p.id) AS units_sold
        FROM products p
        JOIN categories c ON c.id = p.category_id
        $variantJoin
        WHERE $whereSql
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$wishlistIds = [];
if (isLoggedIn()) {
    $w = $pdo->prepare('SELECT product_id FROM wishlists WHERE user_id = ?');
    $w->execute([currentUserId()]);
    $wishlistIds = array_column($w->fetchAll(), 'product_id');
}

$result = [];
foreach ($products as $p) {
    $priceInfo = productDisplayPrice($p);
    $hasVariants = count(getProductVariants((int)$p['id'])) > 0;
    $result[] = [
        'id'             => (int)$p['id'],
        'name'           => $p['name'],
        'slug'           => $p['slug'],
        'image'          => productImageUrl($p['featured_image']),
        'price_final'    => formatPrice($priceInfo['final']),
        'price_original' => formatPrice($priceInfo['price']),
        'has_sale'       => $priceInfo['has_sale'],
        'discount'       => $priceInfo['discount'],
        'in_stock'       => (int)$p['stock'] > 0,
        'has_variants'   => $hasVariants,
        'in_wishlist'    => in_array((int)$p['id'], $wishlistIds),
        'is_new'         => strtotime($p['created_at']) > strtotime('-21 days'),
    ];
}

jsonResponse([
    'success'     => true,
    'products'    => $result,
    'total'       => $total,
    'page'        => $page,
    'total_pages' => $totalPages,
]);
