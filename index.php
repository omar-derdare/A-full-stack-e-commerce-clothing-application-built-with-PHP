<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getDB();

$heroSlides = getHeroSlides();
$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY name LIMIT 4")->fetchAll();
$newArrivals = $pdo->query("SELECT * FROM products WHERE status='active' ORDER BY created_at DESC LIMIT 8")->fetchAll();
$featured = $pdo->query("SELECT * FROM products WHERE status='active' AND featured=1 ORDER BY created_at DESC LIMIT 8")->fetchAll();
$saleProducts = $pdo->query("SELECT * FROM products WHERE status='active' AND sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price ORDER BY created_at DESC LIMIT 8")->fetchAll();

$wishlistIds = [];
if (isLoggedIn()) {
    $w = $pdo->prepare('SELECT product_id FROM wishlists WHERE user_id = ?');
    $w->execute([currentUserId()]);
    $wishlistIds = array_column($w->fetchAll(), 'product_id');
}

$pageTitle = 'Premium Clothing Store';
$metaDescription = 'Shop premium men\'s and women\'s clothing, shoes and accessories. New arrivals every week.';
require_once __DIR__ . '/includes/product-functions.php';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($heroSlides): ?>
<section class="hero-slider">
  <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5500">
    <?php if (count($heroSlides) > 1): ?>
    <div class="carousel-indicators">
      <?php foreach ($heroSlides as $i => $slide): ?>
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="carousel-inner">
      <?php foreach ($heroSlides as $i => $slide): ?>
      <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>" style="background-image:url('<?= e(heroSlideImageUrl($slide['image'])) ?>')">
        <div class="hero-slide-overlay"></div>
        <div class="container hero-slide-content">
          <div class="row">
            <div class="col-lg-6">
              <h1><?= e($slide['title']) ?></h1>
              <?php if ($slide['subtitle']): ?><p><?= e($slide['subtitle']) ?></p><?php endif; ?>
              <?php if ($slide['button_text']): ?>
              <a href="<?= e(BASE_URL . '/' . ltrim($slide['button_link'] ?: 'shop.php', '/')) ?>" class="btn btn-dark"><?= e($slide['button_text']) ?></a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (count($heroSlides) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
    <?php endif; ?>
  </div>
</section>
<?php else: ?>
<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <span class="text-uppercase small fw-medium" style="letter-spacing:2px;color:var(--accent)">New Season</span>
        <h1>Elevate Your<br>Everyday Style</h1>
        <p>Discover premium clothing crafted for comfort and made to last — for men and women who value quality.</p>
        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-dark">Shop Collection</a>
      </div>
      <div class="col-lg-6 text-center d-none d-lg-block">
        <img src="<?= BASE_URL ?>/assets/images/placeholder.jpg" alt="Hero" class="img-fluid rounded" style="max-height:420px;object-fit:cover;">
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="section-title">
      <h2>Shop by Category</h2>
      <p>Explore our curated collections</p>
    </div>
    <div class="row g-3">
      <?php foreach ($categories as $cat): ?>
      <div class="col-6 col-lg-3">
        <a href="<?= BASE_URL ?>/category.php?slug=<?= e($cat['slug']) ?>" class="category-card">
          <img src="<?= e(categoryImageUrl($cat['image'])) ?>" alt="<?= e($cat['name']) ?>" loading="lazy">
          <div class="overlay"><h5><?= e($cat['name']) ?></h5></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($newArrivals): ?>
<section class="section" style="background:var(--cream)">
  <div class="container">
    <div class="section-title"><h2>New Arrivals</h2><p>Fresh drops just for you</p></div>
    <div class="row">
      <?php foreach ($newArrivals as $p) renderProductCard($p, $wishlistIds); ?>
    </div>
    <div class="text-center mt-3"><a href="<?= BASE_URL ?>/shop.php?sort=newest" class="btn btn-outline-dark">View All</a></div>
  </div>
</section>
<?php endif; ?>

<?php if ($featured): ?>
<section class="section">
  <div class="container">
    <div class="section-title"><h2>Featured Products</h2><p>Hand-picked favorites</p></div>
    <div class="row">
      <?php foreach ($featured as $p) renderProductCard($p, $wishlistIds); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="promo-banner">
      <h3>End of Season Sale — Up to 40% Off</h3>
      <p class="mb-4">Limited time only. Don't miss out on our best deals of the year.</p>
      <a href="<?= BASE_URL ?>/shop.php?on_sale=1" class="btn btn-outline-light">Shop the Sale</a>
    </div>
  </div>
</section>

<?php if ($saleProducts): ?>
<section class="section" style="background:var(--cream)">
  <div class="container">
    <div class="section-title"><h2>On Sale</h2><p>Grab them before they're gone</p></div>
    <div class="row">
      <?php foreach ($saleProducts as $p) renderProductCard($p, $wishlistIds); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section text-center">
  <div class="container">
    <div class="section-title"><h2>Follow Us on Instagram</h2><p>@<?= e(strtolower(str_replace(' ', '', setting('store_name','luxe')))) ?></p></div>
    <div class="row g-2">
      <?php for ($i = 0; $i < 6; $i++): ?>
      <div class="col-4 col-md-2">
        <img src="<?= BASE_URL ?>/assets/images/placeholder.jpg" class="img-fluid" style="aspect-ratio:1/1;object-fit:cover;" loading="lazy">
      </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
