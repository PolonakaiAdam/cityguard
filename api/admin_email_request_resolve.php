<?php
// Email csere kérés jóváhagyása/elutasítása (admin)
// POST: request_id, action (approve/reject) → {ok: true}
require_once __DIR__ . '/../app/api.php';

require_role(['admin']);
$data      = read_json();
$requestId = (int)($data['request_id'] ?? 0);
$action    = $data['action'] ?? '';

if (!$requestId || !in_array($action, ['approve', 'reject'])) json_response(['error' => 'Hibás paraméterek.'], 422);

$stmt = db()->prepare("SELECT * FROM email_change_requests WHERE id = ? AND status = 'pending'");
$stmt->execute([$requestId]);
$req = $stmt->fetch();
if (!$req) json_response(['error' => 'Nem található.'], 404);

if ($action === 'approve') {
    db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$req['new_email'], $req['user_id']]);
}

db()->prepare("UPDATE email_change_requests SET status = ?, resolved_at = NOW() WHERE id = ?")
   ->execute([$action === 'approve' ? 'approved' : 'rejected', $requestId]);

json_response(['ok' => true]);
