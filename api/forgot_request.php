<?php
// Elfelejtett jelszó / email csere – megerősítő link küldése emailben
// POST: type (password_reset/email_change), email|user_name, new_email → {ok: true, msg: ...}
require_once __DIR__ . '/../app/api.php';
require_once __DIR__ . '/../app/mailer.php';

$db   = db();
$data = read_json();
$type = trim((string)($data['type'] ?? ''));

if (!in_array($type, ['password_reset', 'email_change'], true)) json_response(['error' => 'Érvénytelen típus.'], 422);

// ── Jelszó visszaállítás ──────────────────────────────────────────────
if ($type === 'password_reset') {
    $identity      = trim((string)($data['email'] ?? ''));
    $deliveryEmail = trim((string)($data['delivery_email'] ?? ''));

    if ($identity === '') json_response(['error' => 'Add meg az email címed vagy felhasználóneved.'], 422);

    $stmt = $db->prepare('SELECT id, name, email FROM users WHERE email = ? OR name = ? LIMIT 1');
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    // Biztonsági ok: nem árulunk el hogy létezik-e a fiók
    if (!$user) json_response(['ok' => true, 'msg' => 'Ha létezik ilyen fiók, elküldtük a linket.']);

    // Célcím meghatározása
    $targetEmail = '';
    if ($deliveryEmail !== '' && filter_var($deliveryEmail, FILTER_VALIDATE_EMAIL)) $targetEmail = $deliveryEmail;
