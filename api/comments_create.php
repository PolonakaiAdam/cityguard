<?php
// Komment hozzáadása bejelentéshez
// POST: report_id, comment → {ok: true}
require_once __DIR__ . '/../app/api.php';

$user      = require_login();
$data      = read_json();
$report_id = input_int($data, 'report_id');
$comment   = input_text($data, 'comment');

