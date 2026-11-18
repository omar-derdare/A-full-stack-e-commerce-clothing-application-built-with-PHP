<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM hero_slides WHERE id = ?');
$stmt->execute([$id]);
$slide = $stmt->fetch();
if (!$slide) {
    flash('error', 'Slide not found.');
    redirect(BASE_URL . '/admin/slider/index.php');
}

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

        $image = $slide['image'];
        try {
            $uploaded = handleImageUpload($_FILES['image'] ?? [], 'slides');
            if ($uploaded) $image = $uploaded;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'UPDATE hero_slides SET title=?, subtitle=?, button_text=?, button_link=?, image=?, sort_order=?, status=? WHERE id=?'
            );
            $stmt->execute([$title, $subtitle ?: null, $buttonText ?: null, $buttonLink ?: null, $image, $sortOrder, $status, $id]);
            flash('success', 'Slide updated successfully.');
            redirect(BASE_URL . '/admin/slider/index.php');
        }
    }
}

$pageTitle = 'Edit Slide';
$pageHeading = 'Edit Slide';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-card" style="max-width:640px;">
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input type="text" name="title" class="form-control" required value="<?= e($slide['title']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Subtitle</label>
      <input type="text" name="subtitle" class="form-control" value="<?= e($slide['subtitle']) ?>">
    </div>
    <div class="row">
      <div class="col-6 mb-3">
        <label class="form-label">Button Text</label>
        <input type="text" name="button_text" class="form-control" value="<?= e($slide['button_text']) ?>">
      </div>
      <div class="col-6 mb-3">
        <label class="form-label">Button Link</label>
        <input type="text" name="button_link" class="form-control" value="<?= e($slide['button_link']) ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Current Image</label><br>
      <img src="<?= e(heroSlideImageUrl($slide['image'])) ?>" class="image-preview mb-2">
      <input type="file" name="image" class="form-control admin-image-input" data-preview="imgPreview" accept=".jpg,.jpeg,.png,.webp">
      <img id="imgPreview" class="image-preview d-none">
    </div>
    <div class="row">
      <div class="col-6 mb-3">
        <label class="form-label">Sort Order</label>
        <input type="number" name="sort_order" class="form-control" value="<?= e($slide['sort_order']) ?>">
      </div>
      <div class="col-6 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="active" <?= $slide['status']==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $slide['status']==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
    </div>
    <button class="btn btn-primary-admin">Save Changes</button>
    <a href="<?= BASE_URL ?>/admin/slider/index.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
