<?php
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
$user = currentUser();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<header class="site-header sticky-top">
  <div class="top-bar d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
      <span>Free shipping over <?= formatPrice((float)setting('free_shipping_threshold', 75)) ?></span>
      <span><i class="bi bi-telephone"></i> <?= e(setting('phone', '')) ?></span>
    </div>
  </div>

  <nav class="navbar navbar-expand-lg main-nav">
    <div class="container">
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Menu">
        <i class="bi bi-list fs-2"></i>
      </button>

      <a class="navbar-brand brand-logo" href="<?= BASE_URL ?>/index.php">
        <?php if (setting('site_logo')): ?>
          <img src="<?= e(UPLOAD_URL . '/logo/' . setting('site_logo')) ?>" alt="<?= e(setting('store_name', 'LUXE')) ?>" style="max-height:40px;">
        <?php else: ?>
          <?= e(setting('store_name', 'LUXE')) ?>
        <?php endif; ?>
      </a>

      <div class="collapse navbar-collapse d-none d-lg-flex">
        <ul class="navbar-nav mx-auto main-menu">
          <li class="nav-item"><a class="nav-link <?= $currentPage==='index.php'?'active':'' ?>" href="<?= BASE_URL ?>/index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link <?= $currentPage==='shop.php'?'active':'' ?>" href="<?= BASE_URL ?>/shop.php">Shop</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Categories</a>
            <ul class="dropdown-menu">
              <?php
                $navCats = getDB()->query("SELECT name, slug FROM categories WHERE status='active' ORDER BY name")->fetchAll();
                foreach ($navCats as $c):
              ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/category.php?slug=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/shop.php?sort=newest">New Arrivals</a></li>
          <li class="nav-item"><a class="nav-link text-danger" href="<?= BASE_URL ?>/shop.php?on_sale=1">Sale</a></li>
          <li class="nav-item"><a class="nav-link <?= $currentPage==='about.php'?'active':'' ?>" href="<?= BASE_URL ?>/about.php">About</a></li>
          <li class="nav-item"><a class="nav-link <?= $currentPage==='contact.php'?'active':'' ?>" href="<?= BASE_URL ?>/contact.php">Contact</a></li>
        </ul>
      </div>

      <div class="nav-icons d-flex align-items-center gap-3">
        <form class="d-none d-md-block search-form position-relative" action="<?= BASE_URL ?>/search.php" method="GET" autocomplete="off">
          <input type="search" name="q" id="navSearchInput" class="form-control search-input" placeholder="Search products..." value="<?= e($_GET['q'] ?? '') ?>">
          <button type="submit" class="search-btn"><i class="bi bi-search"></i></button>
          <div id="searchSuggestions" class="search-suggestions"></div>
        </form>

        <a href="<?= BASE_URL ?>/search.php" class="nav-icon d-md-none"><i class="bi bi-search"></i></a>

        <div class="dropdown">
          <a href="#" class="nav-icon" data-bs-toggle="dropdown"><i class="bi bi-person"></i></a>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php if ($user): ?>
              <li><span class="dropdown-item-text small text-muted">Hi, <?= e($user['first_name']) ?></span></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/profile.php">My Profile</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/orders.php">My Orders</a></li>
              <?php if ($user['role'] === 'admin'): ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php">Admin Dashboard</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">Logout</a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/login.php">Login</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/register.php">Register</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <a href="<?= BASE_URL ?>/wishlist.php" class="nav-icon position-relative">
          <i class="bi bi-heart"></i>
          <span class="badge-count" id="wishlistCount"><?= $wishlistCount ?></span>
        </a>

        <a href="<?= BASE_URL ?>/cart.php" class="nav-icon position-relative">
          <i class="bi bi-bag"></i>
          <span class="badge-count" id="cartCount"><?= $cartCount ?></span>
        </a>
      </div>
    </div>
  </nav>
</header>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
  <div class="offcanvas-header">
    <h5 class="brand-logo"><?= e(setting('store_name', 'LUXE')) ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="nav flex-column gap-2">
      <?php if ($user): ?>
      <li class="pb-2 mb-2 border-bottom">
        <span class="text-muted small">Hi, <?= e($user['first_name']) ?></span>
      </li>
      <li><a class="nav-link" href="<?= BASE_URL ?>/auth/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>/orders.php"><i class="bi bi-box-seam me-2"></i>My Orders</a></li>
      <?php if ($user['role'] === 'admin'): ?>
      <li><a class="nav-link" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
      <?php endif; ?>
      <li><a class="nav-link" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      <?php else: ?>
      <li class="pb-2 mb-2 border-bottom d-flex gap-2">
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-dark btn-sm flex-grow-1">Login</a>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline-dark btn-sm flex-grow-1">Create Account</a>
      </li>
      <?php endif; ?>
      <li><a class="nav-link" href="<?= BASE_URL ?>/index.php">Home</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>/shop.php">Shop</a></li>
      <?php foreach ($navCats as $c): ?>
      <li><a class="nav-link ps-3" href="<?= BASE_URL ?>/category.php?slug=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
      <?php endforeach; ?>
      <li><a class="nav-link" href="<?= BASE_URL ?>/shop.php?sort=newest">New Arrivals</a></li>
      <li><a class="nav-link text-danger" href="<?= BASE_URL ?>/shop.php?on_sale=1">Sale</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>/wishlist.php"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>/cart.php"><i class="bi bi-bag me-2"></i>Cart</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>/about.php">About</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>/contact.php">Contact</a></li>
    </ul>
  </div>
</div>
