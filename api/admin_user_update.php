<?php
// Felhasználó adatainak módosítása (admin)
// POST: user_id, name, email, role, password → {ok: true}
require_once __DIR__ . '/../app/api.php';

$me   = require_role(['admin']);
$data = read_json();

$userId = input_int($data, 'user_id');
$name   = input_text($data, 'name');
$email  = input_text($data, 'email');
$role   = input_text($data, 'role');
$pass   = (string)($data['password'] ?? '');

if ($userId <= 0) {
    json_response(['error' => 'Érvénytelen azonosító.'], 422);
}
if ($userId === (int)$me['id']) {
    json_response(['error' => 'Saját adataidat a Fiókom menüpontban módosíthatod.'], 403);
}
if (!cg_is_valid_role($role)) {
    json_response(['error' => 'Érvénytelen szerepkör.'], 422);
}
if ($name === '' || $email === '') {
    json_response(['error' => 'Név és email kötelező.'], 422);
}
if (!is_valid_email_address($email)) {
    json_response(['error' => 'Érvénytelen email.'], 422);
}
if ($pass !== '' && strlen($pass) < 6) {
    json_response(['error' => 'Jelszó min. 6 karakter.'], 422);
}

$chk = db()->prepare('SELECT id FROM users WHERE id = ?');
$chk->execute([$userId]);
if (!$chk->fetch()) {
    json_response(['error' => 'Felhasználó nem található.'], 404);
}

try {
    if ($pass !== '') {
        db()->prepare('UPDATE users SET name=?, email=?, role=?, password_hash=? WHERE id=?')
           ->execute([$name, $email, $role, password_hash($pass, PASSWORD_DEFAULT), $userId]);
    } else {
        db()->prepare('UPDATE users SET name=?, email=?, role=? WHERE id=?')
           ->execute([$name, $email, $role, $userId]);
    }
    json_response(['ok' => true, 'password_changed' => $pass !== '']);
} catch (PDOException $e) {
    if (is_duplicate_error($e)) {
        json_response(['error' => 'Ez az email cím már foglalt.'], 409);
    }
    throw $e;
}
