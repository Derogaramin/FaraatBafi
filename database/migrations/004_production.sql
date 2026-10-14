-- =====================================================
-- مهاجرت ۰۰۴ — تولید، انبار و کنترل کیفیت (Production)
-- سفارش تولید WO / بازرسی QC / عیب DEF / ماده اولیه RM / موقعیت LOC / بسته BOX
-- کد اصالت FB-1403-0521-W023 (مشتری‌محور، حاوی QR)
-- =====================================================

CREATE TABLE materials (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(16) NOT NULL COMMENT 'RM-COT-R01',
    name_fa      VARCHAR(120) NOT NULL,
    category     ENUM('yarn','dye','cotton','wool','silk','packaging','other') NOT NULL DEFAULT 'yarn',
    unit         ENUM('kg','gr','m','count','l') NOT NULL DEFAULT 'kg',
    price_per_unit BIGINT UNSIGNED NOT NULL DEFAULT 0,
    stock        DECIMAL(12,3) NOT NULL DEFAULT 0,
    min_stock    DECIMAL(12,3) NOT NULL DEFAULT 0 COMMENT 'حد سفارش',
    location_code VARCHAR(24) NULL COMMENT 'LOC-MAIN-A3-01',
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NULL,
    updated_at   DATETIME NULL,
    UNIQUE KEY uq_materials_code (code),
    CONSTRAINT chk_materials_code CHECK (code REGEXP '^RM-[A-Z]{3}-[A-Z][0-9]{2}$'),
    CONSTRAINT chk_location_code CHECK (location_code IS NULL OR location_code REGEXP '^LOC-[A-Z]+-[A-Z][0-9]-[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- سفارش تولید (تخصیص به بافنده): WO-1403-0145
CREATE TABLE work_orders (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(16) NOT NULL,
    variant_id   INT UNSIGNED NOT NULL,
    order_id     INT UNSIGNED NULL COMMENT 'سفارش مشتری مبدأ (null = تولید برای انبار)',
    weaver_id    INT UNSIGNED NULL COMMENT 'بافندهٔ تخصیص‌یافته',
    quantity     INT UNSIGNED NOT NULL DEFAULT 1,
    unit_wage    BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'دستمزد هر واحد (تومان)',
    status       ENUM('draft','assigned','materials_received','weaving','delivered','qc_passed','qc_rejected','completed','cancelled')
                 NOT NULL DEFAULT 'draft',
    priority     ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    due_date     DATE NULL,
    assigned_at  DATETIME NULL,
    materials_at DATETIME NULL COMMENT 'زمان دریافت مواد',
    started_at   DATETIME NULL COMMENT 'زمان شروع بافت',
    delivered_at DATETIME NULL COMMENT 'زمان تحویل به انبار',
    completed_at DATETIME NULL COMMENT 'زمان تسویه',
    note         TEXT NULL,
    created_at   DATETIME NULL,
    updated_at   DATETIME NULL,
    UNIQUE KEY uq_wo_code (code),
    KEY idx_wo_weaver (weaver_id),
    KEY idx_wo_status (status),
    KEY idx_wo_order (order_id),
    CONSTRAINT fk_wo_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id),
    CONSTRAINT fk_wo_weaver  FOREIGN KEY (weaver_id)  REFERENCES weavers(id) ON DELETE SET NULL,
    CONSTRAINT fk_wo_order   FOREIGN KEY (order_id)   REFERENCES orders(id)  ON DELETE SET NULL,
    CONSTRAINT chk_wo_code CHECK (code REGEXP '^WO-[0-9]{4}-[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- توزیع مواد اولیه بین بافندگان
CREATE TABLE material_distributions (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_order_id  INT UNSIGNED NOT NULL,
    material_id    INT UNSIGNED NOT NULL,
    quantity       DECIMAL(12,3) NOT NULL,
    distributed_by INT UNSIGNED NULL COMMENT 'کاربر انباردار',
    distributed_at DATETIME NOT NULL,
    note           VARCHAR(255) NULL,
    KEY idx_md_wo (work_order_id),
    CONSTRAINT fk_md_wo       FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_md_material FOREIGN KEY (material_id)   REFERENCES materials(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- حرکت‌های انبار (ورود/خروج مواد و محصول)
CREATE TABLE inventory_movements (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_id INT UNSIGNED NULL,
    variant_id  INT UNSIGNED NULL,
    direction  ENUM('in','out') NOT NULL,
    quantity   DECIMAL(12,3) NOT NULL DEFAULT 1,
    ref_type   ENUM('purchase','distribution','delivery','sale','return','adjust','consignment') NOT NULL,
    ref_id     INT UNSIGNED NULL,
    user_id    INT UNSIGNED NULL,
    note       VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    KEY idx_mov_material (material_id),
    KEY idx_mov_variant (variant_id),
    CONSTRAINT fk_mov_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL,
    CONSTRAINT fk_mov_variant  FOREIGN KEY (variant_id)  REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- انواع عیب بافت: DEF-CLR-01
CREATE TABLE defect_types (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(12) NOT NULL,
    name_fa    VARCHAR(120) NOT NULL,
    category   VARCHAR(40) NOT NULL DEFAULT 'general',
    severity   ENUM('minor','major','critical') NOT NULL DEFAULT 'minor',
    created_at DATETIME NULL,
    UNIQUE KEY uq_defects_code (code),
    CONSTRAINT chk_defects_code CHECK (code REGEXP '^DEF-[A-Z]{3}-[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- بازرسی کیفیت: QC-1403-0023
CREATE TABLE quality_inspections (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(16) NOT NULL,
    work_order_id INT UNSIGNED NOT NULL,
    inspector_id  INT UNSIGNED NULL COMMENT 'کاربر بازرس',
    result        ENUM('passed','rejected','conditional') NOT NULL,
    defect_type_id INT UNSIGNED NULL,
    score         TINYINT UNSIGNED NULL COMMENT 'امتیاز ۰ تا ۱۰۰',
    note          TEXT NULL,
    inspected_at  DATETIME NOT NULL,
    created_at    DATETIME NULL,
    UNIQUE KEY uq_qc_code (code),
    KEY idx_qc_wo (work_order_id),
    CONSTRAINT fk_qc_wo     FOREIGN KEY (work_order_id)  REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_qc_defect FOREIGN KEY (defect_type_id) REFERENCES defect_types(id) ON DELETE SET NULL,
    CONSTRAINT chk_qc_code CHECK (code REGEXP '^QC-[0-9]{4}-[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- کد اصالت مشتری‌محور: FB-[YEAR]-[SERIES]-[WEAVER]
CREATE TABLE authenticity_codes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(24) NOT NULL,
    variant_id    INT UNSIGNED NOT NULL,
    work_order_id INT UNSIGNED NULL,
    weaver_id     INT UNSIGNED NOT NULL,
    qr_url        VARCHAR(255) NOT NULL COMMENT 'آدرس صفحهٔ اصالت (محتوای QR)',
    status        ENUM('reserved','sold','returned','void') NOT NULL DEFAULT 'reserved',
    order_id      INT UNSIGNED NULL COMMENT 'سفارش فروش متصل',
    sold_at       DATETIME NULL,
    created_at    DATETIME NULL,
    UNIQUE KEY uq_auth_code (code),
    KEY idx_auth_variant (variant_id),
    KEY idx_auth_weaver (weaver_id),
    CONSTRAINT fk_auth_variant FOREIGN KEY (variant_id)    REFERENCES product_variants(id),
    CONSTRAINT fk_auth_wo      FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_auth_weaver  FOREIGN KEY (weaver_id)     REFERENCES weavers(id),
    CONSTRAINT chk_auth_code CHECK (code REGEXP '^FB-[0-9]{4}-[0-9]{4}-W[0-9]{3}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- کالای امانی نزد نمایندگی‌ها (فاز دوم)
CREATE TABLE consignments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id   INT UNSIGNED NOT NULL,
    variant_id  INT UNSIGNED NOT NULL,
    quantity    INT NOT NULL DEFAULT 0 COMMENT 'موجودی فعلی نزد نمایندگی (می‌تواند منفی=بدهی باشد)',
    sent_at     DATETIME NOT NULL,
    updated_at  DATETIME NULL,
    UNIQUE KEY uq_consign (agency_id, variant_id),
    CONSTRAINT fk_consign_agency  FOREIGN KEY (agency_id)  REFERENCES agencies(id) ON DELETE CASCADE,
    CONSTRAINT fk_consign_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
