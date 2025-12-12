<?php
// Bejelentések listája
// GET ?status=new&category_id=1 → {items: [...]}
require_once __DIR__ . '/../app/api.php';

require_login();

$sql = "SELECT r.id, r.user_id, r.title, r.description, r.status, r.created_at, r.address,
               c.name AS category, u.name AS created_by, r.evidence_image
        FROM reports r
        JOIN categories c ON c.id = r.category_id
        JOIN users u ON u.id = r.user_id
        WHERE 1=1";

$params = [];

$status = trim((string)($_GET['status'] ?? ''));
