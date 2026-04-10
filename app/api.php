<?php
// api.php – Közös betöltő minden API végpont számára.
// Ha egy API fájl ezt behúzza, automatikusan megkapja:
// - bootstrap / hibakezelés
// - segédfüggvények
// - közös szerepkör/státusz függvények
// - adatbázis kapcsolat
// - bejelentkezés / jogosultság kezelés

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/domain.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
<?php // refactor 3
