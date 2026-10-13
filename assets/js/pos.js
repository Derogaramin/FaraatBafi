/* assets/js/pos.js */
/* شبیه‌سازی بارکدخوان و محاسبه فروش نمایندگی */

const POS = {
  cart: [],
  products: [
    { id: '1001', barcode: 'FB1001', name: 'حوله حمام طرح سنتی', price: 320000 },
    { id: '1002', barcode: 'FB1002', name: 'حوله دستی پنبه‌ای', price: 180000 },
    { id: '1003', barcode: 'FB1003', name: 'پارچه سنتی', price: 450000 },
    { id: '1004', barcode: 'FB1004', name: 'شال گردن دست‌باف', price: 220000 },
    { id: '1005', barcode: 'FB1005', name: 'دستمال سفره', price: 85000 },
    { id: '1006', barcode: 'FB1006', name: 'روبالشی سنتی', price: 280000 },
  ],

  init() {
    this.attachEvents();
    this.renderProducts();
    this.updateCart();
  },

  attachEvents() {
    const barcodeInput = document.getElementById('barcode-input');
    if (barcodeInput) {
      barcodeInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          this.scanBarcode(barcodeInput.value.trim());
          barcodeInput.value = '';
        }
      });
      // فوکوس خودکار
      barcodeInput.focus();
      document.addEventListener('click', () => barcodeInput.focus());
    }

    // جستجوی متنی
    const searchInput = document.getElementById('pos-search');
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        this.renderProducts(searchInput.value);
      });
    }
  },

  scanBarcode(barcode) {
    const product = this.products.find(p => p.barcode === barcode);
    if (product) {
      this.addToCart(product);
    } else {
      showToast('محصولی با این بارکد یافت نشد', 'error');
    }
  },

  addToCart(product) {
    const existing = this.cart.find(i => i.id === product.id);
    if (existing) {
      existing.qty += 1;
    } else {
      this.cart.push({ ...product, qty: 1 });
    }
    this.updateCart();
    // پخش صدای بیپ (شبیه‌سازی)
    this.beep();
  },

  removeFromCart(productId) {
    this.cart = this.cart.filter(i => i.id !== productId);
    this.updateCart();
  },

  updateQty(productId, qty) {
    const item = this.cart.find(i => i.id === productId);
    if (item) {
      item.qty = Math.max(1, qty);
      this.updateCart();
    }
  },

  getTotal() {
    return this.cart.reduce((sum, i) => sum + i.price * i.qty, 0);
  },

  formatPrice(price) {
    return Number(price).toLocaleString('fa-IR') + ' تومان';
  },

  updateCart() {
    const cartContainer = document.getElementById('pos-cart-items');
    const totalEl = document.getElementById('pos-total');
    const countEl = document.getElementById('pos-item-count');

    if (!cartContainer) return;

    if (this.cart.length === 0) {
      cartContainer.innerHTML = '<p style="text-align:center;color:var(--color-text-muted);">سبد خرید خالی است</p>';
      if (totalEl) totalEl.textContent = this.formatPrice(0);
      if (countEl) countEl.textContent = '۰';
      return;
    }

    cartContainer.innerHTML = this.cart.map(item => `
      <div class="pos-cart-item">
        <div>
          <strong>${item.name}</strong>
          <small style="display:block;color:var(--color-text-muted);">${this.formatPrice(item.price)}</small>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <button onclick="POS.updateQty('${item.id}', ${item.qty - 1})" style="width:28px;height:28px;border-radius:50%;border:1px solid var(--color-border);background:var(--color-bg);cursor:pointer;">−</button>
          <span>${item.qty}</span>
          <button onclick="POS.updateQty('${item.id}', ${item.qty + 1})" style="width:28px;height:28px;border-radius:50%;border:1px solid var(--color-border);background:var(--color-bg);cursor:pointer;">+</button>
          <button onclick="POS.removeFromCart('${item.id}')" style="background:none;border:none;color:var(--color-danger);cursor:pointer;"><i class="ti ti-trash"></i></button>
        </div>
      </div>
    `).join('');

    if (totalEl) totalEl.textContent = this.formatPrice(this.getTotal());
    if (countEl) countEl.textContent = this.cart.reduce((s, i) => s + i.qty, 0).toLocaleString('fa-IR');
  },

  renderProducts(filter = '') {
    const container = document.getElementById('pos-product-list');
    if (!container) return;

    const filtered = this.products.filter(p =>
      p.name.includes(filter) || p.barcode.includes(filter)
    );

    container.innerHTML = filtered.map(p => `
      <div class="pos-product-item" onclick="POS.addToCart({id:'${p.id}', name:'${p.name}', price:${p.price}})">
        <span class="pos-product-name">${p.name}</span>
        <span class="pos-product-price">${this.formatPrice(p.price)}</span>
      </div>
    `).join('');
  },

  checkout() {
    const customerName = document.getElementById('customer-name')?.value;
    const customerPhone = document.getElementById('customer-phone')?.value;

    if (this.cart.length === 0) {
      showToast('سبد خرید خالی است', 'error');
      return;
    }

    if (!customerPhone) {
      showToast('لطفاً شماره تماس مشتری را وارد کنید', 'warning');
      return;
    }

    if (confirm(`جمع کل: ${this.formatPrice(this.getTotal())}\nثبت فروش انجام شود؟`)) {
      showToast('فروش با موفقیت ثبت شد', 'success');
      this.cart = [];
      this.updateCart();
      // پاک کردن فیلدها
      if (document.getElementById('customer-name')) document.getElementById('customer-name').value = '';
      if (document.getElementById('customer-phone')) document.getElementById('customer-phone').value = '';
    }
  },

  beep() {
    // شبیه‌سازی صدای اسکنر
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      osc.type = 'square';
      osc.frequency.setValueAtTime(800, ctx.currentTime);
      osc.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.1);
    } catch(e) {}
  }
};

document.addEventListener('DOMContentLoaded', () => POS.init());