/* ==========================================================
   main.js - global helpers: toasts, live search, quick add
   ========================================================== */

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const id = 't' + Date.now();
  const bg = type === 'success' ? 'text-bg-dark' : (type === 'error' ? 'text-bg-danger' : 'text-bg-secondary');
  const html = `
    <div id="${id}" class="toast align-items-center ${bg} border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>`;
  container.insertAdjacentHTML('beforeend', html);
  const toastEl = document.getElementById(id);
  const toast = new bootstrap.Toast(toastEl, { delay: 2800 });
  toast.show();
  toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

async function apiFetch(url, options = {}) {
  options.headers = Object.assign({
    'X-CSRF-Token': window.CSRF_TOKEN || ''
  }, options.headers || {});
  if (options.body && !(options.body instanceof FormData)) {
    options.headers['Content-Type'] = 'application/json';
  }
  const res = await fetch(url, options);
  let data;
  try { data = await res.json(); } catch (e) { data = { success: false, message: 'Unexpected server response.' }; }
  return data;
}

function updateBadge(id, count) {
  const el = document.getElementById(id);
  if (el) el.textContent = count;
}

/* ---------------- Live search ---------------- */
(function () {
  const input = document.getElementById('navSearchInput');
  const box = document.getElementById('searchSuggestions');
  if (!input || !box) return;

  let timer = null;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { box.classList.remove('show'); box.innerHTML = ''; return; }
    timer = setTimeout(() => runSearch(q), 300);
  });

  async function runSearch(q) {
    try {
      const data = await apiFetch(`${window.BASE_URL}/api/search.php?q=${encodeURIComponent(q)}`);
      if (!data.success || !data.results.length) {
        box.innerHTML = `<div class="p-3 text-muted small">No products found.</div>`;
        box.classList.add('show');
        return;
      }
      box.innerHTML = data.results.map(p => `
        <a href="${window.BASE_URL}/product.php?slug=${p.slug}" class="search-suggestion-item text-decoration-none text-dark">
          <img src="${p.image}" alt="">
          <div>
            <div class="small fw-medium">${p.name}</div>
            <div class="small text-muted">${p.price}</div>
          </div>
        </a>`).join('');
      box.classList.add('show');
    } catch (e) { /* silent */ }
  }

  document.addEventListener('click', (e) => {
    if (!box.contains(e.target) && e.target !== input) box.classList.remove('show');
  });
})();

/* ---------------- Quick add to cart (product cards) ---------------- */
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.quick-add-btn');
  if (!btn) return;
  e.preventDefault();
  const productId = btn.dataset.productId;
  if (btn.dataset.hasVariants === '1') {
    window.location.href = `${window.BASE_URL}/product.php?slug=${btn.dataset.slug}`;
    return;
  }
  btn.disabled = true;
  const data = await apiFetch(`${window.BASE_URL}/api/cart.php?action=add`, {
    method: 'POST',
    body: JSON.stringify({ product_id: productId, quantity: 1 })
  });
  btn.disabled = false;
  if (data.success) {
    showToast('Added to cart');
    updateBadge('cartCount', data.cart_count);
  } else {
    showToast(data.message || 'Could not add to cart', 'error');
  }
});

/* ---------------- Newsletter subscription ---------------- */
document.addEventListener('submit', async (e) => {
  if (e.target.id !== 'newsletterForm') return;
  e.preventDefault();
  const form = e.target;
  const input = form.querySelector('input[type="email"]');
  const btn = form.querySelector('button[type="submit"]');
  const email = input.value.trim();

  if (!email) return;

  btn.disabled = true;
  const originalText = btn.textContent;
  btn.textContent = 'Subscribing...';

  const data = await apiFetch(`${window.BASE_URL}/api/newsletter.php`, {
    method: 'POST',
    body: JSON.stringify({ email })
  });

  btn.disabled = false;
  btn.textContent = originalText;

  if (data.success) {
    showToast(data.message || 'Thanks for subscribing!');
    form.reset();
  } else {
    showToast(data.message || 'Could not subscribe. Please try again.', 'error');
  }
});
