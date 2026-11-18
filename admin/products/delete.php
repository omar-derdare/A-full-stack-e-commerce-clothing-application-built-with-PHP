<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if ($product) {
    // Clean up uploaded images from disk
    if ($product['featured_image']) {
        @unlink(UPLOAD_PATH . '/products/' . $product['featured_image']);
    }
    $images = getProductImages($id);
    foreach ($images as $img) {
        @unlink(UPLOAD_PATH . '/products/' . $img['image']);
    }
    // Foreign keys (product_images, product_variants, cart_items, wishlists) cascade on delete.
    // order_items.product_id is ON DELETE SET NULL, preserving historical order records.
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    flash('success', 'Product deleted.');
} else {
    flash('error', 'Product not found.');
}

redirect(BASE_URL . '/admin/products/index.php');
