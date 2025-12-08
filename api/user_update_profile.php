<?php
// Profil módosítása (név, email, jelszó)
// POST: name, email, password → {ok: true}
require_once __DIR__ . '/../app/api.php';
require_once __DIR__ . '/../app/mailer.php';

$user  = require_login();
$data  = read_json();
$name  = input_text($data, 'name');
$email = input_text($data, 'email');
$pass  = (string)($data['password'] ?? '');

if ($name === '' || $email === '') {
    json_response(['error' => 'Név és email kötelező.'], 422);
}
if (!is_valid_email_address($email)) {
    json_response(['error' => 'Érvénytelen email.'], 422);
}
if ($pass !== '' && strlen($pass) < 8) {
    json_response(['error' => 'Jelszó min. 8 karakter.'], 422);
}

$oldEmail = (string)$user['email'];

try {
    if ($pass !== '') {
        db()->prepare('UPDATE users SET name=?, email=?, password_hash=? WHERE id=?')
           ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $user['id']]);
    } else {
        db()->prepare('UPDATE users SET name=?, email=? WHERE id=?')
           ->execute([$name, $email, $user['id']]);
    }
} catch (PDOException $e) {
    if (is_duplicate_error($e)) {
        json_response(['error' => 'Ez az email cím már foglalt.'], 409);
    }
    throw $e;
}

$_SESSION['user']['name']  = $name;
$_SESSION['user']['email'] = $email;
$_SESSION['user']['profile_image'] = $_SESSION['user']['profile_image'] ?? ($user['profile_image'] ?? null);
