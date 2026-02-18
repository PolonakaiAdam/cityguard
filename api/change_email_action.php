<?php
// Email csere megerősítése tokennel (email linkből)
// POST: token, user_name, new_email → {ok: true}
require_once __DIR__ . '/../app/api.php';
require_once __DIR__ . '/../app/mailer.php';

$data     = read_json();
$token    = trim((string)($data['token']     ?? ''));
$userName = trim((string)($data['user_name'] ?? ''));
$newEmail = trim((string)($data['new_email'] ?? ''));

if ($token === '' || $userName === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Érvénytelen adatok.'], 422);
}

$stmt = db()->prepare("SELECT ect.id, ect.user_id, u.name, u.email
                        FROM email_change_tokens ect
                        JOIN users u ON u.id = ect.user_id
                        WHERE ect.token_hash = ? AND ect.expires_at > NOW() LIMIT 1");
$stmt->execute([hash('sha256', $token)]);
