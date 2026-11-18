<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    flash('error', 'Product not found.');
    redirect(BASE_URL . '/admin/products/index.php');
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$colors = $pdo->query('SELECT * FROM colors ORDER BY name')->fetchAll();
$images = getProductImages($id);
$variants = getProductVariants($id);
$errors = [];

// Remove a single additional image
if (isset($_GET['remove_image'])) {
    $imgId = (int)$_GET['remove_image'];
    $imgStmt = $pdo->prepare('SELECT * FROM product_images WHERE id = ? AND product_id = ?');
    $imgStmt->execute([$imgId, $id]);
    $img = $imgStmt->fetch();
    if ($img) {
        @unlink(UPLOAD_PATH . '/products/' . $img['image']);
        $pdo->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imgId]);
    }
    redirect(BASE_URL . '/admin/products/edit.php?id=' . $id);
}

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
            $skuCheck = $pdo->prepare('SELECT id FROM products WHERE sku = ? AND id != ?');
            $skuCheck->execute([$sku, $id]);
            if ($skuCheck->fetch()) $errors[] = 'This SKU is already in use by another product.';
        }

        $mainImage = $product['featured_image'];
        try {
            $uploaded = handleImageUpload($_FILES['featured_image'] ?? [], 'products');
            if ($uploaded) $mainImage = $uploaded;
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
            $slug = $name !== $product['name'] ? uniqueSlug('products', slugify($name), $id) : $product['slug'];
            $stmt = $pdo->prepare(
                'UPDATE products SET category_id=?, name=?, slug=?, description=?, short_description=?, price=?, sale_price=?, sku=?, stock=?, featured_image=?, status=?, featured=?, updated_at=NOW() WHERE id=?'
            );
            $stmt->execute([$categoryId, $name, $slug, $description ?: null, $shortDescription ?: null, $price, $salePrice, $sku, $stock, $mainImage, $status, $featured, $id]);

            $existingCount = count($images);
            foreach ($additionalImages as $i => $img) {
                $pdo->prepare('INSERT INTO product_images (product_id, image, sort_order, created_at) VALUES (?, ?, ?, NOW())')
                    ->execute([$id, $img, $existingCount + $i]);
            }

            // Replace variants
            $pdo->prepare('DELETE FROM product_variants WHERE product_id = ?')->execute([$id]);
            $sizes = $_POST['variant_size'] ?? [];
            $colors = $_POST['variant_color'] ?? [];
            $stocks = $_POST['variant_stock'] ?? [];
            $skus = $_POST['variant_sku'] ?? [];
            for ($i = 0; $i < count($sizes); $i++) {
                if ($sizes[$i] === '' && $colors[$i] === '') continue;
                $pdo->prepare('INSERT INTO product_variants (product_id, size, color, sku, stock, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
                    ->execute([$id, $sizes[$i] ?: null, $colors[$i] ?: null, $skus[$i] ?: null, (int)($stocks[$i] ?? 0)]);
            }

            flash('success', 'Product updated successfully.');
            redirect(BASE_URL . '/admin/products/index.php');
        }
    }
}

$pageTitle = 'Edit Product';
$pageHeading = 'Edit Product';
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
          <input type="text" name="name" class="form-control" required value="<?= e($product['name']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Short Description</label>
          <input type="text" name="short_description" class="form-control" maxlength="500" value="<?= e($product['short_description']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Full Description</label>
          <textarea name="description" class="form-control" rows="5"><?= e($product['description']) ?></textarea>
        </div>
      </div>

      <div class="admin-card mb-3">
        <h6 class="mb-3">Pricing & Inventory</h6>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?= e($product['price']) ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Sale Price (optional)</label>
            <input type="number" step="0.01" min="0" name="sale_price" class="form-control" value="<?= e($product['sale_price']) ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">SKU</label>
            <input type="text" name="sku" class="form-control" required value="<?= e($product['sku']) ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Base Stock</label>
          <input type="number" min="0" name="stock" class="form-control" value="<?= e($product['stock']) ?>">
        </div>
      </div>

      <div class="admin-card mb-3">
        <h6 class="mb-3">Variants (Size / Color)</h6>
        <div id="variantsWrap">
          <?php foreach ($variants as $v): ?>
          <div class="row g-2 mb-2 variant-row align-items-center">
            <div class="col-3">
              <select name="variant_size[]" class="form-select form-select-sm">
                <option value="">Size</option>
                <?php foreach (AVAILABLE_SIZES as $s): ?>
                <option <?= $v['size']===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-3">
              <select name="variant_color[]" class="form-select form-select-sm">
                <option value="">Color</option>
                <?php foreach ($colors as $col): ?>
                <option <?= $v['color']===$col['name']?'selected':'' ?>><?= e($col['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-3"><input type="number" name="variant_stock[]" class="form-control form-control-sm" value="<?= (int)$v['stock'] ?>" min="0"></div>
            <div class="col-2"><input type="text" name="variant_sku[]" class="form-control form-control-sm" value="<?= e($v['sku']) ?>" placeholder="SKU"></div>
            <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger remove-variant-row"><i class="bi bi-trash"></i></button></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if (empty($colors)): ?>
        <a href="<?= BASE_URL ?>/admin/colors/index.php" class="small d-block mb-2">Manage colors &rarr;</a>
        <?php endif; ?>
        <button type="button" id="addVariantBtn" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-plus"></i> Add Variant</button>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="admin-card mb-3">
        <h6 class="mb-3">Organize</h6>
        <div class="mb-3">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-select" required>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$product['category_id']?'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active" <?= $product['status']==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $product['status']==='inactive'?'selected':'' ?>>Inactive</option>
          </select>
        </div>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" name="featured" id="featured" <?= $product['featured']?'checked':'' ?>>
          <label class="form-check-label" for="featured">Mark as Featured</label>
        </div>
      </div>

      <div class="admin-card mb-3">
        <h6 class="mb-3">Main Image</h6>
        <img src="<?= e(productImageUrl($product['featured_image'])) ?>" class="image-preview mb-2">
        <input type="file" name="featured_image" class="form-control admin-image-input" data-preview="mainPreview" accept=".jpg,.jpeg,.png,.webp">
        <img id="mainPreview" class="image-preview d-none">
      </div>

      <div class="admin-card">
        <h6 class="mb-3">Additional Images</h6>
        <?php if ($images): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php foreach ($images as $img): ?>
          <div class="position-relative">
            <img src="<?= e(productImageUrl($img['image'])) ?>" class="thumb" style="width:60px;height:60px;">
            <a href="?id=<?= $id ?>&remove_image=<?= $img['id'] ?>" class="position-absolute top-0 end-0 bg-danger text-white rounded-circle confirm-delete" data-confirm-msg="Remove this image?" style="width:18px;height:18px;font-size:10px;display:flex;align-items:center;justify-content:center;transform:translate(30%,-30%);"><i class="bi bi-x"></i></a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <input type="file" name="additional_images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
      </div>
    </div>
  </div>

  <div class="mt-3">
    <button class="btn btn-primary-admin">Save Changes</button>
    <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
<script>window.ADMIN_COLORS = <?= json_encode(array_column($colors, 'name')) ?>;</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
