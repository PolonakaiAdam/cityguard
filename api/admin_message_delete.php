<?php
// Üzenet törlése (admin/staff)
// POST: message_id → {ok: true}
require_once __DIR__ . '/../app/api.php';

require_role(['admin', 'staff']);
$data  = read_json();
$msgId = (int)($data['message_id'] ?? 0);
