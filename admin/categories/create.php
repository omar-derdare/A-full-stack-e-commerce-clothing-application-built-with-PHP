<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if ($name === '') {
            $errors[] = 'Category name is required.';
        }

        $imageName = null;
        try {
            $imageName = handleImageUpload($_FILES['image'] ?? [], 'categories');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        if (empty($errors)) {
            $slug = uniqueSlug('categories', slugify($name));
            $stmt = $pdo->prepare(
                'INSERT INTO categories (name, slug, description, image, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$name, $slug, $description ?: null, $imageName, $status]);
            flash('success', 'Category created successfully.');
            redirect(BASE_URL . '/admin/categories/index.php');
        }
    }
}

$pageTitle = 'Add Category';
$pageHeading = 'Add Category';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-card" style="max-width:640px;">
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?= e($_POST['description'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Image</label>
      <input type="file" name="image" class="form-control admin-image-input" data-preview="imgPreview" accept=".jpg,.jpeg,.png,.webp">
      <img id="imgPreview" class="image-preview d-none">
    </div>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
    <button class="btn btn-primary-admin">Create Category</button>
    <a href="<?= BASE_URL ?>/admin/categories/index.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
