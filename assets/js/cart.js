/* assets/js/cart.js */
/* منطق سبد خرید (اضافه/حذف/محاسبه) */

const Cart = {
  items: [],

  init() {
    // بارگذاری از localStorage
    const saved = localStorage.getItem('fort_cart');
    if (saved) {
      try { this.items = JSON.parse(saved); } catch(e) { this.items = []; }
    }
    this.updateUI();
    this.attachEvents();
  },

  save() {
    localStorage.setItem('fort_cart', JSON.stringify(this.items));
  },

  add(productId, name, price, image = '') {
    const existing = this.items.find(i => i.id === productId);
    if (existing) {
      existing.qty += 1;
    } else {
      this.items.push({ id: productId, name, price, qty: 1, image });
    }
    this.save();
    this.updateUI();
    showToast('محصول به سبد خرید افزوده شد', 'success');
    this.animateAdd(productId);
  },

  remove(productId) {
    this.items = this.items.filter(i => i.id !== productId);
    this.save();
    this.updateUI();
  },

  updateQty(productId, qty) {
    const item = this.items.find(i => i.id === productId);
    if (item) {
      item.qty = Math.max(1, qty);
      this.save();
      this.updateUI();
    }
  },

  getTotal() {
    return this.items.reduce((sum, i) => sum + i.price * i.qty, 0);
  },

  getCount() {
    return this.items.reduce((sum, i) => sum + i.qty, 0);
  },

  formatPrice(price) {
    return Number(price).toLocaleString('fa-IR') + ' تومان';
  },

  updateUI() {
    // بروزرسانی تعداد در آیکون سبد
    const countEls = document.querySelectorAll('.cart-count');
    countEls.forEach(el => {
      const count = this.getCount();
      el.textContent = count;
      el.style.display = count > 0 ? 'flex' : 'none';
    });

    // بروزرسانی صفحه سبد خرید
    const cartItemsContainer = document.querySelector('.cart-items');
    const cartSummaryContainer = document.querySelector('.cart-summary');
    if (!cartItemsContainer) return;

    if (this.items.length === 0) {
      cartItemsContainer.innerHTML = `
        <div style="text-align:center; padding:var(--space-2xl);">
          <i class="ti ti-shopping-cart" style="font-size:3rem; color:var(--color-border);"></i>
          <p style="color:var(--color-text-muted); margin-top:var(--space-md);">سبد خرید شما خالی است</p>
          <a href="catalog.html" class="btn" style="margin-top:var(--space-md);">مشاهده محصولات</a>
        </div>`;
      if (cartSummaryContainer) cartSummaryContainer.style.display = 'none';
      return;
    }

    if (cartSummaryContainer) cartSummaryContainer.style.display = 'block';

    cartItemsContainer.innerHTML = this.items.map(item => `
      <div class="cart-item">
        <img src="${item.image || '../assets/images/product-placeholder.png'}" alt="${item.name}" class="item-image">
        <div class="item-details">
          <div class="item-title">${item.name}</div>
          <div class="item-price">${this.formatPrice(item.price)}</div>
          <div class="quantity-control">
            <button onclick="Cart.updateQty('${item.id}', ${item.qty - 1})">−</button>
            <span class="qty">${item.qty}</span>
            <button onclick="Cart.updateQty('${item.id}', ${item.qty + 1})">+</button>
          </div>
        </div>
        <button onclick="Cart.remove('${item.id}')" style="background:none;border:none;cursor:pointer;color:var(--color-danger);">
          <i class="ti ti-trash"></i>
        </button>
      </div>
    `).join('');

    // بروزرسانی خلاصه سبد
    if (cartSummaryContainer) {
      cartSummaryContainer.innerHTML = `
        <h3>خلاصه سفارش</h3>
        ${this.items.map(i => `
          <div class="summary-row">
            <span>${i.name} × ${i.qty}</span>
            <span>${this.formatPrice(i.price * i.qty)}</span>
          </div>
        `).join('')}
        <div class="summary-row" style="color:var(--color-text-muted);">
          <span>هزینه ارسال</span>
          <span>محاسبه در ادامه</span>
        </div>
        <div class="summary-total">
          <span>جمع کل</span>
          <span>${this.formatPrice(this.getTotal())}</span>
        </div>
        <a href="checkout.html" class="btn btn--accent" style="width:100%; margin-top:var(--space-md);">ثبت سفارش</a>
      `;
    }
  },

  animateAdd(productId) {
    // انیمیشن ساده: پالس روی دکمه
    const btn = document.querySelector(`[data-product-id="${productId}"]`);
    if (btn) {
      btn.classList.add('pulse');
      setTimeout(() => btn.classList.remove('pulse'), 300);
    }
  },

  attachEvents() {
    document.querySelectorAll('[data-add-to-cart]').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const id = this.dataset.productId;
        const name = this.dataset.productName;
        const price = parseFloat(this.dataset.productPrice);
        const image = this.dataset.productImage || '';
        Cart.add(id, name, price, image);
      });
    });
  }
};

document.addEventListener('DOMContentLoaded', () => Cart.init());