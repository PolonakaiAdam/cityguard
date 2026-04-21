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

function cg_valid_roles(): array {
    return array_keys(cg_role_labels());
}

function cg_valid_statuses(): array {
    return array_keys(cg_status_labels());
}

function cg_manager_roles(): array {
    return ['admin', 'staff', 'municipality'];
}

function cg_role_label(string $role): string {
    return cg_role_labels()[$role] ?? $role;
}

function cg_status_label(string $status): string {
    return cg_status_labels()[$status] ?? $status;
}

function cg_is_valid_role(string $role): bool {
    return in_array($role, cg_valid_roles(), true);
}

function cg_is_valid_status(string $status): bool {
    return in_array($status, cg_valid_statuses(), true);
}

function cg_is_manager_role(string $role): bool {
    return in_array($role, cg_manager_roles(), true);
}

