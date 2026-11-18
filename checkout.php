<?php
require_once __DIR__ . '/includes/functions.php';

$cartId = getOrCreateCartId();
$items = getCartItems($cartId);

if (empty($items)) {
    flash('cart_error', 'Your cart is empty. Add some products before checking out.');
    redirect(BASE_URL . '/cart.php');
}

foreach ($items as $item) {
    $stockLimit = $item['variant_id'] ? (int)$item['variant_stock'] : (int)$item['product_stock'];
    if ($item['product_status'] !== 'active' || $stockLimit < (int)$item['quantity']) {
        flash('cart_error', 'One or more items in your cart are no longer available in the requested quantity. Please review your cart.');
        redirect(BASE_URL . '/cart.php');
    }
}

$coupon = null;
if (!empty($_SESSION['coupon_code'])) {
    $subtotalCheck = 0;
    foreach ($items as $it) $subtotalCheck += (float)$it['price'] * (int)$it['quantity'];
    $result = validateCoupon($_SESSION['coupon_code'], $subtotalCheck);
    if ($result['valid']) $coupon = $result['coupon'];
}
$totals = getCartTotals($items, $coupon);
$user = currentUser();

$pageTitle = 'Checkout';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h3 class="mb-4">Checkout</h3>
  <div class="row">
    <div class="col-lg-7">
      <div id="checkoutError" class="alert alert-danger d-none"></div>
      <form id="checkoutForm">
        <?= csrfField() ?>
        <h5 class="mb-3">Delivery Information</h5>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-control" required value="<?= e($user['first_name'] ?? '') ?>">
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-control" required value="<?= e($user['last_name'] ?? '') ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" required value="<?= e($user['phone'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Address</label>
          <input type="text" name="address" class="form-control" required value="<?= e($user['address'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-control" required value="<?= e($user['city'] ?? '') ?>">
        </div>

        <h5 class="mb-3 mt-4">Payment Method</h5>
        <div class="p-3 border rounded d-flex align-items-center gap-2" style="border-radius:2px;">
          <input type="radio" checked disabled>
          <div>
            <strong>Cash on Delivery</strong>
            <div class="small text-muted">Pay with cash when your order arrives.</div>
          </div>
        </div>

        <button type="submit" class="btn btn-dark w-100 mt-4" id="placeOrderBtn">Place Order</button>
      </form>
    </div>

    <div class="col-lg-5">
      <div class="summary-box">
        <h5 class="mb-3">Your Order</h5>
        <?php foreach ($items as $item): $lineTotal = (float)$item['price'] * (int)$item['quantity']; ?>
        <div class="d-flex justify-content-between align-items-start mb-2 small">
          <div>
            <div><?= e($item['product_name']) ?> <span class="text-muted">x<?= (int)$item['quantity'] ?></span></div>
            <?php if ($item['size'] || $item['color']): ?>
              <div class="text-muted"><?= $item['size'] ? e($item['size']) : '' ?><?= $item['color'] ? ' / ' . e($item['color']) : '' ?></div>
            <?php endif; ?>
          </div>
          <strong><?= formatPrice($lineTotal) ?></strong>
        </div>
        <?php endforeach; ?>
        <hr>
        <div class="row-line"><span>Subtotal</span><span><?= formatPrice($totals['subtotal']) ?></span></div>
        <?php if ($totals['discount'] > 0): ?>
        <div class="row-line text-success"><span>Discount (<?= e($coupon['code']) ?>)</span><span>-<?= formatPrice($totals['discount']) ?></span></div>
        <?php endif; ?>
        <div class="row-line"><span>Shipping</span><span><?= $totals['shipping'] > 0 ? formatPrice($totals['shipping']) : 'Free' ?></span></div>
        <div class="row-line row-total"><span>Total</span><span><?= formatPrice($totals['total']) ?></span></div>
      </div>
    </div>
  </div>
</div>
<?php $extraScripts = [BASE_URL . '/assets/js/checkout.js']; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
