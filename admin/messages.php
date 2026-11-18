<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();

if (isset($_GET['read'])) {
    $pdo->prepare("UPDATE contact_messages SET status='read' WHERE id = ?")->execute([(int)$_GET['read']]);
    redirect(BASE_URL . '/admin/messages.php');
}
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([(int)$_GET['delete']]);
    redirect(BASE_URL . '/admin/messages.php');
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Messages';
$pageHeading = 'Contact Messages';
require_once __DIR__ . '/includes/header.php';
?>
<div class="admin-card">
  <?php if (empty($messages)): ?>
    <p class="text-muted text-center py-4">No messages yet.</p>
  <?php endif; ?>
  <?php foreach ($messages as $m): ?>
  <div class="border-bottom py-3 d-flex justify-content-between align-items-start">
    <div>
      <div class="d-flex align-items-center gap-2">
        <strong><?= e($m['name']) ?></strong>
        <?php if ($m['status'] === 'new'): ?><span class="badge bg-primary">New</span><?php endif; ?>
      </div>
      <div class="text-muted small"><?= e($m['email']) ?> <?= $m['phone'] ? '· ' . e($m['phone']) : '' ?></div>
      <?php if ($m['subject']): ?><div class="fw-medium mt-1"><?= e($m['subject']) ?></div><?php endif; ?>
      <p class="mb-0 mt-1"><?= nl2br(e($m['message'])) ?></p>
      <div class="text-muted small mt-1"><?= date('M j, Y g:i A', strtotime($m['created_at'])) ?></div>
    </div>
    <div class="text-nowrap">
      <?php if ($m['status'] === 'new'): ?><a href="?read=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary">Mark Read</a><?php endif; ?>
      <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm-msg="Delete this message?"><i class="bi bi-trash"></i></a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
