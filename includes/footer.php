  <footer class="site-footer">
    <div class="newsletter-section">
      <div class="container text-center">
        <h3>Join Our Newsletter</h3>
        <p class="text-muted">Get updates on new arrivals and exclusive offers.</p>
        <form class="newsletter-form mx-auto" id="newsletterForm">
          <input type="email" name="email" class="form-control" placeholder="Your email address" required>
          <button type="submit" class="btn btn-dark">Subscribe</button>
        </form>
      </div>
    </div>

    <div class="container py-5">
      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <h5 class="brand-logo mb-3"><?= e(setting('store_name', 'LUXE')) ?></h5>
          <p class="text-muted"><?= e(setting('footer_about_text') ?: setting('address', '')) ?></p>
          <p class="text-muted mb-1"><i class="bi bi-envelope me-2"></i><?= e(setting('email', '')) ?></p>
          <p class="text-muted"><i class="bi bi-telephone me-2"></i><?= e(setting('phone', '')) ?></p>
          <div class="social-icons mt-3">
            <a href="<?= e(setting('facebook', '#')) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
            <a href="<?= e(setting('instagram', '#')) ?>" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>
            <a href="<?= e(setting('tiktok', '#')) ?>" target="_blank" rel="noopener"><i class="bi bi-tiktok"></i></a>
            <a href="<?= e(setting('whatsapp', '#')) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-md-6">
          <h6>Shop</h6>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/category.php?slug=men">Men</a></li>
            <li><a href="<?= BASE_URL ?>/category.php?slug=women">Women</a></li>
            <li><a href="<?= BASE_URL ?>/category.php?slug=shoes">Shoes</a></li>
            <li><a href="<?= BASE_URL ?>/category.php?slug=accessories">Accessories</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-6">
          <h6>Account</h6>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/auth/login.php">Login</a></li>
            <li><a href="<?= BASE_URL ?>/orders.php">My Orders</a></li>
            <li><a href="<?= BASE_URL ?>/wishlist.php">Wishlist</a></li>
            <li><a href="<?= BASE_URL ?>/cart.php">Cart</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-6">
          <h6>Company</h6>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/about.php">About Us</a></li>
            <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-6">
          <h6>Help</h6>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/shop.php">Shipping Info</a></li>
            <li><a href="<?= BASE_URL ?>/contact.php">Returns</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="footer-bottom text-center py-3">
      <small>&copy; <?= date('Y') ?> <?= e(setting('store_name', 'LUXE Clothing')) ?>. All rights reserved.</small>
    </div>
  </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
  window.CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;
  window.IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/cart.js"></script>
<script src="<?= BASE_URL ?>/assets/js/wishlist.js"></script>
<?php if (!empty($extraScripts)) foreach ($extraScripts as $s): ?>
<script src="<?= e($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
