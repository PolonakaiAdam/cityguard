<?php
// Felhasználó törlése (admin – saját fiók nem törölhető)
// POST: user_id → {ok: true}
require_once __DIR__ . '/../app/api.php';

$me     = require_role(['admin']);
$data   = read_json();
$userId = (int)($data['user_id'] ?? 0);

if ($userId <= 0)              json_response(['error' => 'Érvénytelen azonosító.'], 422);
if ($userId === (int)$me['id']) json_response(['error' => 'Saját magad nem törölheted.'], 403);

$stmt = db()->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$userId]);
if ($stmt->rowCount() === 0) json_response(['error' => 'Felhasználó nem található.'], 404);

json_response(['ok' => true]);
