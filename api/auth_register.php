<?php
// Új felhasználó regisztrálása
// POST: name, email, password → {ok: true}
require_once __DIR__ . '/../app/api.php';

$data  = read_json();
$name  = input_text($data, 'name');
$email = input_text($data, 'email');
$pass  = (string)($data['password'] ?? '');

if ($name === '' || $email === '' || $pass === '') {
    json_response(['error' => 'Minden mező kötelező.'], 422);
}

try {
    db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'citizen')")
       ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
    if (is_duplicate_error($e)) {
        json_response(['error' => 'Ez a felhasználónév már foglalt.'], 409);
    }
    throw $e;
}

// Admin értesítő küldése egyszerű PHP mail()-lal
$subject = 'CityGuard – Új regisztráció';
$message = "Új felhasználó regisztrált a CityGuard rendszerbe.\r\n\r\n"
         . "Felhasználónév: {$name}\r\n"
         . "E-mail cím: {$email}\r\n";
$headers = "From: noreply@cityguard.hu\r\nContent-Type: text/plain; charset=UTF-8";

@mail('toth.zolika100@gmail.com', $subject, $message, $headers);

json_response(['ok' => true, 'msg' => 'Sikeres regisztráció.'], 201);
