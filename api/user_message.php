<?php
// Üzenet/kérés küldése az adminnak
// POST: type, message, new_email → {ok: true}
require_once __DIR__ . '/../app/api.php';

$user     = require_login();
$data     = read_json();
$type     = $data['type']      ?? 'message';
$message  = trim($data['message']   ?? '');
$newEmail = trim($data['new_email'] ?? '');

if (!in_array($type, ['message', 'email_change', 'password_reset'])) json_response(['error' => 'Érvénytelen típus.'], 422);
if ($type === 'message' && $message === '')                           json_response(['error' => 'Az üzenet nem lehet üres.'], 422);
if ($type === 'email_change' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Érvénytelen email cím.'], 422);

// Ellenőrzés: van-e már függőben lévő kérés
$check = db()->prepare("SELECT id FROM user_messages WHERE user_id = ? AND type = ? AND status = 'pending'");
$check->execute([$user['id'], $type]);
if ($check->fetch()) json_response(['error' => 'Már van folyamatban lévő kérésed. Várd meg az admin válaszát.'], 409);

db()->prepare('INSERT INTO user_messages (user_id, type, message, new_email) VALUES (?,?,?,?)')
   ->execute([$user['id'], $type, $message, $newEmail ?: null]);

json_response(['ok' => true]);
