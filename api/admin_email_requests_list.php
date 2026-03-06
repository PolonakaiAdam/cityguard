<?php
// Email csere kérések listája (admin)
// GET → {items: [...]}
require_once __DIR__ . '/../app/api.php';

require_role(['admin']);
