<?php
// Bejelentések GPS koordinátákkal (térképhez)
// GET → {items: [...]}
require_once __DIR__ . '/../app/api.php';

$user = require_login();

$sql    = "SELECT r.id, r.title, r.description, r.status, r.latitude, r.longitude, r.created_at,
                  c.name AS category, u.name AS created_by, r.evidence_image
           FROM reports r
           JOIN categories c ON c.id = r.category_id
           JOIN users u ON u.id = r.user_id
           WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL";
$params = [];

// Citizen csak a saját bejelentéseit látja a térképen
if ($user['role'] === 'citizen') {
    $sql .= ' AND r.user_id = ?';
    $params[] = $user['id'];
}

$sql .= ' ORDER BY r.created_at DESC LIMIT 1000';

$stmt = db()->prepare($sql);
$stmt->execute($params);
json_response(['items' => $stmt->fetchAll()]);
