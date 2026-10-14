-- =====================================================
-- مهاجرت ۰۰۷ — فاز ۰ نقشهٔ راه: امنیت، ممیزی، صف رویدادها
-- (بر اساس docs/09-critique.md و docs/10-roadmap.md)
-- =====================================================

-- تلاش‌های ورود (rate-limit / lockout)
CREATE TABLE login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier   VARCHAR(190) NOT NULL COMMENT 'کد کاربری/موبایل/ایمیل واردشده',
    ip           VARCHAR(45)  NOT NULL,
    success      TINYINT(1)   NOT NULL DEFAULT 0,
    attempted_at DATETIME     NOT NULL,
    KEY idx_attempts_ident (identifier, attempted_at),
    KEY idx_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ممیزی تغییرات فیلدهای حساس (قبل/بعد)
CREATE TABLE field_audits (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    entity      VARCHAR(60)  NOT NULL COMMENT 'variant|material|weaver|agency|...',
    entity_id   INT UNSIGNED NOT NULL,
    entity_code VARCHAR(48)  NULL COMMENT 'کد FUCS موجودیت در صورت وجود',
    field       VARCHAR(60)  NOT NULL,
    old_value   TEXT NULL,
    new_value   TEXT NULL,
    created_at  DATETIME NOT NULL,
    KEY idx_audit_entity (entity, entity_id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- صف رویدادها (پردازش ناهمگام با کارگر)
CREATE TABLE event_queue (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event        VARCHAR(120) NOT NULL,
    payload      LONGTEXT NULL COMMENT 'JSON — فقط دادهٔ سریال‌شدنی',
    status       ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error   TEXT NULL,
    created_at   DATETIME NOT NULL,
    processed_at DATETIME NULL,
    KEY idx_queue_status (status, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- احراز هویت دومرحله‌ای و تغییر گذرواژهٔ اجباری
ALTER TABLE users
    ADD COLUMN totp_secret VARCHAR(64) NULL AFTER password_hash,
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER totp_secret;

-- ایندکس‌های مرکب برای پرس‌وجوهای پرتکرار (نقد §۶٫۵)
CREATE INDEX idx_orders_status_placed ON orders (status, placed_at);
CREATE INDEX idx_orders_agency_placed ON orders (agency_id, placed_at);
CREATE INDEX idx_wo_weaver_status ON work_orders (weaver_id, status);
CREATE INDEX idx_items_variant ON order_items (variant_id);
CREATE INDEX idx_variants_active_featured ON product_variants (is_active, is_featured);
CREATE INDEX idx_payments_weaver_status ON payments (weaver_id, status);
CREATE INDEX idx_posts_published ON posts (status, published_at);
