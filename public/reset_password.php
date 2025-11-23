<?php
/**
 * reset_password.php – Jelszó visszaállítás oldal
 * GET ?token=xxxx
 */
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db.php';
$baseUrl = cg_detect_base_url();

$token = trim($_GET['token'] ?? '');
$valid = false;
$userName = '';

if ($token) {
    $tokenHash = hash('sha256', $token);
    $s = db()->prepare(
        "SELECT u.name FROM password_resets pr JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = ? AND pr.expires_at > NOW() LIMIT 1"
    );
    $s->execute([$tokenHash]);
    $row = $s->fetch();
    if ($row) {
        $valid    = true;
        $userName = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/favicon-16.png" />
  <link rel="shortcut icon" href="favicon.ico" />
  <title>CityGuard – Jelszó visszaállítás</title>
