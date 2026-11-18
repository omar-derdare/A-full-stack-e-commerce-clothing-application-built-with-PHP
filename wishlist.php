<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$pdo = getDB();

$stmt = $pdo->prepare(
    "SELECT w.id AS wishlist_id, p.* FROM wishlists w JOIN products p ON p.id = w.product_id
     WHERE w.user_id = ? ORDER BY w.created_at DESC"
);
$stmt->execute([currentUserId()]);
$items = $stmt->fetchAll();

$pageTitle = 'My Wishlist';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section" id="wishlistPage">
  <h3 class="mb-4">My Wishlist</h3>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <i class="bi bi-heart"></i>
      <p>Your wishlist is empty.</p>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-dark mt-2">Discover Products</a>
    </div>
  <?php else: ?>
  <div class="row">
    <?php foreach ($items as $p):
      $priceInfo = productDisplayPrice($p);
      $hasVariants = count(getProductVariants((int)$p['id'])) > 0;
    ?>
    <div class="col-6 col-md-4 col-lg-3 wishlist-card">
      <div class="product-card">
        <div class="product-img-wrap">
          <a href="<?= BASE_URL ?>/product.php?slug=<?= e($p['slug']) ?>">
            <img src="<?= e(productImageUrl($p['featured_image'])) ?>" alt="<?= e($p['name']) ?>">
          </a>
          <button class="wishlist-remove-btn btn btn-sm position-absolute top-0 end-0 m-2 bg-white rounded-circle" data-product-id="<?= $p['id'] ?>" style="width:34px;height:34px;">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="product-info">
          <div class="product-name"><?= e($p['name']) ?></div>
          <?php if ($p['stock'] <= 0): ?>
            <span class="out-of-stock-label">Out of Stock</span>
          <?php else: ?>
          <div class="price-row"><span class="price-current"><?= formatPrice($priceInfo['final']) ?></span></div>
          <button class="btn btn-dark btn-sm w-100 mt-2 move-to-cart-btn" data-product-id="<?= $p['id'] ?>" data-has-variants="<?= $hasVariants ? 1 : 0 ?>" data-slug="<?= e($p['slug']) ?>">Move to Cart</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
