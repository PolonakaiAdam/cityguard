<?php
// db.php – Adatbázis kapcsolat
// Egyetlen belépési pont: db() – visszaadja a PDO kapcsolatot.
// Első híváskor automatikusan létrehozza az adatbázist és a táblákat.

require_once __DIR__ . '/helpers.php';

// PDO kapcsolat létrehozása egy adott host-ra
function db_connect(array $db, bool $withDatabase = true): PDO {
    $host    = trim((string)($db['host'] ?? 'localhost')) ?: 'localhost';
    $charset = $db['charset'] ?? 'utf8mb4';
    $dsn     = $withDatabase
        ? "mysql:host={$host};dbname={$db['name']};charset={$charset}"
        : "mysql:host={$host};charset={$charset}";

    // Próbálja localhost-ot és 127.0.0.1-et is (XAMPP kompatibilitás)
    $hosts = array_unique([$host, $host === 'localhost' ? '127.0.0.1' : 'localhost']);
    $lastError = null;

    foreach ($hosts as $tryHost) {
        $tryDsn = str_replace("host={$host}", "host={$tryHost}", $dsn);
        try {
            return new PDO($tryDsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (Throwable $e) {
            $lastError = $e;
            app_log('DB kapcsolat sikertelen (' . $tryHost . '): ' . $e->getMessage());
        }
    }

    throw $lastError ?? new RuntimeException('Ismeretlen adatbázis hiba.');
}

// Létrehozza az összes táblát ha még nem léteznek (biztonságos, nem töröl semmit)
function db_create_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id   INT(11)     NOT NULL AUTO_INCREMENT,
        name VARCHAR(60) NOT NULL,
        PRIMARY KEY (id), UNIQUE KEY name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id            INT(11)      NOT NULL AUTO_INCREMENT,
        name          VARCHAR(80)  NOT NULL,
        email         VARCHAR(120) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role          ENUM('admin','staff','citizen','municipality') NOT NULL DEFAULT 'citizen',
        profile_image VARCHAR(255) DEFAULT NULL,
        created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id             INT(11)       NOT NULL AUTO_INCREMENT,
        user_id        INT(11)       NOT NULL,
        category_id    INT(11)       NOT NULL,
        title          VARCHAR(120)  NOT NULL,
        description    TEXT          NOT NULL,
        address        VARCHAR(200)  NOT NULL,
        latitude       DECIMAL(10,7) DEFAULT NULL,
        longitude      DECIMAL(10,7) DEFAULT NULL,
        status         ENUM('new','in_progress','resolved','rejected') NOT NULL DEFAULT 'new',
        assigned_to    INT(11)       DEFAULT NULL,
        created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at     TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        evidence_image VARCHAR(255)  DEFAULT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id), KEY category_id (category_id), KEY assigned_to (assigned_to)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS report_comments (
        id         INT(11)   NOT NULL AUTO_INCREMENT,
        report_id  INT(11)   NOT NULL,
        user_id    INT(11)   NOT NULL,
        comment    TEXT      NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), KEY report_id (report_id), KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_messages (
        id          INT(11)  NOT NULL AUTO_INCREMENT,
        user_id     INT(11)  NOT NULL,
        type        ENUM('message','email_change','password_reset') NOT NULL DEFAULT 'message',
        message     TEXT     NOT NULL DEFAULT '',
        new_email   VARCHAR(255) DEFAULT NULL,
        status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        admin_reply TEXT     DEFAULT NULL,
        replied_at  DATETIME DEFAULT NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        resolved_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id), KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS email_change_requests (
        id           INT(11)      NOT NULL AUTO_INCREMENT,
        user_id      INT(11)      NOT NULL,
        new_email    VARCHAR(255) NOT NULL,
        new_password VARCHAR(255) DEFAULT NULL,
        status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        resolved_at  DATETIME DEFAULT NULL,
        PRIMARY KEY (id), KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id         INT(11)     NOT NULL AUTO_INCREMENT,
        user_id    INT(11)     NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expires_at DATETIME    NOT NULL,
        created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY token_hash (token_hash), KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS email_change_tokens (
        id         INT(11)      NOT NULL AUTO_INCREMENT,
        user_id    INT(11)      NOT NULL,
        token_hash VARCHAR(64)  NOT NULL,
        new_email  VARCHAR(255) NOT NULL,
        expires_at DATETIME     NOT NULL,
        created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY token_hash (token_hash), KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Idegen kulcsok hozzáadása (csak ha még nem léteznek)
    $existing = array_column(
        $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->fetchAll(),
        'CONSTRAINT_NAME'
    );
    $fks = [
        'fk_reports_user'     => "ALTER TABLE reports ADD CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",
        'fk_reports_category' => "ALTER TABLE reports ADD CONSTRAINT fk_reports_category FOREIGN KEY (category_id) REFERENCES categories (id)",
        'fk_reports_assigned' => "ALTER TABLE reports ADD CONSTRAINT fk_reports_assigned FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL",
        'fk_comments_report'  => "ALTER TABLE report_comments ADD CONSTRAINT fk_comments_report FOREIGN KEY (report_id) REFERENCES reports (id) ON DELETE CASCADE",
        'fk_comments_user'    => "ALTER TABLE report_comments ADD CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",
        'fk_messages_user'    => "ALTER TABLE user_messages ADD CONSTRAINT fk_messages_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",
        'fk_ecr_user'         => "ALTER TABLE email_change_requests ADD CONSTRAINT fk_ecr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",
        'fk_pr_user'          => "ALTER TABLE password_resets ADD CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",
        'fk_ect_user'         => "ALTER TABLE email_change_tokens ADD CONSTRAINT fk_ect_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",
    ];
    foreach ($fks as $name => $sql) {
        if (!in_array($name, $existing, true)) {
            try { $pdo->exec($sql); } catch (Throwable) { /* már létezik */ }
        }
    }

    db_seed_defaults($pdo);
}

// Alapadatok feltöltése / javítása (kategóriák és teszt felhasználók)
function db_seed_defaults(PDO $pdo): void {
    $categoryNames = ['Kátyú', 'Közvilágítás', 'Szemét', 'Rongálás', 'Közlekedés', 'Egyéb'];
    $checkCategory = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
    $insertCategory = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
    foreach ($categoryNames as $categoryName) {
        $checkCategory->execute([$categoryName]);
        if (!$checkCategory->fetch()) {
            $insertCategory->execute([$categoryName]);
        }
    }

    $defaults = [
        ['user',         'user',         password_hash('user12345', PASSWORD_DEFAULT), 'citizen'],
        ['admin',        'admin',        password_hash('admin',     PASSWORD_DEFAULT), 'admin'],
        ['Önkormányzat', 'onkormanyzat', password_hash('admin',     PASSWORD_DEFAULT), 'municipality'],
        ['Ügyintéző',    'ugyintezo',    password_hash('admin',     PASSWORD_DEFAULT), 'staff'],
    ];
    $check  = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)');
    foreach ($defaults as $row) {
        $check->execute([$row[1]]);
        if (!$check->fetch()) $insert->execute($row);
    }
}

// Javítja a korábbi hibásan seedelt alapfiókok jelszó hash-ét.
// Csak akkor nyúl hozzájuk, ha pontosan a hibás, régi hash van eltárolva,
// így nem írja felül a kézzel módosított jelszavakat.
function db_has_column(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

function db_apply_schema_updates(PDO $pdo): void {
    if (!db_has_column($pdo, 'users', 'profile_image')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER role");
    }
}

function db_repair_broken_seeded_users(PDO $pdo): void {
    $brokenUserHash = '$2y$10$AYfmjhO91QJRKWihkIu/B.7ur5ccwmjBzpygCous7gXqErmwIIaB.';
    $brokenAdminHash = '$2y$10$wkrPgXkotw125oN6mN5uuO35LAqeBvQmoXT6WiHXqaHSuRyf.pS6W';

    $repairs = [
        ['email' => 'user',        'expected_hash' => $brokenUserHash,  'new_password' => 'user12345', 'role' => 'citizen',      'name' => 'user'],
        ['email' => 'admin',       'expected_hash' => $brokenAdminHash, 'new_password' => 'admin',     'role' => 'admin',        'name' => 'admin'],
        ['email' => 'onkormanyzat','expected_hash' => $brokenAdminHash, 'new_password' => 'admin',     'role' => 'municipality', 'name' => 'Önkormányzat'],
        ['email' => 'ugyintezo',   'expected_hash' => $brokenAdminHash, 'new_password' => 'admin',     'role' => 'staff',        'name' => 'Ügyintéző'],
    ];

    $select = $pdo->prepare('SELECT id, password_hash, role, name FROM users WHERE email = ? LIMIT 1');
    $update = $pdo->prepare('UPDATE users SET password_hash = ?, role = ?, name = ? WHERE id = ?');

    foreach ($repairs as $row) {
        $select->execute([$row['email']]);
        $user = $select->fetch();
        if (!$user) {
            continue;
        }
        if ((string)$user['password_hash'] !== $row['expected_hash']) {
            continue;
        }
        $update->execute([
            password_hash($row['new_password'], PASSWORD_DEFAULT),
            $row['role'],
            $row['name'],
            (int)$user['id'],
        ]);
    }
}

// A fő adatbázis kapcsolat (singleton – csak egyszer csatlakozik)
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $db = app_config()['db'];

    try {
        $pdo = db_connect($db, true);
    } catch (Throwable $e) {
        if (!str_contains(strtolower($e->getMessage()), 'unknown database')) throw $e;
        // Adatbázis nem létezik – létrehozzuk
        app_log('Adatbázis hiányzik, létrehozás: ' . $db['name']);
        $server = db_connect($db, false);
        $safe   = str_replace('`', '``', $db['name']);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$safe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo = db_connect($db, true);
    }

    if (!(bool)$pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn()) {
        db_create_tables($pdo);
    }

    db_apply_schema_updates($pdo);
    db_repair_broken_seeded_users($pdo);

    return $pdo;
}
