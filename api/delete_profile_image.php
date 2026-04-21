<?php
// Saját profilkép törlése
require_once __DIR__ . '/../app/api.php';

$user = require_login();

$stmt = db()->prepare('SELECT profile_image FROM users WHERE id = ? LIMIT 1');
$stmt->execute([(int)$user['id']]);
$oldFile = (string)($stmt->fetchColumn() ?: '');

db()->prepare('UPDATE users SET profile_image = NULL WHERE id = ?')->execute([(int)$user['id']]);

$uploadDir = __DIR__ . '/../public/uploads/profiles/';
if ($oldFile !== '') {
    $oldPath = $uploadDir . basename($oldFile);
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

start_session();
$_SESSION['user']['profile_image'] = null;

json_response(['ok' => true, 'user' => $_SESSION['user']]);
