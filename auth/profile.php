<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = currentUser();
$successMsg = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $formType = $_POST['form_type'] ?? '';

        if ($formType === 'profile') {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name'] ?? '');
            $phone     = trim($_POST['phone'] ?? '');
            $address   = trim($_POST['address'] ?? '');
            $city      = trim($_POST['city'] ?? '');

            if ($firstName === '' || $lastName === '') {
                $errors[] = 'First and last name are required.';
            } else {
                $stmt = getDB()->prepare(
                    'UPDATE users SET first_name=?, last_name=?, phone=?, address=?, city=? WHERE id=?'
                );
                $stmt->execute([$firstName, $lastName, $phone ?: null, $address ?: null, $city ?: null, $user['id']]);
                $_SESSION['user_name'] = $firstName;
                $successMsg = 'Profile updated successfully.';
                $user = currentUser();
            }
        }

        if ($formType === 'password') {
            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (!password_verify($current, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($new) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($new !== $confirm) {
                $errors[] = 'New passwords do not match.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = getDB()->prepare('UPDATE users SET password=? WHERE id=?');
                $stmt->execute([$hash, $user['id']]);
                $successMsg = 'Password changed successfully.';
            }
        }
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
  <h3 class="mb-4">My Profile</h3>
  <?php if ($successMsg): ?><div class="alert alert-success"><?= e($successMsg) ?></div><?php endif; ?>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="p-4" style="background:var(--cream);border-radius:2px;">
        <h5 class="mb-3">Personal Information</h5>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="form_type" value="profile">
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">First name</label>
              <input type="text" name="first_name" class="form-control" value="<?= e($user['first_name']) ?>" required>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Last name</label>
              <input type="text" name="last_name" class="form-control" value="<?= e($user['last_name']) ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" value="<?= e($user['address']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control" value="<?= e($user['city']) ?>">
          </div>
          <button class="btn btn-dark">Save Changes</button>
        </form>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="p-4" style="background:var(--cream);border-radius:2px;">
        <h5 class="mb-3">Change Password</h5>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="form_type" value="password">
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6">
          </div>
          <button class="btn btn-outline-dark">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
