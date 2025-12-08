<?php
// Bejelentkezett felhasználó adatai
// GET → {user: {...}} vagy {user: null}
require_once __DIR__ . '/../app/api.php';

start_session();

if (empty($_SESSION['user']['id'])) {
    json_response(['user' => null]);
}

$stmt = db()->prepare('SELECT id, name, email, role, profile_image FROM users WHERE id = ? LIMIT 1');
