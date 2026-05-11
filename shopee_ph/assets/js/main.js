/* ============================================================
   Shopee PH  —  Main JavaScript
   ============================================================ */

'use strict';

/* ── CSRF helper ──────────────────────────────────────────── */
function getCsrf() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/* ── Toast Notification ───────────────────────────────────── */
function showToast(msg, type = 'success', duration = 3000) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'slideOut .3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

/* ── Cart ─────────────────────────────────────────────────── */
async function addToCart(productId, quantity = 1) {
  const siteUrl = window.SITE_URL || '';
  try {
    const res  = await fetch(`${siteUrl}/api/cart.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrf()
      },
      body: JSON.stringify({ action: 'add', product_id: productId, quantity })
    });
    const data = await res.json();
    if (data.redirect) { window.location = data.redirect; return; }
    if (data.error)    { showToast(data.error, 'error'); return; }

    showToast(data.message || 'Added to cart!', 'success');
    updateCartBadge(data.cart_count);
  } catch (e) {
    showToast('Failed to add to cart. Please try again.', 'error');
  }
}

async function addToCartQty(productId, qty) {
  return addToCart(productId, qty);
}

function updateCartBadge(count) {
  const badge = document.getElementById('cart-badge');
  if (badge) {
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
  } else if (count > 0) {
    const icon = document.querySelector('.nav-icon[href*="cart"]');
    if (icon) {
      const b = document.createElement('span');
      b.className = 'badge';
      b.id = 'cart-badge';
      b.textContent = count;
      icon.appendChild(b);
    }
  }
}

/* ── Wishlist ─────────────────────────────────────────────── */
async function toggleWishlist(event, productId) {
  event.stopPropagation();
  event.preventDefault();
  const btn     = event.currentTarget;
  const siteUrl = window.SITE_URL || '';
  try {
    const res  = await fetch(`${siteUrl}/api/wishlist.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrf()
      },
      body: JSON.stringify({ action: 'toggle', product_id: productId })
    });
    const data = await res.json();
    if (data.redirect) { window.location = data.redirect; return; }
    if (data.error)    { showToast(data.error, 'error'); return; }

    btn.textContent  = data.wishlisted ? '❤️' : '🤍';
    btn.classList.toggle('active', data.wishlisted);
    showToast(data.message, 'success');
  } catch (e) {
    showToast('Please log in to use wishlist.', 'error');
  }
}

/* ── Flash Deal Countdown ─────────────────────────────────── */
function initCountdown() {
  const ch = document.getElementById('ch');
  const cm = document.getElementById('cm');
  const cs = document.getElementById('cs');
  if (!ch) return;

  const endTs = (window.flashEndTimestamp || 0) * 1000;

  function tick() {
    const now  = Date.now();
    let   diff = Math.max(0, Math.floor((endTs - now) / 1000));
    if (endTs === 0) diff = 2 * 3600 + 47 * 60 + 33; // static fallback
    const h = Math.floor(diff / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    ch.textContent = String(h).padStart(2, '0');
    cm.textContent = String(m).padStart(2, '0');
    cs.textContent = String(s).padStart(2, '0');
  }
  tick();
  setInterval(tick, 1000);
}

/* ── Hero Banner Auto-rotate ─────────────────────────────── */
function initHeroDots() {
  const dots = document.querySelectorAll('.hero-dots .dot');
  if (!dots.length) return;
  let current = 0;
  setInterval(() => {
    dots[current].classList.remove('active');
    current = (current + 1) % dots.length;
    dots[current].classList.add('active');
  }, 3500);
}

/* ── Search Autocomplete ──────────────────────────────────── */
function initAutocomplete() {
  const input    = document.getElementById('search-input');
  const siteUrl  = window.SITE_URL || '';
  if (!input) return;

  let dropdown = document.createElement('div');
  dropdown.className = 'autocomplete-dropdown';
  input.parentElement.style.position = 'relative';
  input.parentElement.appendChild(dropdown);

  let debounceTimer;
  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = input.value.trim();
    if (q.length < 2) { dropdown.classList.remove('show'); return; }
    debounceTimer = setTimeout(async () => {
      try {
        const res  = await fetch(`${siteUrl}/api/search.php?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.length) { dropdown.classList.remove('show'); return; }
        dropdown.innerHTML = data.map(p => `
          <a href="${siteUrl}/product.php?id=${p.id}" class="ac-item">
            <div class="ac-emoji ${p.color_class}">${p.emoji}</div>
            <div>
              <div style="font-weight:600;font-size:13px">${escHtml(p.name)}</div>
              <div style="font-size:11px;color:#EE4D2D;font-weight:700">₱${Number(p.price).toLocaleString()}</div>
            </div>
          </a>
        `).join('');
        dropdown.classList.add('show');
      } catch (e) { /* ignore */ }
    }, 280);
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('.search-wrap')) dropdown.classList.remove('show');
  });
  input.addEventListener('focus', () => {
    if (dropdown.children.length) dropdown.classList.add('show');
  });
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ── Voucher Claim Modal ──────────────────────────────────── */
function claimVoucher() {
  const code = prompt('🎟 Enter voucher code (try WELCOME100, SAVE200, FLASH50, FREESHIP):');
  if (!code) return;
  showToast(`Voucher "${code.toUpperCase()}" will be applied at checkout!`, 'success');
  sessionStorage.setItem('pendingVoucher', code.toUpperCase());
}

/* ── Category Strip Active on scroll ─────────────────────── */
function initCatStrip() {
  const strip = document.querySelector('.cat-strip');
  const active = strip?.querySelector('.active');
  if (active) {
    active.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }
}

/* ── Set SITE_URL from PHP output ─────────────────────────── */
(function() {
  // Read from meta tag if set, else derive from location
  const meta = document.querySelector('meta[name="site-url"]');
  window.SITE_URL = meta ? meta.content : (location.origin + '/shopee_ph');
})();

/* ── Init ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initCountdown();
  initHeroDots();
  initAutocomplete();
  initCatStrip();

  // Make product card thumbnails clickable
  document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', e => {
      if (e.target.closest('button') || e.target.closest('a')) return;
      const link = card.querySelector('a[href]');
      if (link) window.location = link.href;
    });
  });

  // Auto-dismiss flash banners
  setTimeout(() => {
    document.querySelectorAll('.flash-banner').forEach(b => {
      b.style.transition = 'opacity .5s';
      b.style.opacity    = '0';
      setTimeout(() => b.remove(), 500);
    });
  }, 4000);
});
