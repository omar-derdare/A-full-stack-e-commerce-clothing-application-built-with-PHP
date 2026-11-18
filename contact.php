<?php
require_once __DIR__ . '/includes/functions.php';

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            $errors[] = 'Please fill in your name, a valid email, and a message.';
        } else {
            $stmt = getDB()->prepare(
                'INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, "new", NOW())'
            );
            $stmt->execute([$name, $email, $phone ?: null, $subject ?: null, $message]);
            $success = true;
        }
    }
}

$pageTitle = 'Contact Us';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <div class="section-title"><h2>Get in Touch</h2><p>We'd love to hear from you</p></div>
  <div class="row g-5">
    <div class="col-lg-5">
      <div class="mb-3"><i class="bi bi-geo-alt me-2"></i><?= e(setting('address', '')) ?></div>
      <div class="mb-3"><i class="bi bi-envelope me-2"></i><?= e(setting('email', '')) ?></div>
      <div class="mb-3"><i class="bi bi-telephone me-2"></i><?= e(setting('phone', '')) ?></div>
    </div>
    <div class="col-lg-7">
      <?php if ($success): ?>
        <div class="alert alert-success">Thank you! Your message has been sent. We'll get back to you soon.</div>
      <?php endif; ?>
      <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
      <form method="POST">
        <?= csrfField() ?>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Subject</label>
            <input type="text" name="subject" class="form-control">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Message</label>
          <textarea name="message" class="form-control" rows="5" required></textarea>
        </div>
        <button class="btn btn-dark">Send Message</button>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
