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
