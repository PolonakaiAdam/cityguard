<?php
// Saját profilkép feltöltése
// POST (multipart): profile_image
require_once __DIR__ . '/../app/api.php';

$user = require_login();

if (!isset($_FILES['profile_image'])) {
    json_response(['error' => 'Nincs kiválasztott fájl.'], 400);
}

$file = $_FILES['profile_image'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    json_response(['error' => 'Feltöltési hiba: ' . (int)$file['error']], 400);
}

$mime = mime_content_type($file['tmp_name']);
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
    json_response(['error' => 'Csak JPG, PNG vagy WEBP kép tölthető fel.'], 422);
}

if ((int)$file['size'] > 10 * 1024 * 1024) {
    json_response(['error' => 'A profilkép legfeljebb 10 MB lehet.'], 422);
}

$ext = strtolower((string)pathinfo((string)$file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
    $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
}

$uploadDir = __DIR__ . '/../public/uploads/profiles/';
ensure_dir($uploadDir);

$stmt = db()->prepare('SELECT profile_image FROM users WHERE id = ? LIMIT 1');
$stmt->execute([(int)$user['id']]);
$oldFile = (string)($stmt->fetchColumn() ?: '');

$newName = 'profile_' . (int)$user['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
    json_response(['error' => 'A profilkép mentése sikertelen.'], 500);
}

db()->prepare('UPDATE users SET profile_image = ? WHERE id = ?')->execute([$newName, (int)$user['id']]);

if ($oldFile !== '') {
    $oldPath = $uploadDir . basename($oldFile);
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

start_session();
$_SESSION['user']['profile_image'] = $newName;

json_response(['ok' => true, 'file' => $newName, 'user' => $_SESSION['user']]);
