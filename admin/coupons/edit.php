<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM coupons WHERE id = ?');
$stmt->execute([$id]);
$coupon = $stmt->fetch();
if (!$coupon) {
    flash('error', 'Coupon not found.');
    redirect(BASE_URL . '/admin/coupons/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = $_POST['type'] ?? 'percentage';
        $value = (float)($_POST['value'] ?? 0);
        $minAmount = (float)($_POST['minimum_amount'] ?? 0);
        $maxDiscount = $_POST['maximum_discount'] !== '' ? (float)$_POST['maximum_discount'] : null;
        $expiresAt = $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null;
        $usageLimit = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
        $status = $_POST['status'] ?? 'active';

        if ($code === '') $errors[] = 'Coupon code is required.';
        if ($value <= 0) $errors[] = 'Value must be greater than 0.';
        if ($type === 'percentage' && $value > 100) $errors[] = 'Percentage value cannot exceed 100.';

        if ($code !== '') {
            $check = $pdo->prepare('SELECT id FROM coupons WHERE code = ? AND id != ?');
            $check->execute([$code, $id]);
            if ($check->fetch()) $errors[] = 'This coupon code already exists.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'UPDATE coupons SET code=?, type=?, value=?, minimum_amount=?, maximum_discount=?, expires_at=?, usage_limit=?, status=? WHERE id=?'
            );
            $stmt->execute([$code, $type, $value, $minAmount, $maxDiscount, $expiresAt, $usageLimit, $status, $id]);
            flash('success', 'Coupon updated successfully.');
            redirect(BASE_URL . '/admin/coupons/index.php');
        }
    }
}

$pageTitle = 'Edit Coupon';
$pageHeading = 'Edit Coupon';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-card" style="max-width:600px;">
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
  <form method="POST">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label">Coupon Code</label>
      <input type="text" name="code" class="form-control text-uppercase" required value="<?= e($coupon['code']) ?>">
    </div>
    <div class="row">
      <div class="col-6 mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select">
          <option value="percentage" <?= $coupon['type']==='percentage'?'selected':'' ?>>Percentage</option>
          <option value="fixed" <?= $coupon['type']==='fixed'?'selected':'' ?>>Fixed Amount</option>
        </select>
      </div>
      <div class="col-6 mb-3">
        <label class="form-label">Value</label>
        <input type="number" step="0.01" min="0" name="value" class="form-control" required value="<?= e($coupon['value']) ?>">
      </div>
    </div>
    <div class="row">
      <div class="col-6 mb-3">
        <label class="form-label">Minimum Order Amount</label>
        <input type="number" step="0.01" min="0" name="minimum_amount" class="form-control" value="<?= e($coupon['minimum_amount']) ?>">
      </div>
      <div class="col-6 mb-3">
        <label class="form-label">Maximum Discount (optional)</label>
        <input type="number" step="0.01" min="0" name="maximum_discount" class="form-control" value="<?= e($coupon['maximum_discount']) ?>">
      </div>
    </div>
    <div class="row">
      <div class="col-6 mb-3">
        <label class="form-label">Expires At (optional)</label>
        <input type="date" name="expires_at" class="form-control" value="<?= e($coupon['expires_at']) ?>">
      </div>
      <div class="col-6 mb-3">
        <label class="form-label">Usage Limit (optional)</label>
        <input type="number" min="1" name="usage_limit" class="form-control" value="<?= e($coupon['usage_limit']) ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active" <?= $coupon['status']==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $coupon['status']==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>
    <button class="btn btn-primary-admin">Save Changes</button>
    <a href="<?= BASE_URL ?>/admin/coupons/index.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
