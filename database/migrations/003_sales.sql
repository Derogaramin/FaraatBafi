-- =====================================================
-- مهاجرت ۰۰۳ — فروش و مشتریان (Sales)
-- سفارش ORD-1403-000125 / فاکتور INV / رسید RCP / بارنامه SHP / مرجوعی RMA / کوپن CPN
-- =====================================================

CREATE TABLE coupons (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(40) NOT NULL COMMENT 'CPN-NOWRUZ-1403',
    title       VARCHAR(120) NOT NULL,
    type        ENUM('percent','amount') NOT NULL DEFAULT 'percent',
    value       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'درصد یا مبلغ (تومان)',
    max_amount  BIGINT UNSIGNED NULL COMMENT 'سقف تخفیف برای درصدی',
    min_order   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    starts_at   DATE NULL,
    expires_at  DATE NULL,
    usage_limit INT UNSIGNED NULL,
    used_count  INT UNSIGNED NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NULL,
    UNIQUE KEY uq_coupons_code (code),
    CONSTRAINT chk_coupons_code CHECK (code REGEXP '^CPN-[A-Z0-9]+-[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(20) NOT NULL COMMENT 'ORD-1403-000125',
    customer_id   INT UNSIGNED NULL,
    user_id       INT UNSIGNED NULL COMMENT 'کاربر ثبت‌کننده (مشتری/نمایندگی)',
    agency_id     INT UNSIGNED NULL COMMENT 'در صورت فروش نمایندگی',
    channel       ENUM('web','agency','phone','admin') NOT NULL DEFAULT 'web',
    status        ENUM('pending','confirmed','in_production','ready','shipped','delivered','cancelled','returned')
                  NOT NULL DEFAULT 'pending',
    subtotal      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    discount      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    shipping_cost BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total         BIGINT UNSIGNED NOT NULL DEFAULT 0,
    coupon_id     INT UNSIGNED NULL,
    receiver      VARCHAR(120) NOT NULL,
    phone         VARCHAR(15)  NOT NULL,
    province      VARCHAR(80)  NULL,
    city          VARCHAR(80)  NULL,
    address       VARCHAR(255) NULL,
    postal_code   VARCHAR(12)  NULL,
    note          TEXT NULL,
    placed_at     DATETIME NOT NULL,
    delivered_at  DATETIME NULL,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    UNIQUE KEY uq_orders_code (code),
    KEY idx_orders_customer (customer_id),
    KEY idx_orders_agency (agency_id),
    KEY idx_orders_status (status),
    KEY idx_orders_placed (placed_at),
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_user     FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE SET NULL,
    CONSTRAINT fk_orders_agency   FOREIGN KEY (agency_id)   REFERENCES agencies(id)  ON DELETE SET NULL,
    CONSTRAINT fk_orders_coupon   FOREIGN KEY (coupon_id)   REFERENCES coupons(id)   ON DELETE SET NULL,
    CONSTRAINT chk_orders_code CHECK (code REGEXP '^ORD-[0-9]{4}-[0-9]{6}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED NULL,
    sku        VARCHAR(48) NOT NULL COMMENT 'اسکنپشوت SKU در لحظهٔ خرید',
    title      VARCHAR(200) NOT NULL COMMENT 'نام محصول در لحظهٔ خرید',
    unit_price BIGINT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    total      BIGINT UNSIGNED NOT NULL,
    KEY idx_items_order (order_id),
    CONSTRAINT fk_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(20) NOT NULL COMMENT 'INV-1403-00230',
    order_id   INT UNSIGNED NOT NULL,
    amount     BIGINT UNSIGNED NOT NULL,
    paid       TINYINT(1) NOT NULL DEFAULT 0,
    method     ENUM('cash','pos','card','credit','wallet') NOT NULL DEFAULT 'card',
    issued_at  DATETIME NOT NULL,
    paid_at    DATETIME NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uq_invoices_code (code),
    UNIQUE KEY uq_invoices_order (order_id),
    CONSTRAINT fk_invoices_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT chk_invoices_code CHECK (code REGEXP '^INV-[0-9]{4}-[0-9]{5}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE receipts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(20) NOT NULL COMMENT 'RCP-1403-00215',
    order_id   INT UNSIGNED NULL,
    invoice_id INT UNSIGNED NULL,
    amount     BIGINT UNSIGNED NOT NULL,
    method     ENUM('cash','pos','card','wallet') NOT NULL DEFAULT 'card',
    received_by INT UNSIGNED NULL COMMENT 'کاربر دریافت‌کننده',
    received_at DATETIME NOT NULL,
    note       VARCHAR(255) NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uq_receipts_code (code),
    KEY idx_receipts_order (order_id),
    CONSTRAINT fk_receipts_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE SET NULL,
    CONSTRAINT fk_receipts_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    CONSTRAINT chk_receipts_code CHECK (code REGEXP '^RCP-[0-9]{4}-[0-9]{5}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipments (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code           VARCHAR(20) NOT NULL COMMENT 'SHP-1403-0041',
    order_id       INT UNSIGNED NOT NULL,
    carrier        VARCHAR(120) NULL COMMENT 'شرکت حمل: پست، تیپاکس، باربری',
    tracking_no    VARCHAR(60)  NULL,
    cost           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status         ENUM('preparing','sent','in_transit','delivered','failed') NOT NULL DEFAULT 'preparing',
    shipped_at     DATETIME NULL,
    delivered_at   DATETIME NULL,
    created_at     DATETIME NULL,
    updated_at     DATETIME NULL,
    UNIQUE KEY uq_shipments_code (code),
    KEY idx_shipments_order (order_id),
    CONSTRAINT fk_shipments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT chk_shipments_code CHECK (code REGEXP '^SHP-[0-9]{4}-[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE returns (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL COMMENT 'RMA-1403-0007',
    order_id    INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NULL,
    reason      VARCHAR(255) NOT NULL,
    status      ENUM('requested','approved','received','refunded','rejected') NOT NULL DEFAULT 'requested',
    refund_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
    requested_at DATETIME NOT NULL,
    resolved_at  DATETIME NULL,
    note        TEXT NULL,
    created_at  DATETIME NULL,
    updated_at  DATETIME NULL,
    UNIQUE KEY uq_returns_code (code),
    KEY idx_returns_order (order_id),
    CONSTRAINT fk_returns_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT chk_returns_code CHECK (code REGEXP '^RMA-[0-9]{4}-[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
