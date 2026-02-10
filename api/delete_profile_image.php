<?php
// Saját profilkép törlése
require_once __DIR__ . '/../app/api.php';

$user = require_login();

$stmt = db()->prepare('SELECT profile_image FROM users WHERE id = ? LIMIT 1');
$stmt->execute([(int)$user['id']]);
$oldFile = (string)($stmt->fetchColumn() ?: '');
