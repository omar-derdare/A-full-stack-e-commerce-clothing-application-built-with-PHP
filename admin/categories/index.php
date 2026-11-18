<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE categories SET status = IF(status='active','inactive','active') WHERE id = ?")->execute([$id]);
    flash('success', 'Category status updated.');
    redirect(BASE_URL . '/admin/categories/index.php');
}

$categories = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c ORDER BY c.name"
)->fetchAll();

$pageTitle = 'Categories';
$pageHeading = 'Categories';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 d-lg-none">Categories</h5>
  <div></div>
  <a href="<?= BASE_URL ?>/admin/categories/create.php" class="btn btn-primary-admin btn-sm"><i class="bi bi-plus-lg"></i> Add Category</a>
</div>

<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="admin-card">
  <div class="table-responsive">
    <table class="table table-admin">
      <thead><tr><th>Image</th><th>Name</th><th>Slug</th><th>Products</th><th>Status</th><th>Created</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td><img src="<?= e(categoryImageUrl($c['image'])) ?>" class="thumb"></td>
          <td><?= e($c['name']) ?></td>
          <td class="text-muted"><?= e($c['slug']) ?></td>
          <td><?= (int)$c['product_count'] ?></td>
          <td>
            <a href="?toggle=<?= $c['id'] ?>" class="badge badge-status bg-<?= $c['status']==='active'?'success':'secondary' ?> text-decoration-none">
              <?= e($c['status']) ?>
            </a>
          </td>
          <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
          <td>
            <a href="<?= BASE_URL ?>/admin/categories/edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <a href="<?= BASE_URL ?>/admin/categories/delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm-msg="Delete this category? Products in it will not be deleted but must be reassigned first."><i class="bi bi-trash"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($categories)): ?><tr><td colspan="7" class="text-center text-muted py-4">No categories yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
