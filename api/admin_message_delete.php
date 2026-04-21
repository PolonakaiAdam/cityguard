<?php
// Üzenet törlése (admin/staff)
// POST: message_id → {ok: true}
require_once __DIR__ . '/../app/api.php';

require_role(['admin', 'staff']);
$data  = read_json();
$msgId = (int)($data['message_id'] ?? 0);

if (!$msgId) json_response(['error' => 'Hiányzó message_id.'], 422);

$stmt = db()->prepare('SELECT id FROM user_messages WHERE id = ?');
$stmt->execute([$msgId]);
if (!$stmt->fetch()) json_response(['error' => 'Üzenet nem található.'], 404);

db()->prepare('DELETE FROM user_messages WHERE id = ?')->execute([$msgId]);
json_response(['ok' => true]);
