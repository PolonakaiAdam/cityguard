<?php
// Új felhasználó regisztrálása
// POST: name, email, password → {ok: true}
require_once __DIR__ . '/../app/api.php';
require_once __DIR__ . '/../app/mailer.php';

$data  = read_json();
$name  = input_text($data, 'name');
$email = input_text($data, 'email');
$pass  = (string)($data['password'] ?? '');

if ($name === '' || $email === '' || $pass === '') {
    json_response(['error' => 'Minden mező kötelező.'], 422);
}
if (!is_valid_email_address($email)) {
    json_response(['error' => 'Érvénytelen email cím.'], 422);
}
if (strlen($pass) < 8) {
    json_response(['error' => 'A jelszó legalább 8 karakter legyen.'], 422);
}

try {
    db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'citizen')")
       ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
    if (is_duplicate_error($e)) {
        json_response(['error' => 'Ez az email cím már foglalt.'], 409);
    }
    throw $e;
}

$html = cg_render_email_layout([
    'preheader' => 'Sikeres CityGuard regisztráció',
    'eyebrow'   => 'Sikeres regisztráció',
    'title'     => '🎉 Sikeres regisztráció!',
    'intro'     => "Szia {$name}! Sikeresen regisztráltál a CityGuard rendszerbe.",
    'body_html' => "
      <div style='font-family:Arial,sans-serif;font-size:15px;line-height:24px;color:#cbd5e1;margin-bottom:18px'>
        Az alábbi adatokkal készült el a fiókod. A levél kialakítása most már ugyanazt a sötét-kék CityGuard hangulatot követi, mint maga az oldal.
      </div>
      " . cg_email_info_rows([
        ['label' => 'Felhasználónév', 'value' => $name],
        ['label' => 'E-mail cím',     'value' => $email],
        ['label' => 'Jelszó',         'value' => $pass],
      ]) . "
      <div style='font-family:Arial,sans-serif;font-size:14px;line-height:22px;color:#94a3b8;margin-top:8px'>
        Bejelentkezés után rögtön az <strong style='color:#dbeafe'>Új bejelentés</strong> oldalra jutsz.
      </div>
    ",
    'footer_note' => 'Ez egy automatikus regisztrációs visszaigazolás.',
]);

send_email($email, $name, 'CityGuard – Sikeres regisztráció', $html);
json_response(['ok' => true, 'msg' => 'Sikeres regisztráció.'], 201);
<?php // refactor 8
