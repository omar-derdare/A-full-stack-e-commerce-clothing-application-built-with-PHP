<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();

if (isset($_GET['approve'])) {
    $pdo->prepare("UPDATE reviews SET status='approved' WHERE id = ?")->execute([(int)$_GET['approve']]);
    redirect(BASE_URL . '/admin/reviews.php');
}
if (isset($_GET['reject'])) {
    $pdo->prepare("UPDATE reviews SET status='rejected' WHERE id = ?")->execute([(int)$_GET['reject']]);
    redirect(BASE_URL . '/admin/reviews.php');
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([(int)$_GET['delete']]);
    redirect(BASE_URL . '/admin/reviews.php');
}

$filter = $_GET['status'] ?? '';
$where = $filter ? 'WHERE r.status = ?' : '';
$params = $filter ? [$filter] : [];

$stmt = $pdo->prepare(
    "SELECT r.*, u.first_name, u.last_name, p.name AS product_name, p.slug AS product_slug
     FROM reviews r JOIN users u ON u.id = r.user_id JOIN products p ON p.id = r.product_id
     $where ORDER BY r.created_at DESC"
);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$pageTitle = 'Reviews';
$pageHeading = 'Reviews';
require_once __DIR__ . '/includes/header.php';
?>
<div class="mb-3 d-flex gap-2">
  <a href="?status=" class="btn btn-sm <?= $filter===''?'btn-primary-admin':'btn-outline-secondary' ?>">All</a>
  <a href="?status=pending" class="btn btn-sm <?= $filter==='pending'?'btn-primary-admin':'btn-outline-secondary' ?>">Pending</a>
  <a href="?status=approved" class="btn btn-sm <?= $filter==='approved'?'btn-primary-admin':'btn-outline-secondary' ?>">Approved</a>
  <a href="?status=rejected" class="btn btn-sm <?= $filter==='rejected'?'btn-primary-admin':'btn-outline-secondary' ?>">Rejected</a>
</div>

<div class="admin-card">
  <div class="table-responsive">
    <table class="table table-admin">
      <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($reviews as $r): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/product.php?slug=<?= e($r['product_slug']) ?>" target="_blank"><?= e($r['product_name']) ?></a></td>
          <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
          <td><?php for($i=1;$i<=5;$i++): ?><i class="bi <?= $i<=$r['rating']?'bi-star-fill text-warning':'bi-star' ?>"></i><?php endfor; ?></td>
          <td style="max-width:260px;"><?= e(substr($r['comment'], 0, 100)) ?></td>
          <td><span class="badge badge-status bg-<?= $r['status']==='approved'?'success':($r['status']==='rejected'?'danger':'secondary') ?>"><?= e($r['status']) ?></span></td>
          <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
          <td class="text-nowrap">
            <?php if ($r['status'] !== 'approved'): ?><a href="?approve=<?= $r['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-check"></i></a><?php endif; ?>
            <?php if ($r['status'] !== 'rejected'): ?><a href="?reject=<?= $r['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-x"></i></a><?php endif; ?>
            <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm-msg="Delete this review?"><i class="bi bi-trash"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($reviews)): ?><tr><td colspan="7" class="text-center text-muted py-4">No reviews found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
