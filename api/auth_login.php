<?php
// Bejelentkezés
// A felhasználó emaillel vagy felhasználónévvel is beléphet.
// POST: email / identity, password
require_once __DIR__ . '/../app/api.php';

$data     = read_json();
$identity = input_text($data, ['email', 'identity']);
$password = (string)($data['password'] ?? '');

if ($identity === '' || $password === '') {
    json_response(['error' => 'Email és jelszó kötelező.'], 422);
}

