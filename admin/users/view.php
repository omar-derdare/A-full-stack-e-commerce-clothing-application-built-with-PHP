<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) {
    flash('error', 'Customer not found.');
    redirect(BASE_URL . '/admin/users/index.php');
}

$ordersStmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$ordersStmt->execute([$id]);
$orders = $ordersStmt->fetchAll();

$statusColors = ['pending'=>'secondary','confirmed'=>'info','processing'=>'primary','shipped'=>'warning','delivered'=>'success','cancelled'=>'danger'];

$pageTitle = $customer['first_name'] . ' ' . $customer['last_name'];
$pageHeading = 'Customer Detail';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="admin-card">
      <h6 class="mb-3">Customer Info</h6>
      <p class="mb-1"><strong><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></strong></p>
      <p class="mb-1 text-muted"><?= e($customer['email']) ?></p>
      <p class="mb-1 text-muted"><?= e($customer['phone']) ?></p>
      <p class="mb-1 text-muted"><?= e($customer['address']) ?> <?= e($customer['city']) ?></p>
      <p class="mb-0 text-muted small">Joined <?= date('M j, Y', strtotime($customer['created_at'])) ?></p>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="admin-card">
      <h6 class="mb-3">Order History</h6>
      <div class="table-responsive">
        <table class="table table-admin">
          <thead><tr><th>Order #</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= e($o['order_number']) ?></td>
              <td><?= formatPrice((float)$o['total']) ?></td>
              <td><span class="badge badge-status bg-<?= $statusColors[$o['order_status']] ?? 'secondary' ?>"><?= e($o['order_status']) ?></span></td>
              <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
              <td><a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($orders)): ?><tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
