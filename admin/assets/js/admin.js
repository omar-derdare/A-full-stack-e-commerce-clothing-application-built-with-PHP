/* ==========================================================
   admin.js - shared admin panel interactions
   ========================================================== */

document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('adminSidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('show'));
  }

  // Image preview for file inputs
  document.querySelectorAll('.admin-image-input').forEach(input => {
    input.addEventListener('change', () => {
      const previewId = input.dataset.preview;
      const preview = document.getElementById(previewId);
      if (input.files && input.files[0] && preview) {
        preview.src = URL.createObjectURL(input.files[0]);
        preview.classList.remove('d-none');
      }
    });
  });

  // Confirm before destructive actions
  document.querySelectorAll('.confirm-delete').forEach(el => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.dataset.confirmMsg || 'Are you sure? This action cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  // Dynamic variant rows (product form)
  const variantsWrap = document.getElementById('variantsWrap');
  const addVariantBtn = document.getElementById('addVariantBtn');
  if (addVariantBtn && variantsWrap) {
    const colorOptions = (window.ADMIN_COLORS || [])
      .map(c => `<option value="${c}">${c}</option>`).join('');

    addVariantBtn.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'row g-2 mb-2 variant-row align-items-center';
      row.innerHTML = `
        <div class="col-3">
          <select name="variant_size[]" class="form-select form-select-sm">
            <option value="">Size</option>
            <option>XS</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option>
          </select>
        </div>
        <div class="col-3">
          <select name="variant_color[]" class="form-select form-select-sm">
            <option value="">Color</option>
            ${colorOptions}
          </select>
        </div>
        <div class="col-3"><input type="number" name="variant_stock[]" class="form-control form-control-sm" placeholder="Stock" min="0" value="0"></div>
        <div class="col-2"><input type="text" name="variant_sku[]" class="form-control form-control-sm" placeholder="SKU (optional)"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger remove-variant-row"><i class="bi bi-trash"></i></button></div>
      `;
      variantsWrap.appendChild(row);
    });

    variantsWrap.addEventListener('click', (e) => {
      const btn = e.target.closest('.remove-variant-row');
      if (btn) btn.closest('.variant-row').remove();
    });
  }
});

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const id = 't' + Date.now();
  const bg = type === 'success' ? 'text-bg-dark' : (type === 'error' ? 'text-bg-danger' : 'text-bg-secondary');
  container.insertAdjacentHTML('beforeend', `
    <div id="${id}" class="toast align-items-center ${bg} border-0" role="alert">
      <div class="d-flex"><div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
    </div>`);
  const toastEl = document.getElementById(id);
  const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
  toast.show();
  toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

async function apiFetch(url, options = {}) {
  options.headers = Object.assign({ 'X-CSRF-Token': window.CSRF_TOKEN || '' }, options.headers || {});
  if (options.body && !(options.body instanceof FormData)) options.headers['Content-Type'] = 'application/json';
  const res = await fetch(url, options);
  let data;
  try { data = await res.json(); } catch (e) { data = { success: false, message: 'Unexpected server response.' }; }
  return data;
}
