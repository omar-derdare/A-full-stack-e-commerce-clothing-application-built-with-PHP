<?php
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$errors = [];
$old = ['first_name'=>'','last_name'=>'','email'=>'','phone'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $old = compact('firstName','lastName','email','phone');
        $old = ['first_name'=>$firstName,'last_name'=>$lastName,'email'=>$email,'phone'=>$phone];

        if ($firstName === '' || $lastName === '') $errors[] = 'Please enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) $errors[] = 'Please enter a valid phone number.';

        if (empty($errors)) {
            $stmt = getDB()->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists.';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = getDB()->prepare(
                'INSERT INTO users (first_name, last_name, email, password, phone, role, status, created_at)
                 VALUES (?, ?, ?, ?, ?, "customer", "active", NOW())'
            );
            $stmt->execute([$firstName, $lastName, $email, $hash, $phone ?: null]);
            $userId = getDB()->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_role'] = 'customer';
            $_SESSION['user_name'] = $firstName;

            redirect(BASE_URL . '/index.php');
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="auth-card">
    <h3 class="text-center mb-4">Create Account</h3>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="POST" novalidate>
      <?= csrfField() ?>
      <div class="row">
        <div class="col-6 mb-3">
          <label class="form-label">First name</label>
          <input type="text" name="first_name" class="form-control" required value="<?= e($old['first_name']) ?>">
        </div>
        <div class="col-6 mb-3">
          <label class="form-label">Last name</label>
          <input type="text" name="last_name" class="form-control" required value="<?= e($old['last_name']) ?>">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= e($old['phone']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required minlength="6">
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required minlength="6">
      </div>
      <button type="submit" class="btn btn-dark w-100">Create Account</button>
    </form>
    <p class="text-center mt-3 mb-0 small">Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Login</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
