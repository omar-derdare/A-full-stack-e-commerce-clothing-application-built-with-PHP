<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$fields = ['store_name','phone','email','address','currency','currency_symbol','shipping_price','free_shipping_threshold','facebook','instagram','tiktok','whatsapp','meta_description','footer_about_text'];
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        foreach ($fields as $key) {
            $value = trim($_POST[$key] ?? '');
            $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
            $stmt->execute([$key, $value, $value]);
        }

        try {
            $logo = handleImageUpload($_FILES['site_logo'] ?? [], 'logo');
            if ($logo) {
                $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES ("site_logo", ?) ON DUPLICATE KEY UPDATE setting_value = ?');
                $stmt->execute([$logo, $logo]);
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        if (empty($errors)) {
            $success = true;
        }
    }
}

$settings = getSettings();
// Refresh cache after update
if ($success) {
    $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
    $settings = [];
    foreach ($stmt->fetchAll() as $row) $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Settings';
$pageHeading = 'Settings';
require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">Settings saved successfully.</div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="admin-card mb-3">
        <h6 class="mb-3">Store Information</h6>
        <div class="mb-3"><label class="form-label">Store Name</label><input type="text" name="store_name" class="form-control" value="<?= e($settings['store_name'] ?? '') ?>"></div>
        <div class="mb-3">
          <label class="form-label">Site Logo</label><br>
          <?php if (!empty($settings['site_logo'])): ?>
            <img src="<?= e(UPLOAD_URL . '/logo/' . $settings['site_logo']) ?>" class="image-preview mb-2" style="max-height:60px;">
          <?php endif; ?>
          <input type="file" name="site_logo" class="form-control admin-image-input" data-preview="logoPreview" accept=".jpg,.jpeg,.png,.webp">
          <img id="logoPreview" class="image-preview d-none" style="max-height:60px;">
          <div class="form-text">Optional. If not set, your store name is shown as text in the header.</div>
        </div>
        <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($settings['phone'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($settings['email'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?= e($settings['address'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Footer About Text</label><textarea name="footer_about_text" class="form-control" rows="2"><?= e($settings['footer_about_text'] ?? '') ?></textarea></div>
        <div class="mb-0"><label class="form-label">Default Meta Description (SEO)</label><textarea name="meta_description" class="form-control" rows="2"><?= e($settings['meta_description'] ?? '') ?></textarea></div>
      </div>

      <div class="admin-card">
        <h6 class="mb-3">Shipping & Currency</h6>
        <div class="row">
          <div class="col-6 mb-3"><label class="form-label">Currency Code</label><input type="text" name="currency" class="form-control" value="<?= e($settings['currency'] ?? 'USD') ?>"></div>
          <div class="col-6 mb-3"><label class="form-label">Currency Symbol</label><input type="text" name="currency_symbol" class="form-control" value="<?= e($settings['currency_symbol'] ?? '$') ?>"></div>
        </div>
        <div class="row">
          <div class="col-6 mb-3"><label class="form-label">Shipping Price</label><input type="number" step="0.01" name="shipping_price" class="form-control" value="<?= e($settings['shipping_price'] ?? '5') ?>"></div>
          <div class="col-6 mb-0"><label class="form-label">Free Shipping Threshold</label><input type="number" step="0.01" name="free_shipping_threshold" class="form-control" value="<?= e($settings['free_shipping_threshold'] ?? '75') ?>"></div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="admin-card">
        <h6 class="mb-3">Social Links</h6>
        <p class="text-muted small">These links appear in the footer and are used throughout the site (e.g. WhatsApp button).</p>
        <div class="mb-3"><label class="form-label"><i class="bi bi-facebook me-1"></i> Facebook</label><input type="text" name="facebook" class="form-control" value="<?= e($settings['facebook'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label"><i class="bi bi-instagram me-1"></i> Instagram</label><input type="text" name="instagram" class="form-control" value="<?= e($settings['instagram'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label"><i class="bi bi-tiktok me-1"></i> TikTok</label><input type="text" name="tiktok" class="form-control" value="<?= e($settings['tiktok'] ?? '') ?>"></div>
        <div class="mb-0"><label class="form-label"><i class="bi bi-whatsapp me-1"></i> WhatsApp Link</label><input type="text" name="whatsapp" class="form-control" value="<?= e($settings['whatsapp'] ?? '') ?>"></div>
      </div>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary-admin">Save Settings</button>
  </div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
