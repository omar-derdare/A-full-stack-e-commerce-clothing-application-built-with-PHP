<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$stmt = getDB()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([currentUserId()]);
$orders = $stmt->fetchAll();

$statusColors = [
    'pending'    => 'secondary',
    'confirmed'  => 'info',
    'processing' => 'primary',
    'shipped'    => 'warning',
    'delivered'  => 'success',
    'cancelled'  => 'danger',
];

$pageTitle = 'My Orders';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h3 class="mb-4">My Orders</h3>

  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <i class="bi bi-box-seam"></i>
      <p>You haven't placed any orders yet.</p>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-dark mt-2">Start Shopping</a>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr><th>Order #</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong><?= e($o['order_number']) ?></strong></td>
          <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
          <td><?= formatPrice((float)$o['total']) ?></td>
          <td class="text-capitalize"><?= str_replace('_',' ', e($o['payment_method'])) ?></td>
          <td><span class="badge bg-<?= $statusColors[$o['order_status']] ?? 'secondary' ?> text-capitalize"><?= e($o['order_status']) ?></span></td>
          <td><a href="<?= BASE_URL ?>/order-details.php?order=<?= e($o['order_number']) ?>" class="btn btn-sm btn-outline-dark">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
