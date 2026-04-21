<?php
// Felhasználói üzenetek listája (admin/staff)
// GET → {items: [...]}
require_once __DIR__ . '/../app/api.php';

require_role(['admin', 'staff']);

$stmt = db()->query("SELECT m.id, m.user_id, m.type, m.message, m.new_email, m.status,
                            m.admin_reply, m.replied_at, m.created_at, m.resolved_at,
                            u.name AS user_name, u.email AS user_email
                     FROM user_messages m
                     JOIN users u ON u.id = m.user_id
                     ORDER BY FIELD(m.status,'pending','approved','rejected'), m.created_at DESC");

json_response(['items' => $stmt->fetchAll()]);
