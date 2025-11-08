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

