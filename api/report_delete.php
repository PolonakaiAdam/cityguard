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

if (!$report) {
    json_response(['error' => 'Nem található.'], 404);
}

$canDelete = cg_is_manager_role((string)$user['role']) || (int)$report['user_id'] === (int)$user['id'];
if (!$canDelete) {
    json_response(['error' => 'Tiltott.'], 403);
}

// Képfájlok törlése
if (!empty($report['evidence_image'])) {
    foreach (explode(',', $report['evidence_image']) as $img) {
        $file = __DIR__ . '/../public/uploads/evidence/' . basename(trim($img));
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

db()->beginTransaction();
db()->prepare('DELETE FROM report_comments WHERE report_id = ?')->execute([$report_id]);
db()->prepare('DELETE FROM reports WHERE id = ?')->execute([$report_id]);
db()->commit();

json_response(['ok' => true]);

