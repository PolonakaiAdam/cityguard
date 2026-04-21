<?php
// bootstrap.php – Alkalmazás indítása
// Ez fut le minden API kérés legelején.
// Beállítja a hibakezelést és az output buffert.

if (defined('CG_BOOTSTRAPPED')) return;
define('CG_BOOTSTRAPPED', true);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Output buffer: megakadályozza, hogy JSON előtt szöveg menjen ki
if (ob_get_level() === 0) ob_start();

require_once __DIR__ . '/helpers.php';

// PHP figyelmeztetéseket kivételekké alakítja
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Elkapott kivételek: logba ír, majd JSON vagy HTML hibaoldalt ad vissza
set_exception_handler(function (Throwable $e): void {
    $safeMessage = public_error($e);
    app_log('HIBA: ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) @ob_end_clean();

    if (expects_json_response()) {
        json_response([
            'error' => $safeMessage,
            'debug' => ['type' => get_class($e), 'file' => basename($e->getFile()), 'line' => $e->getLine()],
        ], 500);
    }

    redirect_to_error_page(500, $safeMessage, request_path());
});

// Végzetes PHP hibák (pl. szintaxishiba, elfogy a memória)
register_shutdown_function(function (): void {
    $err = error_get_last();
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!$err || !in_array($err['type'] ?? 0, $fatal, true)) return;

    $safeMessage = 'Végzetes szerverhiba. Nézd meg a storage/logs/app.log fájlt.';
    app_log('VÉGZETES: ' . ($err['message'] ?? '') . ' @ ' . ($err['file'] ?? '') . ':' . ($err['line'] ?? 0));
    while (ob_get_level() > 0) @ob_end_clean();

    if (expects_json_response()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $safeMessage], JSON_UNESCAPED_UNICODE);
        return;
    }

    redirect_to_error_page(500, $safeMessage, request_path());
});

