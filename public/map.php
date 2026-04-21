<?php
require_once __DIR__ . '/../app/bootstrap.php';
$appHome = rtrim(cg_detect_base_url(), '/') . '/';
$styleVer = public_asset_version('assets/css/style.css');
$mapJsVer = public_asset_version('assets/js/map.js');
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8" />
  <meta name="ngrok-skip-browser-warning" content="true" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <meta name="theme-color" content="#060c1a" />
  <link rel="manifest" href="assets/manifest.json" />
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/favicon-16.png" />
  <link rel="shortcut icon" href="favicon.ico" />
  <title>Cityguard – Térkép</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo (int)$styleVer; ?>" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    html, body { height: 100%; overflow: hidden; }

    /* Térkép teljes képernyős */
    #map { position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; z-index: 1; }
    .leaflet-bottom { bottom: var(--safe-bottom, 0) !important; }
    .leaflet-control-zoom { margin-top: calc(68px + var(--safe-top)) !important; }

    /* A térképoldal topbarja ugyanúgy viselkedjen, mint a főoldalon */
    .map-topbar {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      z-index: 1000 !important;
    }
    .map-topbar .brand { min-width: 0; flex: 1 1 auto; }
    .map-topbar .topbar-actions { flex-shrink: 0; }

    #mapNav {
      display: none !important;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }

    .map-nav-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      border-radius: 10px;
      background: transparent;
      border: 1px solid transparent;
      color: var(--text-dim);
      font-size: 0.82rem;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.15s;
      white-space: nowrap;
      font-family: inherit;
    }
    .map-nav-btn:hover {
      background: rgba(255,255,255,0.07);
      color: #e8edf8;
      border-color: rgba(148,183,255,0.18);
    }
    .map-nav-btn.active {
      background: rgba(56,189,248,0.12);
      color: #38bdf8;
      border-color: rgba(56,189,248,0.3);
    }
    .map-nav-btn .nav-icon { font-style: normal; font-size: 0.9rem; }

    /* User avatar — megegyezik a főoldalival */
    .map-avatar {
      width: 32px; height: 32px; border-radius: 8px;
      background: linear-gradient(145deg, #1e8fde 0%, #0c4fa8 55%, #0a3580 100%);
      display: flex; align-items: center; justify-content: center;
      font-weight: 900; font-size: 0.78rem; color: #fff;
      flex-shrink: 0; letter-spacing: 0.5px;
      box-shadow: 0 4px 12px rgba(14,165,233,0.35), 0 1px 0 rgba(255,255,255,0.2) inset;
      position: relative; text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    .map-avatar::before {
      content: ''; position: absolute; inset: 0; border-radius: 8px;
      background: linear-gradient(160deg, rgba(255,255,255,0.2) 0%, transparent 55%);
      pointer-events: none;
    }
    #me {
      font-size: 0.82rem; font-weight: 600; color: #e8edf8;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      max-width: 120px;
    }

    #mapMsg {
      position: fixed; bottom: calc(20px + var(--safe-bottom)); left: 50%;
      transform: translateX(-50%);
      background: rgba(6,12,26,0.9); border: 1px solid var(--border);
      backdrop-filter: blur(12px); padding: 8px 16px; border-radius: 20px;
      font-size: 0.82rem; color: var(--text-dim); z-index: 1001;
      white-space: nowrap; display: none;
    }
    #mapMsg:not(:empty) { display: block; }

    /* Mobil menü ugyanúgy nyíljon le felülről, mint a főoldalon */
    #mapNavOverlay {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 998;
      background: rgba(0,0,0,0.55);
      -webkit-backdrop-filter: blur(2px);
      backdrop-filter: blur(2px);
    }
    #mapNavOverlay.visible { display: block; }

    #mapNavDrawer {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1001;
      background: linear-gradient(180deg, rgba(6,10,22,0.99) 0%, rgba(9,15,30,0.98) 100%);
      backdrop-filter: blur(32px) saturate(200%);
      -webkit-backdrop-filter: blur(32px) saturate(200%);
      padding: calc(var(--safe-top) + 68px) 16px calc(18px + var(--safe-bottom));
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      gap: 2px;
      width: 100%;
      transform: translateY(-110%);
      transition: transform 0.32s cubic-bezier(.32,.72,0,1), opacity 0.28s ease;
      opacity: 0;
      pointer-events: none;
      box-shadow: 0 24px 64px rgba(0,0,0,0.7), 0 1px 0 rgba(255,255,255,0.04) inset;
      max-height: 100dvh;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      border-bottom: 1px solid rgba(148,183,255,0.1);
    }
    #mapNavDrawer.nav-open {
      transform: translateY(0);
      opacity: 1;
      pointer-events: auto;
    }
    #mapNavDrawer a.btn-nav {
      justify-content: flex-start !important;
      width: 300px !important;
      max-width: 100%;
    }

    #mapHamburger { display: flex; }
    #mapDrawerUserInfo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      padding: 10px 0 4px;
      width: 300px;
      max-width: 100%;
      margin-top: 8px;
      border-top: 1px solid rgba(148,183,255,0.1);
    }
    #mapDrawerUserAvatar {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, rgba(56,189,248,0.2), rgba(79,70,229,0.25));
      display: flex; align-items: center; justify-content: center;
      font-size: 0.85rem; flex-shrink: 0;
      border: 1px solid rgba(56,189,248,0.2);
      color: #fff;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }
    #mapDrawerUserName {
      font-size: 0.82rem; font-weight: 700; color: #e8edf8;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    #mapDrawerUserRole {
      font-size: 0.72rem; color: #8da0c0; margin-top: 1px;
    }

    @media (max-width: 959px) {
      .map-topbar {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
      }
      #mapNav { display: none !important; }
      #mapLogoutBtnDesktop { display: none !important; }
    }

    @media (min-width: 960px) {
      .map-topbar {
        display: grid !important;
        grid-template-columns: 1fr auto 1fr !important;
        grid-template-rows: 52px !important;
        align-items: center !important;
        padding: 0 20px !important;
        height: 58px !important;
        gap: 0 !important;
      }
      #mapNav { display: flex !important; }
      #mapHamburger,
      #mapNavDrawer,
      #mapNavOverlay { display: none !important; }
      #mapUserBlock {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-right: 18px;
        border-right: 1px solid rgba(148,183,255,0.14);
        margin-right: 6px;
      }
      .map-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        font-size: 0.8rem;
        letter-spacing: 1px;
      }
      .map-avatar::before { border-radius: 10px; }
      #me {
        max-width: 140px;
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        background: linear-gradient(90deg, #ffffff 0%, #b8d8f8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }
    }
  </style>
</head>
<body>

  <!-- Full-screen map -->
  <div id="map"></div>

  <!-- Topbar -->
  <header class="topbar map-topbar">
    <!-- Bal: Brand -->
    <div class="brand">
      <div class="logo logo-image"><img src="assets/icons/favicon-64.png" alt="Cityguard logó" /></div>
      <div class="brand-text">
        <div class="brand-title">Cityguard</div>
        <div class="brand-sub">Városi bejelentő rendszer</div>
      </div>
    </div>

    <!-- Közép: Navigációs gombok (ugyanolyan mint a főoldalon) -->
    <nav id="mapNav" style="display:none;align-items:center;justify-content:center;gap:4px;">
      <a class="btn-nav map-nav-btn" href="<?php echo htmlspecialchars($appHome, ENT_QUOTES, "UTF-8"); ?>?view=newReport&amp;from=external" id="mapNavNewReport" style="display:none;">
        <i class="nav-icon">✦</i><span class="nav-label">Új bejelentés</span>
      </a>
      <a class="btn-nav map-nav-btn" href="<?php echo htmlspecialchars($appHome, ENT_QUOTES, "UTF-8"); ?>?view=reports&amp;from=external" id="mapNavReports">
        <i class="nav-icon">≡</i><span class="nav-label">Bejelentések</span>
      </a>
      <a class="btn-nav map-nav-btn active" href="map.php">
        <i class="nav-icon">◎</i><span class="nav-label">Térkép</span>
      </a>
      <a class="btn-nav map-nav-btn" href="<?php echo htmlspecialchars($appHome, ENT_QUOTES, "UTF-8"); ?>?view=profile&amp;from=external" id="mapNavProfile">
        <i class="nav-icon">◉</i><span class="nav-label">Fiókom</span>
      </a>
      <a class="btn-nav map-nav-btn btn-nav-admin" href="<?php echo htmlspecialchars($appHome, ENT_QUOTES, "UTF-8"); ?>?view=admin&amp;from=external" id="mapNavAdmin" style="display:none;">
        <i class="nav-icon">⊞</i><span class="nav-label">Felhasználók</span>
      </a>
    </nav>

    <!-- Jobb: [U] user · Hamburger (mobile) -->
    <div class="topbar-actions">
      <div id="mapUserBlock" style="display:none;align-items:center;gap:9px;">
        <div class="map-avatar" id="mapAvatar"></div>
        <span id="me"></span>
        <button id="mapLogoutBtnDesktop" type="button" style="display:none;padding:0 12px;height:32px;border-radius:8px;background:rgba(244,63,94,0.15);border:1px solid rgba(244,63,94,0.4);color:#f87171;font-size:0.8rem;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;margin-left:6px;">Kilépés</button>
      </div>
      <button class="hamburger" id="mapHamburger" aria-label="Menü" type="button">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- Mobile nav overlay -->
  <div id="mapNavOverlay"></div>
  <!-- Mobile nav drawer -->
  <nav id="mapNavDrawer">
    <a class="btn-nav" href="<?php echo htmlspecialchars($appHome, ENT_QUOTES, "UTF-8"); ?>?view=newReport&amp;from=external" id="mapDrawerNewReport" style="display:none;">
      <i class="nav-icon">✦</i><span class="nav-label">Új bejelentés</span>
    </a>
    <a class="btn-nav" href="<?php echo htmlspecialchars($appHome, ENT_QUOTES, "UTF-8"); ?>?view=reports&amp;from=external">
      <i class="nav-icon">≡</i><span class="nav-label">Bejelentések</span>
    </a>
    <a class="btn-nav active" href="map.php">
      <i class="nav-icon">◎</i><span class="nav-label">Térkép</span>
    </a>
    <a class="btn-nav" href="<?php echo htmlspecialchars($appHome, ENT_QUOTES, "UTF-8"); ?>?view=profile&amp;from=external">
      <i class="nav-icon">◉</i><span class="nav-label">Fiókom</span>
    </a>
    <a class="btn-nav btn-nav-admin" href="<?php echo htmlspecialchars($appHome, ENT_QUOTES, "UTF-8"); ?>?view=admin&amp;from=external" id="mapDrawerAdmin" style="display:none;">
      <i class="nav-icon">⊞</i><span class="nav-label">Felhasználók</span>
    </a>
    <div id="mapDrawerUserInfo">
      <div id="mapDrawerUserAvatar">?</div>
      <div style="flex:1;min-width:0;">
        <div id="mapDrawerUserName"></div>
        <div id="mapDrawerUserRole"></div>
      </div>
      <button id="mapLogoutBtn" type="button" style="padding:7px 13px;border-radius:8px;background:rgba(244,63,94,0.12);border:1px solid rgba(244,63,94,0.3);color:#f87171;font-size:0.78rem;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;flex-shrink:0;">Kilépés</button>
    </div>
  </nav>

  <p id="mapMsg" class="msg"></p>

  <div id="lightbox" class="lightbox hidden" onclick="closeLightbox()">
    <span class="lightbox-close">&times;</span>
    <img class="lightbox-content" id="lightbox-img" alt="Nagyított kép" />
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>window.CG_MAP_API_BASE = <?php echo json_encode(cg_api_url()); ?>;
window.CG_IMG_BASE = <?php echo json_encode(rtrim(cg_detect_base_url(), '/') . '/public/uploads/evidence/'); ?>;
window.CG_PROFILE_IMG_BASE = <?php echo json_encode(rtrim(cg_detect_base_url(), '/') . '/public/uploads/profiles/'); ?>;
window.CG_APP_HOME = <?php echo json_encode($appHome); ?>;</script>
  <script src="assets/js/map.js?v=<?php echo (int)$mapJsVer; ?>"></script>
</body>
</html>
