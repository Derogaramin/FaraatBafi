-- =====================================================
-- مهاجرت ۰۰۶ — پلتفرم: محتوا، پشتیبانی، حاکمیت، اعلان‌ها
-- جلسه MT / مصوبه RES / رأی‌گیری VOT / نامه IR / تیکت TKT
-- =====================================================

CREATE TABLE posts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200) NOT NULL,
    slug         VARCHAR(220) NOT NULL,
    excerpt      VARCHAR(400) NULL,
    body         MEDIUMTEXT NULL,
    image        VARCHAR(190) NULL,
    category     VARCHAR(60) NOT NULL DEFAULT 'blog',
    tags         VARCHAR(255) NULL,
    author_id    INT UNSIGNED NULL,
    status       ENUM('draft','published') NOT NULL DEFAULT 'draft',
    views        INT UNSIGNED NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at   DATETIME NULL,
    updated_at   DATETIME NULL,
    UNIQUE KEY uq_posts_slug (slug),
    KEY idx_posts_status (status, published_at),
    CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tickets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(20) NOT NULL COMMENT 'TKT-1403-00210',
    user_id    INT UNSIGNED NOT NULL,
    subject    VARCHAR(200) NOT NULL,
    body       TEXT NOT NULL,
    category   ENUM('order','product','payment','complaint','other') NOT NULL DEFAULT 'other',
    priority   ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    status     ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
    order_code VARCHAR(20) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_tickets_code (code),
    KEY idx_tickets_user (user_id),
    KEY idx_tickets_status (status),
    CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_tickets_code CHECK (code REGEXP '^TKT-[0-9]{4}-[0-9]{5}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_replies (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id  INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    body       TEXT NOT NULL,
    is_staff   TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    KEY idx_replies_ticket (ticket_id),
    CONSTRAINT fk_replies_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_replies_user   FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(160) NOT NULL,
    body       VARCHAR(400) NULL,
    link       VARCHAR(255) NULL,
    kind       ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    KEY idx_notif_user (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE meetings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(16) NOT NULL COMMENT 'MT-1403-04',
    title       VARCHAR(200) NOT NULL,
    meeting_date DATE NOT NULL,
    location    VARCHAR(160) NULL,
    agenda      TEXT NULL,
    minutes     MEDIUMTEXT NULL COMMENT 'صورت‌جلسه',
    status      ENUM('scheduled','held','cancelled') NOT NULL DEFAULT 'scheduled',
    created_by  INT UNSIGNED NULL,
    created_at  DATETIME NULL,
    updated_at  DATETIME NULL,
    UNIQUE KEY uq_meetings_code (code),
    CONSTRAINT chk_meetings_code CHECK (code REGEXP '^MT-[0-9]{4}-[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE resolutions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL COMMENT 'RES-1403-04-02',
    meeting_id  INT UNSIGNED NOT NULL,
    sequence    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    title       VARCHAR(200) NOT NULL,
    body        TEXT NULL,
    status      ENUM('approved','pending','rejected') NOT NULL DEFAULT 'approved',
    created_at  DATETIME NULL,
    UNIQUE KEY uq_resolutions_code (code),
    CONSTRAINT fk_res_meeting FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    CONSTRAINT chk_resolutions_code CHECK (code REGEXP '^RES-[0-9]{4}-[0-9]{2}-[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE votes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(16) NOT NULL COMMENT 'VOT-1403-001',
    title       VARCHAR(200) NOT NULL,
    description TEXT NULL,
    options     JSON NOT NULL COMMENT '["گزینه الف","گزینه ب"]',
    starts_at   DATETIME NOT NULL,
    ends_at     DATETIME NOT NULL,
    status      ENUM('draft','open','closed') NOT NULL DEFAULT 'draft',
    created_at  DATETIME NULL,
    UNIQUE KEY uq_votes_code (code),
    CONSTRAINT chk_votes_code CHECK (code REGEXP '^VOT-[0-9]{4}-[0-9]{3}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vote_ballots (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vote_id  INT UNSIGNED NOT NULL,
    user_id  INT UNSIGNED NOT NULL,
    choice   SMALLINT UNSIGNED NOT NULL COMMENT 'ایندکس گزینه (از ۰)',
    voted_at DATETIME NOT NULL,
    UNIQUE KEY uq_ballot (vote_id, user_id),
    CONSTRAINT fk_ballot_vote FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ballot_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE internal_letters (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(16) NOT NULL COMMENT 'IR-1403-0105',
    from_user_id INT UNSIGNED NULL,
    to_user_id  INT UNSIGNED NULL,
    subject     VARCHAR(200) NOT NULL,
    body        TEXT NOT NULL,
    kind        ENUM('letter','memo','notice') NOT NULL DEFAULT 'letter',
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    sent_at     DATETIME NOT NULL,
    created_at  DATETIME NULL,
    UNIQUE KEY uq_letters_code (code),
    KEY idx_letters_to (to_user_id, is_read),
    CONSTRAINT chk_letters_code CHECK (code REGEXP '^IR-[0-9]{4}-[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    action     VARCHAR(120) NOT NULL,
    entity     VARCHAR(60) NULL,
    entity_id  INT UNSIGNED NULL,
    ip         VARCHAR(45) NULL,
    created_at DATETIME NOT NULL,
    KEY idx_logs_user (user_id),
    KEY idx_logs_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    `key`   VARCHAR(80) NOT NULL PRIMARY KEY,
    `value` TEXT NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
