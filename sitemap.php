<?php
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');

$pdo = getDB();
$products = $pdo->query("SELECT slug, updated_at FROM products WHERE status='active'")->fetchAll();
$categories = $pdo->query("SELECT slug FROM categories WHERE status='active'")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= e(BASE_URL) ?>/index.php</loc><priority>1.0</priority></url>
  <url><loc><?= e(BASE_URL) ?>/shop.php</loc><priority>0.9</priority></url>
  <url><loc><?= e(BASE_URL) ?>/about.php</loc><priority>0.5</priority></url>
  <url><loc><?= e(BASE_URL) ?>/contact.php</loc><priority>0.5</priority></url>
  <?php foreach ($categories as $c): ?>
  <url><loc><?= e(BASE_URL) ?>/category.php?slug=<?= urlencode($c['slug']) ?></loc><priority>0.8</priority></url>
  <?php endforeach; ?>
  <?php foreach ($products as $p): ?>
  <url>
    <loc><?= e(BASE_URL) ?>/product.php?slug=<?= urlencode($p['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($p['updated_at'])) ?></lastmod>
    <priority>0.7</priority>
  </url>
  <?php endforeach; ?>
</urlset>
