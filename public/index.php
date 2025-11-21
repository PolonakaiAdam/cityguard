<?php
require_once __DIR__ . '/../app/bootstrap.php';
$styleVer = public_asset_version('assets/css/style.css');
$appJsVer = public_asset_version('assets/js/app.js');
?>
<!--
  CityGuard főoldal
  Ez a fájl csak a felületet rajzolja ki.
  A kattintások utáni adatkezelést a public/assets/js/app.js végzi,
  a mentést / olvasást pedig az api/ mappában lévő végpontok.
-->
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8" />
  <meta name="ngrok-skip-browser-warning" content="true" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <meta name="apple-mobile-web-app-title" content="Cityguard" />
  <meta name="theme-color" content="#060c1a" />
  <link rel="manifest" href="assets/manifest.json" />
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/favicon-16.png" />
  <link rel="shortcut icon" href="favicon.ico" />
  <title>Cityguard</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo (int)$styleVer; ?>" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>

<!-- ─── HEADER ─────────────────────────────────────────────── -->
<header class="topbar">
  <div class="brand">
    <div class="logo logo-image" id="topbarLogo"><img src="assets/icons/favicon-64.png" alt="Cityguard logó" /></div>
    <div class="brand-text">
      <div class="brand-title">Cityguard</div>
      <div class="brand-sub">Városi bejelentő rendszer</div>
    </div>
  </div>

  <!-- ─── NAVIGATION (desktop: middle column / mobile: drawer) ── -->
  <nav id="mainNav" class="hidden">
    <button class="btn-nav active" data-view="newReport" id="navNewReport" type="button">
      <i class="nav-icon">✦</i><span class="nav-label">Új bejelentés</span>
    </button>
    <button class="btn-nav" data-view="reports" id="navReports" type="button">
      <i class="nav-icon">≡</i><span class="nav-label">Bejelentések</span>
    </button>
    <a class="btn-nav" href="map.php" id="navMap">
      <i class="nav-icon">◎</i><span class="nav-label">Térkép</span>
    </a>
    <button class="btn-nav hidden" data-view="municipality" id="navMunicipality" type="button">
      <i class="nav-icon">▦</i><span class="nav-label">Áttekintés</span>
    </button>
    <button class="btn-nav" data-view="profile" id="navProfile" type="button">
      <i class="nav-icon">◉</i><span class="nav-label">Fiókom</span>
    </button>
    <div class="nav-divider hidden" id="navAdminDivider"></div>
    <button class="btn-nav btn-nav-admin hidden" data-view="admin" id="navAdmin" type="button">
      <i class="nav-icon">⊞</i><span class="nav-label">Felhasználók</span>
    </button>
    <button class="btn-nav btn-nav-admin hidden" data-view="messages" id="navMessages" type="button">
      <i class="nav-icon">◈</i><span class="nav-label">Kérések <span id="adminBadge" class="hidden" style="background:#ef4444;color:#fff;border-radius:999px;padding:1px 7px;font-size:0.65rem;margin-left:2px;font-weight:800;vertical-align:middle;">0</span></span>
    </button>
    <div class="nav-divider" style="margin-top:8px;"></div>
    <!-- Mobil: felhasználói info + kilépés a drawer alján -->
    <div id="navUserInfo" style="display:flex;align-items:center;justify-content:center;gap:12px;padding:10px 0 4px;width:300px;">
      <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,rgba(56,189,248,0.2),rgba(79,70,229,0.25));display:flex;align-items:center;justify-content:center;font-size:0.85rem;flex-shrink:0;border:1px solid rgba(56,189,248,0.2);" id="navUserAvatar">?</div>
      <div style="flex:1;min-width:0;">
        <div id="navUserName" style="font-size:0.82rem;font-weight:700;color:#e8edf8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
        <div id="navUserRole" style="font-size:0.72rem;color:#8da0c0;margin-top:1px;"></div>
      </div>
      <button id="btnLogoutMobile" type="button" style="padding:7px 13px;border-radius:8px;background:rgba(244,63,94,0.12);border:1px solid rgba(244,63,94,0.3);color:#f87171;font-size:0.78rem;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;flex-shrink:0;">Kilépés</button>
    </div>
  </nav>

  <div class="topbar-actions">
    <!-- Nincs bejelentkezve szöveg (csak kijelentkezve látható) -->
    <span id="notLoggedIn" style="font-size:0.78rem;color:var(--text-dim);font-weight:500;display:none;">Nincs bejelentkezve</span>
    <!-- Avatar + név (csak bejelentkezve látható) -->
    <div id="userInfoBlock" style="display:none;align-items:center;gap:8px;">
      <div id="topbarAvatar" style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#0ea5e9,#1e3a8a);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.78rem;color:#fff;flex-shrink:0;letter-spacing:0.5px;"></div>
      <span id="me" style="font-size:0.82rem;font-weight:600;color:#e8edf8;white-space:nowrap;"></span>
    </div>
    <button id="btnLogout" class="hidden" type="button" style="padding:0 14px;height:32px;border-radius:8px;background:rgba(244,63,94,0.15);border:1px solid rgba(244,63,94,0.4);color:#f87171;font-size:0.8rem;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;">Kilépés</button>
    <button class="hamburger hidden" id="hamburgerBtn" aria-label="Menü" type="button">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<!-- Nav háttér overlay -->
<div id="navOverlay"></div>

<main class="container">

  <!-- ─── BELÉPÉS / REGISZTRÁCIÓ ─────────────────────────────── -->
  <section id="authWrap" class="auth-wrap">

    <!-- BELÉPÉSI KÁRTYA -->
    <section id="loginCard" class="auth-card">
      <h2>Belépés</h2>
      <p class="muted">Üdvözöl a Cityguard – bejelentkezz a folytatáshoz.</p>
      <label>E-mail cím vagy felhasználónév</label>
      <input id="loginEmail" type="text" placeholder="email vagy pl. admin" autocomplete="username" />
      <label>Jelszó</label>
      <div style="position:relative">
        <input id="loginPassword" type="password" placeholder="••••••••" autocomplete="current-password" style="padding-right:48px" />
        <button type="button" onclick="togglePw('loginPassword',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-dim);font-size:1.1rem;min-height:auto;padding:4px;line-height:1">👁</button>
      </div>
      <button id="btnLogin" class="btn btn-primary w100" type="button">Belépés</button>
      <p id="loginMsg" class="msg"></p>
      <div class="switch">
        <span>Még nincs fiókod?</span>
        <button id="toRegister" class="link-btn" type="button">Regisztrálok</button>
      </div>
    </section>

    <!-- REGISZTRÁCIÓS KÁRTYA -->
    <section id="registerCard" class="auth-card hidden">
      <h2>Regisztráció</h2>
      <p class="muted">Hozz létre fiókot – pár másodperc az egész.</p>
      <label>Teljes név</label>
      <input id="regName" placeholder="pl. Kovács Anna" autocomplete="name" />
      <label>E-mail cím</label>
      <input id="regEmail" type="email" placeholder="valaki@email.hu" autocomplete="email" />
      <label>Jelszó <span class="muted small">(min. 8 karakter)</span></label>
      <div style="position:relative">
        <input id="regPassword" type="password" placeholder="••••••••" autocomplete="new-password" style="padding-right:48px" />
        <button type="button" onclick="togglePw('regPassword',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-dim);font-size:1.1rem;min-height:auto;padding:4px;line-height:1">👁</button>
      </div>

      <div class="privacy-check">
        <label>
          <input type="checkbox" id="privacyConsent" />
          <span>
            Elolvastam és elfogadom az
            <button type="button" class="link-btn" id="openPrivacy">Adatkezelési tájékoztatót</button>.
            Tudomásul veszem, hogy nevem és e-mail címem a rendszerben tárolásra kerül.
          </span>
        </label>
      </div>

      <button id="btnRegister" class="btn btn-primary w100" type="button">Fiók létrehozása</button>
      <p id="regMsg" class="msg"></p>
      <div class="switch">
        <span>Van már fiókod?</span>
        <button id="toLogin" class="link-btn" type="button">Belépek</button>
      </div>
    </section>

  </section><!-- /authWrap -->

  <!-- ─── ÚJ BEJELENTÉS (csak citizen) ────────────────────────── -->
  <section id="newReportCard" class="card hidden">
    <div class="card-head">
      <h2>Új bejelentés</h2>
      <span class="badge" id="roleBadge">—</span>
    </div>

    <div>
      <label>Kategória</label>
      <select id="categorySelect" style="border-radius:12px;"></select>
    </div>

    <div style="margin-top:18px;">
      <label>Bejelentés tárgya</label>
      <input id="reportTitle" placeholder="pl. Nagy kátyú a Petőfi utcában" />
    </div>

    <div style="margin-top:18px;">
      <label>Leírás / megjegyzés <span class="muted small" style="font-weight:400;text-transform:none;letter-spacing:0;">(opcionális)</span></label>
      <textarea id="reportComment" placeholder="Írd le részletesebben mi történt, mikor észlelted, mi okozza a problémát…" style="min-height:88px;resize:vertical;border-radius:12px;font-family:inherit;font-size:0.9rem;line-height:1.5;"></textarea>
    </div>

    <div class="evidence-section" style="margin-top:18px;">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
        <div>
          <div class="inner-card-title" style="margin-bottom:2px;">Bizonyíték fotók</div>
          <p class="muted small" style="margin:0;">JPG, PNG, WEBP – max. 10 MB/kép</p>
        </div>
        <label class="btn-evidence-upload" id="evidenceLabel" style="cursor:pointer;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span id="evidenceLabelText">Kép hozzáadása</span>
          <input type="file" id="evidence" name="evidence" accept="image/*" multiple style="display:none;" />
        </label>
      </div>
      <div id="evidenceDropZone" class="evidence-drop-zone">
        <div id="evidenceEmpty" class="evidence-empty-state">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:rgba(148,183,255,0.3);margin-bottom:8px;"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          <div style="font-size:0.8rem;color:rgba(148,183,255,0.4);font-weight:600;">Még nincs kép hozzáadva</div>
          <div style="font-size:0.72rem;color:rgba(148,183,255,0.25);margin-top:3px;">Kattints a „Kép hozzáadása" gombra</div>
        </div>
        <div id="evidencePreview" class="evidence-preview-grid"></div>
      </div>
    </div>

    <div class="inner-card">
      <div class="inner-card-title">Helymeghatározás <span style="color:#f87171;font-size:0.9em;">*</span></div>
      <p class="muted small" style="margin-bottom:4px;">Válaszd ki, hogyan adod meg a helyszínt:</p>
      <div class="location-method-row">
        <label class="location-method-btn">
          <input type="radio" name="locationMethod" value="gps" checked />
          📍 GPS – aktuális pozíció
        </label>
        <label class="location-method-btn">
          <input type="radio" name="locationMethod" value="map" />
          🗺️ Térképen jelölöm
        </label>
      </div>

      <div id="gpsSection">
        <div class="gps-status-row">
          <span id="gpsStatus" class="muted small">Nincs megadva – kattints a GPS gombra vagy jelöld a térképen.</span>
          <button id="btnGetGps" class="btn btn-soft" type="button" style="min-height:36px;padding:8px 16px;font-size:0.83rem;">📍 GPS lekérése</button>
        </div>
      </div>

      <div id="mapSection" style="margin-top:14px;">
        <div id="reportMap" style="height:260px;border-radius:12px;overflow:hidden;border:1px solid var(--border);"></div>
        <p class="muted small" style="margin:8px 0 0;">GPS után a jelölő automatikusan megjelenik. Térképen jelölés módban kattints a pontos helyre (a jelölő húzható is).</p>
        <span id="mapStatus" class="muted small"></span>
      </div>
    </div>

    <button id="btnCreateReport" class="btn btn-primary w100" type="button" style="margin-top:22px;">
      Bejelentés elküldése →
    </button>
    <p id="createMsg" class="msg"></p>
  </section>

  <!-- ─── BEJELENTÉSEK LISTÁJA ─────────────────────────────────── -->
  <section id="reportsCard" class="card hidden">
    <div class="section-header">
      <div>
        <h2 id="reportsTitle">Bejelentések</h2>
        <div id="reportsSubtitle" class="muted small" style="margin-top:2px"></div>
      </div>
      <button id="btnLoad" class="btn btn-soft" type="button" style="min-height:38px;padding:8px 16px;font-size:0.83rem;">↺ Frissítés</button>
    </div>
    <!-- Státusz szűrő gombok -->
    <div id="statusFilterBtns" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
      <button class="status-filter-btn sfb-active" data-status="" style="flex:1;min-width:70px;"><span class="sfb-count" id="sfbAll">0</span><span class="sfb-label">Összes</span></button>
      <button class="status-filter-btn sfb-new" data-status="new" style="flex:1;min-width:70px;"><span class="sfb-count" id="sfbNew">0</span><span class="sfb-label">Új</span></button>
      <button class="status-filter-btn sfb-prog" data-status="in_progress" style="flex:1;min-width:70px;"><span class="sfb-count" id="sfbProg">0</span><span class="sfb-label">Folyamatban</span></button>
      <button class="status-filter-btn sfb-ok" data-status="resolved" style="flex:1;min-width:70px;"><span class="sfb-count" id="sfbOk">0</span><span class="sfb-label">Megoldva</span></button>
      <button class="status-filter-btn sfb-rej" data-status="rejected" style="flex:1;min-width:70px;"><span class="sfb-count" id="sfbRej">0</span><span class="sfb-label">Elutasítva</span></button>
    </div>
    <div id="reportsStats"></div>
    <div id="reportsAlerts" style="margin-top:12px;"></div>
    <ul id="list" class="list" style="margin-top:16px;"></ul>
  </section>

  <!-- ─── PROFIL (csak user) ───────────────────────────────────── -->
  <section id="profileCard" class="card hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:4px;">
      <h2 style="margin:0;">Fiókom</h2>
      <span id="profileRoleBadge" style="font-size:0.75rem;font-weight:700;padding:4px 12px;border-radius:999px;background:rgba(56,189,248,0.12);border:1px solid rgba(56,189,248,0.3);color:#7dd3fc;font-family:'DM Mono',monospace;">—</span>
    </div>

    <div class="profile-section-label">Profilkép</div>
    <div class="profile-avatar-card">
      <div id="profileAvatarPreview" class="profile-avatar-preview">?</div>
      <div class="profile-avatar-actions">
        <p class="muted small" style="margin:0 0 10px;">Minden felhasználó feltölthet saját profilképet. Engedélyezett formátumok: JPG, PNG, WEBP.</p>
        <input id="profileAvatarFile" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" />
        <div class="profile-upload-row">
          <button id="btnUploadProfileImage" class="btn btn-soft" type="button">Profilkép feltöltése</button>
          <button id="btnRemoveProfileImage" class="btn btn-soft" type="button">Profilkép törlése</button>
        </div>
        <p id="profileAvatarMsg" class="msg"></p>
      </div>
