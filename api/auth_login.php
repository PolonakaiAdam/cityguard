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

$stmt = db()->prepare('SELECT id, name, email, role, profile_image, password_hash FROM users WHERE email = ? OR name = ? LIMIT 1');
$stmt->execute([$identity, $identity]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['error' => 'Hibás email vagy jelszó.'], 401);
}

start_session();
$_SESSION['user'] = [
    'id'    => (int)$user['id'],
    'name'  => $user['name'],
    'email' => $user['email'],
    'role'  => $user['role'],
    'profile_image' => $user['profile_image'] ?? null,
];

json_response(['ok' => true, 'user' => $_SESSION['user']]);
