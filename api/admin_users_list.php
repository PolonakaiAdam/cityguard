<?php
// Összes felhasználó listája (admin)
// GET → {items: [...]}
require_once __DIR__ . '/../app/api.php';

$me   = require_role(['admin']);
$stmt = db()->prepare('SELECT id, name, email, role, created_at FROM users WHERE id != ? ORDER BY created_at DESC');
$stmt->execute([$me['id']]);
json_response(['items' => $stmt->fetchAll()]);
