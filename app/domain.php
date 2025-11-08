<?php
// domain.php – Az alkalmazás közös "szótára".
// Itt vannak azok az állandó értékek, amelyeket több helyen is használunk:
// - szerepkörök
// - státuszok
// - címkék magyarul
// - egyszerű jogosultsági ellenőrzések
//
// Ez azért jó, mert ha később valamit módosítani kell, nem 8 külön fájlban
// kell ugyanazt átírni.

function cg_role_labels(): array {
    return [
        'admin'        => 'Adminisztrátor',
        'staff'        => 'Ügyintéző',
        'citizen'      => 'Lakos',
        'municipality' => 'Önkormányzati tag',
    ];
}

function cg_status_labels(): array {
    return [
        'new'         => 'Új',
        'in_progress' => 'Folyamatban',
        'resolved'    => 'Megoldva',
        'rejected'    => 'Elutasítva',
    ];
}

