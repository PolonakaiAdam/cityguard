<?php
// Bejelentés szerkesztése
// POST: report_id, title, category_id, latitude, longitude → {ok: true}
require_once __DIR__ . '/../app/api.php';

$user = require_login();
$data = read_json();
$id   = input_int($data, 'report_id');

if ($id <= 0) {
    json_response(['error' => 'Hibás azonosító.'], 422);
}

$stmt = db()->prepare('SELECT id, user_id FROM reports WHERE id = ?');
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    json_response(['error' => 'Nem található.'], 404);
}

$isOwner   = (int)$report['user_id'] === (int)$user['id'];
$isManager = cg_is_manager_role((string)$user['role']);

if (!$isOwner && !$isManager) {
    json_response(['error' => 'Tiltott.'], 403);
}

$title       = input_text($data, 'title');
$category_id = input_int($data, 'category_id');
$lat         = input_float_or_null($data, 'latitude');
$lng         = input_float_or_null($data, 'longitude');

if ($title === '' || $category_id <= 0 || $lat === null || $lng === null) {
    json_response(['error' => 'Hiányzó mezők.'], 422);
}

db()->prepare('UPDATE reports SET title=?, category_id=?, latitude=?, longitude=? WHERE id=?')
   ->execute([$title, $category_id, $lat, $lng, $id]);

json_response(['ok' => true]);
