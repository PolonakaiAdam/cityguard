<?php
// Üzenet/kérés jóváhagyása vagy elutasítása
// POST: message_id, action (approve/reject), new_password → {ok: true}
require_once __DIR__ . '/../app/api.php';

require_role(['admin', 'staff']);
$data    = read_json();
$msgId   = (int)($data['message_id'] ?? 0);
$action  = $data['action'] ?? '';
$newPass = trim($data['new_password'] ?? '');

if (!$msgId || !in_array($action, ['approve', 'reject'])) json_response(['error' => 'Hibás paraméterek.'], 422);

$stmt = db()->prepare("SELECT * FROM user_messages WHERE id = ? AND status = 'pending'");
$stmt->execute([$msgId]);
$msg = $stmt->fetch();
if (!$msg) json_response(['error' => 'Nem található függőben lévő kérés.'], 404);

$response = ['ok' => true];

if ($action === 'approve') {
    if ($msg['type'] === 'email_change') {
        db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$msg['new_email'], $msg['user_id']]);
        $response['info'] = 'email_changed';
    } elseif ($msg['type'] === 'password_reset') {
        if ($newPass === '' || strlen($newPass) < 6) json_response(['error' => 'Új jelszó min. 6 karakter.'], 422);
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
           ->execute([password_hash($newPass, PASSWORD_DEFAULT), $msg['user_id']]);
        $response['info'] = 'password_changed';
    }
}

db()->prepare("UPDATE user_messages SET status = ?, resolved_at = NOW() WHERE id = ?")
   ->execute([$action === 'approve' ? 'approved' : 'rejected', $msgId]);

json_response($response);
