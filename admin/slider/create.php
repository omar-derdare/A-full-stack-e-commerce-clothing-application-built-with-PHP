<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $buttonText = trim($_POST['button_text'] ?? '');
        $buttonLink = trim($_POST['button_link'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if ($title === '') $errors[] = 'Title is required.';

        $image = null;
        try {
            $image = handleImageUpload($_FILES['image'] ?? [], 'slides');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
        if (!$image) $errors[] = 'A background image is required.';

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'INSERT INTO hero_slides (title, subtitle, button_text, button_link, image, sort_order, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$title, $subtitle ?: null, $buttonText ?: null, $buttonLink ?: null, $image, $sortOrder, $status]);
            flash('success', 'Slide created successfully.');
            redirect(BASE_URL . '/admin/slider/index.php');
        }
    }
}

$pageTitle = 'Add Slide';
$pageHeading = 'Add Slide';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-card" style="max-width:640px;">
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input type="text" name="title" class="form-control" required value="<?= e($_POST['title'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Subtitle</label>
      <input type="text" name="subtitle" class="form-control" value="<?= e($_POST['subtitle'] ?? '') ?>">
    </div>
    <div class="row">
      <div class="col-6 mb-3">
        <label class="form-label">Button Text</label>
        <input type="text" name="button_text" class="form-control" value="<?= e($_POST['button_text'] ?? 'Shop Now') ?>">
      </div>
      <div class="col-6 mb-3">
        <label class="form-label">Button Link</label>
        <input type="text" name="button_link" class="form-control" placeholder="shop.php" value="<?= e($_POST['button_link'] ?? '') ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Background Image</label>
      <input type="file" name="image" class="form-control admin-image-input" data-preview="imgPreview" accept=".jpg,.jpeg,.png,.webp" required>
      <img id="imgPreview" class="image-preview d-none">
      <div class="form-text">Recommended: a wide landscape image, at least 1600px wide, for a crisp full-width background.</div>
    </div>
    <div class="row">
      <div class="col-6 mb-3">
        <label class="form-label">Sort Order</label>
        <input type="number" name="sort_order" class="form-control" value="<?= e($_POST['sort_order'] ?? '0') ?>">
      </div>
      <div class="col-6 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    <button class="btn btn-primary-admin">Create Slide</button>
    <a href="<?= BASE_URL ?>/admin/slider/index.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
