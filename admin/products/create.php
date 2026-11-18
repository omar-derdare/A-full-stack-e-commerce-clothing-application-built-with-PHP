<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$colors = $pdo->query('SELECT * FROM colors ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $salePrice = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
        $sku = trim($_POST['sku'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $featured = isset($_POST['featured']) ? 1 : 0;

        if ($name === '') $errors[] = 'Product name is required.';
        if ($categoryId <= 0) $errors[] = 'Please select a category.';
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';
        if ($salePrice !== null && $salePrice >= $price) $errors[] = 'Sale price must be less than the regular price.';
        if ($sku === '') $errors[] = 'SKU is required.';

        if ($sku !== '') {
            $skuCheck = $pdo->prepare('SELECT id FROM products WHERE sku = ?');
            $skuCheck->execute([$sku]);
            if ($skuCheck->fetch()) $errors[] = 'This SKU is already in use.';
        }

        $mainImage = null;
        try {
            $mainImage = handleImageUpload($_FILES['featured_image'] ?? [], 'products');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        $additionalImages = [];
        if (!empty($_FILES['additional_images']['name'][0])) {
            $count = count($_FILES['additional_images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                $file = [
                    'name' => $_FILES['additional_images']['name'][$i],
                    'type' => $_FILES['additional_images']['type'][$i],
                    'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                    'error' => $_FILES['additional_images']['error'][$i],
                    'size' => $_FILES['additional_images']['size'][$i],
                ];
                try {
                    $img = handleImageUpload($file, 'products');
                    if ($img) $additionalImages[] = $img;
                } catch (RuntimeException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        if (empty($errors)) {
            $slug = uniqueSlug('products', slugify($name));
            $stmt = $pdo->prepare(
                'INSERT INTO products (category_id, name, slug, description, short_description, price, sale_price, sku, stock, featured_image, status, featured, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([$categoryId, $name, $slug, $description ?: null, $shortDescription ?: null, $price, $salePrice, $sku, $stock, $mainImage, $status, $featured]);
            $productId = (int)$pdo->lastInsertId();

            foreach ($additionalImages as $i => $img) {
                $pdo->prepare('INSERT INTO product_images (product_id, image, sort_order, created_at) VALUES (?, ?, ?, NOW())')
                    ->execute([$productId, $img, $i]);
            }

            // Variants
            $sizes = $_POST['variant_size'] ?? [];
            $colors = $_POST['variant_color'] ?? [];
            $stocks = $_POST['variant_stock'] ?? [];
            $skus = $_POST['variant_sku'] ?? [];
            for ($i = 0; $i < count($sizes); $i++) {
                if ($sizes[$i] === '' && $colors[$i] === '') continue;
                $pdo->prepare('INSERT INTO product_variants (product_id, size, color, sku, stock, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
                    ->execute([$productId, $sizes[$i] ?: null, $colors[$i] ?: null, $skus[$i] ?: null, (int)($stocks[$i] ?? 0)]);
            }

            flash('success', 'Product created successfully.');
            redirect(BASE_URL . '/admin/products/index.php');
        }
    }
}

$pageTitle = 'Add Product';
$pageHeading = 'Add Product';
require_once __DIR__ . '/../includes/header.php';
?>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="admin-card mb-3">
        <h6 class="mb-3">Basic Information</h6>
        <div class="mb-3">
          <label class="form-label">Product Name</label>
          <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Short Description</label>
          <input type="text" name="short_description" class="form-control" maxlength="500" value="<?= e($_POST['short_description'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Full Description</label>
          <textarea name="description" class="form-control" rows="5"><?= e($_POST['description'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="admin-card mb-3">
        <h6 class="mb-3">Pricing & Inventory</h6>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?= e($_POST['price'] ?? '') ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Sale Price (optional)</label>
            <input type="number" step="0.01" min="0" name="sale_price" class="form-control" value="<?= e($_POST['sale_price'] ?? '') ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">SKU</label>
            <input type="text" name="sku" class="form-control" required value="<?= e($_POST['sku'] ?? '') ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Base Stock</label>
          <input type="number" min="0" name="stock" class="form-control" value="<?= e($_POST['stock'] ?? '0') ?>">
          <div class="form-text">If this product has size/color variants, manage stock per variant below instead.</div>
        </div>
      </div>

      <div class="admin-card mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">Variants (Size / Color)</h6>
          <?php if (empty($colors)): ?>
            <a href="<?= BASE_URL ?>/admin/colors/index.php" class="small">Add colors first &rarr;</a>
          <?php endif; ?>
        </div>
        <div id="variantsWrap"></div>
        <button type="button" id="addVariantBtn" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-plus"></i> Add Variant</button>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="admin-card mb-3">
        <h6 class="mb-3">Organize</h6>
        <div class="mb-3">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-select" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" name="featured" id="featured">
          <label class="form-check-label" for="featured">Mark as Featured</label>
        </div>
      </div>

      <div class="admin-card mb-3">
        <h6 class="mb-3">Main Image</h6>
        <input type="file" name="featured_image" class="form-control admin-image-input" data-preview="mainPreview" accept=".jpg,.jpeg,.png,.webp">
        <img id="mainPreview" class="image-preview d-none">
      </div>

      <div class="admin-card">
        <h6 class="mb-3">Additional Images</h6>
        <input type="file" name="additional_images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
      </div>
    </div>
  </div>

  <div class="mt-3">
    <button class="btn btn-primary-admin">Create Product</button>
    <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
<script>window.ADMIN_COLORS = <?= json_encode(array_column($colors, 'name')) ?>;</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
