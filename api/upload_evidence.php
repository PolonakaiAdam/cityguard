<?php
// Kép feltöltése bejelentéshez
// POST (multipart): report_id, evidence (fájl) → {ok: true, file: 'nev.jpg'}
require_once __DIR__ . '/../app/api.php';

$user      = require_login();
$report_id = (int)($_POST['report_id'] ?? 0);

if ($report_id <= 0)           json_response(['error' => 'Hibás report_id.'], 400);
if (!isset($_FILES['evidence'])) json_response(['error' => 'Nincs fájl.'], 400);

$file = $_FILES['evidence'];
if ($file['error'] !== UPLOAD_ERR_OK) json_response(['error' => 'Feltöltési hiba: ' . $file['error']], 400);

$mime    = mime_content_type($file['tmp_name']);
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowed)) json_response(['error' => 'Csak jpg/png/webp engedélyezett.'], 400);

