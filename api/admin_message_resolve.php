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
