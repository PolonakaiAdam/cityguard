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
            `id` INT(11) NOT NULL AUTO_INCREMENT, `user_id` INT(11) NOT NULL,
            `token_hash` VARCHAR(64) NOT NULL, `new_email` VARCHAR(255) NOT NULL,
            `expires_at` DATETIME NOT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), UNIQUE KEY `token_hash` (`token_hash`), KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $name => $sql) {
        run_step("Tábla: `{$name}`", function () use (&$pdo, $sql, $name) {
            if (!$pdo) throw new RuntimeException('Nincs DB kapcsolat.');
            $pdo->exec($sql);
            return (bool)$pdo->query("SHOW TABLES LIKE '{$name}'")->fetchColumn() ? 'OK' : throw new RuntimeException('Tábla nem jött létre.');
        });
    }

    run_step('Felhasználó profilkép oszlop ellenőrzése', function () use (&$pdo) {
        if (!$pdo) throw new RuntimeException('Nincs DB kapcsolat.');
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER role");
            return 'profile_image oszlop hozzáadva';
        }
        return 'profile_image oszlop már létezik';
    });

    // Seed: kategóriák
    run_step('Kategóriák feltöltése / javítása', function () use (&$pdo) {
        if (!$pdo) throw new RuntimeException('Nincs DB kapcsolat.');
        $categoryNames = ['Kátyú','Közvilágítás','Szemét','Rongálás','Közlekedés','Egyéb'];
        $check = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $insert = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
        $added = 0;
        foreach ($categoryNames as $name) {
            $check->execute([$name]);
            if (!$check->fetch()) {
                $insert->execute([$name]);
                $added++;
            }
        }
        return $added > 0 ? "{$added} kategória hozzáadva / javítva" : 'Minden alap kategória már létezik';
    });

    // Seed: felhasználók
    run_step('Teszt felhasználók létrehozása / javítása', function () use (&$pdo) {
        if (!$pdo) throw new RuntimeException('Nincs DB kapcsolat.');
        $defaults = [
            ['user',         'user',         password_hash('user12345', PASSWORD_DEFAULT), 'citizen'],
            ['admin',        'admin',        password_hash('admin',     PASSWORD_DEFAULT), 'admin'],
            ['Önkormányzat', 'onkormanyzat', password_hash('admin',     PASSWORD_DEFAULT), 'municipality'],
            ['Ügyintéző',    'ugyintezo',    password_hash('admin',     PASSWORD_DEFAULT), 'staff'],
        ];
        $brokenUserHash  = '$2y$10$AYfmjhO91QJRKWihkIu/B.7ur5ccwmjBzpygCous7gXqErmwIIaB.';
        $brokenAdminHash = '$2y$10$wkrPgXkotw125oN6mN5uuO35LAqeBvQmoXT6WiHXqaHSuRyf.pS6W';

        $check  = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)');
        $update = $pdo->prepare('UPDATE users SET name = ?, password_hash = ?, role = ? WHERE id = ?');

        $added = 0; $repaired = 0; $skipped = 0;
        foreach ($defaults as $row) {
            $check->execute([$row[1]]);
            $existing = $check->fetch();
            if (!$existing) {
                $insert->execute($row);
                $added++;
                continue;
            }

            $expectedBrokenHash = $row[1] === 'user' ? $brokenUserHash : $brokenAdminHash;
            if ((string)$existing['password_hash'] === $expectedBrokenHash) {
                $update->execute([$row[0], $row[2], $row[3], (int)$existing['id']]);
                $repaired++;
                continue;
            }

            $skipped++;
        }
        return "{$added} létrehozva, {$repaired} javítva, {$skipped} változatlan";
    });

    // Opcionális: config.php frissítése
    if ($update_config) {
        run_step('config/config.php frissítése', function () use ($host, $user, $pass, $dbname) {
            $path = __DIR__ . '/config/config.php';
            if (!is_file($path)) return 'Nem található – kihagyva';
            $content = file_get_contents($path);
            $content = preg_replace("/'host'\s*=>\s*'[^']*'/",  "'host'    => '{$host}'",   $content);
            $content = preg_replace("/'name'\s*=>\s*'[^']*'/",  "'name'    => '{$dbname}'", $content);
            $content = preg_replace("/'user'\s*=>\s*'[^']*'/",  "'user'    => '{$user}'",   $content);
            $content = preg_replace("/'pass'\s*=>\s*'[^']*'/",  "'pass'    => '{$pass}'",   $content);
            file_put_contents($path, $content);
            return 'config.php frissítve';
        });
    }

    $done = true;
}
?>
<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CityGuard Telepítő</title>
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    :root {
        --bg: #0f172a;
        --card: #1e293b;
        --border: #334155;
        --accent: #38bdf8;
        --green: #22c55e;
        --red: #f87171;
        --yellow: #facc15;
        --text: #e2e8f0;
        --muted: #94a3b8;
        --r: 12px;
    }

    body {
        background: var(--bg);
        color: var(--text);
        font-family: 'Segoe UI', system-ui, sans-serif;
        min-height: 100vh;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 40px 16px 60px;
    }

    .wrap {
        width: 100%;
        max-width: 660px;
    }

    .header {
        text-align: center;
        margin-bottom: 28px;
    }

    .logo {
        width: 72px;
        height: 72px;
        margin: 0 auto 10px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(56, 189, 248, .28);
        border: 1px solid rgba(56, 189, 248, .28);
        background: linear-gradient(145deg, #1e8fde 0%, #0c4fa8 50%, #0a3580 100%);
    }

    .logo img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: inherit;
    }

    .subtitle {
        color: var(--muted);
        margin-top: 4px;
        font-size: .9rem;
    }

    .warn {
        background: rgba(250, 204, 21, .1);
        border: 1px solid rgba(250, 204, 21, .3);
        border-radius: var(--r);
        padding: 12px 16px;
        color: var(--yellow);
        font-size: .85rem;
        margin-bottom: 20px;
    }

    .card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--r);
        padding: 24px;
        margin-bottom: 16px;
    }

    .card h2 {
        font-size: .95rem;
        font-weight: 600;
        color: var(--accent);
        margin-bottom: 18px;
    }

    .grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .full {
        grid-column: 1 / -1;
    }

    label {
        display: block;
        font-size: .78rem;
        color: var(--muted);
        margin-bottom: 4px;
        font-weight: 500;
    }

    input[type=text],
    input[type=password] {
        width: 100%;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 9px 12px;
        color: var(--text);
        font-size: .88rem;
        outline: none;
    }

    input:focus {
        border-color: var(--accent);
    }

    .cb-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 4px;
    }

    .cb-row input {
        width: 15px;
        height: 15px;
        accent-color: var(--accent);
    }

    .cb-row label {
        margin: 0;
        font-size: .85rem;
        color: var(--text);
    }

    .btn {
        width: 100%;
        margin-top: 18px;
        padding: 12px;
        background: var(--accent);
        color: #0f172a;
        font-weight: 700;
        font-size: .95rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }

    .btn:hover {
        opacity: .85;
    }

    .btn-sec {
        background: var(--border);
        color: var(--text);
    }

    .steps {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .step {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 9px 12px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .03);
        border: 1px solid var(--border);
    }

    .icon {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: .7rem;
        font-weight: 700;
        margin-top: 1px;
    }

    .ok .icon {
        background: rgba(34, 197, 94, .2);
        color: var(--green);
    }

    .err .icon {
        background: rgba(248, 113, 113, .2);
        color: var(--red);
    }

    .info .icon {
        background: rgba(56, 189, 248, .15);
        color: var(--accent);
    }

    .step-body {
        flex: 1;
    }

    .step-title {
        font-size: .85rem;
        font-weight: 600;
    }

    .ok .step-title {
        color: var(--text);
    }

    .err .step-title {
        color: var(--red);
    }

    .step-msg {
        font-size: .75rem;
        color: var(--muted);
        margin-top: 2px;
        font-family: monospace;
    }

    .err .step-msg {
        color: #fca5a5;
    }

    .banner {
        border-radius: var(--r);
        padding: 14px 18px;
        margin-bottom: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .banner.ok {
        background: rgba(34, 197, 94, .1);
        border: 1px solid rgba(34, 197, 94, .3);
        color: var(--green);
    }

    .banner.err {
        background: rgba(248, 113, 113, .1);
        border: 1px solid rgba(248, 113, 113, .3);
        color: var(--red);
    }

    table.cred {
        width: 100%;
        border-collapse: collapse;
        font-size: .85rem;
    }

    table.cred th {
        text-align: left;
        color: var(--muted);
        padding: 4px 8px 6px 0;
        border-bottom: 1px solid var(--border);
        font-weight: 500;
    }

    table.cred td {
        padding: 7px 8px 7px 0;
    }

    table.cred td:first-child {
        color: var(--muted);
        font-size: .8rem;
        width: 120px;
    }

    code {
        background: rgba(56, 189, 248, .12);
        color: var(--accent);
        border-radius: 4px;
        padding: 1px 6px;
        font-size: .82rem;
    }

    .del-note {
        margin-top: 14px;
        padding: 10px 13px;
        background: rgba(248, 113, 113, .08);
        border: 1px solid rgba(248, 113, 113, .25);
        border-radius: 8px;
        color: var(--red);
        font-size: .8rem;
    }

    .sql-box {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 14px 16px;
        font-size: .78rem;
        color: var(--muted);
        line-height: 1.7;
    }

    .sql-box strong {
        color: var(--accent);
    }

    .footer {
        text-align: center;
        color: var(--muted);
        font-size: .75rem;
        margin-top: 20px;
    }

    .tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 16px;
    }

    .tab {
        flex: 1;
        padding: 10px;
        text-align: center;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        background: var(--bg);
        color: var(--muted);
        text-decoration: none;
    }

    .tab.active {
        background: var(--accent);
        color: #0f172a;
        border-color: var(--accent);
    }
    </style>
</head>

<body>
    <div class="wrap">

        <div class="header">
            <div class="logo"><img src="public/assets/icons/favicon-64.png" alt="Cityguard logó"></div>
            <div class="subtitle">Adatbázis Telepítő </div>
        </div>


        <?php if ($done): ?>

        <div class="banner <?= $success ? 'ok' : 'err' ?>">
            <?= $success ? '✓ Telepítés sikeresen befejezve!' : '✗ Néhány lépés sikertelen – ellenőrizd a hibákat!' ?>
        </div>

        <div class="card">
            <h2>Telepítési lépések</h2>
            <div class="steps">
                <?php foreach ($steps as $s): ?>
                <div class="step <?= $s['ok'] ? 'ok' : 'err' ?>">
                    <div class="icon"><?= $s['ok'] ? '✓' : '✗' ?></div>
                    <div class="step-body">
                        <div class="step-title"><?= htmlspecialchars($s['title']) ?></div>
                        <?php if ($s['msg']): ?><div class="step-msg"><?= htmlspecialchars($s['msg']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="card">
            <h2>Alapértelmezett belépési adatok</h2>
            <table class="cred">
                <thead>
                    <tr>
                        <th>Szerepkör</th>
                        <th>E-mail / felhasználónév</th>
                        <th>Jelszó</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Állampolgár</td>
                        <td><code>user</code></td>
                        <td><code>user12345</code></td>
                    </tr>
                    <tr>
                        <td>Admin</td>
                        <td><code>admin</code></td>
                        <td><code>admin</code></td>
                    </tr>
                    <tr>
                        <td>Önkormányzat</td>
                        <td><code>onkormanyzat</code></td>
                        <td><code>admin</code></td>
                    </tr>
                    <tr>
                        <td>Ügyintéző</td>
                        <td><code>ugyintezo</code></td>
                        <td><code>admin</code></td>
                    </tr>
                </tbody>
            </table>
            <div class="del-note">Változtasd meg a jelszavakat, és töröld az <strong>install.php</strong> fájlt!
            </div>
            <div style="margin-top:18px;text-align:center">
                <p style="color:#94a3b8;font-size:14px;margin-bottom:12px">Az oldal automatikusan átirányít 5 másodperc
                    múlva...</p>
                <a href="<?php echo public_url(); ?>" class="btn"
                    style="display:inline-block;padding:12px 32px;background:#1d4ed8;color:#fff;border-radius:10px;text-decoration:none;font-weight:700">➜
                    Tovább a bejelentkezéshez</a>
            </div>
            <script>
            setTimeout(function() {
                window.location.href = '<?php echo public_url(); ?>';
            }, 5000);
            </script>
        </div>
        <?php endif; ?>

        <form method="get"><button class="btn btn-sec">← Vissza</button></form>

        <?php else: ?>

        <?php $tab = $_GET['tab'] ?? 'auto'; ?>
        <div class="tabs">
            <a href="?tab=auto" class="tab <?= $tab === 'auto'  ? 'active' : '' ?>">🚀 Automatikus telepítés</a>
            <a href="?tab=sql" class="tab <?= $tab === 'sql'   ? 'active' : '' ?>">📋 phpMyAdmin SQL</a>
        </div>

        <?php if ($tab === 'sql'): ?>

        <!-- phpMyAdmin útmutató -->
        <div class="card">
            <h2>Telepítés phpMyAdmin-ban (manuális)</h2>
            <div class="sql-box">
                <strong>1. lépés</strong> – Nyisd meg a phpMyAdmin-t (általában:
                <code>http://localhost/phpmyadmin</code>)<br><br>
                <strong>2. lépés</strong> – Kattints a bal oldalsávon az <code>Új</code> gombra → hozz létre egy
                <code>cityguard</code> nevű adatbázist, karakterkészlet: <code>utf8mb4_general_ci</code><br><br>
                <strong>3. lépés</strong> – Válaszd ki a <code>cityguard</code> adatbázist a bal sávban<br><br>
                <strong>4. lépés</strong> – Kattints az <strong>Importálás</strong> fülre (felső menü)<br><br>
                <strong>5. lépés</strong> – Kattints a <strong>Fájl kiválasztása</strong> gombra → keresd meg és válaszd
                ki ezt a fájlt:<br>
                <code
                    style="display:block;margin:8px 0;padding:8px;background:rgba(0,0,0,.3);border-radius:6px">sql/cityguard.sql</code>
                <strong>6. lépés</strong> – Görgess le az oldal aljára → kattints a <strong>Importálás</strong>
                gombra<br><br>
                <strong>7. lépés</strong> – Ha minden zöld ✓ → kész! Szerkeszd meg a <code>config/config.php</code>
                fájlt a saját adatbázisos adataiddal.
            </div>
        </div>

        <div class="card">
            <h2>Mi kerül az adatbázisba?</h2>
            <div class="steps">
                <?php foreach ([
            ['8 tábla', 'users, categories, reports, report_comments, user_messages, email_change_requests, password_resets, email_change_tokens'],
            ['6 kategória', 'Kátyú, Közvilágítás, Szemét, Rongálás, Közlekedés, Egyéb'],
            ['4 teszt felhasználó', 'user / user12345 · admin / admin · onkormanyzat / admin · ugyintezo / admin'],
          ] as [$t, $m]): ?>
                <div class="step info">
                    <div class="icon">→</div>
                    <div class="step-body">
                        <div class="step-title"><?= $t ?></div>
                        <div class="step-msg"><?= $m ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php else: ?>

        <!-- Automatikus telepítő form -->
        <div class="card">
            <h2>Adatbázis kapcsolati adatok</h2>
            <form method="post">
                <div class="grid">
                    <div>
                        <label>MySQL Host</label>
                        <input type="text" name="host" value="localhost" placeholder="localhost">
                    </div>
                    <div>
                        <label>Adatbázis neve</label>
                        <input type="text" name="dbname" value="cityguard" placeholder="cityguard">
                    </div>
                    <div>
                        <label>Felhasználónév</label>
                        <input type="text" name="user" value="root" placeholder="root">
                    </div>
                    <div>
                        <label>Jelszó</label>
                        <input type="password" name="pass" value="" placeholder="(üres ha nincs)">
                    </div>
                    <div class="full">
                        <div class="cb-row">
                            <input type="checkbox" id="uc" name="update_config" value="1" checked>
                            <label for="uc">config/config.php automatikus frissítése</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn">Telepítés indítása</button>
            </form>
        </div>

        <?php endif; ?>

        <?php endif; ?>

        <div class="footer">CityGuard Installer </div>
    </div>
</body>

</html>