/* ==========================================================
   cart.js - cart page interactions
   ========================================================== */

document.addEventListener('DOMContentLoaded', () => {
  const cartPage = document.getElementById('cartPage');
  if (!cartPage) return;

  cartPage.addEventListener('click', async (e) => {
    const incBtn = e.target.closest('.qty-inc');
    const decBtn = e.target.closest('.qty-dec');
    const removeBtn = e.target.closest('.remove-item');

    if (incBtn || decBtn) {
      const row = e.target.closest('.cart-item-row');
      const input = row.querySelector('.qty-input');
      let qty = parseInt(input.value, 10) || 1;
      qty = incBtn ? qty + 1 : Math.max(1, qty - 1);
      await updateQty(row.dataset.itemId, qty);
    }

    if (removeBtn) {
      const row = e.target.closest('.cart-item-row');
      await removeItem(row.dataset.itemId);
    }
  });

  cartPage.addEventListener('change', async (e) => {
    if (e.target.classList.contains('qty-input')) {
      const row = e.target.closest('.cart-item-row');
      let qty = parseInt(e.target.value, 10) || 1;
      if (qty < 1) qty = 1;
      await updateQty(row.dataset.itemId, qty);
    }
  });

  const couponForm = document.getElementById('couponForm');
  if (couponForm) {
    couponForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const code = document.getElementById('couponInput').value.trim();
      if (!code) return;
      const data = await apiFetch(`${window.BASE_URL}/api/coupon.php?action=apply`, {
        method: 'POST',
        body: JSON.stringify({ code })
      });
      if (data.success) {
        showToast('Coupon applied');
        window.location.reload();
      } else {
        showToast(data.message || 'Invalid coupon', 'error');
      }
    });
  }

  const removeCouponBtn = document.getElementById('removeCouponBtn');
  if (removeCouponBtn) {
    removeCouponBtn.addEventListener('click', async () => {
      await apiFetch(`${window.BASE_URL}/api/coupon.php?action=remove`, { method: 'POST', body: JSON.stringify({}) });
      window.location.reload();
    });
  }

  async function updateQty(itemId, qty) {
    const data = await apiFetch(`${window.BASE_URL}/api/cart.php?action=update`, {
      method: 'POST',
      body: JSON.stringify({ item_id: itemId, quantity: qty })
    });
    if (data.success) {
      window.location.reload();
    } else {
      showToast(data.message || 'Could not update cart', 'error');
    }
  }

  async function removeItem(itemId) {
    const data = await apiFetch(`${window.BASE_URL}/api/cart.php?action=remove`, {
      method: 'POST',
      body: JSON.stringify({ item_id: itemId })
    });
    if (data.success) {
      showToast('Item removed');
      window.location.reload();
    } else {
      showToast(data.message || 'Could not remove item', 'error');
    }
  }
});
