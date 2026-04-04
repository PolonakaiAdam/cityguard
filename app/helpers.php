<?php
// helpers.php – Általános segédfüggvények
// Ezeket az egész rendszer használja.
// A cél: a kis ismétlődő feladatok egy helyen legyenek,
// így az API fájlok rövidebbek és könnyebben olvashatók.

// JSON választ küld és leállítja a scriptet
function json_response(mixed $data, int $code = 200): void {
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// POST / fetch kérés törzsét olvassa be (JSON vagy form-encoded)
function read_json(): array {
    $raw = file_get_contents('php://input');
    if ($raw !== false && trim($raw) !== '') {
        $data = json_decode($raw, true);
        if (is_array($data)) return $data;
    }
    return !empty($_POST) ? $_POST : [];
}

// Egy vagy több kulcs közül visszaadja az első szöveges értéket
function input_text(array $data, string|array $keys, string $default = ''): string {
    foreach ((array)$keys as $key) {
        if (array_key_exists($key, $data)) {
            return trim((string)$data[$key]);
        }
    }
    return $default;
}

// Egész szám kérése a bemenetből
function input_int(array $data, string|array $keys, int $default = 0): int {
    foreach ((array)$keys as $key) {
        if (array_key_exists($key, $data)) {
            return (int)$data[$key];
        }
    }
    return $default;
}

// Lebegőpontos szám vagy null visszaadása
function input_float_or_null(array $data, string|array $keys): ?float {
    foreach ((array)$keys as $key) {
        if (array_key_exists($key, $data) && $data[$key] !== '' && $data[$key] !== null) {
            return (float)$data[$key];
        }
    }
    return null;
}

// Email cím ellenőrzése
function is_valid_email_address(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Adatbázis duplicate / unique kulcs hiba felismerése
function is_duplicate_error(Throwable $e): bool {
    $message = strtolower($e->getMessage());
    return str_contains($message, 'duplicate') || str_contains($message, 'unique');
}

// Könyvtárat hoz létre ha még nem létezik
function ensure_dir(string $dir): void {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// Logbejegyzést ír a storage/logs/app.log fájlba
function app_log(string $message): void {
    $dir = __DIR__ . '/../storage/logs';
    ensure_dir($dir);
    @file_put_contents(
        $dir . '/app.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

// Kivételből felhasználóbarát hibaüzenetet gyárt
function public_error(Throwable $e): string {
    $msg   = trim($e->getMessage());
    $lower = strtolower($msg);
    if ($msg === '')                                              return 'Ismeretlen szerverhiba történt.';
    if (str_contains($lower, 'unknown database'))                return 'Az adatbázis hiányzik. Futtasd az install.php-t.';
    if (str_contains($lower, 'access denied'))                   return 'Hibás adatbázis felhasználó vagy jelszó (config/config.php).';
    if (str_contains($lower, 'could not find driver'))           return 'A pdo_mysql PHP kiterjesztés nincs bekapcsolva.';
    if (str_contains($lower, 'table') && str_contains($lower, "doesn't exist")) return 'Hiányzó adatbázis tábla – futtasd az install.php-t.';
    return $msg;
}

// Konfiguráció betöltése (csak egyszer)
function app_config(): array {
    static $cfg = null;
    if ($cfg === null) $cfg = require __DIR__ . '/../config/config.php';
    return $cfg;
}

// Az alkalmazás alap URL-jét állapítja meg automatikusan (ngrok/localhost/éles)
function base_url(): string {
    static $cached = null;
    if ($cached !== null) return $cached;

    $cfg      = app_config();
    $fallback = rtrim(preg_replace('#/public/?$#', '', $cfg['app_url'] ?? ''), '/');

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return $cached = ($fallback ?: 'http://localhost/cityguard');
    }

    $proto    = 'http';
    $fwdProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    if ($fwdProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) {
        $proto = 'https';
    }

    $script = rtrim(preg_replace('#/(public|api)(/.*)?$#', '', $_SERVER['SCRIPT_NAME'] ?? ''), '/');
    return $cached = $proto . '://' . $host . $script;
}

// A public/ mappa URL-je (pl. emailekben lévő linkekhez)
function public_url(string $path = ''): string {
    return rtrim(base_url(), '/') . '/public/' . ltrim($path, '/');
}

// A public/ mappán belüli valódi fájlrendszer útvonal
function public_path(string $path = ''): string {
    return __DIR__ . '/../public/' . ltrim($path, '/');
}

// Verziószám statikus fájlhoz (cache töréshez)
function public_asset_version(string $path): int {
    return @filemtime(public_path($path)) ?: time();
}

// Régi nevek átirányítása visszafelé kompatibilitáshoz
function cg_log(string $msg): void { app_log($msg); }
function cg_detect_base_url(): string { return base_url(); }
function cg_public_url(string $path = ''): string { return public_url($path); }
function cg_ensure_dir(string $dir): void { ensure_dir($dir); }
function cg_public_error(Throwable $e): string { return public_error($e); }

// Az api/ mappa URL-je (pl. JS-ből fetch hívásokhoz)
function cg_api_url(): string {
    return rtrim(base_url(), '/') . '/api/';
}


// Aktuális kérés útvonala (pl. /cityguard/public/index.php)
function request_path(): string {
    return (string)($_SERVER['REQUEST_URI'] ?? '');
}

