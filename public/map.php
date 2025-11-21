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
