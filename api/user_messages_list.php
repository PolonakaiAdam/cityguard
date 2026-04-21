<?php
// Bejelentkezett felhasználó üzenetei
// GET → {items: [...]}
require_once __DIR__ . '/../app/api.php';

$user = require_login();
$stmt = db()->prepare("SELECT id, type, message, new_email, status, admin_reply, replied_at, created_at, resolved_at
                        FROM user_messages WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
json_response(['items' => $stmt->fetchAll()]);
