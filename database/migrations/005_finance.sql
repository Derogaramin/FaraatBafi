-- =====================================================
-- مهاجرت ۰۰۵ — مالی (Finance)
-- فیش دستمزد PAY-W045-1403-02 / پورسانت COM-A03-1403-03 / سند حسابداری ACC-1403-05-003
-- =====================================================

CREATE TABLE payments (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(24) NOT NULL COMMENT 'فیش دستمزد بافنده',
    weaver_id     INT UNSIGNED NOT NULL,
    work_order_id INT UNSIGNED NULL,
    amount        BIGINT UNSIGNED NOT NULL COMMENT 'مبلغ (تومان)',
    period        VARCHAR(20) NULL COMMENT 'دورهٔ تسویه: 1403-05',
    method        ENUM('cash','bank','check') NOT NULL DEFAULT 'bank',
    status        ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    paid_at       DATETIME NULL,
    paid_by       INT UNSIGNED NULL,
    note          VARCHAR(255) NULL,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    UNIQUE KEY uq_payments_code (code),
    KEY idx_payments_weaver (weaver_id),
    KEY idx_payments_status (status),
    CONSTRAINT fk_payments_weaver FOREIGN KEY (weaver_id)     REFERENCES weavers(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_wo     FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE SET NULL,
    CONSTRAINT chk_payments_code CHECK (code REGEXP '^PAY-W[0-9]{3}-[0-9]{4}-[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commissions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(24) NOT NULL COMMENT 'COM-A03-1403-03',
    agency_id    INT UNSIGNED NOT NULL,
    order_id     INT UNSIGNED NULL,
    base_amount  BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'مبلغ پایهٔ فروش',
    rate         DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT 'نرخ درصد',
    amount       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    period       VARCHAR(20) NULL COMMENT 'دوره: 1403-03',
    status       ENUM('pending','approved','paid','rejected') NOT NULL DEFAULT 'pending',
    paid_at      DATETIME NULL,
    created_at   DATETIME NULL,
    updated_at   DATETIME NULL,
    UNIQUE KEY uq_commissions_code (code),
    KEY idx_comm_agency (agency_id),
    CONSTRAINT fk_comm_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE,
    CONSTRAINT fk_comm_order  FOREIGN KEY (order_id)  REFERENCES orders(id)   ON DELETE SET NULL,
    CONSTRAINT chk_comm_code CHECK (code REGEXP '^COM-A[0-9]{2}-[0-9]{4}-[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE accounting_documents (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL COMMENT 'ACC-1403-05-003',
    title       VARCHAR(190) NOT NULL,
    kind        ENUM('income','expense','transfer') NOT NULL DEFAULT 'income',
    amount      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    doc_date    DATE NOT NULL,
    ref_type    VARCHAR(40) NULL,
    ref_id      INT UNSIGNED NULL,
    created_by  INT UNSIGNED NULL,
    note        VARCHAR(255) NULL,
    created_at  DATETIME NULL,
    UNIQUE KEY uq_acc_code (code),
    KEY idx_acc_date (doc_date),
    CONSTRAINT chk_acc_code CHECK (code REGEXP '^ACC-[0-9]{4}-[0-9]{2}-[0-9]{3}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
