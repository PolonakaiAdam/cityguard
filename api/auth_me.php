<?php
// Bejelentkezett felhasználó adatai
// GET → {user: {...}} vagy {user: null}
require_once __DIR__ . '/../app/api.php';

start_session();

if (empty($_SESSION['user']['id'])) {
    json_response(['user' => null]);
}

$stmt = db()->prepare('SELECT id, name, email, role, profile_image FROM users WHERE id = ? LIMIT 1');
$stmt->execute([(int)$_SESSION['user']['id']]);
$user = $stmt->fetch();

if (!$user) {
    unset($_SESSION['user']);
    json_response(['user' => null]);
}

$_SESSION['user'] = [
    'id'            => (int)$user['id'],
    'name'          => $user['name'],
    'email'         => $user['email'],
    'role'          => $user['role'],
    'profile_image' => $user['profile_image'] ?? null,
];

json_response(['user' => $_SESSION['user']]);
