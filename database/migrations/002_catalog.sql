-- =====================================================
-- مهاجرت ۰۰۲ — کاتالوگ محصولات (Catalog)
-- معماری «مدل فنی + واریانت (SKU)» بر اساس FUCS v3
-- تفکیک دغدغه‌ها: مدل فنی از رنگ و طرح جداست
-- =====================================================

-- انواع محصول: HT حوله، DS دستمال، SH شال، PC پوشاک، MO ملافه، TN تن‌پوش، PR پرده، HC رومیزی، FB فرت بافت، RB روبندک و …
CREATE TABLE product_types (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(4)  NOT NULL COMMENT 'HT/DS/SH/…',
    name_fa     VARCHAR(80) NOT NULL,
    name_en     VARCHAR(80) NULL,
    description TEXT NULL,
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NULL,
    updated_at  DATETIME NULL,
    UNIQUE KEY uq_types_code (code),
    CONSTRAINT chk_types_code CHECK (code REGEXP '^[A-Z]{2,3}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- کتابخانهٔ رنگ — کد سه‌نویسه‌ای: حرف خانواده + دو رقم (R01 قرمز روناس، I01 نیلی، …)
CREATE TABLE colors (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(4)  NOT NULL,
    name_fa    VARCHAR(80) NOT NULL COMMENT 'نام سنتی رنگ: روناس، نیلی، پوست پیازی…',
    hex        CHAR(7)     NOT NULL DEFAULT '#8B2E3A',
    family     VARCHAR(40) NULL COMMENT 'خانوادهٔ رنگ',
    is_natural TINYINT(1)  NOT NULL DEFAULT 1 COMMENT 'رنگرزی طبیعی است؟',
    is_active  TINYINT(1)  NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_colors_code (code),
    CONSTRAINT chk_colors_code CHECK (code REGEXP '^[A-Z][0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- کتابخانهٔ طرح — P + چهار رقم صعودی
CREATE TABLE patterns (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(8)  NOT NULL COMMENT 'P0001..P9999',
    name_fa    VARCHAR(120) NOT NULL COMMENT 'نام طرح: ترنج، بته‌جقه، خشتی…',
    designer   VARCHAR(120) NULL,
    image      VARCHAR(190) NULL,
    motif      VARCHAR(60)  NULL COMMENT 'سبک نقش‌مایه',
    description TEXT NULL,
    is_active  TINYINT(1)  NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_patterns_code (code),
    CONSTRAINT chk_patterns_code CHECK (code REGEXP '^P[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- مدل فنی:  [TYPE]-[WEAVE][KNOT][STITCH]-CH[XX]   مثال HT-2LBSTD-CH125
CREATE TABLE product_models (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30)  NOT NULL COMMENT 'کد مدل فنی FUCS',
    type_id     INT UNSIGNED NOT NULL,
    weave       ENUM('1L','2L','3L','4L') NOT NULL DEFAULT '2L' COMMENT 'پودگذاری',
    knot        ENUM('B','G')             NOT NULL DEFAULT 'B' COMMENT 'گره: B بی‌گره / G گره‌دار',
    stitch      ENUM('NS','STD','2N','R','CUST') NOT NULL DEFAULT 'STD' COMMENT 'دوخت',
    chelle      DECIMAL(5,2) NOT NULL COMMENT 'چله (مثلاً 12.50)',
    name_fa     VARCHAR(160) NOT NULL COMMENT 'نام نمایشی مدل',
    base_price  BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'قیمت پایه (تومان)',
    weight_gr   INT UNSIGNED NULL COMMENT 'وزن تقریبی (گرم)',
    size_cm     VARCHAR(60)  NULL COMMENT 'ابعاد: 70×140',
    description TEXT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NULL,
    updated_at  DATETIME NULL,
    UNIQUE KEY uq_models_code (code),
    KEY idx_models_type (type_id),
    CONSTRAINT fk_models_type FOREIGN KEY (type_id) REFERENCES product_types(id),
    CONSTRAINT chk_models_code CHECK (code REGEXP '^[A-Z]{2,3}-(1L|2L|3L|4L)(B|G)(NS|STD|2N|R|CUST)-CH[0-9]{2,3}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- واریانت فروشگاهی (SKU) = مدل + رنگ + طرح
CREATE TABLE product_variants (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku        VARCHAR(48)  NOT NULL COMMENT 'HT-2LBSTD-CH125-R01-P0003',
    model_id   INT UNSIGNED NOT NULL,
    color_id   INT UNSIGNED NOT NULL,
    pattern_id INT UNSIGNED NOT NULL,
    price      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'قیمت فروش (تومان)',
    cost       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'بهای تمام‌شده (تومان)',
    stock      INT NOT NULL DEFAULT 0 COMMENT 'موجودی انبار',
    reserved   INT NOT NULL DEFAULT 0 COMMENT 'رزروشده در سبدهای فعال',
    image      VARCHAR(190) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_variants_sku (sku),
    UNIQUE KEY uq_variants_combo (model_id, color_id, pattern_id),
    KEY idx_variants_model (model_id),
    CONSTRAINT fk_variants_model   FOREIGN KEY (model_id)   REFERENCES product_models(id),
    CONSTRAINT fk_variants_color   FOREIGN KEY (color_id)   REFERENCES colors(id),
    CONSTRAINT fk_variants_pattern FOREIGN KEY (pattern_id) REFERENCES patterns(id),
    CONSTRAINT chk_variants_sku CHECK (sku REGEXP '^[A-Z]{2,3}-(1L|2L|3L|4L)(B|G)(NS|STD|2N|R|CUST)-CH[0-9]{2,3}-[A-Z][0-9]{2}-P[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
