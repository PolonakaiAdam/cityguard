<?php
// Jelszó visszaállítás megerősítése tokennel
// POST: token, password → {ok: true}
require_once __DIR__ . '/../app/api.php';

$data     = read_json();
$token    = trim((string)($data['token']    ?? ''));
$password = (string)($data['password'] ?? '');

if ($token === '' || strlen($password) < 6) json_response(['error' => 'Jelszó min. 6 karakter.'], 422);

