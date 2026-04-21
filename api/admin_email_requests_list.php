<?php
// Email csere kérések listája (admin)
// GET → {items: [...]}
require_once __DIR__ . '/../app/api.php';

require_role(['admin']);

$stmt = db()->query("SELECT ecr.id, ecr.user_id, ecr.new_email, ecr.status, ecr.created_at,
                            u.name AS user_name, u.email AS current_email
                     FROM email_change_requests ecr
                     JOIN users u ON u.id = ecr.user_id
                     ORDER BY ecr.status ASC, ecr.created_at DESC");

json_response(['items' => $stmt->fetchAll()]);
