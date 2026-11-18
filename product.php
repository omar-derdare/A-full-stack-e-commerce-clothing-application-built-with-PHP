<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product-functions.php';
$pdo = getDB();

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.status = 'active' LIMIT 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container section text-center"><div class="empty-state"><i class="bi bi-emoji-frown"></i><h4 class="mt-3">Product not found</h4><a href="' . BASE_URL . '/shop.php" class="btn btn-dark mt-3">Browse Products</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$images = getProductImages((int)$product['id']);
$variants = getProductVariants((int)$product['id']);
$priceInfo = productDisplayPrice($product);
$rating = getProductAverageRating((int)$product['id']);

$sizes = array_values(array_unique(array_filter(array_column($variants, 'size'))));
$colors = array_values(array_unique(array_filter(array_column($variants, 'color'))));

$inWishlist = false;
if (isLoggedIn()) {
    $w = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?');
    $w->execute([currentUserId(), $product['id']]);
    $inWishlist = (bool)$w->fetch();
}

// Reviews
$reviewsStmt = $pdo->prepare(
    "SELECT r.*, u.first_name, u.last_name FROM reviews r JOIN users u ON u.id = r.user_id
     WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC LIMIT 20"
);
$reviewsStmt->execute([$product['id']]);
$reviews = $reviewsStmt->fetchAll();

$canReview = false;
$alreadyReviewed = false;
if (isLoggedIn()) {
    $purchaseCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM order_items oi JOIN orders o ON o.id = oi.order_id
         WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status != 'cancelled'"
    );
    $purchaseCheck->execute([currentUserId(), $product['id']]);
    $canReview = (int)$purchaseCheck->fetchColumn() > 0;

    $reviewedCheck = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ?');
    $reviewedCheck->execute([currentUserId(), $product['id']]);
    $alreadyReviewed = (int)$reviewedCheck->fetchColumn() > 0;
}

// Review submission
$reviewError = '';
$reviewSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $reviewError = 'Security token expired.';
    } elseif (!isLoggedIn()) {
        $reviewError = 'Please login to leave a review.';
    } elseif (!$canReview) {
        $reviewError = 'Only customers who purchased this product can review it.';
    } elseif ($alreadyReviewed) {
        $reviewError = 'You have already reviewed this product.';
    } else {
        $ratingVal = max(1, min(5, (int)($_POST['rating'] ?? 0)));
        $comment = trim($_POST['comment'] ?? '');
        $ins = $pdo->prepare('INSERT INTO reviews (user_id, product_id, rating, comment, status, created_at) VALUES (?, ?, ?, ?, "pending", NOW())');
        $ins->execute([currentUserId(), $product['id'], $ratingVal, $comment]);
        $reviewSuccess = 'Thank you! Your review has been submitted and is awaiting approval.';
        $alreadyReviewed = true;
    }
}

// Related products (same category)
$relatedStmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND status='active' ORDER BY RAND() LIMIT 4");
$relatedStmt->execute([$product['category_id'], $product['id']]);
$related = $relatedStmt->fetchAll();

// Recently viewed (session based)
if (empty($_SESSION['recently_viewed'])) $_SESSION['recently_viewed'] = [];
$_SESSION['recently_viewed'] = array_values(array_diff($_SESSION['recently_viewed'], [(int)$product['id']]));
array_unshift($_SESSION['recently_viewed'], (int)$product['id']);
$_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 8);

$recentlyViewed = [];
$viewedIds = array_filter(array_slice($_SESSION['recently_viewed'], 1), fn($id) => $id != $product['id']);
if ($viewedIds) {
    $placeholders = implode(',', array_fill(0, count($viewedIds), '?'));
    $rv = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND status='active'");
    $rv->execute($viewedIds);
    $recentlyViewed = $rv->fetchAll();
}

$wishlistIds = $inWishlist ? [(int)$product['id']] : [];

$pageTitle = $product['name'];
$metaDescription = $product['short_description'] ?: substr(strip_tags($product['description']), 0, 160);
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <nav class="small text-muted mb-3">
    <a href="<?= BASE_URL ?>/index.php" class="text-muted">Home</a> /
    <a href="<?= BASE_URL ?>/category.php?slug=<?= e($product['category_slug']) ?>" class="text-muted"><?= e($product['category_name']) ?></a> /
    <span><?= e($product['name']) ?></span>
  </nav>

  <div class="row g-5">
    <div class="col-lg-6">
      <div class="product-gallery-main mb-3" id="mainImageWrap">
        <img id="mainImage" src="<?= e(productImageUrl($product['featured_image'])) ?>" alt="<?= e($product['name']) ?>">
      </div>
      <?php if ($images): ?>
      <div class="d-flex gap-2 product-gallery-thumbs flex-wrap">
        <img src="<?= e(productImageUrl($product['featured_image'])) ?>" class="active" data-full="<?= e(productImageUrl($product['featured_image'])) ?>">
        <?php foreach ($images as $img): ?>
        <img src="<?= e(productImageUrl($img['image'])) ?>" data-full="<?= e(productImageUrl($img['image'])) ?>">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-6">
      <span class="text-uppercase small text-muted"><?= e($product['category_name']) ?></span>
      <h2 class="mt-1 mb-2"><?= e($product['name']) ?></h2>

      <div class="d-flex align-items-center gap-2 mb-3">
        <span class="stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="bi <?= $i <= round($rating['average']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
          <?php endfor; ?>
        </span>
        <span class="text-muted small">(<?= $rating['total'] ?> review<?= $rating['total'] === 1 ? '' : 's' ?>)</span>
      </div>

      <div class="d-flex align-items-center gap-3 mb-3">
        <span class="fs-4 fw-bold"><?= formatPrice($priceInfo['final']) ?></span>
        <?php if ($priceInfo['has_sale']): ?>
          <span class="price-old fs-5"><?= formatPrice($priceInfo['price']) ?></span>
          <span class="badge-sale">-<?= $priceInfo['discount'] ?>%</span>
        <?php endif; ?>
      </div>

      <p class="text-muted mb-1"><strong>SKU:</strong> <span id="skuDisplay"><?= e($product['sku']) ?></span></p>
      <p class="mb-3">
        <?php if ($product['stock'] > 0): ?>
          <span class="text-success"><i class="bi bi-check-circle"></i> In Stock</span>
        <?php else: ?>
          <span class="text-danger"><i class="bi bi-x-circle"></i> Out of Stock</span>
        <?php endif; ?>
      </p>

      <p><?= nl2br(e($product['short_description'] ?: substr(strip_tags($product['description']), 0, 200))) ?></p>

      <form id="addToCartForm">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

        <?php if ($sizes): ?>
        <div class="mb-3">
          <label class="form-label fw-medium">Size</label><br>
          <?php foreach ($sizes as $s): ?>
            <span class="size-box variant-size" data-size="<?= e($s) ?>"><?= e($s) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($colors): ?>
        <div class="mb-3">
          <label class="form-label fw-medium">Color</label><br>
          <?php
          foreach ($colors as $c):
            $hex = colorHex($c);
          ?>
            <span class="color-swatch variant-color" data-color="<?= e($c) ?>" title="<?= e($c) ?>" style="background:<?= $hex ?>"></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div id="variantStockMsg" class="small text-muted mb-2"></div>

        <div class="mb-4 d-flex align-items-center gap-3">
          <label class="form-label fw-medium mb-0">Quantity</label>
          <div class="qty-selector">
            <button type="button" id="qtyDec">−</button>
            <input type="number" id="qtyInput" value="1" min="1">
            <button type="button" id="qtyInc">+</button>
          </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <button type="submit" class="btn btn-dark flex-grow-1" id="addToCartBtn" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
            <i class="bi bi-bag-plus"></i> Add to Cart
          </button>
          <button type="button" class="btn btn-outline-dark flex-grow-1" id="buyNowBtn" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>Buy Now</button>
          <button type="button" class="btn btn-outline-dark wishlist-toggle-btn wishlist-btn <?= $inWishlist ? 'active' : '' ?>" style="position:static;opacity:1;width:48px;height:48px;border-radius:2px;" data-product-id="<?= $product['id'] ?>">
            <i class="bi <?= $inWishlist ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
          </button>
        </div>
      </form>

      <hr class="my-4">
      <h6>Description</h6>
      <p class="text-muted"><?= nl2br(e($product['description'])) ?></p>
    </div>
  </div>

  <!-- Reviews -->
  <div class="mt-5">
    <h4 class="mb-4">Customer Reviews</h4>
    <div class="row">
      <div class="col-lg-7">
        <?php if (empty($reviews)): ?>
          <p class="text-muted">No reviews yet. Be the first to review this product!</p>
        <?php else: ?>
          <?php foreach ($reviews as $r): ?>
          <div class="border-bottom pb-3 mb-3">
            <div class="d-flex justify-content-between">
              <strong><?= e($r['first_name']) ?> <?= e(substr($r['last_name'],0,1)) ?>.</strong>
              <span class="text-muted small"><?= timeAgo($r['created_at']) ?></span>
            </div>
            <div class="stars small mb-1">
              <?php for ($i=1;$i<=5;$i++): ?><i class="bi <?= $i <= $r['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i><?php endfor; ?>
            </div>
            <p class="mb-0"><?= nl2br(e($r['comment'])) ?></p>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="col-lg-5">
        <div class="p-4" style="background:var(--cream);border-radius:2px;">
          <h6>Write a Review</h6>
          <?php if ($reviewError): ?><div class="alert alert-danger py-2 small"><?= e($reviewError) ?></div><?php endif; ?>
          <?php if ($reviewSuccess): ?><div class="alert alert-success py-2 small"><?= e($reviewSuccess) ?></div><?php endif; ?>

          <?php if (!isLoggedIn()): ?>
            <p class="small text-muted">Please <a href="<?= BASE_URL ?>/auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">login</a> to write a review.</p>
          <?php elseif ($alreadyReviewed): ?>
            <p class="small text-muted">You've already reviewed this product. Thank you!</p>
          <?php elseif (!$canReview): ?>
            <p class="small text-muted">Only customers who purchased this product can leave a review.</p>
          <?php else: ?>
            <form method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="submit_review" value="1">
              <input type="hidden" name="rating" id="ratingInput" value="5">
              <div class="rating-stars-input mb-2" id="ratingStars">
                <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star-fill <?= $i<=5?'active':'' ?>" data-val="<?= $i ?>"></i><?php endfor; ?>
              </div>
              <textarea name="comment" class="form-control mb-2" rows="3" placeholder="Share your experience..." required></textarea>
              <button class="btn btn-dark btn-sm">Submit Review</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php if ($related): ?>
  <div class="mt-5">
    <h4 class="mb-4">Related Products</h4>
    <div class="row"><?php foreach ($related as $p) renderProductCard($p, $wishlistIds); ?></div>
  </div>
  <?php endif; ?>

  <?php if ($recentlyViewed): ?>
  <div class="mt-5">
    <h4 class="mb-4">Recently Viewed</h4>
    <div class="row"><?php foreach ($recentlyViewed as $p) renderProductCard($p, $wishlistIds); ?></div>
  </div>
  <?php endif; ?>
</div>

<script>window.IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;</script>
<script>
(function () {
  const variants = <?= json_encode($variants) ?>;
  const requiresVariant = <?= (!empty($sizes) || !empty($colors)) ? 'true' : 'false' ?>;
  let selectedSize = null, selectedColor = null;

  document.querySelectorAll('.variant-size').forEach(el => {
    el.addEventListener('click', () => {
      document.querySelectorAll('.variant-size').forEach(x => x.classList.remove('active'));
      el.classList.add('active');
      selectedSize = el.dataset.size;
      checkVariantStock();
    });
  });
  document.querySelectorAll('.variant-color').forEach(el => {
    el.addEventListener('click', () => {
      document.querySelectorAll('.variant-color').forEach(x => x.classList.remove('active'));
      el.classList.add('active');
      selectedColor = el.dataset.color;
      checkVariantStock();
    });
  });

  function findVariant() {
    return variants.find(v => (!selectedSize || v.size === selectedSize) && (!selectedColor || v.color === selectedColor)
      && (selectedSize ? v.size === selectedSize : true) && (selectedColor ? v.color === selectedColor : true));
  }

  function checkVariantStock() {
    const msg = document.getElementById('variantStockMsg');
    const v = findVariant();
    if (v) {
      msg.textContent = v.stock > 0 ? `${v.stock} in stock` : 'This combination is out of stock';
      msg.className = v.stock > 0 ? 'small text-success mb-2' : 'small text-danger mb-2';
    } else {
      msg.textContent = '';
    }
  }

  // Gallery thumbnails
  document.querySelectorAll('.product-gallery-thumbs img').forEach(thumb => {
    thumb.addEventListener('click', () => {
      document.getElementById('mainImage').src = thumb.dataset.full;
      document.querySelectorAll('.product-gallery-thumbs img').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });

  // Simple zoom effect
  const mainWrap = document.getElementById('mainImageWrap');
  const mainImg = document.getElementById('mainImage');
  mainWrap.addEventListener('mousemove', (e) => {
    const rect = mainWrap.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    mainImg.style.transformOrigin = `${x}% ${y}%`;
    mainImg.style.transform = 'scale(1.6)';
  });
  mainWrap.addEventListener('mouseleave', () => { mainImg.style.transform = 'scale(1)'; });

  // Quantity
  const qtyInput = document.getElementById('qtyInput');
  document.getElementById('qtyInc').addEventListener('click', () => qtyInput.value = parseInt(qtyInput.value || 1) + 1);
  document.getElementById('qtyDec').addEventListener('click', () => qtyInput.value = Math.max(1, parseInt(qtyInput.value || 1) - 1));

  // Rating stars input
  document.querySelectorAll('#ratingStars i').forEach(star => {
    star.addEventListener('click', () => {
      const val = parseInt(star.dataset.val, 10);
      document.getElementById('ratingInput').value = val;
      document.querySelectorAll('#ratingStars i').forEach(s => s.classList.toggle('active', parseInt(s.dataset.val,10) <= val));
    });
  });

  async function addToCart(redirectToCheckout) {
    if (requiresVariant && (!selectedSize && document.querySelector('.variant-size') || !selectedColor && document.querySelector('.variant-color'))) {
      if (document.querySelector('.variant-size') && !selectedSize) { showToast('Please select a size', 'error'); return; }
      if (document.querySelector('.variant-color') && !selectedColor) { showToast('Please select a color', 'error'); return; }
    }
    let variantId = null;
    if (requiresVariant) {
      const v = findVariant();
      if (!v) { showToast('This combination is not available', 'error'); return; }
      if (v.stock <= 0) { showToast('This combination is out of stock', 'error'); return; }
      variantId = v.id;
    }

    const productId = document.querySelector('input[name="product_id"]').value;
    const qty = parseInt(qtyInput.value, 10) || 1;

    const data = await apiFetch(`${window.BASE_URL}/api/cart.php?action=add`, {
      method: 'POST',
      body: JSON.stringify({ product_id: productId, variant_id: variantId, quantity: qty })
    });

    if (data.success) {
      updateBadge('cartCount', data.cart_count);
      if (redirectToCheckout) {
        window.location.href = `${window.BASE_URL}/checkout.php`;
      } else {
        showToast('Added to cart');
      }
    } else {
      showToast(data.message || 'Could not add to cart', 'error');
    }
  }

  document.getElementById('addToCartForm').addEventListener('submit', (e) => { e.preventDefault(); addToCart(false); });
  document.getElementById('buyNowBtn').addEventListener('click', () => addToCart(true));
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
