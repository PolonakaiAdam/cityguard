<?php
// auth.php – Bejelentkezés és jogosultság ellenőrzés
//
// Egyszerűen:
// - start_session()   → elindítja a sessiont
// - require_login()   → csak belépett user mehet tovább
// - require_role([...]) → csak bizonyos szerepkörök mehetnek tovább

require_once __DIR__ . '/helpers.php';

// Session indítása (HTTPS/ngrok kompatibilis cookie beállításokkal)
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $cfg = app_config();
    session_name($cfg['session_name']);

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0])) === 'https';

    session_set_cookie_params([
        'path'     => '/',
        'httponly' => true,
        'samesite' => $isHttps ? 'None' : 'Lax',
        'secure'   => $isHttps,
    ]);
    session_start();
}

// Megköveteli a bejelentkezést – ha nincs session, 401-es hibát küld
function require_login(): array {
    start_session();
    if (!isset($_SESSION['user'])) {
        json_response(['error' => 'Unauthorized'], 401);
    }
    return $_SESSION['user'];
}

// Megköveteli a megadott szerepkört (pl. ['admin', 'staff']) – ha nincs, 403-as hibát küld
function require_role(array $roles): array {
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        json_response(['error' => 'Forbidden'], 403);
    }
    return $user;
}
