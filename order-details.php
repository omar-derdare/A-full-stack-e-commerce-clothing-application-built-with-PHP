<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$orderNumber = $_GET['order'] ?? '';
$stmt = getDB()->prepare('SELECT * FROM orders WHERE order_number = ? AND user_id = ? LIMIT 1');
$stmt->execute([$orderNumber, currentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Order Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container section text-center"><div class="empty-state"><i class="bi bi-exclamation-circle"></i><h4 class="mt-3">Order not found</h4><a href="' . BASE_URL . '/orders.php" class="btn btn-dark mt-3">Back to My Orders</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$itemsStmt = getDB()->prepare(
    "SELECT oi.*, p.slug AS product_slug, p.featured_image FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?"
);
$itemsStmt->execute([$order['id']]);
$items = $itemsStmt->fetchAll();

$statusSteps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
$currentStepIndex = array_search($order['order_status'], $statusSteps);

$statusColors = [
    'pending' => 'secondary', 'confirmed' => 'info', 'processing' => 'primary',
    'shipped' => 'warning', 'delivered' => 'success', 'cancelled' => 'danger',
];

$pageTitle = 'Order ' . $order['order_number'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <a href="<?= BASE_URL ?>/orders.php" class="small text-muted"><i class="bi bi-arrow-left"></i> Back to My Orders</a>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2 mb-4">
    <h3 class="mb-0">Order <?= e($order['order_number']) ?></h3>
    <span class="badge bg-<?= $statusColors[$order['order_status']] ?? 'secondary' ?> text-capitalize fs-6"><?= e($order['order_status']) ?></span>
  </div>

  <?php if ($order['order_status'] !== 'cancelled'): ?>
  <div class="d-flex justify-content-between mb-5 flex-wrap gap-2">
    <?php foreach ($statusSteps as $i => $step): ?>
    <div class="text-center flex-grow-1">
      <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center <?= $i <= $currentStepIndex ? 'bg-dark text-white' : 'bg-light text-muted' ?>" style="width:34px;height:34px;">
        <i class="bi bi-check"></i>
      </div>
      <div class="small mt-1 text-capitalize"><?= $step ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-7">
      <h6 class="mb-3">Items</h6>
      <?php foreach ($items as $item): ?>
      <div class="cart-item-row d-flex gap-3">
        <img src="<?= e(productImageUrl($item['featured_image'])) ?>" alt="">
        <div class="flex-grow-1">
          <div><?= e($item['product_name']) ?></div>
          <?php if ($item['size'] || $item['color']): ?>
          <div class="small text-muted"><?= $item['size'] ? 'Size: ' . e($item['size']) : '' ?> <?= $item['color'] ? ' / Color: ' . e($item['color']) : '' ?></div>
          <?php endif; ?>
          <div class="small text-muted">Qty: <?= (int)$item['quantity'] ?> x <?= formatPrice((float)$item['price']) ?></div>
        </div>
        <strong><?= formatPrice((float)$item['subtotal']) ?></strong>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="col-lg-5">
      <div class="summary-box mb-4">
        <h6 class="mb-3">Delivery Address</h6>
        <p class="mb-1"><?= e($order['customer_name']) ?></p>
        <p class="mb-1"><?= e($order['address']) ?>, <?= e($order['city']) ?></p>
        <p class="mb-0"><?= e($order['phone']) ?></p>
      </div>
      <div class="summary-box">
        <h6 class="mb-3">Order Summary</h6>
        <div class="row-line"><span>Subtotal</span><span><?= formatPrice((float)$order['subtotal']) ?></span></div>
        <?php if ($order['discount'] > 0): ?>
        <div class="row-line text-success"><span>Discount <?= $order['coupon_code'] ? '(' . e($order['coupon_code']) . ')' : '' ?></span><span>-<?= formatPrice((float)$order['discount']) ?></span></div>
        <?php endif; ?>
        <div class="row-line"><span>Shipping</span><span><?= $order['shipping'] > 0 ? formatPrice((float)$order['shipping']) : 'Free' ?></span></div>
        <div class="row-line row-total"><span>Total</span><span><?= formatPrice((float)$order['total']) ?></span></div>
        <div class="row-line mt-2"><span>Payment</span><span class="text-capitalize"><?= str_replace('_',' ', e($order['payment_method'])) ?></span></div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
