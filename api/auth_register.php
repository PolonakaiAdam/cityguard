<?php
// Új felhasználó regisztrálása
// POST: name, email, password → {ok: true}
require_once __DIR__ . '/../app/api.php';
require_once __DIR__ . '/../app/mailer.php';

$data  = read_json();
$name  = input_text($data, 'name');
$email = input_text($data, 'email');
$pass  = (string)($data['password'] ?? '');

if ($name === '' || $email === '' || $pass === '') {
    json_response(['error' => 'Minden mező kötelező.'], 422);
}
if (!is_valid_email_address($email)) {
    json_response(['error' => 'Érvénytelen email cím.'], 422);
}
if (strlen($pass) < 8) {
    json_response(['error' => 'A jelszó legalább 8 karakter legyen.'], 422);
}
