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
