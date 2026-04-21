<?php
// Email csere megerősítése tokennel
// POST: token, user_name, new_email → {ok: true}
require_once __DIR__ . '/../app/api.php';

$data     = read_json();
$token    = trim((string)($data['token']     ?? ''));
$userName = trim((string)($data['user_name'] ?? ''));
$newEmail = trim((string)($data['new_email'] ?? ''));

if ($token === '' || $userName === '' || $newEmail === '') {
    json_response(['error' => 'Érvénytelen adatok.'], 422);
}

$stmt = db()->prepare("SELECT ect.id, ect.user_id, u.name, u.email
                        FROM email_change_tokens ect
                        JOIN users u ON u.id = ect.user_id
                        WHERE ect.token_hash = ? AND ect.expires_at > NOW() LIMIT 1");
$stmt->execute([hash('sha256', $token)]);
$row = $stmt->fetch();
if (!$row) json_response(['error' => 'A link érvénytelen vagy lejárt.'], 400);

$dbName = mb_strtolower(trim((string)$row['name']), 'UTF-8');
$inName = mb_strtolower($userName, 'UTF-8');
if ($dbName !== $inName) json_response(['error' => 'A megadott név nem egyezik.'], 422);

$chk = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
$chk->execute([$newEmail, $row['user_id']]);
if ($chk->fetch()) json_response(['error' => 'Ez az email már foglalt.'], 409);

db()->beginTransaction();
db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$newEmail, $row['user_id']]);
db()->prepare('DELETE FROM email_change_tokens WHERE id = ? OR user_id = ?')->execute([$row['id'], $row['user_id']]);
db()->commit();

json_response(['ok' => true, 'msg' => 'Email cím sikeresen megváltozott!']);
