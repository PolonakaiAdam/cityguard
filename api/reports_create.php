<?php
// Új bejelentés létrehozása
// POST: title, category_id, latitude, longitude, comment → {id: ...}
require_once __DIR__ . '/../app/api.php';

$user = require_role(['citizen', 'staff']);
$data = read_json();

$title       = input_text($data, 'title');
$category_id = input_int($data, 'category_id');
$lat         = input_float_or_null($data, 'latitude');
$lng         = input_float_or_null($data, 'longitude');
$description = input_text($data, 'comment');

if ($title === '' || $category_id <= 0) {
    json_response(['error' => 'Hiányzó adatok.'], 422);
}
if ($lat === null || $lng === null) {
    json_response(['error' => 'GPS helyzet kötelező.'], 422);
}
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    json_response(['error' => 'Hibás koordináta.'], 422);
}

db()->prepare("INSERT INTO reports (user_id, category_id, title, description, address, latitude, longitude) VALUES (?,?,?,?,?,?,?)")
   ->execute([$user['id'], $category_id, $title, $description, '', $lat, $lng]);

json_response(['ok' => true, 'id' => (int)db()->lastInsertId()], 201);
