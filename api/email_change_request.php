<?php
// Új email cím csere kérés küldése (admin jóváhagyás szükséges)
// POST: new_email → {ok: true}
require_once __DIR__ . '/../app/api.php';

$user      = require_login();
$data      = read_json();
$new_email = trim($data['new_email'] ?? '');

