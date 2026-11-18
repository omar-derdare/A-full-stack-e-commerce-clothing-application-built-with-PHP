/* ==========================================================
   checkout.js - place order via AJAX (Cash on Delivery)
   ========================================================== */

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('checkoutForm');
  if (!form) return;

  const errorBox = document.getElementById('checkoutError');
  const submitBtn = document.getElementById('placeOrderBtn');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    errorBox.classList.add('d-none');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Placing Order...';

    const payload = {
      first_name: form.first_name.value.trim(),
      last_name: form.last_name.value.trim(),
      phone: form.phone.value.trim(),
      address: form.address.value.trim(),
      city: form.city.value.trim(),
    };

    const data = await apiFetch(`${window.BASE_URL}/api/checkout.php`, {
      method: 'POST',
      body: JSON.stringify(payload)
    });

    if (data.success) {
      window.location.href = data.redirect;
    } else {
      errorBox.textContent = data.message || 'Could not place your order. Please try again.';
      errorBox.classList.remove('d-none');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Place Order';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });
});
