<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product-functions.php';
$pdo = getDB();

$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();
$colors = $pdo->query("SELECT DISTINCT color FROM product_variants WHERE color IS NOT NULL AND color != '' ORDER BY color")->fetchAll(PDO::FETCH_COLUMN);

$page = max(1, (int)($_GET['page'] ?? 1));

$wishlistIds = [];
if (isLoggedIn()) {
    $w = $pdo->prepare('SELECT product_id FROM wishlists WHERE user_id = ?');
    $w->execute([currentUserId()]);
    $wishlistIds = array_column($w->fetchAll(), 'product_id');
}

$pageTitle = 'Shop All';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section" id="shopPage" data-page="<?= $page ?>">
  <div class="row">
    <div class="col-lg-3 mb-4">
      <div class="filter-sidebar" id="filterSidebar">
        <div class="filter-group">
          <h6>Category</h6>
          <?php foreach ($categories as $c): ?>
          <div class="form-check">
            <input class="form-check-input filter-input" type="checkbox" value="<?= $c['id'] ?>" name="category" id="cat<?= $c['id'] ?>">
            <label class="form-check-label" for="cat<?= $c['id'] ?>"><?= e($c['name']) ?></label>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="filter-group">
          <h6>Price Range</h6>
          <div class="d-flex gap-2 align-items-center">
            <input type="number" min="0" class="form-control form-control-sm filter-input" id="priceMin" placeholder="Min">
            <span>-</span>
            <input type="number" min="0" class="form-control form-control-sm filter-input" id="priceMax" placeholder="Max">
          </div>
        </div>

        <div class="filter-group">
          <h6>Size</h6>
          <?php foreach (AVAILABLE_SIZES as $s): ?>
            <span class="size-box filter-size me-1 mb-1" data-size="<?= $s ?>"><?= $s ?></span>
          <?php endforeach; ?>
        </div>

        <?php if ($colors): ?>
        <div class="filter-group">
          <h6>Color</h6>
          <?php
          foreach ($colors as $col):
            $hex = colorHex($col);
          ?>
            <span class="color-swatch filter-color me-1 mb-1" data-color="<?= e($col) ?>" title="<?= e($col) ?>" style="background:<?= $hex ?>;border:1px solid #ddd"></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="filter-group form-check">
          <input class="form-check-input filter-input" type="checkbox" id="onSaleFilter" <?= !empty($_GET['on_sale']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="onSaleFilter">On Sale Only</label>
        </div>

        <button class="btn btn-dark w-100 btn-sm" id="clearFiltersBtn">Clear Filters</button>
      </div>
    </div>

    <div class="col-lg-9">
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
        <div class="col-6 col-md-4 col-lg-4">
          <div class="skeleton" style="aspect-ratio:3/4;margin-bottom:12px;"></div>
        </div>
        <?php endfor; ?>
      </div>

      <nav id="paginationWrap" class="mt-4"></nav>
    </div>
  </div>
</div>

<script>window.IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;</script>
<script>
(function () {
  const grid = document.getElementById('productGrid');
  const resultsCount = document.getElementById('resultsCount');
  const pagination = document.getElementById('paginationWrap');
  const sortSelect = document.getElementById('sortSelect');
  const priceMin = document.getElementById('priceMin');
  const priceMax = document.getElementById('priceMax');
  let currentPage = 1;
  let selectedSize = null;
  let selectedColor = null;

  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('sort')) sortSelect.value = urlParams.get('sort');
  const initialOnSale = urlParams.get('on_sale') === '1';
  if (initialOnSale) document.getElementById('onSaleFilter').checked = true;
  const initialCategorySlug = urlParams.get('category_slug');

  function buildQuery() {
    const params = new URLSearchParams();
    params.set('page', currentPage);
    params.set('sort', sortSelect.value);

    const cats = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(el => el.value);
    if (cats.length) params.set('category', cats.join(','));
    if (initialCategorySlug) params.set('category_slug', initialCategorySlug);

    if (priceMin.value) params.set('price_min', priceMin.value);
    if (priceMax.value) params.set('price_max', priceMax.value);
    if (selectedSize) params.set('size', selectedSize);
    if (selectedColor) params.set('color', selectedColor);
    if (document.getElementById('onSaleFilter').checked) params.set('on_sale', '1');

    const q = urlParams.get('q');
    if (q) params.set('q', q);

    return params.toString();
  }

  async function loadProducts() {
    grid.innerHTML = Array.from({length: 8}).map(() => `
      <div class="col-6 col-md-4 col-lg-4"><div class="skeleton" style="aspect-ratio:3/4;margin-bottom:12px;"></div></div>
    `).join('');

    const data = await apiFetch(`${window.BASE_URL}/api/products.php?${buildQuery()}`);
    if (!data.success) {
      grid.innerHTML = `<div class="empty-state w-100"><i class="bi bi-exclamation-circle"></i><p>Something went wrong loading products.</p></div>`;
      return;
    }

    if (!data.products.length) {
      grid.innerHTML = `<div class="empty-state w-100"><i class="bi bi-search"></i><p>No products match your filters.</p></div>`;
      resultsCount.textContent = '0 products found';
      pagination.innerHTML = '';
      return;
    }

    resultsCount.textContent = `${data.total} product${data.total === 1 ? '' : 's'} found`;
    grid.innerHTML = data.products.map(renderCard).join('');
    renderPagination(data.total_pages, data.page);
  }

  function renderCard(p) {
    const wishlistActive = p.in_wishlist ? 'active' : '';
    const heartIcon = p.in_wishlist ? 'bi-heart-fill' : 'bi-heart';
    const badge = p.has_sale ? `<span class="badge-sale">-${p.discount}%</span>` : (p.is_new ? `<span class="badge-new">New</span>` : '');
    const quickAdd = p.in_stock ? `<button class="quick-add-btn" data-product-id="${p.id}" data-has-variants="${p.has_variants ? 1 : 0}" data-slug="${p.slug}">${p.has_variants ? 'Select Options' : 'Quick Add'}</button>` : '';
    const priceBlock = p.in_stock
      ? `<div class="price-row"><span class="price-current">${p.price_final}</span>${p.has_sale ? `<span class="price-old">${p.price_original}</span>` : ''}</div>`
      : `<span class="out-of-stock-label">Out of Stock</span>`;

    return `
    <div class="col-6 col-md-4 col-lg-4">
      <div class="product-card">
        <div class="product-img-wrap">
          <a href="${window.BASE_URL}/product.php?slug=${p.slug}"><img src="${p.image}" alt="${p.name}" loading="lazy"></a>
          ${badge}
          <button class="wishlist-btn ${wishlistActive}" data-product-id="${p.id}"><i class="bi ${heartIcon}"></i></button>
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
    for (let i = 1; i <= totalPages; i++) {
      html += `<li class="page-item ${i === current ? 'active' : ''}"><a class="page-link page-link-go" href="#" data-page="${i}">${i}</a></li>`;
    }
    html += '</ul>';
    pagination.innerHTML = html;
  }

  pagination?.addEventListener('click', (e) => {
    const link = e.target.closest('.page-link-go');
    if (!link) return;
    e.preventDefault();
    currentPage = parseInt(link.dataset.page, 10);
    window.scrollTo({top: 0, behavior:'smooth'});
    loadProducts();
  });

  document.querySelectorAll('.filter-input').forEach(el => el.addEventListener('change', () => { currentPage = 1; loadProducts(); }));
  sortSelect.addEventListener('change', () => { currentPage = 1; loadProducts(); });
  [priceMin, priceMax].forEach(el => el.addEventListener('change', () => { currentPage = 1; loadProducts(); }));

  document.querySelectorAll('.filter-size').forEach(el => {
    el.addEventListener('click', () => {
      const wasActive = el.classList.contains('active');
      document.querySelectorAll('.filter-size').forEach(x => x.classList.remove('active'));
      if (!wasActive) { el.classList.add('active'); selectedSize = el.dataset.size; } else { selectedSize = null; }
      currentPage = 1; loadProducts();
    });
  });

  document.querySelectorAll('.filter-color').forEach(el => {
    el.addEventListener('click', () => {
      const wasActive = el.classList.contains('active');
      document.querySelectorAll('.filter-color').forEach(x => x.classList.remove('active'));
      if (!wasActive) { el.classList.add('active'); selectedColor = el.dataset.color; } else { selectedColor = null; }
      currentPage = 1; loadProducts();
    });
  });

  document.getElementById('clearFiltersBtn').addEventListener('click', () => {
    document.querySelectorAll('.filter-input').forEach(el => el.checked = false);
    priceMin.value = ''; priceMax.value = '';
    document.querySelectorAll('.filter-size, .filter-color').forEach(x => x.classList.remove('active'));
    selectedSize = null; selectedColor = null;
    currentPage = 1;
    loadProducts();
  });

  loadProducts();
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
