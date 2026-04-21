<?php
// Új email cím csere kérés küldése (admin jóváhagyás szükséges)
// POST: new_email → {ok: true}
require_once __DIR__ . '/../app/api.php';

$user      = require_login();
$data      = read_json();
$new_email = trim($data['new_email'] ?? '');

if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Érvénytelen email cím.'], 422);

$stmt = db()->prepare("SELECT id FROM email_change_requests WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user['id']]);
if ($stmt->fetch()) json_response(['error' => 'Már van folyamatban lévő kérésed.'], 409);

db()->prepare('INSERT INTO email_change_requests (user_id, new_email) VALUES (?,?)')->execute([$user['id'], $new_email]);
json_response(['ok' => true]);
