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

