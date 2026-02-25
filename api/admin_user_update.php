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
