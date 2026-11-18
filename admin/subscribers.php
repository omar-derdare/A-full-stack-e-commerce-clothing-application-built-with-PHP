<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id = ?')->execute([(int)$_GET['delete']]);
    redirect(BASE_URL . '/admin/subscribers.php');
}

if (isset($_GET['export'])) {
    $rows = $pdo->query('SELECT email, created_at FROM newsletter_subscribers ORDER BY created_at DESC')->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter_subscribers.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Subscribed At']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['email'], $r['created_at']]);
    }
    fclose($out);
    exit;
}

$subscribers = $pdo->query('SELECT * FROM newsletter_subscribers ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Newsletter Subscribers';
$pageHeading = 'Newsletter Subscribers';
require_once __DIR__ . '/includes/header.php';
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted small mb-0"><?= count($subscribers) ?> subscriber<?= count($subscribers)===1?'':'s' ?> total</p>
    <?php if ($subscribers): ?>
    <a href="<?= BASE_URL ?>/admin/subscribers.php?export=1" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Export CSV</a>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table table-admin">
      <thead><tr><th>Email</th><th>Subscribed</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($subscribers as $s): ?>
        <tr>
          <td><?= e($s['email']) ?></td>
          <td><?= date('M j, Y g:i A', strtotime($s['created_at'])) ?></td>
          <td><a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm-msg="Remove this subscriber?"><i class="bi bi-trash"></i></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($subscribers)): ?><tr><td colspan="3" class="text-center text-muted py-4">No subscribers yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
