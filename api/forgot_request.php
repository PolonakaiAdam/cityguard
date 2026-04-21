<?php
// Elfelejtett jelszó / email csere – token generálás
// POST: type (password_reset/email_change), email|user_name, new_email → {ok: true, msg: ..., token: ...}
require_once __DIR__ . '/../app/api.php';

$db   = db();
$data = read_json();
$type = trim((string)($data['type'] ?? ''));

if (!in_array($type, ['password_reset', 'email_change'], true)) json_response(['error' => 'Érvénytelen típus.'], 422);

// ── Jelszó visszaállítás ──────────────────────────────────────────────
if ($type === 'password_reset') {
    $identity = trim((string)($data['email'] ?? ''));

    if ($identity === '') json_response(['error' => 'Add meg az email címed vagy felhasználóneved.'], 422);

    $stmt = $db->prepare('SELECT id, name, email FROM users WHERE email = ? OR name = ? LIMIT 1');
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    if (!$user) json_response(['ok' => true, 'msg' => 'Ha létezik ilyen fiók, generáltuk a tokent.']);

    $db->prepare('DELETE FROM password_resets WHERE user_id = ? OR expires_at < NOW()')->execute([$user['id']]);
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);
    $db->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)')
       ->execute([$user['id'], hash('sha256', $token), $expires]);

    $link = public_url('reset_password.php') . '?token=' . urlencode($token);
    json_response(['ok' => true, 'msg' => 'Token generálva.', 'reset_link' => $link]);
}

// ── Email csere ───────────────────────────────────────────────────────
$userName = trim((string)($data['user_name'] ?? ''));
$newEmail = trim((string)($data['new_email'] ?? ''));

if ($userName === '') json_response(['error' => 'Add meg a felhasználónevedet.'], 422);
if ($newEmail === '')  json_response(['error' => 'Add meg az új email címet.'], 422);

$stmt = $db->prepare('SELECT id, name, email FROM users WHERE name = ? OR email = ? LIMIT 1');
$stmt->execute([$userName, $userName]);
$user = $stmt->fetch();
if (!$user) json_response(['error' => 'Nem találtunk ilyen felhasználót.'], 404);

$chk = $db->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
$chk->execute([$newEmail, $user['id']]);
if ($chk->fetch()) json_response(['error' => 'Ez az email már foglalt.'], 409);

$db->prepare('DELETE FROM email_change_tokens WHERE user_id = ? OR expires_at < NOW()')->execute([$user['id']]);
$token   = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 3600);
$db->prepare('INSERT INTO email_change_tokens (user_id, token_hash, new_email, expires_at) VALUES (?,?,?,?)')
   ->execute([$user['id'], hash('sha256', $token), $newEmail, $expires]);

$link = public_url('change_email.php') . '?token=' . urlencode($token);
json_response(['ok' => true, 'msg' => 'Token generálva.', 'change_link' => $link]);
