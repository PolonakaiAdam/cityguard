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

