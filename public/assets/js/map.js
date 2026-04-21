// CityGuard – térképes nézet logikája
// Ez a fájl csak a térképes oldalhoz kell.

// ════════════════════════════════════════════════════════════════════════
// CityGuard – map.js
// A nyilvános térkép oldal logikája (map.php).
// Betölti a bejelentéseket, megjeleníti a térképen, GPS navigációt biztosít.
// ════════════════════════════════════════════════════════════════════════

// API végpont alap URL-je
const API_BASE = (typeof window.CG_MAP_API_BASE !== "undefined") ? window.CG_MAP_API_BASE : "../api/"
const PROFILE_IMG_BASE = (typeof window.CG_PROFILE_IMG_BASE !== "undefined") ? window.CG_PROFILE_IMG_BASE : "../uploads/profiles/"

// ngrok esetén szükséges speciális fejléc hozzáadása a kérésekhez
function addNgrokHeaders(opt) {
  opt = opt || {}
  opt.headers = opt.headers || {}
  const host = String(window.location.hostname || '').toLowerCase()
  if (host.includes('ngrok')) opt.headers['ngrok-skip-browser-warning'] = '1'
  return opt
}

const MLY_TOKEN = 'MLY|25519454501062753|c9c3865db9c4924e78ac7f4268810866'

// ────────────────────────────────────────────────────────────────────────
// SEGÉDFÜGGVÉNYEK – képnézegető, HTML escape, API hívó
// ────────────────────────────────────────────────────────────────────────
// Képnézegető megnyitása/bezárása
function openLightbox(src) {
  const lb = document.getElementById("lightbox"), img = document.getElementById("lightbox-img")
  if (lb && img) { img.src = src; lb.classList.remove("hidden") }
}
function closeLightbox() { document.getElementById("lightbox")?.classList.add("hidden") }
document.addEventListener("keydown", e => { if (e.key === "Escape") { closeLightbox() } })

const STATUSZ_HU = { new:"Új", in_progress:"Folyamatban", resolved:"Megoldva", rejected:"Elutasítva" }
const SZEREPKOR_HU = { admin:"Adminisztrátor", staff:"Ügyintéző", citizen:"Lakos", municipality:"Önkormányzati tag" }
const statusHu = s => STATUSZ_HU[s] ?? s
const roleHu = r => SZEREPKOR_HU[r] ?? r
const $ = sel => document.querySelector(sel)

// Általános API hívó (egyszerűsített változat, csak JSON-t kezel)
async function api(path, method="GET", body=null) {
  const opt = addNgrokHeaders({ method, credentials: 'include', headers:{} })
  if (body) { opt.headers["Content-Type"]="application/json"; opt.body=JSON.stringify(body) }
  const res = await fetch(API_BASE+path, opt)
  const data = await res.json().catch(()=>({}))
  if (!res.ok) throw data
  return data
}
function setText(el, t="") { if(el) el.textContent=t||"" }
function escapeHtml(s) {
  return String(s??"").replaceAll("&","&amp;").replaceAll("<","&lt;")
    .replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;")
}
function markerIcon(status) {
  const cls = {new:"m-new",in_progress:"m-prog",resolved:"m-ok",rejected:"m-rej"}[status]||"m-prog"
  return L.divIcon({ className:`cg-marker ${cls}`, html:`<div class="pin"></div>`, iconSize:[18,18], iconAnchor:[9,9] })
}
function profileImageUrl(fileName) {
  return fileName ? PROFILE_IMG_BASE + encodeURIComponent(fileName).replace(/%2F/g, "/") : ""
}
function renderUserAvatar(el, user) {
  if (!el) return
  const initials = String(user?.name || "?").trim().split(/\s+/).filter(Boolean).map(w => w[0]).join("").slice(0,2).toUpperCase() || "?"
  const imageUrl = profileImageUrl(user?.profile_image)
  el.textContent = imageUrl ? "" : initials
  el.style.backgroundImage = imageUrl ? `url("${imageUrl}")` : ""
  el.style.backgroundSize = "cover"
  el.style.backgroundPosition = "center"
  el.style.backgroundRepeat = "no-repeat"
}

// ────────────────────────────────────────────────────────────────────────
// INICIALIZÁLÁS – oldal betöltésekor fut le
// 1. Ellenőrzi bejelentkezést (ha nincs, visszairányít a főoldalra)
// 2. Felállítja a navigációt és a térképet
// 3. Betölti a bejelentéseket és elhelyezi őket a térképen
// ────────────────────────────────────────────────────────────────────────
async function init() {
  const me = await api("auth_me.php")
  if (!me.user) { location.href = (window.CG_APP_HOME || "./"); return }
  setText($("#me"), me.user.name)
  setText($("#mapDrawerUserName"), me.user.name)
  setText($("#mapDrawerUserRole"), roleHu(me.user.role))

  const av = $("#mapAvatar")
  renderUserAvatar(av, me.user)
  renderUserAvatar($("#mapDrawerUserAvatar"), me.user)

  const ub = $("#mapUserBlock")
  if (ub) ub.style.display = "flex"

  // A desktop navigáció megjelenítését a CSS intézi.
  // Mobilon NEM szabad inline flexre állítani, mert rácsúszik a topbarra.

  // Admin gomb megjelenítése ha az admin jogosultsága van
  // Show admin buttons if user is admin
  if (me.user.role === "admin" || me.user.is_admin) {
    const adminBtn = document.getElementById("mapNavAdmin")
    if (adminBtn) adminBtn.style.display = "inline-flex"
    const adminDrawerBtn = document.getElementById("mapDrawerAdmin")
    if (adminDrawerBtn) adminDrawerBtn.style.display = "flex"
  }

  // "Új bejelentés" gomb csak citizen és staff számára
  // Show "Új bejelentés" for citizen and staff only
  const role = me.user.role
  if (role === "citizen" || role === "staff") {
    const newRepBtn = document.getElementById("mapNavNewReport")
    if (newRepBtn) newRepBtn.style.display = "inline-flex"
    const newRepDrawerBtn = document.getElementById("mapDrawerNewReport")
    if (newRepDrawerBtn) newRepDrawerBtn.style.display = "flex"
  }

  // Mobilos hamburger menü logika
  // Mobile hamburger logic
  const ham = document.getElementById("mapHamburger")
  const drawer = document.getElementById("mapNavDrawer")
  const overlay = document.getElementById("mapNavOverlay")
  if (ham && drawer) {
    ham.addEventListener("click", () => {
      drawer.classList.toggle("nav-open")
      ham.classList.toggle("open")
      if (overlay) overlay.classList.toggle("visible")
    })
    if (overlay) overlay.addEventListener("click", () => {
      drawer.classList.remove("nav-open")
      ham.classList.remove("open")
      overlay.classList.remove("visible")
    })
  }

  document.querySelectorAll("#mapNavDrawer a.btn-nav").forEach(link => {
    link.addEventListener("click", () => {
      drawer?.classList.remove("nav-open")
      ham?.classList.remove("open")
      overlay?.classList.remove("visible")
    })
  })
  // Kijelentkezés a térkép oldalról
  // Logout from map page
  // Logout - both mobile drawer and desktop button
  async function doLogout() {
    try { await api("auth_logout.php", "POST", {}) } catch(e) {}
    location.href = (window.CG_APP_HOME || "./")
  }
  document.getElementById("mapLogoutBtn")?.addEventListener("click", doLogout)
  const desktopLogout = document.getElementById("mapLogoutBtnDesktop")
  if (desktopLogout) {
    desktopLogout.style.display = "inline-block"
    desktopLogout.addEventListener("click", doLogout)
  }

  // ────────────────────────────────────────────────────────────────────────
  // TÉRKÉP INICIALIZÁLÁS (Leaflet)
  // ────────────────────────────────────────────────────────────────────────
  // ── Térkép ───────────────────────────────────────────────
  const map = L.map("map", {
    zoomControl:false, minZoom:4, maxZoom:21,
    maxBounds:[[-60,-180],[75,180]], maxBoundsViscosity:1.0, worldCopyJump:false
  }).setView([47.4979,19.0402],13)

  L.control.zoom({position:"topleft"}).addTo(map)

  // Domborzati réteg (csak a rétegváltóban használjuk)
  const topoLayer = L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png",{maxZoom:17,attribution:"&copy; OpenTopoMap"})

  document.head.insertAdjacentHTML("beforeend",`<style>
    .cg-marker .pin{width:16px;height:16px;border-radius:999px;border:2.5px solid rgba(255,255,255,.9);box-shadow:0 2px 8px rgba(0,0,0,.4)}
    .cg-marker.m-new .pin{background:#f59e0b}.cg-marker.m-prog .pin{background:#0ea5e9}
    .cg-marker.m-ok .pin{background:#22c55e}.cg-marker.m-rej .pin{background:#ef4444}
  </style>`)

  // ────────────────────────────────────────────────────────────────────────
  // RÉTEGVÁLTÓ – Google Maps stílusú panel (jobb alul)
  // Típusok: Alap, Műhold, Domborzat | Stílusok: Smooth, Sötét, Szabadtéri stb.
  // ────────────────────────────────────────────────────────────────────────
  // ── Rétegváltó ──────────────────────────────────────────
  // ── Google Maps stílusú rétegváltó ─────────────────────
  const STADIA = 'bca32bab-2628-4306-a25b-8fa429a5d10b'
  let activeLayer = null

  const stadiaLayers = {
    smooth:     L.tileLayer(`https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}.png?api_key=${STADIA}`,{maxZoom:20,attribution:"© Stadia Maps"}),
    dark:       L.tileLayer(`https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}.png?api_key=${STADIA}`,{maxZoom:20,attribution:"© Stadia Maps"}),
    satellite:  L.tileLayer(`https://tiles.stadiamaps.com/tiles/alidade_satellite/{z}/{x}/{y}.png?api_key=${STADIA}`,{maxZoom:20,attribution:"© Stadia Maps"}),
    bright:     L.tileLayer(`https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}.png?api_key=${STADIA}`,{maxZoom:20,attribution:"© Stadia Maps"}),
    outdoors:   L.tileLayer(`https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}.png?api_key=${STADIA}`,{maxZoom:20,attribution:"© Stadia Maps"}),
    toner:      L.tileLayer(`https://tiles.stadiamaps.com/tiles/stamen_toner/{z}/{x}/{y}.png?api_key=${STADIA}`,{maxZoom:20,attribution:"© Stadia Maps"}),
    watercolor: L.tileLayer(`https://tiles.stadiamaps.com/tiles/stamen_watercolor/{z}/{x}/{y}.jpg?api_key=${STADIA}`,{maxZoom:18,attribution:"© Stadia Maps"}),
  }

  activeLayer = stadiaLayers.bright
  stadiaLayers.bright.addTo(map)

  const gSatBase   = L.tileLayer("https://mt{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}",{maxZoom:21,subdomains:["0","1","2","3"],attribution:"© Google"})
  const gSatLabels = L.tileLayer("https://mt{s}.google.com/vt/lyrs=h&x={x}&y={y}&z={z}",{maxZoom:21,subdomains:["0","1","2","3"]})
  const gSatLayer  = L.layerGroup([gSatBase,gSatLabels])

  document.head.insertAdjacentHTML("beforeend",`<style>
    #gmBtn{position:fixed;bottom:calc(36px + var(--safe-bottom,0px));right:16px;z-index:1001;
      width:54px;height:54px;border-radius:8px;background:#fff;
      box-shadow:0 2px 10px rgba(0,0,0,.35);display:flex;flex-direction:column;
      align-items:center;justify-content:center;cursor:pointer;gap:3px;border:none;font-family:inherit;}
    #gmBtn:hover{box-shadow:0 4px 16px rgba(0,0,0,.4);}
    #gmBtn .gm-th{width:38px;height:28px;border-radius:4px;background-size:cover;background-position:center;border:2px solid rgba(0,0,0,.15);}
    #gmBtn .gm-lb{font-size:.6rem;font-weight:600;color:#333;line-height:1;}
    #gmPanel{position:fixed;bottom:calc(100px + var(--safe-bottom,0px));right:16px;z-index:1002;
      background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.3);
      width:320px;max-width:calc(100vw - 32px);display:none;flex-direction:column;overflow:hidden;}
    #gmPanel.on{display:flex;}
    .gm-head{display:flex;align-items:center;justify-content:space-between;padding:12px 16px 8px;border-bottom:1px solid #e8eaed;}
    .gm-head span{font-size:.85rem;font-weight:700;color:#202124;}
    .gm-x{width:28px;height:28px;border-radius:50%;border:none;background:#f1f3f4;cursor:pointer;font-size:.9rem;color:#5f6368;display:flex;align-items:center;justify-content:center;}
    .gm-sec{padding:10px 16px;}
    .gm-sec-t{font-size:.72rem;font-weight:700;color:#5f6368;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
    .gm-grid{display:flex;flex-wrap:wrap;gap:8px;}
    .gm-item{display:flex;flex-direction:column;align-items:center;gap:4px;cursor:pointer;width:68px;}
    .gm-thumb{width:64px;height:48px;border-radius:8px;background-size:cover;background-position:center;background-color:#e8eaed;border:3px solid transparent;transition:border-color .15s;}
    .gm-item.on .gm-thumb{border-color:#1a73e8;box-shadow:0 0 0 1px #1a73e8;}
    .gm-lbl{font-size:.68rem;font-weight:500;color:#202124;text-align:center;line-height:1.2;}
    .gm-item.on .gm-lbl{color:#1a73e8;font-weight:700;}
    .gm-div{height:1px;background:#e8eaed;margin:0 16px;}
  </style>`)

  const gmBtn = document.createElement("button"); gmBtn.id="gmBtn"
  gmBtn.innerHTML = `<div class="gm-th" style="background-image:url('https://tiles.stadiamaps.com/tiles/alidade_smooth/14/9258/5724.png?api_key=${STADIA}')"></div><span class="gm-lb">Rétegek</span>`
  document.body.appendChild(gmBtn)

  const gmPanel = document.createElement("div"); gmPanel.id="gmPanel"
  gmPanel.innerHTML = `
    <div class="gm-head"><span>Térkép részletei</span><button class="gm-x" id="gmX">✕</button></div>
    <div class="gm-sec"><div class="gm-sec-t">Térkép típusa</div><div class="gm-grid" id="gmTypes"></div></div>
    <div class="gm-div"></div>
    <div class="gm-sec"><div class="gm-sec-t">Stílus</div><div class="gm-grid" id="gmStyles"></div></div>`
  document.body.appendChild(gmPanel)

  gmBtn.onclick = () => gmPanel.classList.toggle("on")
  document.getElementById("gmX").onclick = () => gmPanel.classList.remove("on")
  document.addEventListener("click", e => {
    if (!gmPanel.contains(e.target) && e.target!==gmBtn && !gmBtn.contains(e.target))
      gmPanel.classList.remove("on")
  })

  function switchLayer(newLayer) {
    if (activeLayer===newLayer) return
    map.removeLayer(activeLayer); map.addLayer(newLayer); activeLayer=newLayer
  }

  // Minden grid-elem klikkelésekor a másik grid összes elemén törlődik a kiemelés
  function makeGrid(containerId, items, activeId, siblingGridId) {
    const grid = document.getElementById(containerId)
    items.forEach(item => {
      const el = document.createElement("div"); el.className="gm-item"+(item.id===activeId?" on":"")
      el.innerHTML = `<div class="gm-thumb" style="background-image:url('${item.thumb}')"></div><div class="gm-lbl">${item.label}</div>`
      el.onclick = () => {
        grid.querySelectorAll(".gm-item").forEach(i=>i.classList.remove("on"))
        el.classList.add("on")
        // A másik grid összes eleméről levesszük a kiemelést
        if (siblingGridId) {
          const sib = document.getElementById(siblingGridId)
          if (sib) sib.querySelectorAll(".gm-item").forEach(i=>i.classList.remove("on"))
        }
        switchLayer(item.layer); gmPanel.classList.remove("on")
      }
      grid.appendChild(el)
    })
  }

  makeGrid("gmTypes", [
    {id:"default",   label:"Alapértelmezett", layer:stadiaLayers.bright,    thumb:`https://tiles.stadiamaps.com/tiles/osm_bright/10/575/361.png?api_key=${STADIA}`},
    {id:"satellite", label:"Műhold",          layer:gSatLayer,              thumb:"https://mt0.google.com/vt/lyrs=s&x=575&y=361&z=10"},
    {id:"topo",      label:"Domborzat",        layer:topoLayer,              thumb:"https://tile.opentopomap.org/10/575/361.png"},
  ], "default", "gmStyles")

  makeGrid("gmStyles", [
    {id:"smooth",     label:"Smooth",     layer:stadiaLayers.smooth,     thumb:`https://tiles.stadiamaps.com/tiles/alidade_smooth/14/9258/5724.png?api_key=${STADIA}`},
    {id:"dark",       label:"Sötét",       layer:stadiaLayers.dark,       thumb:`https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/14/9258/5724.png?api_key=${STADIA}`},
    {id:"outdoors",   label:"Szabadtéri",  layer:stadiaLayers.outdoors,   thumb:`https://tiles.stadiamaps.com/tiles/outdoors/14/9258/5724.png?api_key=${STADIA}`},
    {id:"toner",      label:"Toner",       layer:stadiaLayers.toner,      thumb:`https://tiles.stadiamaps.com/tiles/stamen_toner/14/9258/5724.png?api_key=${STADIA}`},
    {id:"watercolor", label:"Akvarell",    layer:stadiaLayers.watercolor, thumb:`https://tiles.stadiamaps.com/tiles/stamen_watercolor/14/9258/5724.jpg?api_key=${STADIA}`},
  ], null, "gmTypes")


    // ────────────────────────────────────────────────────────────────────────
    // BEJELENTÉSEK BETÖLTÉSE ÉS MEGJELENÍTÉSE A TÉRKÉPEN
    // ────────────────────────────────────────────────────────────────────────
    // ── Bejelentések ─────────────────────────────────────────
  try{
    const data=await api("reports_geo_list.php")
    const items=data.items??[]
    if(!items.length){setText($("#mapMsg"),"Nincs megjeleníthető bejelentés.");return}
    const bounds=[]
    items.forEach(r=>{
      const lat=Number(r.latitude),lng=Number(r.longitude)
      if(!Number.isFinite(lat)||!Number.isFinite(lng)) return
      bounds.push([lat,lng])
            const evImgs=r.evidence_image?r.evidence_image.split(',').map(s=>s.trim()).filter(Boolean):[]
      const imgBase = (typeof window.CG_IMG_BASE !== 'undefined') ? window.CG_IMG_BASE : '../uploads/evidence/'
      const ev=evImgs.length?`<br/>${evImgs.map(img=>`<img src="${imgBase}${escapeHtml(img)}" style="max-width:100%;max-height:100px;border-radius:4px;margin-top:8px;margin-right:4px;cursor:pointer" onclick="openLightbox(this.src)"/>`).join('')}`:""
      // Navigáció: ha van saját pozíció, abból indul, különben Google Maps dönti el
      const navUrl=`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`
      const navBtn=`<br/><a href="javascript:void(0)" onclick="openNavigation(${lat},${lng})" style="display:inline-block;margin-top:8px;padding:6px 14px;background:#1a73e8;color:#fff;border-radius:8px;text-decoration:none;font-size:.8rem;font-weight:700">🧭 Navigáció</a>`
      L.marker([lat,lng],{icon:markerIcon(r.status)}).addTo(map)
        .bindPopup(`<b>#${r.id} – ${escapeHtml(r.title)}</b><br/>Állapot: <b>${escapeHtml(statusHu(r.status))}</b><br/>${escapeHtml(r.category)}<br/>${escapeHtml(r.created_by)}<br/><small style="color:#64748b">${escapeHtml(r.created_at)}</small>${ev}${navBtn}`)
    })
    if(bounds.length) map.fitBounds(bounds,{padding:[40,40]})
  }catch(e){setText($("#mapMsg"),e.error||"Hiba")}

  // Automatikus frissítés: 10 másodpercenként
  // Automatikus frissítés 10mp-enként (új bejelentések)
  setInterval(async()=>{
    try{ await api("reports_geo_list.php") }catch(e){}
  },10000)

  // GPS indítása: watchPosition folyamatosan frissíti a pozíciót
// GPS indítása
  // GPS indítása és saját pozíció megjelenítése
  startGPS(map)
}

// ────────────────────────────────────────────────────────────────────────
// GPS ÉS NAVIGÁCIÓ
// ────────────────────────────────────────────────────────────────────────
// ── GPS & Navigáció ───────────────────────────────────────
let myPosition = null
let myMarker = null
let routeLayer = null
let navActive = false
let navDestination = null
let watchId = null
let mapRef = null // map referencia tárolása

// A felhasználó saját pozíciójának kék körvonalú jelölője
// Saját pozíció marker ikon
const myIcon = L.divIcon({
  className: '',
  html: `<div style="width:18px;height:18px;border-radius:50%;background:#1a73e8;border:3px solid #fff;box-shadow:0 0 0 3px rgba(26,115,232,.4),0 2px 8px rgba(0,0,0,.4)"></div>`,
  iconSize: [18,18], iconAnchor: [9,9]
})

// GPS indítása
function startGPS(map) {
  mapRef = map
  if (!navigator.geolocation) return
  watchId = navigator.geolocation.watchPosition(pos => {
    myPosition = { lat: pos.coords.latitude, lng: pos.coords.longitude }
    if (!myMarker) {
      myMarker = L.marker([myPosition.lat, myPosition.lng], {icon: myIcon, zIndexOffset: 1000}).addTo(map)
      myMarker.bindTooltip('📍 Te vagy itt', {permanent: false, direction: 'top'})
    } else {
      myMarker.setLatLng([myPosition.lat, myPosition.lng])
    }
    // Ha navigáció aktív, frissítsük az útvonalat
    if (navActive && navDestination) {
      drawRoute(map, myPosition.lat, myPosition.lng, navDestination.lat, navDestination.lng, false)
    }
  }, () => {}, { enableHighAccuracy: true, maximumAge: 5000 })
}

// OSRM nyílt forráskódú útvonaltervező API hívása
// OSRM útvonal rajzolás
async function drawRoute(map, fromLat, fromLng, toLat, toLng, fitBounds=true) {
  try {
    const url = `https://router.project-osrm.org/route/v1/driving/${fromLng},${fromLat};${toLng},${toLat}?overview=full&geometries=geojson`
    const res = await fetch(url)
    const data = await res.json()
    if (!data.routes?.length) return

    // Régi útvonal törlése
    if (routeLayer) map.removeLayer(routeLayer)

    const coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]])
    routeLayer = L.polyline(coords, {
      color: '#1a73e8', weight: 5, opacity: 0.85,
      dashArray: null, lineCap: 'round', lineJoin: 'round'
    }).addTo(map)

    if (fitBounds) map.fitBounds(routeLayer.getBounds(), {padding: [40,40]})

    // Távolság és idő megjelenítése
    const dist = (data.routes[0].distance / 1000).toFixed(1)
    const mins = Math.round(data.routes[0].duration / 60)
    showNavInfo(dist, mins)
  } catch(e) {
    console.error('Route error:', e)
  }
}

// Navigációs infó panel: távolság és menetidő megjelenítése
// Navigációs infó panel
let navInfoEl = null
function showNavInfo(dist, mins) {
  if (!navInfoEl) {
    navInfoEl = document.createElement('div')
    navInfoEl.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);z-index:2000;background:#1a2035;border:1px solid rgba(148,183,255,.2);border-radius:16px;padding:12px 20px;display:flex;align-items:center;gap:16px;box-shadow:0 4px 24px rgba(0,0,0,.4);font-family:inherit'
    document.body.appendChild(navInfoEl)
  }
  navInfoEl.innerHTML = `
    <div style="text-align:center">
      <div style="font-size:1.4rem;font-weight:800;color:#38bdf8;line-height:1">${dist} km</div>
      <div style="font-size:.7rem;color:#8da0c0;margin-top:2px">távolság</div>
    </div>
    <div style="width:1px;height:32px;background:rgba(148,183,255,.2)"></div>
    <div style="text-align:center">
      <div style="font-size:1.4rem;font-weight:800;color:#10b981;line-height:1">${mins} perc</div>
      <div style="font-size:.7rem;color:#8da0c0;margin-top:2px">menetidő</div>
    </div>
    <div style="width:1px;height:32px;background:rgba(148,183,255,.2)"></div>
    <button onclick="stopNavigation()" style="padding:8px 14px;border-radius:10px;background:rgba(244,63,94,.15);border:1px solid rgba(244,63,94,.4);color:#f87171;font-size:.8rem;font-weight:700;cursor:pointer;font-family:inherit">✕ Leállítás</button>
  `
}

// Navigáció leállítása
function stopNavigation() {
  navActive = false
  navDestination = null
  if (routeLayer && mapRef) { mapRef.removeLayer(routeLayer); routeLayer = null }
  if (navInfoEl) { navInfoEl.remove(); navInfoEl = null }
}

// Navigáció indítása a bejelentés popup gombból
// Navigáció indítása a popup gombból
function openNavigation(destLat, destLng) {
  navDestination = { lat: destLat, lng: destLng }
  navActive = true

  const startNav = (fromLat, fromLng) => {
    drawRoute(mapRef, fromLat, fromLng, destLat, destLng, true)
    // Térképet centráljuk a saját pozícióra
    mapRef.setView([fromLat, fromLng], 15)
  }

  if (myPosition) {
    startNav(myPosition.lat, myPosition.lng)
  } else {
    // GPS lekérés
    navigator.geolocation?.getCurrentPosition(
      pos => {
        myPosition = { lat: pos.coords.latitude, lng: pos.coords.longitude }
        if (!myMarker && mapRef) {
          myMarker = L.marker([myPosition.lat, myPosition.lng], {icon: myIcon, zIndexOffset: 1000}).addTo(mapRef)
        }
        startNav(myPosition.lat, myPosition.lng)
      },
      () => alert('GPS nem elérhető. Engedélyezd a helymeghatározást!'),
      { enableHighAccuracy: true, timeout: 8000 }
    )
  }
}


// ────────────────────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => { init() })

<?php // refactor 7
