/* ============================================================
   app.js — اسکریپت اصلی سامانهٔ فرت‌بافی (Vanilla، بدون وابستگی)
   تم روز/شب | Toast | مودال تأیید | منوها | سبد AJAX | نمودار | POS
   ============================================================ */
(function () {
  'use strict';

  /* ─────────── تم روز و شب ─────────── */
  const THEME_KEY = 'faraat-theme';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
  }

  // تم ذخیره‌شده یا ترجیح سیستم (فایل inline در <head> از FOUC جلوگیری می‌کند)
  const savedTheme = localStorage.getItem(THEME_KEY);
  if (savedTheme) applyTheme(savedTheme);

  document.addEventListener('click', function (e) {
    const toggle = e.target.closest('[data-theme-toggle]');
    if (toggle) {
      const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      localStorage.setItem(THEME_KEY, next);
      return;
    }
  });

  /* ─────────── Toast ─────────── */
  const TOAST_ICONS = {
    success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>',
    error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l10 18H2z"/><path d="M12 9v5M12 17.5v.5"/></svg>',
    info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>',
  };

  window.faraatToast = function (message, type, timeout) {
    type = type || 'info';
    timeout = timeout || 4200;
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'toast toast--' + type;
    toast.setAttribute('role', 'status');
    toast.innerHTML = '<span class="toast__icon">' + (TOAST_ICONS[type] || TOAST_ICONS.info) + '</span><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(function () {
      toast.classList.add('is-hiding');
      setTimeout(function () { toast.remove(); }, 320);
    }, timeout);
    toast.addEventListener('click', function () { toast.remove(); });
  };

  // نمایش فلش‌های سمت سرور (data-* روی body)
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-flash]').forEach(function (el) {
      faraatToast(el.getAttribute('data-flash-message') || el.textContent.trim(), el.getAttribute('data-flash') || 'info', 5000);
      el.remove();
    });
  });

  /* ─────────── مودال تأیید (برای تغییر وضعیت‌ها) ─────────── */
  window.faraatConfirm = function (options) {
    return new Promise(function (resolve) {
      const backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop';
      backdrop.innerHTML =
        '<div class="modal" role="dialog" aria-modal="true">' +
        '<div class="modal__title">' + (options.title || 'تأیید عملیات') + '</div>' +
        '<div class="modal__body">' + (options.message || 'آیا مطمئن هستید؟') + '</div>' +
        '<div class="modal__footer">' +
        '<button type="button" class="btn ' + (options.danger ? 'btn--danger' : 'btn--primary') + '" data-yes>' + (options.confirmText || 'بله، انجام شود') + '</button>' +
        '<button type="button" class="btn btn--outline" data-no>' + (options.cancelText || 'انصراف') + '</button>' +
        '</div></div>';
      document.body.appendChild(backdrop);
      requestAnimationFrame(function () { backdrop.classList.add('is-open'); });

      function close(result) {
        backdrop.classList.remove('is-open');
        setTimeout(function () { backdrop.remove(); }, 220);
        document.removeEventListener('keydown', onKey);
        resolve(result);
      }
      function onKey(e) { if (e.key === 'Escape') close(false); }
      backdrop.querySelector('[data-yes]').addEventListener('click', function () { close(true); });
      backdrop.querySelector('[data-no]').addEventListener('click', function () { close(false); });
      backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(false); });
      document.addEventListener('keydown', onKey);
    });
  };

  // فرم‌های دارای data-confirm → تأیید مودال قبل از submit
  document.addEventListener('submit', async function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
    if (form.dataset.confirmed === '1') return;
    e.preventDefault();
    const ok = await faraatConfirm({
      title: form.getAttribute('data-confirm-title') || 'تأیید عملیات',
      message: form.getAttribute('data-confirm'),
      confirmText: form.getAttribute('data-confirm-yes') || 'بله، ادامه بده',
      danger: form.hasAttribute('data-confirm-danger'),
    });
    if (ok) {
      form.dataset.confirmed = '1';
      form.requestSubmit ? form.requestSubmit() : form.submit();
    }
  });

  // دکمه‌های data-confirm-action (غیر فرم) → پرکردن فرم مخفی
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('[data-confirm-action]');
    if (!btn) return;
    e.preventDefault();
    const ok = await faraatConfirm({
      title: btn.getAttribute('data-confirm-title') || 'تأیید عملیات',
      message: btn.getAttribute('data-confirm-action'),
      danger: btn.hasAttribute('data-confirm-danger'),
    });
    if (ok) {
      const target = document.querySelector(btn.getAttribute('data-target'));
      if (target) {
        const input = target.querySelector('[data-fill]');
        if (input) input.value = btn.getAttribute('data-value') || '';
        target.requestSubmit ? target.requestSubmit() : target.submit();
      }
    }
  });

  /* ─────────── منوی موبایل سایت عمومی ─────────── */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-mobile-menu]');
    if (btn) {
      const nav = document.querySelector(btn.getAttribute('data-mobile-menu'));
      if (nav) nav.classList.toggle('is-open');
    }
  });

  /* ─────────── سایدبار پنل‌ها ─────────── */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-sidebar-toggle]');
    if (btn) {
      document.querySelector('.panel-sidebar')?.classList.toggle('is-open');
      document.querySelector('.sidebar-backdrop')?.classList.toggle('is-open');
    }
    const backdrop = e.target.closest('.sidebar-backdrop');
    if (backdrop) {
      document.querySelector('.panel-sidebar')?.classList.remove('is-open');
      backdrop.classList.remove('is-open');
    }
  });

  /* ─────────── dropdown (کاربر / اعلان‌ها) ─────────── */
  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-dropdown]');
    document.querySelectorAll('.user-dropdown.is-open, .notif-popover.is-open').forEach(function (dd) {
      if (!trigger || !dd.contains(trigger) && !trigger.parentElement.contains(dd)) dd.classList.remove('is-open');
    });
    if (trigger) {
      const dd = document.querySelector(trigger.getAttribute('data-dropdown'));
      if (dd) {
        e.stopPropagation();
        dd.classList.toggle('is-open');
      }
    }
  });

  /* ─────────── CSRF برای fetch ─────────── */
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  window.faraatCsrf = csrfMeta ? csrfMeta.getAttribute('content') : '';

  window.faraatPost = async function (url, data) {
    const body = new URLSearchParams(Object.assign({ _token: window.faraatCsrf }, data || {}));
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: body.toString(),
    });
    return { status: res.status, json: await res.json().catch(function () { return null; }) };
  };

  /* ─────────── افزودن به سبد (AJAX) + سبد کشویی ─────────── */
  const drawer = {
    el: document.getElementById('cart-drawer'),
    backdrop: document.getElementById('drawer-backdrop'),

    isOpen: function () { return this.el && this.el.classList.contains('is-open'); },

    open: async function () {
      if (!this.el) return;
      await this.refresh();
      this.el.classList.add('is-open');
      this.backdrop && this.backdrop.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    },

    close: function () {
      if (!this.el) return;
      this.el.classList.remove('is-open');
      this.backdrop && this.backdrop.classList.remove('is-open');
      document.body.style.overflow = '';
    },

    refresh: async function () {
      if (!this.el) return;
      const url = this.el.getAttribute('data-drawer-url');
      if (!url) return;
      const body = this.el.querySelector('.drawer__body');
      try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        body.innerHTML = await res.text();
      } catch (e) { /* offline */ }
    },
  };
  window.faraatDrawer = drawer;

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-drawer-close]')) drawer.close();
    if (e.target.closest('[data-drawer-open]')) { e.preventDefault(); drawer.open(); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer.isOpen()) drawer.close();
  });

  // حذف قلم از داخل کشو
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('[data-drawer-remove]');
    if (!btn || !drawer.el) return;
    const ok = await faraatConfirm({
      title: 'حذف از سبد',
      message: 'این قلم از سبد خرید حذف شود؟',
      confirmText: 'حذف شود',
      danger: true,
    });
    if (!ok) return;
    const res = await faraatPost(drawer.el.getAttribute('data-drawer-remove-url'), { sku: btn.getAttribute('data-drawer-remove') });
    if (res.json && typeof res.json.cart_count !== 'undefined') {
      document.querySelectorAll('[data-cart-count]').forEach(function (el) { el.textContent = toFa(res.json.cart_count); });
    }
    await drawer.refresh();
  });

  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;
    e.preventDefault();
    btn.disabled = true;
    const sku = btn.getAttribute('data-sku');
    const qtyInput = document.querySelector(btn.getAttribute('data-qty-input') || '[data-qty]');
    const qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
    try {
      const res = await faraatPost(btn.getAttribute('data-add-to-cart'), { sku: sku, qty: qty });
      if (res.json && res.json.ok) {
        faraatToast(res.json.message || 'به سبد اضافه شد', 'success', 2600);
        document.querySelectorAll('[data-cart-count]').forEach(function (el) { el.textContent = toFa(res.json.cart_count); });
        // سبد کشویی فقط در صفحات عمومی (که کشو وجود دارد) باز می‌شود
        if (drawer.el && !btn.hasAttribute('data-no-drawer')) drawer.open();
      } else {
        faraatToast((res.json && res.json.message) || 'خطا در افزودن به سبد', 'error');
      }
    } catch (err) {
      faraatToast('خطای ارتباط با سرور', 'error');
    }
    btn.disabled = false;
  });

  /* ─────────── نمایان‌شدن نرم با اسکرول ─────────── */
  if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });
    document.querySelectorAll('.reveal, [data-reveal]').forEach(function (el) { revealObserver.observe(el); });
  } else {
    document.querySelectorAll('.reveal, [data-reveal]').forEach(function (el) { el.classList.add('is-visible'); });
  }

  /* ─────────── تبدیل رقم به فارسی ─────────── */
  const FA = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
  window.toFa = function (v) { return String(v).replace(/\d/g, function (d) { return FA[+d]; }); };
  document.querySelectorAll('[data-fa-num]').forEach(function (el) {
    el.textContent = toFa(el.textContent.trim());
  });

  /* ─────────── پیش‌نمایش SKU زنده (فرم واریانت) ─────────── */
  const skuForm = document.querySelector('[data-sku-preview]');
  if (skuForm) {
    const url = skuForm.getAttribute('data-sku-preview');
    const out = document.querySelector('[data-sku-output]');
    const update = async function () {
      const model = skuForm.querySelector('[name=model_id]');
      const color = skuForm.querySelector('[name=color_id]');
      const pattern = skuForm.querySelector('[name=pattern_id]');
      if (!model || !color || !pattern || !model.value || !color.value || !pattern.value) return;
      try {
        const res = await faraatPost(url, { model_id: model.value, color_id: color.value, pattern_id: pattern.value });
        if (res.json && res.json.ok && out) {
          out.textContent = res.json.sku;
          out.classList.toggle('text-danger', !!res.json.exists);
          const hint = document.querySelector('[data-sku-hint]');
          if (hint) hint.textContent = res.json.exists ? '⚠ این SKU پیش‌تر ثبت شده است' : 'SKU به‌صورت خودکار ساخته می‌شود';
        }
      } catch (err) { /* noop */ }
    };
    skuForm.addEventListener('change', update);
    update();
  }

  /* ─────────── نمودار میله‌ای ─────────── */
  document.querySelectorAll('[data-bar-chart]').forEach(function (el) {
    try {
      const data = JSON.parse(el.getAttribute('data-bar-chart'));
      const max = Math.max.apply(null, data.map(function (d) { return d.value; }).concat([1]));
      el.innerHTML = data.map(function (d) {
        const h = Math.max(4, Math.round((d.value / max) * 100));
        return '<div class="bar-col">' +
          '<div class="bar" style="height:' + h + '%" data-value="' + d.labelFull + '"></div>' +
          '<span class="bar-label">' + d.label + '</span></div>';
      }).join('');
    } catch (err) { /* noop */ }
  });

  /* ─────────── POS نمایندگی: اسکن SKU ─────────── */
  const posInput = document.querySelector('[data-pos-input]');
  if (posInput) {
    posInput.addEventListener('keydown', async function (e) {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      const sku = posInput.value.trim();
      if (!sku) return;
      const res = await faraatPost(posInput.getAttribute('data-pos-input'), { sku: sku, qty: 1 });
      if (res.json && res.json.ok) {
        faraatToast(res.json.message || 'اضافه شد', 'success', 2500);
        location.reload();
      } else {
        faraatToast((res.json && res.json.message) || 'SKU یافت نشد', 'error');
        posInput.select();
      }
    });
    posInput.focus();
  }

  /* ─────────── شمارش معکوس موعد (پنل بافنده) ─────────── */
  document.querySelectorAll('[data-countdown]').forEach(function (el) {
    // فقط رنگ‌بندی بر اساس روزهای مانده (سرور فارسی‌سازی کرده)
    const days = parseInt(el.getAttribute('data-countdown'), 10);
    if (isNaN(days)) return;
    if (days < 0) el.classList.add('is-late');
    else if (days <= 3) el.classList.add('is-late');
    else if (days <= 7) el.classList.add('');
    else el.classList.add('is-ok');
  });

  /* ─────────── ارسال فرم با AJAX (data-ajax-form) ─────────── */
  document.addEventListener('submit', async function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ajax-form')) return;
    e.preventDefault();
    const btn = form.querySelector('button[type=submit]');
    if (btn) btn.disabled = true;
    try {
      const data = Object.fromEntries(new FormData(form).entries());
      const res = await faraatPost(form.action, data);
      if (res.json && res.json.ok) {
        faraatToast(res.json.message || 'انجام شد', 'success');
        if (res.json.reload !== false) setTimeout(function () { location.reload(); }, 700);
      } else {
        faraatToast((res.json && (res.json.message || Object.values(res.json.errors || {}).join(' '))) || 'خطا', 'error');
        if (btn) btn.disabled = false;
      }
    } catch (err) {
      faraatToast('خطای ارتباط با سرور', 'error');
      if (btn) btn.disabled = false;
    }
  });

  /* ─────────── فیلتر خودکار فرم‌ها (تغییر → submit) ─────────── */
  document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.requestSubmit ? el.form.requestSubmit() : el.form.submit(); });
  });

  /* ─────────── جست‌وجوی زندهٔ جدول‌ها ─────────── */
  const searchInput = document.querySelector('[data-table-search]');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const q = searchInput.value.trim().toLowerCase();
      document.querySelectorAll(searchInput.getAttribute('data-table-search')).forEach(function (tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }
})();
