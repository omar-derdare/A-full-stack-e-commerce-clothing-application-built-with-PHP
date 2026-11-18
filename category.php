<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product-functions.php';
$pdo = getDB();

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND status='active' LIMIT 1");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Category Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container section text-center"><div class="empty-state"><i class="bi bi-emoji-frown"></i><h4 class="mt-3">Category not found</h4><a href="' . BASE_URL . '/shop.php" class="btn btn-dark mt-3">Browse All Products</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = e($category['name']);
$metaDescription = $category['description'] ?: ('Shop ' . $category['name'] . ' at ' . setting('store_name'));
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section" id="shopPage">
  <div class="section-title">
    <h2><?= e($category['name']) ?></h2>
    <?php if ($category['description']): ?><p><?= e($category['description']) ?></p><?php endif; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span id="resultsCount" class="text-muted small">Loading products...</span>
    <select id="sortSelect" class="form-select form-select-sm w-auto">
      <option value="newest">Newest</option>
      <option value="price_low">Price: Low to High</option>
      <option value="price_high">Price: High to Low</option>
      <option value="popular">Popular</option>
      <option value="best_selling">Best Selling</option>
    </select>
  </div>

  <div id="productGrid" class="row">
    <?php for ($i = 0; $i < 8; $i++): ?>
    <div class="col-6 col-md-4 col-lg-3"><div class="skeleton" style="aspect-ratio:3/4;margin-bottom:12px;"></div></div>
    <?php endfor; ?>
  </div>
  <nav id="paginationWrap" class="mt-4"></nav>
</div>

<script>window.IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;</script>
<script>
(function () {
  const grid = document.getElementById('productGrid');
  const resultsCount = document.getElementById('resultsCount');
  const pagination = document.getElementById('paginationWrap');
  const sortSelect = document.getElementById('sortSelect');
  const categorySlug = <?= json_encode($category['slug']) ?>;
  let currentPage = 1;

  async function loadProducts() {
    const params = new URLSearchParams({ page: currentPage, sort: sortSelect.value, category_slug: categorySlug });
    const data = await apiFetch(`${window.BASE_URL}/api/products.php?${params.toString()}`);
    if (!data.success || !data.products.length) {
      grid.innerHTML = `<div class="empty-state w-100"><i class="bi bi-search"></i><p>No products found in this category.</p></div>`;
      resultsCount.textContent = '0 products';
      pagination.innerHTML = '';
      return;
    }
    resultsCount.textContent = `${data.total} product${data.total === 1 ? '' : 's'} found`;
    grid.innerHTML = data.products.map(renderCard).join('');
    renderPagination(data.total_pages, data.page);
  }

  function renderCard(p) {
    const badge = p.has_sale ? `<span class="badge-sale">-${p.discount}%</span>` : (p.is_new ? `<span class="badge-new">New</span>` : '');
    const quickAdd = p.in_stock ? `<button class="quick-add-btn" data-product-id="${p.id}" data-has-variants="${p.has_variants ? 1 : 0}" data-slug="${p.slug}">${p.has_variants ? 'Select Options' : 'Quick Add'}</button>` : '';
    const priceBlock = p.in_stock
      ? `<div class="price-row"><span class="price-current">${p.price_final}</span>${p.has_sale ? `<span class="price-old">${p.price_original}</span>` : ''}</div>`
      : `<span class="out-of-stock-label">Out of Stock</span>`;
    return `
    <div class="col-6 col-md-4 col-lg-3">
      <div class="product-card">
        <div class="product-img-wrap">
          <a href="${window.BASE_URL}/product.php?slug=${p.slug}"><img src="${p.image}" alt="${p.name}" loading="lazy"></a>
          ${badge}
          <button class="wishlist-btn ${p.in_wishlist ? 'active' : ''}" data-product-id="${p.id}"><i class="bi ${p.in_wishlist ? 'bi-heart-fill' : 'bi-heart'}"></i></button>
          ${quickAdd}
        </div>
        <div class="product-info">
          <div class="product-name"><a href="${window.BASE_URL}/product.php?slug=${p.slug}" class="text-dark">${p.name}</a></div>
          ${priceBlock}
        </div>
      </div>
    </div>`;
  }

  function renderPagination(totalPages, current) {
    if (totalPages <= 1) { pagination.innerHTML = ''; return; }
    let html = '<ul class="pagination justify-content-center">';
    for (let i = 1; i <= totalPages; i++) html += `<li class="page-item ${i===current?'active':''}"><a class="page-link page-link-go" href="#" data-page="${i}">${i}</a></li>`;
    pagination.innerHTML = html + '</ul>';
  }

  pagination.addEventListener('click', (e) => {
    const link = e.target.closest('.page-link-go');
    if (!link) return;
    e.preventDefault();
    currentPage = parseInt(link.dataset.page, 10);
    window.scrollTo({top:0, behavior:'smooth'});
    loadProducts();
  });
  sortSelect.addEventListener('change', () => { currentPage = 1; loadProducts(); });

  loadProducts();
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
