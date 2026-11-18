<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $check = $pdo->prepare('SELECT COUNT(*) FROM product_variants WHERE color = (SELECT name FROM colors WHERE id = ?)');
    $check->execute([$id]);
    if ((int)$check->fetchColumn() > 0) {
        flash('error', 'Cannot delete this color because it is used by existing product variants.');
    } else {
        $pdo->prepare('DELETE FROM colors WHERE id = ?')->execute([$id]);
        flash('success', 'Color deleted.');
    }
    redirect(BASE_URL . '/admin/colors/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $hex = trim($_POST['hex_code'] ?? '#cccccc');

        if ($name === '') $errors[] = 'Color name is required.';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) $errors[] = 'Please choose a valid color.';

        if ($name !== '') {
            $check = $pdo->prepare('SELECT id FROM colors WHERE name = ?');
            $check->execute([$name]);
            if ($check->fetch()) $errors[] = 'This color already exists.';
        }

        if (empty($errors)) {
            $pdo->prepare('INSERT INTO colors (name, hex_code, created_at) VALUES (?, ?, NOW())')->execute([$name, $hex]);
            flash('success', 'Color added successfully.');
            redirect(BASE_URL . '/admin/colors/index.php');
        }
    }
}

$colors = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM product_variants pv WHERE pv.color = c.name) AS usage_count
     FROM colors c ORDER BY c.name"
)->fetchAll();

$pageTitle = 'Colors';
$pageHeading = 'Colors';
require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="admin-card">
      <h6 class="mb-3">Add New Color</h6>
      <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endforeach; ?>
      <form method="POST">
        <?= csrfField() ?>
        <div class="mb-3">
          <label class="form-label">Color Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Charcoal" required value="<?= e($_POST['name'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Color Swatch</label>
          <input type="color" name="hex_code" class="form-control form-control-color" value="<?= e($_POST['hex_code'] ?? '#cccccc') ?>" title="Choose color">
        </div>
        <button class="btn btn-primary-admin w-100">Add Color</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="admin-card">
      <h6 class="mb-3">Available Colors</h6>
      <p class="text-muted small">These colors appear as swatches on the shop, product pages, and in the product variant editor.</p>
      <div class="table-responsive">
        <table class="table table-admin">
          <thead><tr><th>Swatch</th><th>Name</th><th>Hex</th><th>Used In</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($colors as $c): ?>
            <tr>
              <td><span style="display:inline-block;width:26px;height:26px;border-radius:50%;background:<?= e($c['hex_code']) ?>;border:1px solid #ddd;"></span></td>
              <td><?= e($c['name']) ?></td>
              <td class="text-muted"><?= e($c['hex_code']) ?></td>
              <td><?= (int)$c['usage_count'] ?> variant<?= $c['usage_count']==1?'':'s' ?></td>
              <td><a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm-msg="Delete this color?"><i class="bi bi-trash"></i></a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($colors)): ?><tr><td colspan="5" class="text-center text-muted py-4">No colors yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
