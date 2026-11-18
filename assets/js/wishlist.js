/* ==========================================================
   wishlist.js - wishlist toggle buttons + wishlist page
   ========================================================== */

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.wishlist-btn');
  if (!btn) return;
  e.preventDefault();

  if (!window.IS_LOGGED_IN) {
    window.location.href = `${window.BASE_URL}/auth/login.php?redirect=${encodeURIComponent(window.location.href)}`;
    return;
  }

  const productId = btn.dataset.productId;
  const isActive = btn.classList.contains('active');
  const action = isActive ? 'remove' : 'add';

  const data = await apiFetch(`${window.BASE_URL}/api/wishlist.php?action=${action}`, {
    method: 'POST',
    body: JSON.stringify({ product_id: productId })
  });

  if (data.success) {
    btn.classList.toggle('active', !isActive);
    btn.querySelector('i').className = !isActive ? 'bi bi-heart-fill' : 'bi bi-heart';
    updateBadge('wishlistCount', data.wishlist_count);
    showToast(!isActive ? 'Added to wishlist' : 'Removed from wishlist');
  } else {
    showToast(data.message || 'Something went wrong', 'error');
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const page = document.getElementById('wishlistPage');
  if (!page) return;

  page.addEventListener('click', async (e) => {
    const moveBtn = e.target.closest('.move-to-cart-btn');
    const removeBtn = e.target.closest('.wishlist-remove-btn');

    if (moveBtn) {
      const productId = moveBtn.dataset.productId;
      if (moveBtn.dataset.hasVariants === '1') {
        window.location.href = `${window.BASE_URL}/product.php?slug=${moveBtn.dataset.slug}`;
        return;
      }
      const addData = await apiFetch(`${window.BASE_URL}/api/cart.php?action=add`, {
        method: 'POST',
        body: JSON.stringify({ product_id: productId, quantity: 1 })
      });
      if (addData.success) {
        await apiFetch(`${window.BASE_URL}/api/wishlist.php?action=remove`, {
          method: 'POST',
          body: JSON.stringify({ product_id: productId })
        });
        showToast('Moved to cart');
        updateBadge('cartCount', addData.cart_count);
        moveBtn.closest('.wishlist-card').remove();
      } else {
        showToast(addData.message || 'Could not add to cart', 'error');
      }
    }

    if (removeBtn) {
      const productId = removeBtn.dataset.productId;
      const data = await apiFetch(`${window.BASE_URL}/api/wishlist.php?action=remove`, {
        method: 'POST',
        body: JSON.stringify({ product_id: productId })
      });
      if (data.success) {
        updateBadge('wishlistCount', data.wishlist_count);
        removeBtn.closest('.wishlist-card').remove();
      }
    }
  });
});
