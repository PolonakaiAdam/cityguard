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
