<?php
// Saját üzenet törlése
// POST: message_id → {ok: true}
require_once __DIR__ . '/../app/api.php';

$user  = require_login();
$data  = read_json();
$msgId = (int)($data['message_id'] ?? 0);

if (!$msgId) json_response(['error' => 'Hiányzó message_id.'], 422);

$stmt = db()->prepare('SELECT id FROM user_messages WHERE id = ? AND user_id = ?');
$stmt->execute([$msgId, $user['id']]);
if (!$stmt->fetch()) json_response(['error' => 'Üzenet nem található.'], 404);

db()->prepare('DELETE FROM user_messages WHERE id = ? AND user_id = ?')->execute([$msgId, $user['id']]);
json_response(['ok' => true]);
