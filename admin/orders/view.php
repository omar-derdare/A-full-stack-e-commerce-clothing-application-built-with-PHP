<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) {
    flash('error', 'Order not found.');
    redirect(BASE_URL . '/admin/orders/index.php');
}

$itemsStmt = $pdo->prepare(
    "SELECT oi.*, p.slug AS product_slug, p.featured_image FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?"
);
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$statusColors = ['pending'=>'secondary','confirmed'=>'info','processing'=>'primary','shipped'=>'warning','delivered'=>'success','cancelled'=>'danger'];

$pageTitle = 'Order ' . $order['order_number'];
$pageHeading = 'Order Details';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h5 class="mb-0"><?= e($order['order_number']) ?></h5>
    <span class="text-muted small">Placed <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></span>
  </div>
  <span class="badge badge-status bg-<?= $statusColors[$order['order_status']] ?? 'secondary' ?> fs-6"><?= e($order['order_status']) ?></span>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="admin-card mb-3">
      <h6 class="mb-3">Items</h6>
      <div class="table-responsive">
        <table class="table table-admin">
          <thead><tr><th>Product</th><th>Size/Color</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
          <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td class="d-flex align-items-center gap-2">
                <img src="<?= e(productImageUrl($item['featured_image'])) ?>" class="thumb"><?= e($item['product_name']) ?>
              </td>
              <td><?= e($item['size'] ?: '-') ?> <?= $item['color'] ? '/ ' . e($item['color']) : '' ?></td>
              <td><?= (int)$item['quantity'] ?></td>
              <td><?= formatPrice((float)$item['price']) ?></td>
              <td><?= formatPrice((float)$item['subtotal']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="admin-card">
      <h6 class="mb-3">Update Order Status</h6>
      <div id="statusUpdateError" class="alert alert-danger d-none"></div>
      <form id="statusForm" class="d-flex gap-2 flex-wrap align-items-center">
        <select name="order_status" id="orderStatusSelect" class="form-select" style="width:200px;">
          <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $order['order_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="payment_status" id="paymentStatusSelect" class="form-select" style="width:200px;">
          <?php foreach (['pending','paid','failed','refunded'] as $s): ?>
          <option value="<?= $s ?>" <?= $order['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary-admin" id="updateStatusBtn">Update</button>
      </form>
      <p class="text-muted small mt-2 mb-0">Cancelling an order automatically restores stock for all items.</p>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="admin-card mb-3">
      <h6 class="mb-3">Customer & Delivery</h6>
      <p class="mb-1"><strong><?= e($order['customer_name']) ?></strong></p>
      <p class="mb-1"><?= e($order['address']) ?>, <?= e($order['city']) ?></p>
      <p class="mb-0"><?= e($order['phone']) ?></p>
    </div>
    <div class="admin-card">
      <h6 class="mb-3">Payment Summary</h6>
      <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><span><?= formatPrice((float)$order['subtotal']) ?></span></div>
      <?php if ($order['discount'] > 0): ?>
      <div class="d-flex justify-content-between mb-1 text-success"><span>Discount <?= $order['coupon_code'] ? '('.e($order['coupon_code']).')' : '' ?></span><span>-<?= formatPrice((float)$order['discount']) ?></span></div>
      <?php endif; ?>
      <div class="d-flex justify-content-between mb-1"><span>Shipping</span><span><?= formatPrice((float)$order['shipping']) ?></span></div>
      <hr>
      <div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?= formatPrice((float)$order['total']) ?></span></div>
      <div class="text-muted small mt-2">Payment method: <?= str_replace('_',' ', e($order['payment_method'])) ?></div>
    </div>
  </div>
</div>

<script>
document.getElementById('statusForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const errBox = document.getElementById('statusUpdateError');
  errBox.classList.add('d-none');
  const data = await apiFetch(`${window.BASE_URL}/admin/orders/update-status.php`, {
    method: 'POST',
    body: JSON.stringify({
      order_id: <?= $order['id'] ?>,
      order_status: document.getElementById('orderStatusSelect').value,
      payment_status: document.getElementById('paymentStatusSelect').value
    })
  });
  if (data.success) {
    showToast('Order updated');
    setTimeout(() => window.location.reload(), 600);
  } else {
    errBox.textContent = data.message || 'Could not update order.';
    errBox.classList.remove('d-none');
  }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
