<?php
// ============================================================
// CityGuard – install.php
// Adatbázis telepítő: létrehozza a táblákat és feltölti az alapadatokat.
// FONTOS: Telepítés után töröld ezt a fájlt!
// ============================================================

define('INSTALL_VERSION', '2.0');

$steps   = [];
$success = true;
$done    = false;

// ── Segédfüggvények ──────────────────────────────────────────

function run_step(string $title, callable $fn): void {
    global $steps, $success;
    try {
        $msg = $fn();
        $ok  = true;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        $ok  = false;
    }
    if (!$ok) $success = false;
    $steps[] = ['title' => $title, 'ok' => $ok, 'msg' => $msg ?? ''];
}

function make_pdo(string $host, string $user, string $pass, string $db = ''): PDO {
    $dsn = "mysql:host={$host};charset=utf8mb4" . ($db ? ";dbname={$db}" : '');
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

// ── Feldolgozás (POST) ───────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host          = trim($_POST['host']   ?? 'localhost');
    $user          = trim($_POST['user']   ?? 'root');
    $pass          =       $_POST['pass']  ?? '';
    $dbname        = trim($_POST['dbname'] ?? 'cityguard');
    $update_config = !empty($_POST['update_config']);

    $pdo = null;

    // 1. Kapcsolat
    run_step('MySQL kapcsolat tesztelése', function () use ($host, $user, $pass, &$pdo) {
        $pdo = make_pdo($host, $user, $pass);
        return 'OK – MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn();
    });

    // 2. Adatbázis
    run_step("Adatbázis létrehozása: `{$dbname}`", function () use (&$pdo, $host, $user, $pass, $dbname) {
        if (!$pdo) throw new RuntimeException('Előző lépés sikertelen.');
        $safe = str_replace('`', '``', $dbname);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo = make_pdo($host, $user, $pass, $dbname);
        return 'Adatbázis kész';
    });

    // 3–9. Táblák
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id`            INT(11)      NOT NULL AUTO_INCREMENT,
            `name`          VARCHAR(80)  NOT NULL,
            `email`         VARCHAR(120) NOT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `role`          ENUM('admin','staff','citizen','municipality') NOT NULL DEFAULT 'citizen',
            `profile_image` VARCHAR(255) DEFAULT NULL,
            `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'categories' => "CREATE TABLE IF NOT EXISTS `categories` (
            `id`   INT(11)     NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(60) NOT NULL,
            PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'reports' => "CREATE TABLE IF NOT EXISTS `reports` (
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
            KEY `user_id` (`user_id`), KEY `category_id` (`category_id`), KEY `assigned_to` (`assigned_to`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'report_comments' => "CREATE TABLE IF NOT EXISTS `report_comments` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, `report_id` INT(11) NOT NULL,
            `user_id` INT(11) NOT NULL, `comment` TEXT NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), KEY `report_id` (`report_id`), KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'user_messages' => "CREATE TABLE IF NOT EXISTS `user_messages` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, `user_id` INT(11) NOT NULL,
            `type` ENUM('message','email_change','password_reset') NOT NULL DEFAULT 'message',
            `message` TEXT NOT NULL DEFAULT '', `new_email` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `admin_reply` TEXT DEFAULT NULL, `replied_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `resolved_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`), KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'email_change_requests' => "CREATE TABLE IF NOT EXISTS `email_change_requests` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, `user_id` INT(11) NOT NULL,
            `new_email` VARCHAR(255) NOT NULL, `new_password` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `resolved_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`), KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'password_resets' => "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, `user_id` INT(11) NOT NULL,
            `token_hash` VARCHAR(64) NOT NULL, `expires_at` DATETIME NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), UNIQUE KEY `token_hash` (`token_hash`), KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'email_change_tokens' => "CREATE TABLE IF NOT EXISTS `email_change_tokens` (
