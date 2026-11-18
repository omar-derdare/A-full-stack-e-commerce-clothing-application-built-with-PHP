<?php
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$errors = [];
$redirectTo = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $errors[] = 'Please enter both email and password.';
        } else {
            $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Invalid email or password.';
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'Your account has been deactivated. Please contact support.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'];

                if ($redirectTo && strpos($redirectTo, '/') === 0) {
                    redirect(BASE_URL . $redirectTo);
                }
                redirect($user['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
            }
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="auth-card">
    <h3 class="text-center mb-4">Welcome Back</h3>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="POST" novalidate>
      <?= csrfField() ?>
      <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">
      <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-dark w-100">Login</button>
    </form>
    <p class="text-center mt-3 mb-0 small">Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Register</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
