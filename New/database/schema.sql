-- پایگاه داده faraat
CREATE DATABASE IF NOT EXISTS faraat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE faraat;

-- جدول کاربران (تمامی نقش‌ها: عضو اصلی، مدیر اجرایی، بافنده، نمایندگی، مشتری، پرسنل)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL UNIQUE,
    mobile VARCHAR(15) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('member','admin','weaver','agent','customer','staff') NOT NULL DEFAULT 'customer',
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- جدول نوع محصول (product_types)
CREATE TABLE product_types (
    code VARCHAR(3) PRIMARY KEY,   -- HT, DS, SH, ...
    name_fa VARCHAR(100) NOT NULL,
    has_stitching BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;
INSERT INTO product_types (code, name_fa, has_stitching) VALUES
('HT','حوله',1), ('DS','دستمال',0), ('SH','شال',0), ('PC','پانچو',1), ('MO','مانتو',1),
('TN','تونیک',1), ('PR','پیراهن',1), ('HC','کلاه حمام',1), ('FB','پارچه تخت',0), ('RB','ربدوشامبر',1);

-- جدول مدل فنی محصول (product_models)
CREATE TABLE product_models (
    model_code VARCHAR(30) PRIMARY KEY,   -- e.g. HT-2LBSTD-CH125
    type_code VARCHAR(3) NOT NULL,
    weave CHAR(2) NOT NULL CHECK (weave IN ('1L','2L','3L')),
    knot CHAR(1) NOT NULL CHECK (knot IN ('B','G')),
    stitch CHAR(4) NOT NULL CHECK (stitch IN ('NS','STD','2N','R','CUST')),
    chaleh TINYINT UNSIGNED NOT NULL,     -- chaleh*10
    base_weight_grams SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    width_cm DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_code) REFERENCES product_types(code)
) ENGINE=InnoDB;

-- جدول رنگ‌ها (colors)
CREATE TABLE colors (
    color_code VARCHAR(3) PRIMARY KEY,   -- R01, T01, ...
    name_fa VARCHAR(50) NOT NULL,
    hex CHAR(7) NOT NULL                 -- #8B2E3A
) ENGINE=InnoDB;
INSERT INTO colors (color_code, name_fa, hex) VALUES
('R01','قرمز روناس','#8B2E3A'), ('R02','قرمز آلبالویی','#A13D4A'), ('I01','نیلی','#1A3B4C'),
('T01','فیروزه‌ای','#1BA8A1'), ('O01','اخرایی زعفران','#C68A3C'), ('O02','خردلی','#D49A3E'),
('K01','کرم پنبه‌ای','#FDFBF7'), ('W01','سفید طبیعی','#FEFEFE'), ('B01','مشکی','#000000');

-- جدول طرح‌ها (patterns)
CREATE TABLE patterns (
    pattern_code VARCHAR(5) PRIMARY KEY,  -- P0001, P0002, ...
    name_fa VARCHAR(100) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT INTO patterns (pattern_code, name_fa) VALUES
('P0001','راه‌راه ساده'), ('P0002','لوزی سنتی تربت‌جام'), ('P0003','بته‌جقه'), ('P0004','گل شاه عباسی');

-- جدول واریانت قابل فروش (product_variants)
CREATE TABLE product_variants (
    sku VARCHAR(50) PRIMARY KEY,         -- e.g. HT-2LBSTD-CH125-R01-P0003
    model_code VARCHAR(30) NOT NULL,
    color_code VARCHAR(3) NOT NULL,
    pattern_code VARCHAR(5) NOT NULL,
    price_modifier DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (model_code) REFERENCES product_models(model_code),
    FOREIGN KEY (color_code) REFERENCES colors(color_code),
    FOREIGN KEY (pattern_code) REFERENCES patterns(pattern_code)
) ENGINE=InnoDB;

-- جدول بافندگان (جزئیات بیشتر برای کاربران نقش weaver)
CREATE TABLE weaver_details (
    user_id INT NOT NULL PRIMARY KEY,
    weaver_code VARCHAR(4) NOT NULL UNIQUE,   -- W045
    national_code VARCHAR(10) NULL,
    address TEXT,
    skills TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (weaver_code) REFERENCES users(code)
) ENGINE=InnoDB;

-- جدول نمایندگی‌ها (جزئیات بیشتر)
CREATE TABLE agency_details (
    user_id INT NOT NULL PRIMARY KEY,
    agency_code VARCHAR(3) NOT NULL UNIQUE,   -- A03
    province VARCHAR(50),
    city VARCHAR(50),
    address TEXT,
    commission_percent DECIMAL(4,2) NOT NULL DEFAULT 5.00,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (agency_code) REFERENCES users(code)
) ENGINE=InnoDB;

-- جدول سفارش‌های مشتری (orders)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(18) NOT NULL UNIQUE,   -- ORD-1403-000125
    customer_id INT NOT NULL,
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    shipping_address TEXT,
    tracking_code VARCHAR(30) NULL,
    FOREIGN KEY (customer_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- جدول آیتم‌های سفارش
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (sku) REFERENCES product_variants(sku)
) ENGINE=InnoDB;

-- جدول سفارش‌های تولید (work_orders)
CREATE TABLE work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_code VARCHAR(15) NOT NULL UNIQUE,  -- WO-1403-0145
    order_id INT NOT NULL,
    weaver_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('pending','materials_delivered','weaving','completed','qc_passed','shipped') NOT NULL DEFAULT 'pending',
    materials_delivered_date DATE NULL,
    weaving_start_date DATE NULL,
    completed_date DATE NULL,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (weaver_id) REFERENCES users(id),
    FOREIGN KEY (sku) REFERENCES product_variants(sku)
) ENGINE=InnoDB;

-- جدول کد اصالت (authenticity_codes)
CREATE TABLE authenticity_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(25) NOT NULL UNIQUE,          -- FB-1403-0521-W023
    work_order_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL,
    weaver_id INT NOT NULL,
    produced_date DATE NOT NULL,
    qr_url VARCHAR(255) NOT NULL,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
    FOREIGN KEY (sku) REFERENCES product_variants(sku),
    FOREIGN KEY (weaver_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- جدول تیکت‌ها (برای فاز اول جهت آمادگی)
CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_code VARCHAR(18) NOT NULL UNIQUE,    -- TKT-1403-00210
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    status ENUM('open','closed','pending') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- جلسات (آمادگی)
CREATE TABLE meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_code VARCHAR(12) NOT NULL UNIQUE,   -- MT-1403-04
    title VARCHAR(200) NOT NULL,
    meeting_date DATE NOT NULL,
    minutes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- مصوبات
CREATE TABLE resolutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resolution_code VARCHAR(18) NOT NULL UNIQUE, -- RES-1403-04-02
    meeting_id INT NOT NULL,
    description TEXT NOT NULL,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id)
) ENGINE=InnoDB;

-- رأی‌گیری‌ها
CREATE TABLE votings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voting_code VARCHAR(15) NOT NULL UNIQUE,    -- VOT-1403-001
    title VARCHAR(200) NOT NULL,
    options JSON NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- اسناد مالی (آمادگی)
CREATE TABLE accounting_docs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_code VARCHAR(18) NOT NULL UNIQUE,       -- ACC-1403-05-003
    doc_date DATE NOT NULL,
    description TEXT,
    amount DECIMAL(15,2) NOT NULL
) ENGINE=InnoDB;

-- فیش دستمزد
CREATE TABLE weaver_payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payslip_code VARCHAR(20) NOT NULL UNIQUE,   -- PAY-W045-1403-02
    weaver_id INT NOT NULL,
    period VARCHAR(10) NOT NULL,                -- e.g., 1403-02
    total_amount DECIMAL(12,2) NOT NULL,
    paid_at DATE NULL,
    FOREIGN KEY (weaver_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ...

-- اندیس‌های اضافی
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_work_orders_weaver ON work_orders(weaver_id);