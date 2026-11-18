<?php
require_once __DIR__ . '/includes/functions.php';

$cartId = getOrCreateCartId();
$items = getCartItems($cartId);

$coupon = null;
if (!empty($_SESSION['coupon_code'])) {
    $subtotalCheck = 0;
    foreach ($items as $it) $subtotalCheck += (float)$it['price'] * (int)$it['quantity'];
    $result = validateCoupon($_SESSION['coupon_code'], $subtotalCheck);
    if ($result['valid']) {
        $coupon = $result['coupon'];
    } else {
        unset($_SESSION['coupon_code']);
    }
}
$totals = getCartTotals($items, $coupon);

$pageTitle = 'Shopping Cart';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section" id="cartPage">
  <h3 class="mb-4">Shopping Cart</h3>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <i class="bi bi-bag"></i>
      <p>Your cart is empty.</p>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-dark mt-2">Continue Shopping</a>
    </div>
  <?php else: ?>
  <div class="row">
    <div class="col-lg-8">
      <?php foreach ($items as $item):
        $lineTotal = (float)$item['price'] * (int)$item['quantity'];
        $stockLimit = $item['variant_id'] ? (int)$item['variant_stock'] : (int)$item['product_stock'];
      ?>
      <div class="cart-item-row d-flex gap-3" data-item-id="<?= $item['id'] ?>">
        <img src="<?= e(productImageUrl($item['featured_image'])) ?>" alt="">
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between">
            <div>
              <a href="<?= BASE_URL ?>/product.php?slug=<?= e($item['product_slug']) ?>" class="fw-medium text-dark"><?= e($item['product_name']) ?></a>
              <?php if ($item['size'] || $item['color']): ?>
                <div class="small text-muted"><?= $item['size'] ? 'Size: ' . e($item['size']) : '' ?> <?= $item['color'] ? ' / Color: ' . e($item['color']) : '' ?></div>
              <?php endif; ?>
              <?php if ($item['product_status'] !== 'active' || $stockLimit <= 0): ?>
                <div class="small text-danger">No longer available</div>
              <?php endif; ?>
            </div>
            <button class="btn btn-sm text-muted remove-item"><i class="bi bi-trash"></i></button>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="qty-selector qty-selector-sm">
              <button type="button" class="qty-dec">−</button>
              <input type="number" class="qty-input" value="<?= (int)$item['quantity'] ?>" min="1" max="<?= $stockLimit ?>">
              <button type="button" class="qty-inc">+</button>
            </div>
            <strong><?= formatPrice($lineTotal) ?></strong>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline-dark mt-3"><i class="bi bi-arrow-left"></i> Continue Shopping</a>
    </div>

    <div class="col-lg-4">
      <div class="summary-box">
        <h5 class="mb-3">Order Summary</h5>

        <?php if ($coupon): ?>
        <div class="d-flex justify-content-between align-items-center mb-2 small">
          <span class="text-success"><i class="bi bi-check-circle"></i> Coupon "<?= e($coupon['code']) ?>" applied</span>
          <button class="btn btn-sm p-0 text-danger" id="removeCouponBtn">Remove</button>
        </div>
        <?php else: ?>
        <form id="couponForm" class="d-flex gap-2 mb-3">
          <input type="text" id="couponInput" class="form-control form-control-sm" placeholder="Coupon code">
          <button class="btn btn-dark btn-sm">Apply</button>
        </form>
        <?php endif; ?>

        <div class="row-line"><span>Subtotal</span><span><?= formatPrice($totals['subtotal']) ?></span></div>
        <?php if ($totals['discount'] > 0): ?>
        <div class="row-line text-success"><span>Discount</span><span>-<?= formatPrice($totals['discount']) ?></span></div>
        <?php endif; ?>
        <div class="row-line"><span>Shipping</span><span><?= $totals['shipping'] > 0 ? formatPrice($totals['shipping']) : 'Free' ?></span></div>
        <div class="row-line row-total"><span>Total</span><span><?= formatPrice($totals['total']) ?></span></div>

        <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-dark w-100 mt-3">Proceed to Checkout</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
