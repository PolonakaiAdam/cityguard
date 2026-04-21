<?php
/**
 * reset_password.php – Jelszó visszaállítás oldal
 * GET ?token=xxxx
 */
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db.php';
$baseUrl = cg_detect_base_url();

$token = trim($_GET['token'] ?? '');
$valid = false;
$userName = '';

if ($token) {
    $tokenHash = hash('sha256', $token);
    $s = db()->prepare(
        "SELECT u.name FROM password_resets pr JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = ? AND pr.expires_at > NOW() LIMIT 1"
    );
    $s->execute([$tokenHash]);
    $row = $s->fetch();
    if ($row) {
        $valid    = true;
        $userName = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/favicon-16.png" />
  <link rel="shortcut icon" href="favicon.ico" />
  <title>CityGuard – Jelszó visszaállítás</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/public/assets/css/style.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg,#0f172a); }
    .reset-card { background:var(--surface,#1e293b); border-radius:16px; padding:36px 32px; width:100%; max-width:400px; box-shadow:0 8px 32px rgba(0,0,0,.4); }
    .reset-card h2 { margin:0 0 8px; font-size:1.4rem; color:var(--text,#e8edf8); }
    .reset-card p { color:var(--text-dim,#94a3b8); font-size:.9rem; margin:0 0 22px; }
    .field { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
    .field label { font-size:.8rem; font-weight:600; color:var(--text-dim,#94a3b8); letter-spacing:.04em; text-transform:uppercase; }
    .field input { background:var(--input-bg,#0f172a); border:1.5px solid var(--border,#334155); border-radius:8px; color:var(--text,#e8edf8); padding:10px 12px; font-size:.95rem; outline:none; }
    .field input:focus { border-color:#3b82f6; }
    .btn-primary { width:100%; padding:12px; border:none; border-radius:9px; background:#1d4ed8; color:#fff; font-size:1rem; font-weight:700; cursor:pointer; margin-top:8px; }
    .btn-primary:hover { background:#1e40af; }
    #msg { margin-top:14px; font-size:.9rem; min-height:20px; }
    .err { color:#f87171; } .ok { color:#34d399; }
    .invalid-box { text-align:center; padding:20px 0; }
    .invalid-box span { font-size:2.5rem; }
    .invalid-box h3 { color:#f87171; margin:10px 0 6px; }
    .invalid-box p { color:#94a3b8; font-size:.9rem; }
    .invalid-box a { color:#3b82f6; }
  </style>
</head>
<body>
<div class="reset-card">
  <?php if ($valid): ?>
    <h2>🔑 Új jelszó beállítása</h2>
    <p>Üdvözlünk, <strong style="color:var(--text,#e8edf8)"><?= $userName ?></strong>! Add meg az új jelszavadat.</p>

    <div class="field">
      <label>Új jelszó</label>
      <input id="pw1" type="password" placeholder="Legalább 6 karakter" autocomplete="new-password">
    </div>
    <div class="field">
      <label>Jelszó megerősítése</label>
      <input id="pw2" type="password" placeholder="Írd be újra" autocomplete="new-password">
    </div>
    <button class="btn-primary" id="btnReset">Jelszó mentése</button>
    <div id="msg"></div>

    <script>
      function ngrokHeaders(h){ const host = String(window.location.hostname||'').toLowerCase(); return host.includes('ngrok') ? Object.assign({'ngrok-skip-browser-warning':'1'}, h||{}) : (h||{}); }
      const token = <?= json_encode($token) ?>;
      document.getElementById('btnReset').addEventListener('click', async () => {
        const pw1 = document.getElementById('pw1').value;
        const pw2 = document.getElementById('pw2').value;
        const msg = document.getElementById('msg');
        msg.className = ''; msg.textContent = '';

        if (pw1.length < 6) { msg.className='err'; msg.textContent='A jelszónak legalább 6 karakter kell!'; return; }
        if (pw1 !== pw2)    { msg.className='err'; msg.textContent='A két jelszó nem egyezik!'; return; }

        try {
          const r = await fetch('<?= htmlspecialchars($baseUrl) ?>/api/reset_password_action.php', {
            method: 'POST',
            headers: ngrokHeaders({'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With':'XMLHttpRequest'}),
            credentials: 'include',
            body: new URLSearchParams({ token, password: pw1 }).toString()
          });
          const text = await r.text();
          let d = {};
          try { d = JSON.parse(text); } catch(_) { d = { error: text || 'Ismeretlen szerverhiba' }; }
          if (d.ok) {
            msg.className = 'ok';
            msg.textContent = d.msg || 'Jelszó sikeresen módosítva!';
            document.getElementById('btnReset').disabled = true;
            setTimeout(() => { window.location.href = '<?= htmlspecialchars($baseUrl) ?>/public/index.php?reset=success&type=password'; }, 2500);
          } else {
            msg.className = 'err';
            msg.textContent = d.error || 'Hiba történt.';
          }
        } catch(e) {
          msg.className='err'; msg.textContent='Hálózati hiba. Próbáld újra.';
        }
      });
    </script>
  <?php else: ?>
    <div class="invalid-box">
      <span>⚠️</span>
      <h3>Érvénytelen vagy lejárt link</h3>
      <p>Ez a jelszó-visszaállítási link már nem érvényes.<br>
      Kérj új visszaállítót a <a href="<?= htmlspecialchars($baseUrl) ?>/public/index.php">bejelentkezési oldalon</a>.</p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
