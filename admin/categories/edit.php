<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->execute([$id]);
$category = $stmt->fetch();
if (!$category) {
    flash('error', 'Category not found.');
    redirect(BASE_URL . '/admin/categories/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if ($name === '') $errors[] = 'Category name is required.';

        $imageName = $category['image'];
        try {
            $uploaded = handleImageUpload($_FILES['image'] ?? [], 'categories');
            if ($uploaded) $imageName = $uploaded;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        if (empty($errors)) {
            $slug = $name !== $category['name'] ? uniqueSlug('categories', slugify($name), $id) : $category['slug'];
            $stmt = $pdo->prepare('UPDATE categories SET name=?, slug=?, description=?, image=?, status=? WHERE id=?');
            $stmt->execute([$name, $slug, $description ?: null, $imageName, $status, $id]);
            flash('success', 'Category updated successfully.');
            redirect(BASE_URL . '/admin/categories/index.php');
        }
    }
}

$pageTitle = 'Edit Category';
$pageHeading = 'Edit Category';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-card" style="max-width:640px;">
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" required value="<?= e($category['name']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?= e($category['description']) ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Current Image</label><br>
      <img src="<?= e(categoryImageUrl($category['image'])) ?>" class="image-preview mb-2">
      <input type="file" name="image" class="form-control admin-image-input" data-preview="imgPreview" accept=".jpg,.jpeg,.png,.webp">
      <img id="imgPreview" class="image-preview d-none">
    </div>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active" <?= $category['status']==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $category['status']==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>
    <button class="btn btn-primary-admin">Save Changes</button>
    <a href="<?= BASE_URL ?>/admin/categories/index.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
