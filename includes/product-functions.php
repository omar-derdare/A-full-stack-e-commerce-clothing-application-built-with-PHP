<?php
/**
 * Shared product card renderer. Requires functions.php to already be loaded.
 */
function renderProductCard(array $p, array $wishlistIds = []): void
{
    $priceInfo = productDisplayPrice($p);
    $inWishlist = in_array($p['id'], $wishlistIds);
    static $variantCache = [];
    if (!isset($variantCache[$p['id']])) {
        $variantCache[$p['id']] = count(getProductVariants($p['id'])) > 0;
    }
    $hasVariants = $variantCache[$p['id']];
    $isNew = strtotime($p['created_at']) > strtotime('-21 days');
    ?>
    <div class="col-6 col-md-4 col-lg-3">
      <div class="product-card">
        <div class="product-img-wrap">
          <a href="<?= BASE_URL ?>/product.php?slug=<?= e($p['slug']) ?>">
            <img src="<?= e(productImageUrl($p['featured_image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
          </a>
          <?php if ($priceInfo['has_sale']): ?>
            <span class="badge-sale">-<?= $priceInfo['discount'] ?>%</span>
          <?php elseif ($isNew): ?>
            <span class="badge-new">New</span>
          <?php endif; ?>
          <button class="wishlist-btn <?= $inWishlist ? 'active' : '' ?>" data-product-id="<?= $p['id'] ?>">
            <i class="bi <?= $inWishlist ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
          </button>
          <?php if ($p['stock'] > 0): ?>
          <button class="quick-add-btn" data-product-id="<?= $p['id'] ?>" data-has-variants="<?= $hasVariants ? 1 : 0 ?>" data-slug="<?= e($p['slug']) ?>">
            <?= $hasVariants ? 'Select Options' : 'Quick Add' ?>
          </button>
          <?php endif; ?>
        </div>
        <div class="product-info">
          <div class="product-name"><a href="<?= BASE_URL ?>/product.php?slug=<?= e($p['slug']) ?>" class="text-dark"><?= e($p['name']) ?></a></div>
          <?php if ($p['stock'] <= 0): ?>
            <span class="out-of-stock-label">Out of Stock</span>
          <?php else: ?>
          <div class="price-row">
            <span class="price-current"><?= formatPrice($priceInfo['final']) ?></span>
            <?php if ($priceInfo['has_sale']): ?><span class="price-old"><?= formatPrice($priceInfo['price']) ?></span><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
}
