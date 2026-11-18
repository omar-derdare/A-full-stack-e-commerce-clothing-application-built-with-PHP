<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    jsonResponse(['success' => true, 'results' => []]);
}

$like = '%' . $q . '%';
$stmt = getDB()->prepare(
    "SELECT id, name, slug, price, sale_price, featured_image
     FROM products
     WHERE status = 'active' AND (name LIKE ? OR sku LIKE ? OR description LIKE ?)
     ORDER BY name ASC
     LIMIT 6"
);
$stmt->execute([$like, $like, $like]);
$rows = $stmt->fetchAll();

$results = [];
foreach ($rows as $p) {
    $priceInfo = productDisplayPrice($p);
    $results[] = [
        'name'  => $p['name'],
        'slug'  => $p['slug'],
        'image' => productImageUrl($p['featured_image']),
        'price' => formatPrice($priceInfo['final']),
    ];
}

jsonResponse(['success' => true, 'results' => $results]);
