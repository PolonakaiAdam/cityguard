<?php
// Bejelentéshez feltöltött kép törlése
// POST: report_id, image → {ok: true, images: [...]}
require_once __DIR__ . '/../app/api.php';

$user      = require_login();
$data      = read_json();
$report_id = input_int($data, 'report_id');
$image     = basename(input_text($data, 'image'));

if ($report_id <= 0 || $image === '') {
    json_response(['error' => 'Hiányzó adatok.'], 422);
}

$stmt = db()->prepare('SELECT id, user_id, evidence_image FROM reports WHERE id = ?');
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    json_response(['error' => 'Nem található.'], 404);
}

$isOwner   = (int)$report['user_id'] === (int)$user['id'];
$isManager = cg_is_manager_role((string)$user['role']);
if (!$isOwner && !$isManager) {
    json_response(['error' => 'Tiltott.'], 403);
}

$images = array_values(array_filter(array_map('trim', explode(',', (string)$report['evidence_image']))));
if (!in_array($image, $images, true)) {
    json_response(['error' => 'Kép nem található.'], 404);
}

$images = array_values(array_filter($images, fn($img) => $img !== $image));
$path   = __DIR__ . '/../public/uploads/evidence/' . $image;

if (is_file($path)) {
    @unlink($path);
}

db()->prepare('UPDATE reports SET evidence_image = ? WHERE id = ?')->execute([implode(',', $images), $report_id]);
json_response(['ok' => true, 'images' => $images]);
