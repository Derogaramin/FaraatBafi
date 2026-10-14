-- =====================================================
-- مهاجرت ۰۰۱ — هویت و اشخاص (Identity)
-- سامانه جامع مدیریت تعاونی فرت‌بافی
-- همهٔ کدها بر اساس نظام FUCS v3 با CHECK/REGEXP اعتبارسنجی می‌شوند
-- =====================================================

CREATE TABLE code_counters (
    scope      VARCHAR(120) NOT NULL PRIMARY KEY COMMENT 'دامنهٔ شمارنده — مثل order.1403',
    last_value INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(12)  NOT NULL COMMENT 'کد کاربری FUCS با پیشوند نقش (M/S/W/A/C)',
    name          VARCHAR(120) NOT NULL,
    username      VARCHAR(60)  NULL,
    phone         VARCHAR(15)  NOT NULL,
    email         VARCHAR(160) NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar        VARCHAR(190) NULL,
    status        ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    UNIQUE KEY uq_users_code (code),
    UNIQUE KEY uq_users_phone (phone),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT chk_users_code CHECK (code REGEXP '^[MSWAC][0-9]{2,6}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- اعضای اصلی تعاونی (سهام‌داران و هیئت‌مدیره) — کد M###
CREATE TABLE members (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    code        VARCHAR(8)   NOT NULL COMMENT 'M001..M999',
    national_id VARCHAR(10)  NULL,
    position    VARCHAR(120) NULL COMMENT 'سمت: رئیس هیئت‌مدیره، بازرس و …',
    shares      INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'تعداد سهام',
    share_value BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ارزش هر سهم (تومان)',
    join_date   DATE NULL,
    note        TEXT NULL,
    created_at  DATETIME NULL,
    updated_at  DATETIME NULL,
    UNIQUE KEY uq_members_code (code),
    UNIQUE KEY uq_members_user (user_id),
    CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_members_code CHECK (code REGEXP '^M[0-9]{3}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- پرسنل اجرایی — کد S###
CREATE TABLE staff (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    code        VARCHAR(8)   NOT NULL COMMENT 'S001..S999',
    position    VARCHAR(120) NULL,
    hire_date   DATE NULL,
    base_salary BIGINT UNSIGNED NOT NULL DEFAULT 0,
    national_id VARCHAR(10)  NULL,
    created_at  DATETIME NULL,
    updated_at  DATETIME NULL,
    UNIQUE KEY uq_staff_code (code),
    UNIQUE KEY uq_staff_user (user_id),
    CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_staff_code CHECK (code REGEXP '^S[0-9]{3}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- بافندگان — کد W###
CREATE TABLE weavers (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NULL COMMENT 'کاربر پنل بافنده (اختیاری)',
    code         VARCHAR(8)   NOT NULL COMMENT 'W001..W999',
    name         VARCHAR(120) NOT NULL,
    phone        VARCHAR(15)  NULL,
    national_id  VARCHAR(10)  NULL,
    city         VARCHAR(80)  NULL,
    village      VARCHAR(80)  NULL,
    address      VARCHAR(255) NULL,
    specialty    VARCHAR(120) NULL COMMENT 'تخصص بافت: حوله، شال، دستمال و …',
    skill_level  ENUM('apprentice','junior','mid','senior','master') NOT NULL DEFAULT 'mid' COMMENT 'سطح مهارت',
    loom_count   TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'تعداد دستگاه بافت',
    wage_rate    BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'دستمزد هر واحد بافت (تومان)',
    balance      BIGINT NOT NULL DEFAULT 0 COMMENT 'ماندهٔ حساب دستمزد (تومان) — مثبت: بدهی تعاونی به بافنده',
    rating       DECIMAL(3,2) NOT NULL DEFAULT 3.00 COMMENT 'امتیاز کیفی ۰ تا ۵',
    status       ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    joined_at    DATE NULL,
    note         TEXT NULL,
    created_at   DATETIME NULL,
    updated_at   DATETIME NULL,
    UNIQUE KEY uq_weavers_code (code),
    UNIQUE KEY uq_weavers_user (user_id),
    KEY idx_weavers_status (status),
    CONSTRAINT fk_weavers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_weavers_code CHECK (code REGEXP '^W[0-9]{3}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- نمایندگی‌های فروش — کد A##
CREATE TABLE agencies (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NULL,
    code            VARCHAR(8)   NOT NULL COMMENT 'A01..A99',
    name            VARCHAR(160) NOT NULL,
    manager_name    VARCHAR(120) NULL,
    province        VARCHAR(80)  NULL,
    city            VARCHAR(80)  NULL,
    address         VARCHAR(255) NULL,
    phone           VARCHAR(15)  NULL,
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00 COMMENT 'نرخ پورسانت (درصد)',
    credit_limit    BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'سقف اعتبار خرید (تومان)',
    balance         BIGINT NOT NULL DEFAULT 0 COMMENT 'ماندهٔ حساب — مثبت: بدهی نمایندگی',
    status          ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    started_at      DATE NULL,
    created_at      DATETIME NULL,
    updated_at      DATETIME NULL,
    UNIQUE KEY uq_agencies_code (code),
    UNIQUE KEY uq_agencies_user (user_id),
    CONSTRAINT fk_agencies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_agencies_code CHECK (code REGEXP '^A[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- مشتریان — کد C######
CREATE TABLE customers (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    code       VARCHAR(12)  NOT NULL COMMENT 'C000001..',
    name       VARCHAR(120) NOT NULL,
    phone      VARCHAR(15)  NOT NULL,
    email      VARCHAR(160) NULL,
    province   VARCHAR(80)  NULL,
    city       VARCHAR(80)  NULL,
    address    VARCHAR(255) NULL,
    postal_code VARCHAR(12) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_customers_code (code),
    UNIQUE KEY uq_customers_user (user_id),
    KEY idx_customers_phone (phone),
    CONSTRAINT fk_customers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_customers_code CHECK (code REGEXP '^C[0-9]{6}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
