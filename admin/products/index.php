<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE products SET status = IF(status='active','inactive','active') WHERE id = ?")->execute([$id]);
    redirect(BASE_URL . '/admin/products/index.php');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];
if (!empty($_GET['q'])) {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    $like = '%' . $_GET['q'] . '%';
    $params[] = $like; $params[] = $like;
}
if (!empty($_GET['category'])) {
    $where[] = 'p.category_id = ?';
    $params[] = (int)$_GET['category'];
}
if (!empty($_GET['status'])) {
    $where[] = 'p.status = ?';
    $params[] = $_GET['status'];
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id
     WHERE $whereSql ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$pageTitle = 'Products';
$pageHeading = 'Products';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="mb-0 d-lg-none">Products</h5>
  <form class="d-flex gap-2 flex-wrap">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search products..." value="<?= e($_GET['q'] ?? '') ?>" style="width:200px;">
    <select name="category" class="form-select form-select-sm" style="width:160px;">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= $c['id'] ?>" <?= ($_GET['category'] ?? '')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-select form-select-sm" style="width:130px;">
      <option value="">All Status</option>
      <option value="active" <?= ($_GET['status'] ?? '')==='active'?'selected':'' ?>>Active</option>
      <option value="inactive" <?= ($_GET['status'] ?? '')==='inactive'?'selected':'' ?>>Inactive</option>
    </select>
    <button class="btn btn-sm btn-outline-secondary">Filter</button>
  </form>
  <a href="<?= BASE_URL ?>/admin/products/create.php" class="btn btn-primary-admin btn-sm"><i class="bi bi-plus-lg"></i> Add Product</a>
</div>

<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="admin-card">
  <div class="table-responsive">
    <table class="table table-admin">
      <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Featured</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($products as $p): $priceInfo = productDisplayPrice($p); ?>
        <tr>
          <td><img src="<?= e(productImageUrl($p['featured_image'])) ?>" class="thumb"></td>
          <td>
            <?= e($p['name']) ?>
            <div class="text-muted small">SKU: <?= e($p['sku']) ?></div>
          </td>
          <td><?= e($p['category_name']) ?></td>
          <td>
            <?= formatPrice($priceInfo['final']) ?>
            <?php if ($priceInfo['has_sale']): ?><div class="text-muted small text-decoration-line-through"><?= formatPrice($priceInfo['price']) ?></div><?php endif; ?>
          </td>
          <td>
            <?php if ($p['stock'] <= 0): ?><span class="text-danger fw-medium">0</span>
            <?php elseif ($p['stock'] <= 5): ?><span class="text-warning fw-medium"><?= $p['stock'] ?></span>
            <?php else: ?><?= $p['stock'] ?><?php endif; ?>
          </td>
          <td><?= $p['featured'] ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>' ?></td>
          <td><a href="?toggle=<?= $p['id'] ?>" class="badge badge-status bg-<?= $p['status']==='active'?'success':'secondary' ?> text-decoration-none"><?= e($p['status']) ?></a></td>
          <td class="text-nowrap">
            <a href="<?= BASE_URL ?>/admin/products/edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <a href="<?= BASE_URL ?>/admin/products/delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm-msg="Delete this product permanently?"><i class="bi bi-trash"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($products)): ?><tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <nav><ul class="pagination pagination-sm justify-content-center mt-3">
    <?php for ($i=1;$i<=$totalPages;$i++): ?>
      <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>&q=<?= e($_GET['q'] ?? '') ?>&category=<?= e($_GET['category'] ?? '') ?>&status=<?= e($_GET['status'] ?? '') ?>"><?= $i ?></a></li>
    <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
