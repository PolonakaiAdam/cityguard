<?php
// Jelszó visszaállítás megerősítése tokennel
// POST: token, password → {ok: true}
require_once __DIR__ . '/../app/api.php';

$data     = read_json();
$token    = trim((string)($data['token']    ?? ''));
$password = (string)($data['password'] ?? '');

if ($token === '' || strlen($password) < 6) json_response(['error' => 'Jelszó min. 6 karakter.'], 422);

$stmt = db()->prepare("SELECT pr.id, pr.user_id FROM password_resets pr WHERE pr.token_hash = ? AND pr.expires_at > NOW() LIMIT 1");
$stmt->execute([hash('sha256', $token)]);
$row = $stmt->fetch();
if (!$row) json_response(['error' => 'A link érvénytelen vagy lejárt.'], 400);

db()->beginTransaction();
db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
db()->prepare('DELETE FROM password_resets WHERE id = ? OR user_id = ?')->execute([$row['id'], $row['user_id']]);
db()->commit();

json_response(['ok' => true, 'msg' => 'Jelszavad megváltozott!']);
