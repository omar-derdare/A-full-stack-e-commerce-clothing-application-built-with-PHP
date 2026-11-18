<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <div class="row align-items-center g-5">
    <div class="col-lg-6">
      <img src="<?= BASE_URL ?>/assets/images/placeholder.jpg" class="img-fluid rounded" alt="About us">
    </div>
    <div class="col-lg-6">
      <span class="text-uppercase small" style="color:var(--accent);letter-spacing:2px;">Our Story</span>
      <h2 class="mt-2 mb-3">Crafted for Everyday Elegance</h2>
      <p class="text-muted"><?= e(setting('store_name', 'LUXE Clothing')) ?> was founded on a simple idea: premium clothing shouldn't be complicated. We design pieces that are comfortable, durable, and made to fit seamlessly into your everyday life.</p>
      <p class="text-muted">From our carefully sourced fabrics to our attention to fit and finish, every piece in our collection is created with intention. We believe in quality over quantity, and timeless design over fleeting trends.</p>
      <div class="row g-3 mt-3">
        <div class="col-4 text-center">
          <h3 class="mb-0"><?= (int)(getDB()->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn()) ?>+</h3>
          <div class="small text-muted">Products</div>
        </div>
        <div class="col-4 text-center">
          <h3 class="mb-0"><?= (int)(getDB()->query("SELECT COUNT(*) FROM orders")->fetchColumn()) ?>+</h3>
          <div class="small text-muted">Orders Shipped</div>
        </div>
        <div class="col-4 text-center">
          <h3 class="mb-0">4.8<i class="bi bi-star-fill text-warning fs-6"></i></h3>
          <div class="small text-muted">Avg. Rating</div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
