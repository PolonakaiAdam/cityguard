<?php
// Komment hozzáadása bejelentéshez
// POST: report_id, comment → {ok: true}
require_once __DIR__ . '/../app/api.php';

$user      = require_login();
$data      = read_json();
$report_id = input_int($data, 'report_id');
$comment   = input_text($data, 'comment');

if ($report_id <= 0 || $comment === '') {
    json_response(['error' => 'Hiányzó adatok.'], 422);
}
if (mb_strlen($comment) > 2000) {
    json_response(['error' => 'Max 2000 karakter.'], 422);
}

$chk = db()->prepare('SELECT id FROM reports WHERE id = ?');
$chk->execute([$report_id]);
if (!$chk->fetch()) {
    json_response(['error' => 'Bejelentés nem található.'], 404);
}

db()->prepare('INSERT INTO report_comments (report_id, user_id, comment) VALUES (?,?,?)')
   ->execute([$report_id, $user['id'], $comment]);

json_response(['ok' => true, 'id' => (int)db()->lastInsertId()], 201);

