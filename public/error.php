<?php
require_once __DIR__ . '/../app/helpers.php';
/**
 * CityGuard – Hibaoldal
 * Használat: error.php?code=500&msg=Leírás
 * Vagy: error.php?code=404
 */
$code    = (int)($_GET['code'] ?? 500);
$msgRaw  = trim($_GET['msg'] ?? '');
$from    = trim($_GET['from'] ?? '');

// Csak engedélyezett kódok
if (!in_array($code, [400, 401, 403, 404, 500, 503], true)) {
    $code = 500;
}

http_response_code($code);

$titles = [
    400 => 'Hibás kérés',
    401 => 'Bejelentkezés szükséges',
    403 => 'Hozzáférés megtagadva',
    404 => 'Az oldal nem található',
    500 => 'Szerverhiba',
    503 => 'A szerver átmenetileg nem elérhető',
];
$icons = [
    400 => '⚠️', 401 => '🔒', 403 => '🚫',
    404 => '🔍', 500 => '🔥', 503 => '🛠️',
];
$hints = [
    400 => 'A küldött adat érvénytelen vagy hiányos.',
    401 => 'Ez az oldal csak bejelentkezés után érhető el.',
    403 => 'Nincs jogosultságod ehhez a művelethez.',
    404 => 'A keresett oldal nem létezik, vagy áthelyezték.',
    500 => 'Belső szerverhiba történt. Ha ez ismétlődik, nézd meg a <code>storage/logs/app.log</code> fájlt.',
    503 => 'A szerver karbantartás alatt áll. Próbáld újra néhány perc múlva.',
];

$title   = $titles[$code]  ?? 'Ismeretlen hiba';
$icon    = $icons[$code]   ?? '❓';
$hint    = $hints[$code]   ?? '';
$safeMsg = $msgRaw ? htmlspecialchars($msgRaw, ENT_QUOTES, 'UTF-8') : '';
$errorPath  = parse_url(error_page_url($code), PHP_URL_PATH) ?: '/public/error.php';
$publicBase = rtrim(str_replace('\\', '/', dirname($errorPath)), '/');
$homeUrl    = ($publicBase ?: '') . '/index.php';
$favicon32  = ($publicBase ?: '') . '/assets/icons/favicon-32.png';
$favicon16  = ($publicBase ?: '') . '/assets/icons/favicon-16.png';
$faviconIco = ($publicBase ?: '') . '/favicon.ico';
?><!doctype html>
<html lang="hu">
<head>
