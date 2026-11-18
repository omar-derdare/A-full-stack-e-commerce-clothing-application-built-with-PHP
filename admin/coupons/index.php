<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE coupons SET status = IF(status='active','inactive','active') WHERE id = ?")->execute([$id]);
    redirect(BASE_URL . '/admin/coupons/index.php');
}

$coupons = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Coupons';
$pageHeading = 'Coupons';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div></div>
  <a href="<?= BASE_URL ?>/admin/coupons/create.php" class="btn btn-primary-admin btn-sm"><i class="bi bi-plus-lg"></i> Add Coupon</a>
</div>

<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<div class="admin-card">
  <div class="table-responsive">
    <table class="table table-admin">
      <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Usage</th><th>Expires</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($coupons as $c): ?>
        <tr>
          <td><strong><?= e($c['code']) ?></strong></td>
          <td class="text-capitalize"><?= e($c['type']) ?></td>
          <td><?= $c['type']==='percentage' ? (float)$c['value'].'%' : formatPrice((float)$c['value']) ?></td>
          <td><?= formatPrice((float)$c['minimum_amount']) ?></td>
          <td><?= (int)$c['used_count'] ?> / <?= $c['usage_limit'] !== null ? (int)$c['usage_limit'] : '∞' ?></td>
          <td><?= $c['expires_at'] ? date('M j, Y', strtotime($c['expires_at'])) : 'Never' ?></td>
          <td><a href="?toggle=<?= $c['id'] ?>" class="badge badge-status bg-<?= $c['status']==='active'?'success':'secondary' ?> text-decoration-none"><?= e($c['status']) ?></a></td>
          <td>
            <a href="<?= BASE_URL ?>/admin/coupons/edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <a href="<?= BASE_URL ?>/admin/coupons/delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm-msg="Delete this coupon?"><i class="bi bi-trash"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($coupons)): ?><tr><td colspan="8" class="text-center text-muted py-4">No coupons yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
