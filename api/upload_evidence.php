<?php
// Kép feltöltése bejelentéshez
// POST (multipart): report_id, evidence (fájl) → {ok: true, file: 'nev.jpg'}
require_once __DIR__ . '/../app/api.php';

$user      = require_login();
$report_id = (int)($_POST['report_id'] ?? 0);

if ($report_id <= 0)             json_response(['error' => 'Hibás report_id.'], 400);
if (!isset($_FILES['evidence'])) json_response(['error' => 'Nincs fájl.'], 400);

// Csak saját bejelentéshez tölthet fel bizonyítékot citizen,
// manager (admin/staff/municipality) bármely bejelentéshez tölthet fel
$repStmt = db()->prepare('SELECT user_id FROM reports WHERE id = ?');
$repStmt->execute([$report_id]);
$repRow = $repStmt->fetch();
if (!$repRow) json_response(['error' => 'Bejelentés nem található.'], 404);

$isOwner   = (int)$repRow['user_id'] === (int)$user['id'];
$isManager = cg_is_manager_role((string)$user['role']);
if (!$isOwner && !$isManager) json_response(['error' => 'Nincs jogosultságod.'], 403);

$file = $_FILES['evidence'];
if ($file['error'] !== UPLOAD_ERR_OK) json_response(['error' => 'Feltöltési hiba: ' . $file['error']], 400);

$mime    = mime_content_type($file['tmp_name']);
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowed)) json_response(['error' => 'Csak jpg/png/webp engedélyezett.'], 400);

$ext     = in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'])
           ? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) : 'jpg';
$newName = 'evidence_' . $report_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

$uploadDir = __DIR__ . '/../public/uploads/evidence/';
ensure_dir($uploadDir);

if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
    json_response(['error' => 'Fájl mentése sikertelen.'], 500);
}

// Meglévő képekhez hozzáfűzés (vesszővel elválasztva)
$stmt = db()->prepare('SELECT evidence_image FROM reports WHERE id = ?');
$stmt->execute([$report_id]);
$existing = $stmt->fetchColumn();

$newValue = ($existing && trim($existing) !== '') ? $existing . ',' . $newName : $newName;
db()->prepare('UPDATE reports SET evidence_image = ? WHERE id = ?')->execute([$newValue, $report_id]);

json_response(['ok' => true, 'file' => $newName]);

