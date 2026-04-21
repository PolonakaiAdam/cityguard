<?php
// Bejelentés státuszának módosítása
// POST: report_id, status → {ok: true}
require_once __DIR__ . '/../app/api.php';

require_role(['admin', 'staff', 'municipality']);

$data   = read_json();
$id     = input_int($data, 'report_id');
$status = input_text($data, 'status');

if ($id <= 0 || !cg_is_valid_status($status)) {
    json_response(['error' => 'Hibás adatok.'], 422);
}

db()->prepare('UPDATE reports SET status = ? WHERE id = ?')->execute([$status, $id]);
json_response(['ok' => true]);
