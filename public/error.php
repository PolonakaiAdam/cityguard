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
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($favicon32, ENT_QUOTES, "UTF-8") ?>" />
  <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($favicon16, ENT_QUOTES, "UTF-8") ?>" />
  <link rel="shortcut icon" href="<?= htmlspecialchars($faviconIco, ENT_QUOTES, "UTF-8") ?>" />
  <title>CityGuard – <?= $code . ' ' . htmlspecialchars($title) ?></title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,sans-serif;background:#060c1a;color:#e8edf8;
         min-height:100vh;display:flex;flex-direction:column;align-items:center;
         justify-content:center;padding:24px;text-align:center}
    .card{background:#0f1929;border:1px solid #1e293b;border-radius:20px;
          padding:40px 32px 32px;max-width:500px;width:100%;
          box-shadow:0 24px 64px rgba(0,0,0,.5)}
    .icon{font-size:3.5rem;margin-bottom:16px;line-height:1}
    .code{font-family:'DM Mono',monospace;font-size:.8rem;font-weight:700;
          color:#38bdf8;background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.25);
          border-radius:6px;padding:3px 10px;display:inline-block;margin-bottom:14px}
    h1{font-size:1.4rem;font-weight:800;color:#e8edf8;margin-bottom:10px}
    .hint{font-size:.88rem;color:#8da0c0;line-height:1.6;margin-bottom:8px}
    .hint code{background:#1e293b;padding:2px 6px;border-radius:4px;font-size:.82rem;color:#38bdf8}
    .detail{margin-top:14px;padding:12px 16px;background:rgba(244,63,94,.08);
            border:1px solid rgba(244,63,94,.25);border-radius:10px;
            font-size:.82rem;color:#f87171;text-align:left;word-break:break-word;line-height:1.5}
    .actions{display:flex;gap:10px;justify-content:center;margin-top:24px;flex-wrap:wrap}
    .btn{display:inline-block;padding:10px 22px;border-radius:10px;font-size:.88rem;
         font-weight:700;cursor:pointer;text-decoration:none;border:none;font-family:inherit}
    .btn-primary{background:#1d4ed8;color:#fff}
    .btn-primary:hover{background:#1e40af}
    .btn-soft{background:rgba(255,255,255,.07);color:#8da0c0;border:1px solid rgba(148,183,255,.18)}
    .btn-soft:hover{background:rgba(255,255,255,.12)}
    .brand{margin-top:32px;font-size:.78rem;color:#334155}
  </style>
</head>
<body>
  <div class="card">
    <div class="icon"><?= $icon ?></div>
    <div class="code"><?= $code ?></div>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p class="hint"><?= $hint ?></p>
    <?php if ($safeMsg): ?>
    <div class="detail">
      <strong>Részletek:</strong><br><?= $safeMsg ?>
    </div>
    <?php endif; ?>
    <div class="actions">
      <a href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, "UTF-8") ?>" class="btn btn-primary">← Vissza a főoldalra</a>
      <?php if ($from): ?>
      <a href="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-soft">↩ Előző oldal</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="brand">CityGuard Rendszer</div>
</body>
</html>

