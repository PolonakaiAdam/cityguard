<?php
// Bejelentés törlése
// Saját bejelentést a tulajdonos törölheti, minden más esetben a kezelő szerepkörök.
require_once __DIR__ . '/../app/api.php';

$user      = require_login();
$request   = read_json();
$report_id = (int)($_GET['report_id'] ?? $request['report_id'] ?? 0);

if ($report_id <= 0) {
    json_response(['error' => 'Hibás azonosító.'], 422);
}

$stmt = db()->prepare('SELECT id, user_id, evidence_image FROM reports WHERE id = ?');
$stmt->execute([$report_id]);
$report = $stmt->fetch();

