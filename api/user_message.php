<?php
// Üzenet/kérés küldése az adminnak
// POST: type, message, new_email → {ok: true}
require_once __DIR__ . '/../app/api.php';

$user     = require_login();
$data     = read_json();
$type     = $data['type']      ?? 'message';
$message  = trim($data['message']   ?? '');
$newEmail = trim($data['new_email'] ?? '');

