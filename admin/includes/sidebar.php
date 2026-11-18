<?php
$currentUri = $_SERVER['REQUEST_URI'];
function navActive($needle, $uri) { return strpos($uri, $needle) !== false ? 'active' : ''; }
?>
<div class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <a href="<?= BASE_URL ?>/admin/index.php"><?= e(setting('store_name', 'LUXE')) ?> <span class="admin-badge">Admin</span></a>
  </div>
  <ul class="sidebar-nav">
    <li><a href="<?= BASE_URL ?>/admin/index.php" class="<?= $currentUri === '/admin/index.php' || basename($currentUri)==='index.php' && strpos($currentUri,'/admin/')!==false && !preg_match('#/admin/\w+/#',$currentUri) ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
    <li><a href="<?= BASE_URL ?>/admin/products/index.php" class="<?= navActive('/admin/products', $currentUri) ?>"><i class="bi bi-tshirt"></i> Products</a></li>
    <li><a href="<?= BASE_URL ?>/admin/categories/index.php" class="<?= navActive('/admin/categories', $currentUri) ?>"><i class="bi bi-tags"></i> Categories</a></li>
    <li><a href="<?= BASE_URL ?>/admin/colors/index.php" class="<?= navActive('/admin/colors', $currentUri) ?>"><i class="bi bi-palette"></i> Colors</a></li>
    <li><a href="<?= BASE_URL ?>/admin/slider/index.php" class="<?= navActive('/admin/slider', $currentUri) ?>"><i class="bi bi-images"></i> Homepage Slider</a></li>
    <li><a href="<?= BASE_URL ?>/admin/orders/index.php" class="<?= navActive('/admin/orders', $currentUri) ?>"><i class="bi bi-bag-check"></i> Orders</a></li>
    <li><a href="<?= BASE_URL ?>/admin/users/index.php" class="<?= navActive('/admin/users', $currentUri) ?>"><i class="bi bi-people"></i> Customers</a></li>
    <li><a href="<?= BASE_URL ?>/admin/coupons/index.php" class="<?= navActive('/admin/coupons', $currentUri) ?>"><i class="bi bi-ticket-perforated"></i> Coupons</a></li>
    <li><a href="<?= BASE_URL ?>/admin/reviews.php" class="<?= navActive('/admin/reviews', $currentUri) ?>"><i class="bi bi-star"></i> Reviews</a></li>
    <li><a href="<?= BASE_URL ?>/admin/messages.php" class="<?= navActive('/admin/messages', $currentUri) ?>"><i class="bi bi-envelope"></i> Messages</a></li>
    <li><a href="<?= BASE_URL ?>/admin/subscribers.php" class="<?= navActive('/admin/subscribers', $currentUri) ?>"><i class="bi bi-newspaper"></i> Newsletter</a></li>
    <li><a href="<?= BASE_URL ?>/admin/settings/index.php" class="<?= navActive('/admin/settings', $currentUri) ?>"><i class="bi bi-gear"></i> Settings</a></li>
  </ul>
</div>
