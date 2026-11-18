<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ORDERS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];
if (!empty($_GET['status'])) { $where[] = 'order_status = ?'; $params[] = $_GET['status']; }
if (!empty($_GET['payment_status'])) { $where[] = 'payment_status = ?'; $params[] = $_GET['payment_status']; }
if (!empty($_GET['date'])) { $where[] = 'DATE(created_at) = ?'; $params[] = $_GET['date']; }
if (!empty($_GET['customer'])) { $where[] = '(customer_name LIKE ? OR order_number LIKE ?)'; $like = '%'.$_GET['customer'].'%'; $params[] = $like; $params[] = $like; }
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM orders WHERE $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusColors = ['pending'=>'secondary','confirmed'=>'info','processing'=>'primary','shipped'=>'warning','delivered'=>'success','cancelled'=>'danger'];

$pageTitle = 'Orders';
$pageHeading = 'Orders';
require_once __DIR__ . '/../includes/header.php';
?>
<form class="d-flex gap-2 flex-wrap mb-3">
  <input type="text" name="customer" class="form-control form-control-sm" placeholder="Search customer or order #" value="<?= e($_GET['customer'] ?? '') ?>" style="width:220px;">
  <select name="status" class="form-select form-select-sm" style="width:150px;">
    <option value="">All Order Status</option>
    <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
    <option value="<?= $s ?>" <?= ($_GET['status'] ?? '')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="payment_status" class="form-select form-select-sm" style="width:150px;">
    <option value="">All Payment Status</option>
    <?php foreach (['pending','paid','failed','refunded'] as $s): ?>
    <option value="<?= $s ?>" <?= ($_GET['payment_status'] ?? '')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="date" class="form-control form-control-sm" value="<?= e($_GET['date'] ?? '') ?>" style="width:160px;">
  <button class="btn btn-sm btn-outline-secondary">Filter</button>
  <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-sm btn-outline-danger">Clear</a>
</form>

<div class="admin-card">
  <div class="table-responsive">
    <table class="table table-admin">
      <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Order Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= e($o['order_number']) ?></td>
          <td><?= e($o['customer_name']) ?><div class="text-muted small"><?= e($o['phone']) ?></div></td>
          <td><?= formatPrice((float)$o['total']) ?></td>
          <td>
            <span class="badge badge-status bg-<?= $o['payment_status']==='paid'?'success':($o['payment_status']==='failed'?'danger':'secondary') ?>"><?= e($o['payment_status']) ?></span>
          </td>
          <td><span class="badge badge-status bg-<?= $statusColors[$o['order_status']] ?? 'secondary' ?>"><?= e($o['order_status']) ?></span></td>
          <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
          <td><a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($orders)): ?><tr><td colspan="7" class="text-center text-muted py-4">No orders found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <nav><ul class="pagination pagination-sm justify-content-center mt-3">
    <?php for ($i=1;$i<=$totalPages;$i++): ?>
      <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
    <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
