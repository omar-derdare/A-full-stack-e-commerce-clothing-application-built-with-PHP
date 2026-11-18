<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE hero_slides SET status = IF(status='active','inactive','active') WHERE id = ?")->execute([$id]);
    redirect(BASE_URL . '/admin/slider/index.php');
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('SELECT image FROM hero_slides WHERE id = ?');
    $stmt->execute([$id]);
    if ($slide = $stmt->fetch()) {
        if ($slide['image'] && $slide['image'] !== 'placeholder.jpg') {
            @unlink(UPLOAD_PATH . '/slides/' . $slide['image']);
        }
        $pdo->prepare('DELETE FROM hero_slides WHERE id = ?')->execute([$id]);
        flash('success', 'Slide deleted.');
    }
    redirect(BASE_URL . '/admin/slider/index.php');
}

$slides = $pdo->query('SELECT * FROM hero_slides ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Homepage Slider';
$pageHeading = 'Homepage Slider';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted small mb-0">Manage the background slider shown at the top of your homepage.</p>
  <a href="<?= BASE_URL ?>/admin/slider/create.php" class="btn btn-primary-admin btn-sm"><i class="bi bi-plus-lg"></i> Add Slide</a>
</div>

<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<div class="admin-card">
  <div class="table-responsive">
    <table class="table table-admin">
      <thead><tr><th>Image</th><th>Title</th><th>Button</th><th>Order</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($slides as $s): ?>
        <tr>
          <td><img src="<?= e(heroSlideImageUrl($s['image'])) ?>" class="thumb"></td>
          <td><?= e($s['title']) ?><div class="text-muted small"><?= e($s['subtitle']) ?></div></td>
          <td><?= e($s['button_text']) ?></td>
          <td><?= (int)$s['sort_order'] ?></td>
          <td><a href="?toggle=<?= $s['id'] ?>" class="badge badge-status bg-<?= $s['status']==='active'?'success':'secondary' ?> text-decoration-none"><?= e($s['status']) ?></a></td>
          <td class="text-nowrap">
            <a href="<?= BASE_URL ?>/admin/slider/edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm-msg="Delete this slide?"><i class="bi bi-trash"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($slides)): ?><tr><td colspan="6" class="text-center text-muted py-4">No slides yet. The homepage will show a default hero instead.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
