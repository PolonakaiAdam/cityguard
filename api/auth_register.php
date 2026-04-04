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

try {
    db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'citizen')")
       ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
    if (is_duplicate_error($e)) {
        json_response(['error' => 'Ez az email cím már foglalt.'], 409);
    }
    throw $e;
}

$html = cg_render_email_layout([
    'preheader' => 'Sikeres CityGuard regisztráció',
    'eyebrow'   => 'Sikeres regisztráció',
