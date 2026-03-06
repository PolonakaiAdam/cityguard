<?php
// Email csere kérés jóváhagyása/elutasítása (admin)
// POST: request_id, action (approve/reject) → {ok: true}
require_once __DIR__ . '/../app/api.php';

require_role(['admin']);
$data      = read_json();
$requestId = (int)($data['request_id'] ?? 0);
$action    = $data['action'] ?? '';
