<?php
// Bejelentési kategóriák listája
// GET → {items: [{id, name}]}
require_once __DIR__ . '/../app/api.php';

json_response(['items' => db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll()]);
