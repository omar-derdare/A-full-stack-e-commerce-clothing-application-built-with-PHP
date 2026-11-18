<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product-functions.php';
$pdo = getDB();

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$products = [];
$total = 0;

if ($q !== '') {
    $like = '%' . $q . '%';
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE status='active' AND (name LIKE ? OR description LIKE ? OR sku LIKE ?)");
    $countStmt->execute([$like, $like, $like]);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM products WHERE status='active' AND (name LIKE ? OR description LIKE ? OR sku LIKE ?) ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute([$like, $like, $like]);
    $products = $stmt->fetchAll();
}

$totalPages = max(1, (int)ceil($total / $perPage));

$wishlistIds = [];
if (isLoggedIn()) {
    $w = $pdo->prepare('SELECT product_id FROM wishlists WHERE user_id = ?');
    $w->execute([currentUserId()]);
    $wishlistIds = array_column($w->fetchAll(), 'product_id');
}

$pageTitle = $q !== '' ? 'Search: ' . $q : 'Search';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <div class="section-title">
    <h2>Search Results</h2>
    <form action="<?= BASE_URL ?>/search.php" method="GET" class="mx-auto" style="max-width:480px;">
      <div class="input-group">
        <input type="search" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Search products...">
        <button class="btn btn-dark" type="submit"><i class="bi bi-search"></i></button>
      </div>
    </form>
  </div>

  <?php if ($q === ''): ?>
    <div class="empty-state"><i class="bi bi-search"></i><p>Enter a search term to find products.</p></div>
  <?php elseif (empty($products)): ?>
    <div class="empty-state"><i class="bi bi-emoji-frown"></i><p>No products found for "<?= e($q) ?>".</p></div>
  <?php else: ?>
    <p class="text-muted mb-3"><?= $total ?> result<?= $total === 1 ? '' : 's' ?> for "<?= e($q) ?>"</p>
    <div class="row">
      <?php foreach ($products as $p) renderProductCard($p, $wishlistIds); ?>
    </div>
    <?php if ($totalPages > 1): ?>
    <ul class="pagination justify-content-center mt-4">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?q=<?= urlencode($q) ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
    <?php endif; ?>
  <?php endif; ?>
</div>
<script>window.IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
