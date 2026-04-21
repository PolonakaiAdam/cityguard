<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db.php';
$baseUrl = cg_detect_base_url();

$db = db();
$db->exec("CREATE TABLE IF NOT EXISTS email_change_tokens (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    new_email VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY token_hash (token_hash),
    KEY user_id (user_id),
    CONSTRAINT ect_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$token    = trim($_GET['token'] ?? '');
$valid    = false;
$userName = '';
$newEmail = '';
$oldEmail = '';

if ($token) {
    $tokenHash = hash('sha256', $token);
    $s = $db->prepare(
        "SELECT u.name, u.email AS old_email, ect.new_email
         FROM email_change_tokens ect
         JOIN users u ON u.id = ect.user_id
         WHERE ect.token_hash = ? AND ect.expires_at > NOW() LIMIT 1"
    );
    $s->execute([$tokenHash]);
    $row = $s->fetch();
    if ($row) {
        $valid    = true;
        $userName = htmlspecialchars($row['name'],      ENT_QUOTES, 'UTF-8');
        $oldEmail = htmlspecialchars($row['old_email'], ENT_QUOTES, 'UTF-8');
        $newEmail = htmlspecialchars($row['new_email'], ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="ngrok-skip-browser-warning" content="true">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/favicon-16.png" />
  <link rel="shortcut icon" href="favicon.ico" />
  <title>CityGuard – Email megerősítés</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/public/assets/css/style.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg,#060c1a); }
    .card {
      background: linear-gradient(180deg,rgba(13,20,38,.98),rgba(9,14,28,.98));
      border: 1px solid rgba(148,183,255,.15);
      border-radius: 20px; padding: 40px 36px;
      width: 100%; max-width: 420px;
      box-shadow: 0 24px 64px rgba(0,0,0,.5);
      text-align: center;
    }
    .icon { font-size: 3rem; margin-bottom: 16px; line-height: 1; }
    h2 { margin: 0 0 8px; font-size: 1.35rem; font-weight: 800; color: #e8edf8; }
    .sub { color: #8da0c0; font-size: .88rem; margin: 0 0 28px; line-height: 1.5; }
    .email-box {
      background: rgba(56,189,248,.08);
      border: 1px solid rgba(56,189,248,.25);
      border-radius: 12px; padding: 14px 18px;
      margin-bottom: 24px;
    }
    .email-box .label { font-size: .72rem; font-weight: 700; color: #38bdf8; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 4px; }
    .email-box .value { font-size: 1.05rem; font-weight: 700; color: #e8edf8; word-break: break-all; }
    .arrow { color: #8da0c0; font-size: .8rem; margin: 6px 0; }
    .btn-confirm {
      width: 100%; padding: 14px; border: none; border-radius: 12px;
      background: linear-gradient(135deg,#0891b2,#0369a1);
      color: #fff; font-size: 1rem; font-weight: 800;
      cursor: pointer; font-family: inherit; letter-spacing: .01em;
      box-shadow: 0 4px 16px rgba(8,145,178,.35);
      transition: opacity .15s;
    }
    .btn-confirm:hover { opacity: .9; }
    .btn-confirm:disabled { opacity: .5; cursor: not-allowed; }
    #msg { margin-top: 16px; font-size: .9rem; min-height: 20px; }
    .err { color: #f87171; }
    .ok  { color: #34d399; }
    .invalid-box { text-align:center; padding: 10px 0; }
    .invalid-box .big { font-size: 2.8rem; margin-bottom: 12px; }
    .invalid-box h3 { color: #f87171; margin: 0 0 8px; font-size: 1.15rem; }
    .invalid-box p  { color: #8da0c0; font-size: .88rem; line-height: 1.5; }
    .invalid-box a  { color: #38bdf8; text-decoration: none; }
    .back-link { display: block; margin-top: 18px; font-size: .82rem; color: #8da0c0; text-decoration: none; }
    .back-link:hover { color: #e8edf8; }
  </style>
</head>
<body>
<div class="card">
  <?php if ($valid): ?>

    <div class="icon">📧</div>
    <h2>Email cím megerősítése</h2>
    <p class="sub">Szia, <strong style="color:#e8edf8"><?= $userName ?></strong>!<br>
    Erősítsd meg, hogy erre az email címre szeretnéd cserélni a fiókodat:</p>

    <div class="email-box">
      <div class="label">Jelenlegi email</div>
      <div class="value" style="color:#8da0c0"><?= $oldEmail ?></div>
      <div class="arrow">↓</div>
      <div class="label">Új email</div>
      <div class="value"><?= $newEmail ?></div>
    </div>

    <button class="btn-confirm" id="btnConfirm">✓ Igen, ezt az emailt szeretném</button>
    <div id="msg"></div>
    <a class="back-link" href="<?= htmlspecialchars($baseUrl) ?>/public/">← Vissza a bejelentkezéshez</a>

    <script>
      function ngrokHeaders(h){ const host = String(window.location.hostname||'').toLowerCase(); return host.includes('ngrok') ? Object.assign({'ngrok-skip-browser-warning':'1'}, h||{}) : (h||{}); }
      const token = <?= json_encode($token) ?>;
      const userName = <?= json_encode($row['name']) ?>;
      const newEmail = <?= json_encode($row['new_email']) ?>;

      document.getElementById('btnConfirm').addEventListener('click', async function() {
        const btn = this;
        const msg = document.getElementById('msg');
        btn.disabled = true;
        btn.textContent = 'Megerősítés...';
        msg.className = ''; msg.textContent = '';

        try {
          const r = await fetch('<?= htmlspecialchars($baseUrl) ?>/api/change_email_action.php', {
            method: 'POST',
            headers: ngrokHeaders({'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With':'XMLHttpRequest'}),
            credentials: 'include',
            body: new URLSearchParams({ token, user_name: userName, new_email: newEmail }).toString()
          });
          const text = await r.text();
          let d = {};
          try { d = JSON.parse(text); } catch(_) { d = { error: text || 'Ismeretlen szerverhiba' }; }
          if (d.ok) {
            msg.className = 'ok';
            msg.textContent = '✅ Email sikeresen módosítva! Átirányítás...';
            btn.textContent = '✓ Sikeres!';
            setTimeout(() => { window.location.href = '<?= htmlspecialchars($baseUrl) ?>/public/?reset=success&type=email'; }, 2500);
          } else {
            msg.className = 'err';
            msg.textContent = d.error || 'Hiba történt.';
            btn.disabled = false;
            btn.textContent = '✓ Igen, ezt az emailt szeretném';
          }
        } catch(e) {
          msg.className = 'err';
          msg.textContent = 'Hálózati hiba. Próbáld újra.';
          btn.disabled = false;
          btn.textContent = '✓ Igen, ezt az emailt szeretném';
        }
      });
    </script>

  <?php else: ?>
    <div class="invalid-box">
      <div class="big">⚠️</div>
      <h3>Érvénytelen vagy lejárt link</h3>
      <p>Ez az email-megerősítő link már nem érvényes vagy lejárt.<br>
      Kérj új linket a fiókod beállításainál.</p>
      <a href="<?= htmlspecialchars($baseUrl) ?>/public/">← Vissza a bejelentkezéshez</a>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
