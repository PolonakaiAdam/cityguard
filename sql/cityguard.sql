-- ============================================================
-- CityGuard – Teljes adatbázis séma
-- phpMyAdmin-ban: Importálás → ezt a fájlt választod → Végrehajtás
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET NAMES utf8mb4;

-- Adatbázis létrehozása (ha még nincs)
CREATE DATABASE IF NOT EXISTS `cityguard`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;
USE `cityguard`;

-- ============================================================
-- TÁBLÁK
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(80)  NOT NULL,
    `email`         VARCHAR(120) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('admin','staff','citizen','municipality') NOT NULL DEFAULT 'citizen',
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `categories` (
    `id`   INT(11)     NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(60) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `reports` (
    `id`             INT(11)       NOT NULL AUTO_INCREMENT,
    `user_id`        INT(11)       NOT NULL,
    `category_id`    INT(11)       NOT NULL,
    `title`          VARCHAR(120)  NOT NULL,
    `description`    TEXT          NOT NULL,
    `address`        VARCHAR(200)  NOT NULL,
    `latitude`       DECIMAL(10,7) DEFAULT NULL,
    `longitude`      DECIMAL(10,7) DEFAULT NULL,
    `status`         ENUM('new','in_progress','resolved','rejected') NOT NULL DEFAULT 'new',
    `assigned_to`    INT(11)       DEFAULT NULL,
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `evidence_image` VARCHAR(255)  DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id`     (`user_id`),
    KEY `category_id` (`category_id`),
    KEY `assigned_to` (`assigned_to`),
    KEY `latlng`      (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `report_comments` (
    `id`         INT(11)   NOT NULL AUTO_INCREMENT,
    `report_id`  INT(11)   NOT NULL,
    `user_id`    INT(11)   NOT NULL,
    `comment`    TEXT      NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `report_id` (`report_id`),
    KEY `user_id`   (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_messages` (
    `id`          INT(11)  NOT NULL AUTO_INCREMENT,
    `user_id`     INT(11)  NOT NULL,
    `type`        ENUM('message','email_change','password_reset') NOT NULL DEFAULT 'message',
    `message`     TEXT     NOT NULL DEFAULT '',
    `new_email`   VARCHAR(255) DEFAULT NULL,
    `status`      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_reply` TEXT     DEFAULT NULL,
    `replied_at`  DATETIME DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `email_change_requests` (
    `id`           INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`      INT(11)      NOT NULL,
    `new_email`    VARCHAR(255) NOT NULL,
    `new_password` VARCHAR(255) DEFAULT NULL,
    `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `resolved_at`  DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT(11)     NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)     NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME    NOT NULL,
    `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token_hash` (`token_hash`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `email_change_tokens` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)      NOT NULL,
    `token_hash` VARCHAR(64)  NOT NULL,
    `new_email`  VARCHAR(255) NOT NULL,
    `expires_at` DATETIME     NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token_hash` (`token_hash`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- IDEGEN KULCSOK
-- ============================================================

ALTER TABLE `reports`
    ADD CONSTRAINT `fk_reports_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_reports_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
    ADD CONSTRAINT `fk_reports_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `report_comments`
    ADD CONSTRAINT `fk_comments_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_comments_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE;

ALTER TABLE `user_messages`
    ADD CONSTRAINT `fk_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `email_change_requests`
    ADD CONSTRAINT `fk_ecr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `password_resets`
    ADD CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `email_change_tokens`
    ADD CONSTRAINT `fk_ect_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- ============================================================
-- ALAPADATOK
-- Jelszavak: user → "user12345" | admin/onkormanyzat/ugyintezo → "admin"
-- ============================================================

INSERT IGNORE INTO `categories` (`name`) VALUES
    ('Kátyú'),
    ('Közvilágítás'),
    ('Szemét'),
    ('Rongálás'),
    ('Közlekedés'),
    ('Egyéb');

INSERT IGNORE INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES
    ('user',         'user',         '$2y$12$icK0fwFA7SBwsIMlRAbg2uGIerfzXjGVtweLCB81.MEHARY6Mv9e2', 'citizen'),
    ('admin',        'admin',        '$2y$12$D4Wk3LvoFPgHX/z8w2sIl.7Sas2mhCoIbWD.qyARFHf7DyaWvNFIy', 'admin'),
    ('Önkormányzat', 'onkormanyzat', '$2y$12$D4Wk3LvoFPgHX/z8w2sIl.7Sas2mhCoIbWD.qyARFHf7DyaWvNFIy', 'municipality'),
    ('Ügyintéző',    'ugyintezo',    '$2y$12$D4Wk3LvoFPgHX/z8w2sIl.7Sas2mhCoIbWD.qyARFHf7DyaWvNFIy', 'staff');

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
-- ============================================================
-- Kész! Belépési adatok:
--   user        / user12345   (állampolgár)
--   admin       / admin       (adminisztrátor)
--   onkormanyzat/ admin       (önkormányzat)
--   ugyintezo   / admin       (ügyintéző)
-- ============================================================
