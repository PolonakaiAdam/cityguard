<?php
// Admin válasz küldése üzenetre
// POST: message_id, reply → {ok: true}
require_once __DIR__ . '/../app/api.php';

require_role(['admin', 'staff']);
$data  = read_json();
$msgId = (int)($data['message_id'] ?? 0);
$reply = trim($data['reply'] ?? '');
