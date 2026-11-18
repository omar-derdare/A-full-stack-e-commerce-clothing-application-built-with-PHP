<?php
require_once __DIR__ . '/includes/functions.php';

$orderNumber = $_GET['order'] ?? '';
$stmt = getDB()->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    redirect(BASE_URL . '/index.php');
}

// Security: only the owner (or a guest session that just placed it) can view this immediately after.
if ($order['user_id'] && $order['user_id'] != currentUserId()) {
    redirect(BASE_URL . '/index.php');
}

$pageTitle = 'Order Confirmed';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section text-center">
  <div class="mx-auto" style="max-width:560px;">
    <i class="bi bi-check-circle-fill text-success" style="font-size:70px;"></i>
    <h2 class="mt-3">Order Placed Successfully!</h2>
    <p class="text-muted">Thank you for your order. We'll deliver it soon — pay in cash when it arrives.</p>

    <div class="summary-box text-start mt-4">
      <div class="row-line"><span>Order Number</span><strong><?= e($order['order_number']) ?></strong></div>
      <div class="row-line"><span>Payment Method</span><span>Cash on Delivery</span></div>
      <div class="row-line"><span>Status</span><span class="text-capitalize"><?= e($order['order_status']) ?></span></div>
      <div class="row-line row-total"><span>Total</span><span><?= formatPrice((float)$order['total']) ?></span></div>
    </div>

    <div class="d-flex gap-2 justify-content-center mt-4">
      <a href="<?= BASE_URL ?>/order-details.php?order=<?= e($order['order_number']) ?>" class="btn btn-dark">View Order Details</a>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline-dark">Continue Shopping</a>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
