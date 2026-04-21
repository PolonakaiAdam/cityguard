<?php
// Kijelentkezés: session törlés
// POST → {ok: true}
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/auth.php';

start_session();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'] ?? false, $p['httponly'] ?? true);
}
session_destroy();
json_response(['ok' => true]);
