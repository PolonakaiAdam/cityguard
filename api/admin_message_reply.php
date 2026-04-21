<?php
// Admin válasz küldése üzenetre
// POST: message_id, reply → {ok: true}
require_once __DIR__ . '/../app/api.php';

require_role(['admin', 'staff']);
$data  = read_json();
$msgId = (int)($data['message_id'] ?? 0);
$reply = trim($data['reply'] ?? '');

if (!$msgId || $reply === '') json_response(['error' => 'Hiányzó adatok.'], 422);

$stmt = db()->prepare('SELECT id FROM user_messages WHERE id = ?');
$stmt->execute([$msgId]);
if (!$stmt->fetch()) json_response(['error' => 'Üzenet nem található.'], 404);

db()->prepare('UPDATE user_messages SET admin_reply = ?, replied_at = NOW() WHERE id = ?')
   ->execute([$reply, $msgId]);

json_response(['ok' => true]);
