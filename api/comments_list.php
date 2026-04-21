<?php
// Egy bejelentés kommentjei
// GET ?report_id=X → {items: [...]}
require_once __DIR__ . '/../app/api.php';

require_login();
$report_id = (int)($_GET['report_id'] ?? 0);
if ($report_id <= 0) json_response(['error' => 'Hiányzó report_id.'], 422);

$stmt = db()->prepare("SELECT rc.id, rc.comment, rc.created_at, u.name AS author, u.role AS author_role
                        FROM report_comments rc
                        JOIN users u ON u.id = rc.user_id
                        WHERE rc.report_id = ? ORDER BY rc.created_at ASC");
$stmt->execute([$report_id]);
json_response(['items' => $stmt->fetchAll()]);
