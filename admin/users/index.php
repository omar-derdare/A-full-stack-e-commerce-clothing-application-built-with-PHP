<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ? AND role = 'customer'")->execute([$id]);
    redirect(BASE_URL . '/admin/users/index.php');
}

$q = trim($_GET['q'] ?? '');
$where = "role = 'customer'";
$params = [];
if ($q !== '') {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $like = "%$q%";
    $params = [$like, $like, $like];
}

$stmt = $pdo->prepare(
    "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
            (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.id AND o.order_status != 'cancelled') AS total_spent
     FROM users u WHERE $where ORDER BY created_at DESC"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customers';
$pageHeading = 'Customers';
require_once __DIR__ . '/../includes/header.php';
?>
<form class="mb-3">
  <input type="text" name="q" class="form-control form-control-sm" style="width:260px;" placeholder="Search customers..." value="<?= e($q) ?>">
</form>

<div class="admin-card">
  <div class="table-responsive">
    <table class="table table-admin">
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Status</th><th>Joined</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
          <td><?= e($c['email']) ?></td>
          <td><?= e($c['phone']) ?></td>
          <td><?= (int)$c['order_count'] ?></td>
          <td><?= formatPrice((float)$c['total_spent']) ?></td>
          <td><a href="?toggle=<?= $c['id'] ?>" class="badge badge-status bg-<?= $c['status']==='active'?'success':'secondary' ?> text-decoration-none"><?= e($c['status']) ?></a></td>
          <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
          <td><a href="<?= BASE_URL ?>/admin/users/view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($customers)): ?><tr><td colspan="8" class="text-center text-muted py-4">No customers found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
