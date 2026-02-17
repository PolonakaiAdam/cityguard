<?php
// Saját üzenet törlése
// POST: message_id → {ok: true}
require_once __DIR__ . '/../app/api.php';

$user  = require_login();
$data  = read_json();
$msgId = (int)($data['message_id'] ?? 0);

