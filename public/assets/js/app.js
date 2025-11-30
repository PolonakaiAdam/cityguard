// ════════════════════════════════════════════════════════════════════════
// CityGuard – app.js
// Ez a fájl a felület "agya".
// Röviden:
// - figyeli a gombokat
// - adatot küld az API-nak
// - megkapja a választ
// - frissíti a képernyőt
//
// Ha valaki meg akarja érteni a működést, ezt a 3 fájlt együtt nézze:
// 1. public/index.php
// 2. public/assets/js/app.js
// 3. api/*.php
// ════════════════════════════════════════════════════════════════════════

// ── Biztonságos event binding - soha nem dob hibát ───────
// ────────────────────────────────────────────────────────────────────────
// SEGÉDFÜGGVÉNYEK – kis dolgok, amiket máshol is használunk
// ────────────────────────────────────────────────────────────────────────

// Biztonságos eseményfigyelő: ha az elem nem létezik, nem dob hibát
function _bind(el, ev, fn) {
  try { if (el && typeof el.addEventListener === 'function') el.addEventListener(ev, fn) } catch(e) {}
}

// ── Konfirmáció modal ─────────────────────────────────────
// ────────────────────────────────────────────────────────────────────────
// MODAL ABLAKOK – felugró megerősítő párbeszéd ablakok
// modernConfirm: régi stílusú, showConfirmModal: újabb, szebb verzió
// Mindkettő Promise-t ad vissza: true = igen, false = nem
// ────────────────────────────────────────────────────────────────────────

function modernConfirm(msg, sub) {
  return new Promise(resolve => {
    document.getElementById('confirmDialog')?.remove()
    const o = document.createElement('div')
    o.id = 'confirmDialog'
    o.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(2,6,20,.75);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;padding:20px;animation:cdFadeIn .18s ease'
    o.innerHTML = `<style>@keyframes cdFadeIn{from{opacity:0}to{opacity:1}}@keyframes cdUp{from{opacity:0;transform:translateY(16px) scale(.97)}to{opacity:1;transform:none}}</style>
      <div style="background:linear-gradient(180deg,rgba(13,20,38,.98),rgba(9,14,28,.98));border:1px solid rgba(148,183,255,.18);border-radius:20px;padding:28px 28px 22px;width:100%;max-width:340px;box-shadow:0 32px 64px rgba(0,0,0,.6);animation:cdUp .22s cubic-bezier(.32,.72,0,1);text-align:center">
        <div style="font-size:2rem;margin-bottom:14px">🗑️</div>
        <div style="font-size:1rem;font-weight:700;color:#e8edf8;margin-bottom:8px">${msg}</div>
        ${sub ? `<div style="font-size:.83rem;color:#8da0c0;margin-bottom:20px">${sub}</div>` : '<div style="margin-bottom:20px"></div>'}
        <div style="display:flex;gap:10px">
          <button id="cdNo"  style="flex:1;height:42px;border-radius:10px;background:rgba(255,255,255,.07);border:1px solid rgba(148,183,255,.18);color:#8da0c0;font-size:.88rem;font-weight:600;cursor:pointer;font-family:inherit">Mégse</button>
          <button id="cdYes" style="flex:1;height:42px;border-radius:10px;background:rgba(244,63,94,.18);border:1px solid rgba(244,63,94,.4);color:#f87171;font-size:.88rem;font-weight:700;cursor:pointer;font-family:inherit">Törlés</button>
        </div>
      </div>`
    document.body.appendChild(o)
    const close = r => { o.remove(); resolve(r) }
    o.querySelector('#cdNo').onclick  = () => close(false)
    o.querySelector('#cdYes').onclick = () => close(true)
    o.onclick = e => { if (e.target === o) close(false) }
    const esc = e => { if (e.key==='Escape'){close(false);document.removeEventListener('keydown',esc)} if(e.key==='Enter'){close(true);document.removeEventListener('keydown',esc)} }
    document.addEventListener('keydown', esc)
  })
}

// ────────────────────────────────────────────────────────────────────────
// HAMBURGER MENÜ – mobilon a jobb felső sarokban lévő menügomb
// ────────────────────────────────────────────────────────────────────────
// ── Hamburger ─────────────────────────────────────────────
const hamburgerBtn = document.getElementById('hamburgerBtn')
const navOverlay   = document.getElementById('navOverlay')
const mainNav      = document.getElementById('mainNav')

function openNav()  { mainNav?.classList.add('nav-open'); hamburgerBtn?.classList.add('open'); navOverlay?.classList.add('visible'); document.body.style.overflow='hidden' }
function closeNav() { mainNav?.classList.remove('nav-open'); hamburgerBtn?.classList.remove('open'); navOverlay?.classList.remove('visible'); document.body.style.overflow='' }

_bind(hamburgerBtn, 'click', () => mainNav?.classList.contains('nav-open') ? closeNav() : openNav())
_bind(navOverlay, 'click', closeNav)
document.querySelectorAll('.btn-nav').forEach(b => b.addEventListener('click', () => { if(window.innerWidth<960) closeNav() }))

// ────────────────────────────────────────────────────────────────────────
// KONSTANSOK – állandó értékek, amiket sokfelé használunk
// API: szerver végpont alap URL, IMG: feltöltött képek alap URL
// SZEREPKOR/STATUSZ: megjelenítési szövegek magyarul
// ────────────────────────────────────────────────────────────────────────
// ── Alapok ────────────────────────────────────────────────
const API = (typeof window.CG_API_BASE !== 'undefined') ? window.CG_API_BASE : '../api/'
const IMG = (typeof window.CG_IMG_BASE !== 'undefined') ? window.CG_IMG_BASE : '../uploads/evidence/'
const PROFILE_IMG = (typeof window.CG_PROFILE_IMG_BASE !== 'undefined') ? window.CG_PROFILE_IMG_BASE : '../uploads/profiles/'
const SZEREPKOR = { admin:'Adminisztrátor', staff:'Ügyintéző', citizen:'Lakos', municipality:'Önkormányzati tag' }
const STATUSZ    = { new:'Új', in_progress:'Folyamatban', resolved:'Megoldva', rejected:'Elutasítva' }
const stHu = s => STATUSZ[s] ?? s

// Biztonságos querySelector - addEventListener mindig az EventTarget prototype-ról hívódik
window.$ = function safeQS(sel) {
  // id selector optimalizáció: getElementById gyorsabb és megbízhatóbb
  var idMatch = /^#([\w-]+)$/.exec(sel)
  var el = idMatch ? document.getElementById(idMatch[1]) : document.querySelector(sel)
  return el
}
const $ = window.$

// EventTarget.prototype patch: biztosítja hogy addEventListener mindig működjön


// Jelszó megjelenítés/elrejtés
function togglePw(inputId, btn) {
  const input = document.getElementById(inputId)
  if (!input) return
  if (input.type === 'password') {
    input.type = 'text'
    btn.textContent = '🙈'
    btn.style.color = 'var(--sky)'
  } else {
    input.type = 'password'
    btn.textContent = '👁'
    btn.style.color = 'var(--text-dim)'
  }
}

function extractServerError(text, status) {
  const raw = String(text || '').trim()
  if (!raw) return `Üres szerverválasz (${status}). Ellenőrizd, hogy fut-e az Apache és a MySQL.`
  const compact = raw.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  if (/ngrok/i.test(compact) && /browser warning|visit site|tunnel/i.test(compact)) {
    return 'Az ngrok védőoldala akadályozta a kérést. Frissítsd az oldalt, és használd a javított ngrokos csomagot.'
  }
  if (/PDO|SQLSTATE|Fatal error|Warning|Parse error|Access denied|Unknown database|Call to undefined function/i.test(compact)) {
    return compact.slice(0, 280)
  }
  return compact.slice(0, 220) || `Szerver hiba (${status})`
}

function showErrorFeedback(el, msg) {
  setText(el, '❌ ' + msg)
  try { console.error('CityGuard UI error:', msg) } catch (_) {}
}

function applySpecialRequestHeaders(opt) {
  opt = opt || {}
  opt.headers = opt.headers || {}
  opt.headers['Accept'] = opt.headers['Accept'] || 'application/json, text/plain, */*'
  opt.headers['X-Requested-With'] = opt.headers['X-Requested-With'] || 'XMLHttpRequest'
  const host = String(window.location.hostname || '').toLowerCase()
  if (host.includes('ngrok')) {
    opt.headers['ngrok-skip-browser-warning'] = '1'
  }
  return opt
}

function toUrlEncodedBody(body) {
  const params = new URLSearchParams()
  Object.entries(body || {}).forEach(([key, value]) => {
    if (value === undefined || value === null) return
    if (Array.isArray(value)) {
      value.forEach(v => params.append(key + '[]', String(v)))
      return
    }
    params.append(key, String(value))
  })
  return params.toString()
}

// ────────────────────────────────────────────────────────────────────────
// API KOMMUNIKÁCIÓ – hogyan küldjük a kéréseket a szerver felé
// Az api() függvény az egyetlen belépési pont minden szerver kérésnél.
// Automatikusan kezeli az ngrok fejléceket, hibákat és JSON konverziót.
// ────────────────────────────────────────────────────────────────────────

async function api(path, method='GET', body=null) {
  const upperMethod = String(method || 'GET').toUpperCase()
  const opt = applySpecialRequestHeaders({ method: upperMethod, credentials: 'include', headers:{} })
  if (body && upperMethod !== 'GET') {
    opt.headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8'
    opt.body = toUrlEncodedBody(body)
  }
  let res
  try {
    res = await fetch(API + path, opt)
  } catch (err) {
    const host = String(window.location.hostname || '').toLowerCase()
    const extra = host.includes('ngrok') ? ' Ngrok alatt ellenőrizd, hogy fut-e az Apache, a MySQL, és az ngrok a jó portra mutat.' : ''
    throw { error: 'Nem sikerült elérni a szervert. Ellenőrizd, hogy fut-e az Apache és a MySQL.' + extra }
  }
  const raw = await res.text()
  let data = null
  const trimmed = String(raw || '').trim()
  if (trimmed) {
    try {
      const start = trimmed.indexOf('{')
      const end = trimmed.lastIndexOf('}')
      if (start !== -1 && end !== -1 && end > start) data = JSON.parse(trimmed.slice(start, end + 1))
    } catch (_) {}
  }
  if (!data) data = { error: extractServerError(trimmed, res.status), status: res.status }
  if (!res.ok) {
    if (!data.error && trimmed) data.error = trimmed.slice(0, 300)
    throw data
  }
  return data
}

// ── Szép confirm modal ────────────────────────────────────
function showConfirmModal({ icon='🗑️', title='Biztosan törlöd?', message='Ez a művelet nem visszavonható.', confirmText='Törlés', cancelText='Mégse', danger=true } = {}) {
  return new Promise(resolve => {
    // Stílus hozzáadása ha még nincs
    if (!document.getElementById('cmStyle')) {
      const st = document.createElement('style'); st.id='cmStyle'
      st.textContent='@keyframes cmFadeIn{from{opacity:0}to{opacity:1}}@keyframes cmSlideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}'
      document.head.appendChild(st)
    }

    const overlay = document.createElement('div')
    overlay.style.cssText='position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);animation:cmFadeIn .15s ease'

    const box = document.createElement('div')
    box.style.cssText='background:#1a2035;border:1px solid rgba(148,183,255,.15);border-radius:20px;padding:32px 28px 24px;max-width:320px;width:90%;text-align:center;box-shadow:0 24px 64px rgba(0,0,0,.5);animation:cmSlideUp .2s ease'

    const iconEl = document.createElement('div')
    iconEl.style.cssText='font-size:2.8rem;margin-bottom:16px;line-height:1'
    iconEl.textContent = icon

    const titleEl = document.createElement('div')
    titleEl.style.cssText='font-size:1.05rem;font-weight:800;color:#e8edf8;margin-bottom:8px'
    titleEl.textContent = title

    const msgEl = document.createElement('div')
    msgEl.style.cssText='font-size:.85rem;color:#8da0c0;margin-bottom:28px;line-height:1.5'
    msgEl.textContent = message

    const btnRow = document.createElement('div')
    btnRow.style.cssText='display:flex;gap:10px'

    const cancelBtn = document.createElement('button')
    cancelBtn.style.cssText='flex:1;padding:12px;border-radius:12px;border:1px solid rgba(148,183,255,.2);background:rgba(255,255,255,.06);color:#c0cfe8;font-size:.9rem;font-weight:600;cursor:pointer;font-family:inherit'
    cancelBtn.textContent = cancelText

    const confirmBtn = document.createElement('button')
    confirmBtn.style.cssText=`flex:1;padding:12px;border-radius:12px;border:none;background:${danger?'#c0392b':'#1a73e8'};color:#fff;font-size:.9rem;font-weight:700;cursor:pointer;font-family:inherit`
    confirmBtn.textContent = confirmText

    btnRow.append(cancelBtn, confirmBtn)
    box.append(iconEl, titleEl, msgEl, btnRow)
    overlay.appendChild(box)
    document.body.appendChild(overlay)

    const close = (val) => { overlay.remove(); resolve(val) }
    cancelBtn.onclick = () => close(false)
    confirmBtn.onclick = () => close(true)
    overlay.addEventListener('click', e => { if(e.target===overlay) close(false) })
  })
}

// Képfeltöltés: külön függvény, mert FormData-t küld, nem URL-kódolt stringet
async function uploadEvidence(reportId, file) {
  if (!file) return false
  const fd = new FormData()
  fd.append('report_id', reportId); fd.append('evidence', file)
  const res = await fetch(API+'upload_evidence.php', applySpecialRequestHeaders({ method:'POST', credentials:'include', body:fd }))
  const txt = await res.text()
  // Supports both legacy 'OK' and new JSON {'ok':true} response
  if (res.ok) {
    try { const j = JSON.parse(txt); if (j.ok) return true; if (j.error) throw new Error(j.error) } catch(e) { if (e.message && e.message !== txt) throw e }
    if (txt.trim() === 'OK') return true
  }
  throw new Error(txt.trim() || 'Képfeltöltés sikertelen')
}

// Profilkép feltöltés: ugyanúgy FormData, mint a bizonyíték képeknél
async function uploadProfileImage(file) {
  if (!file) throw new Error('Nincs kiválasztott fájl.')
  const fd = new FormData()
  fd.append('profile_image', file)
  const res = await fetch(API+'upload_profile_image.php', applySpecialRequestHeaders({ method:'POST', credentials:'include', body:fd }))
  const data = await res.json().catch(()=>({}))
  if (!res.ok || !data.ok) throw new Error(data.error || 'Profilkép feltöltése sikertelen.')
  return data
}

const setText = (el, t='') => { if(el) el.textContent = t||'' }
const setHtml = (el, html='') => { if(el) el.innerHTML = html||'' }
const esc = s => String(s??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;")

// ────────────────────────────────────────────────────────────────────────
// PROFILKÉP SEGÉDFÜGGVÉNYEK – minden avatar ugyanazzal a logikával rajzolódik ki
// ────────────────────────────────────────────────────────────────────────
function profileImageUrl(fileName) {
  return fileName ? PROFILE_IMG + encodeURIComponent(fileName).replace(/%2F/g, '/') : ''
}
function avatarLetters(name) {
  return String(name||'?').trim().split(/\s+/).filter(Boolean).map(w=>w[0]).join('').slice(0,2).toUpperCase() || '?'
}
function paintAvatar(el, user, fallbackName) {
  if (!el) return
  const name = fallbackName || user?.name || '?'
  const img = profileImageUrl(user?.profile_image)
  el.textContent = img ? '' : avatarLetters(name)
  el.style.backgroundImage = img ? `url("${img}")` : ''
  el.style.backgroundSize = 'cover'
  el.style.backgroundPosition = 'center'
  el.style.backgroundRepeat = 'no-repeat'
}
function refreshIdentityVisuals(user) {
  paintAvatar($('#topbarAvatar'), user)
  paintAvatar($('#navUserAvatar'), user)
  paintAvatar($('#profileAvatarPreview'), user, user?.name)
  setText($('#me'), user?.name || '')
  setText($('#navUserName'), user?.name || '')
  setText($('#navUserRole'), user ? (SZEREPKOR[user.role] ?? user.role) : '')
}

// ────────────────────────────────────────────────────────────────────────
// ADATVÉDELMI MODAL + LIGHTBOX
// Privacy modal: az "adatkezelési tájékoztató" felugró ablak
// Lightbox: képnézegető – kattintásra felnagyítja a képet
// ────────────────────────────────────────────────────────────────────────
// ── Privacy modal ─────────────────────────────────────────
const openPrivacy  = () => { $('#privacyModal')?.classList.remove('hidden'); document.body.style.overflow='hidden' }
const closePrivacy = () => { $('#privacyModal')?.classList.add('hidden');    document.body.style.overflow='' }
_bind(document.getElementById('openPrivacy'), 'click', e => { e.preventDefault(); openPrivacy() })
_bind(document.getElementById('closePrivacy'), 'click', closePrivacy)
_bind(document.getElementById('closePrivacy2'), 'click', closePrivacy)
_bind(document.getElementById('acceptPrivacy'), 'click', () => { const cb=$('#privacyConsent'); if(cb) cb.checked=true; closePrivacy() })
_bind(document.getElementById('privacyModal'), 'click', e => { if(e.target===$('#privacyModal')) closePrivacy() })
document.addEventListener('keydown', e => { if(e.key==='Escape'){ document.getElementById('lightbox')?.classList.add('hidden'); closePrivacy() } })

// ── Lightbox ──────────────────────────────────────────────
// Egyszerű lightbox: egy képet mutat
function openLightbox(src) { const lb=$('#lightbox'),img=$('#lightbox-img'); if(lb&&img){img.src=src;lb.classList.remove('hidden')} }
function closeLightbox()    { $('#lightbox')?.classList.add('hidden') }

// Galéria modal: az összes képet megmutatja, lapozható
function openGallery(images, startIndex) {
  let current = startIndex || 0
  const overlay = document.createElement('div')
  overlay.id = 'galleryOverlay'
  overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.92);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:16px;'

  function render() {
    overlay.innerHTML = `
      <div style="position:relative;width:100%;max-width:720px;display:flex;flex-direction:column;align-items:center;gap:14px;">
        <button onclick="document.getElementById('galleryOverlay').remove()" style="position:absolute;top:-8px;right:0;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:8px;width:36px;height:36px;color:#fff;font-size:1.1rem;cursor:pointer;z-index:2;">✕</button>
        <div style="font-size:.78rem;color:#8da0c0;font-weight:600;">${current+1} / ${images.length} kép</div>
        <img src="${IMG}${esc(images[current])}" style="max-width:100%;max-height:70vh;border-radius:12px;object-fit:contain;box-shadow:0 8px 40px rgba(0,0,0,.6);" />
        <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">
          ${images.map((img, i) => `<div onclick="document.getElementById('galleryOverlay').__ci=${i};document.getElementById('galleryOverlay').__render()" data-gi="${i}" style="width:52px;height:52px;border-radius:8px;overflow:hidden;cursor:pointer;border:2px solid ${i===current?'#38bdf8':'rgba(255,255,255,.15)'};flex-shrink:0;"><img src="${IMG}${esc(img)}" style="width:100%;height:100%;object-fit:cover;"/></div>`).join('')}
        </div>
        <div style="display:flex;gap:12px;">
          ${current > 0 ? `<button id="galPrev" style="padding:10px 20px;border-radius:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:.9rem;cursor:pointer;">← Előző</button>` : ''}
          ${current < images.length-1 ? `<button id="galNext" style="padding:10px 20px;border-radius:10px;background:rgba(56,189,248,.2);border:1px solid rgba(56,189,248,.4);color:#38bdf8;font-size:.9rem;font-weight:700;cursor:pointer;">Következő →</button>` : ''}
        </div>
      </div>`
    overlay.querySelector('#galPrev')?.addEventListener('click', () => { current--; render() })
    overlay.querySelector('#galNext')?.addEventListener('click', () => { current++; render() })
    overlay.querySelectorAll('[data-gi]').forEach(el => {
      el.addEventListener('click', () => { current = +el.dataset.gi; render() })
    })
  }

  render()
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove() })
  document.body.appendChild(overlay)
}

// ────────────────────────────────────────────────────────────────────────
// NÉZETEK (VIEWS) – az oldalon belüli "képernyők" váltása
// Minden nézet egy-egy elrejtett div. showView() elrejti az összeset,
// majd megmutatja a kívántat és betölti az adatokat.
// ────────────────────────────────────────────────────────────────────────
// ── Views ─────────────────────────────────────────────────
const VIEWS = { newReport:'#newReportCard', reports:'#reportsCard', profile:'#profileCard', admin:'#adminCard', messages:'#adminMessagesCard', municipality:'#municipalityCard' }

function getDefaultViewForRole(role) {
  const defaults = {
    citizen: 'newReport',
    staff: 'reports',
    admin: 'reports',
    municipality: 'municipality'
  }
  return defaults[role] || 'profile'
}

function canOpenView(user, viewKey) {
  if (!user) return false

  const allowedViews = {
    citizen: ['newReport', 'reports', 'profile'],
    staff: ['reports', 'profile', 'messages'],
    admin: ['reports', 'profile', 'admin'],
    municipality: ['municipality', 'profile']
  }

  return (allowedViews[user.role] || ['profile']).includes(viewKey)
}

function showView(key) {
  const targetView = canOpenView(ME, key) ? key : getDefaultViewForRole(ME?.role)

  Object.values(VIEWS).forEach(s => $(s)?.classList.add('hidden'))
  $(VIEWS[targetView])?.classList.remove('hidden')
  document.querySelectorAll('.btn-nav[data-view]').forEach(b => b.classList.toggle('active', b.dataset.view===targetView))

  if (targetView==='reports')      loadReports()
  if (targetView==='admin')        loadAdminUsers()
  if (targetView==='messages')     loadAdminMessages()
  if (targetView==='profile')      loadUserMessageHistory()
  if (targetView==='municipality') loadMunicipalityDashboard()
  if (targetView==='newReport') {
    // A térkép div hidden szülőbe volt – most hogy látszik, invalidateSize kell
    // Kis késleltetés kell hogy a böngésző befejezze a display:none → block váltást
    setTimeout(() => {
      if (!reportMap) initReportMap()
      else reportMap.invalidateSize()
    }, 100)
  }
}
document.querySelectorAll('.btn-nav[data-view]').forEach(b => b.addEventListener('click', () => showView(b.dataset.view)))

// ────────────────────────────────────────────────────────────────────────
// BEJELENTKEZETT FELHASZNÁLÓ UI
// showAuthedUI(user): ha user=null, login panel látszik, különben a főnavigáció.
// Szerepkör alapján bizonyos menüpontok elrejtve/megmutatva.
// ────────────────────────────────────────────────────────────────────────
// ── Auth UI ───────────────────────────────────────────────
function showAuthPanel(w) {
  $('#loginCard')?.classList.toggle('hidden', w!=='login')
  $('#registerCard')?.classList.toggle('hidden', w!=='register')
  setText($('#loginMsg')); setText($('#regMsg'))
}

function initials(name) { return (name||'?').trim().split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase() }

function showAuthedUI(user) {
  $('#authWrap').classList.toggle('hidden', !!user)
  $('#mainNav')?.classList.toggle('hidden', !user)
  $('#btnLogout')?.classList.toggle('hidden', !user)
  $('#hamburgerBtn')?.classList.toggle('hidden', !user)
  const uib = $('#userInfoBlock')
  if (uib) uib.style.display = user ? 'flex' : 'none'
  const nli = $('#notLoggedIn')
  if (nli) nli.style.display = user ? 'none' : 'inline'

  if (!user) { showAuthPanel('login'); Object.values(VIEWS).forEach(s=>$(s)?.classList.add('hidden')); return }

  refreshIdentityVisuals(user)

  const { role } = user
  const isC = role==='citizen', isS = role==='staff', isA = role==='admin', isM = role==='municipality'

  // Az első elérhető oldal szerepkörönként:
  // citizen  -> Új bejelentés
  // staff    -> Bejelentések
  // admin    -> Bejelentések
  // municipality -> Áttekintés
  $('#navNewReport')?.classList.toggle('hidden', !isC)
  $('#navReports')?.classList.toggle('hidden', isM)
  $('#navMunicipality')?.classList.toggle('hidden', !isM)
  $('#navAdmin')?.classList.toggle('hidden', !isA)
  $('#navMessages')?.classList.toggle('hidden', !isS)
  $('#navAdminDivider')?.classList.toggle('hidden', !isA && !isS)

  const pn=$('#profileName'), pe=$('#profileEmail')
  if (pn) pn.value = user.name
  if (pe) pe.value = user.email

  if ($('#roleBadge')) $('#roleBadge').textContent = SZEREPKOR[role]??role

  const prb = $('#profileRoleBadge')
  if (prb) {
    prb.textContent = SZEREPKOR[role]??role
    const colors = { admin:'rgba(239,68,68,.15);border-color:rgba(239,68,68,.4);color:#fca5a5', staff:'rgba(56,189,248,.15);border-color:rgba(56,189,248,.4);color:#7dd3fc', citizen:'rgba(16,185,129,.15);border-color:rgba(16,185,129,.35);color:#6ee7b7', municipality:'rgba(245,158,11,.15);border-color:rgba(245,158,11,.35);color:#fbbf24' }
    prb.style.cssText = `font-size:.75rem;font-weight:700;padding:4px 12px;border-radius:999px;border:1px solid;font-family:'DM Mono',monospace;background:${colors[role]||colors.citizen}`
  }

  $('#citizenOnlySection')?.classList.toggle('hidden', !isC)

  // Reports oldal role-specifikus fejléce
  const titleEl=$('#reportsTitle'), subEl=$('#reportsSubtitle')
  if (titleEl) {
    const titles = {
      citizen:      ['📋 Bejelentések', 'Tekintsd meg a városban tett összes bejelentést'],
      staff:        ['📋 Bejelentések kezelése', 'Kezeld és frissítsd az összes bejelentést'],
      admin:        ['📋 Összes bejelentés', 'Adminisztrátori áttekintés – kezelhetsz és törölhetsz mindent'],
      municipality: ['📋 Bejelentések', 'Összes polgári bejelentés áttekintése']
    }
    const [t,s] = titles[role] ?? titles.citizen
    titleEl.textContent = t
    if (subEl) subEl.textContent = s
  }

  const preview = $('#profileAvatarPreview')
  if (preview) preview.setAttribute('aria-label', `${user.name} profilképe`)
}

// ────────────────────────────────────────────────────────────────────────
// GPS ÉS TÉRKÉP – az új bejelentéshez szükséges helymeghatározás
// LOCATION tárolja az aktuális koordinátákat.
// initReportMap(): Leaflet térkép inicializálás (csak egyszer fut le)
// placeMarker(): jelölő elhelyezés + GPS pontossági kör
// ────────────────────────────────────────────────────────────────────────
// ── GPS / Térkép ──────────────────────────────────────────
let ME=null, LOCATION={lat:null,lng:null,acc:null,method:'gps'}, reportMap=null, reportMarker=null, gpsCircle=null

function initReportMap() {
  if (reportMap) return
  reportMap = L.map('reportMap').setView([47.4979,19.0402],13)
  L.tileLayer('https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}.png?api_key=bca32bab-2628-4306-a25b-8fa429a5d10b',{maxZoom:20,attribution:'© Stadia Maps'}).addTo(reportMap)
  reportMap.on('click', e => { LOCATION={lat:e.latlng.lat,lng:e.latlng.lng,acc:null,method:'map'}; placeMarker(e.latlng); updateLocUI() })
}

function placeMarker(latlng, acc) {
  if (reportMarker) reportMarker.setLatLng(latlng)
  else {
    reportMarker = L.marker(latlng,{draggable:true}).addTo(reportMap)
    reportMarker.on('dragend', ev => { LOCATION.lat=ev.target.getLatLng().lat; LOCATION.lng=ev.target.getLatLng().lng; LOCATION.acc=null; LOCATION.method='map'; if(gpsCircle){gpsCircle.remove();gpsCircle=null}; updateLocUI() })
  }
  if (gpsCircle) { gpsCircle.remove(); gpsCircle=null }
  if (acc>0) gpsCircle = L.circle(latlng,{radius:acc,color:'#38bdf8',fillColor:'#38bdf8',fillOpacity:.12,weight:1.5,dashArray:'4 4'}).addTo(reportMap)
  reportMap.setView(latlng, Math.max(reportMap.getZoom(),16),{animate:true})
}

function updateLocUI() {
  const g=$('#gpsStatus'), m=$('#mapStatus')
  if (!g||!m) return
  if (!LOCATION.lat) { setText(g,'Nincs megadva – kattints a GPS gombra vagy jelöld a térképen.'); setText(m,''); return }
  const c=`${LOCATION.lat.toFixed(5)}, ${LOCATION.lng.toFixed(5)}`
  const a=LOCATION.acc?` · ±${Math.round(LOCATION.acc)} m`:''
  if (LOCATION.method==='gps') { setText(g,`✓ GPS: ${c}${a}`); setText(m,'') }
  else { setText(g,''); setText(m,`✓ Kijelölve: ${c}`) }
}

async function getGps() {
  if (!navigator.geolocation) { setText($('#createMsg'),'GPS nem támogatott'); return }
  setText($('#gpsStatus'),'⏳ Helymeghatározás…')
  if (!reportMap) initReportMap()
  return new Promise(resolve => navigator.geolocation.getCurrentPosition(
    pos => { LOCATION={lat:pos.coords.latitude,lng:pos.coords.longitude,acc:pos.coords.accuracy,method:'gps'}; placeMarker(L.latLng(LOCATION.lat,LOCATION.lng),LOCATION.acc); updateLocUI(); resolve(true) },
    err => { LOCATION.lat=LOCATION.lng=LOCATION.acc=null; updateLocUI(); const msgs={1:'GPS nincs engedélyezve.',2:'Helyzet nem elérhető.',3:'Időtúllépés.'}; setText($('#createMsg'),msgs[err.code]||'GPS hiba'); resolve(false) },
    {enableHighAccuracy:true,timeout:15000,maximumAge:0}
  ))
}

// ────────────────────────────────────────────────────────────────────────
// CÍM KERESÉS – szövegbe írt cím → koordináták (Nominatim API-val)
// searchAddress(): lekérdezi az OpenStreetMap geocoding API-ját
// 500ms-os késleltetéssel, hogy ne küldjön kérést minden betűleütésnél
// ────────────────────────────────────────────────────────────────────────
// ── Cím keresés (Nominatim) ───────────────────────────────
let searchTimer = null
async function searchAddress(q) {
  if (!q||q.length<3) return
  const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=5&accept-language=hu`)
  const results = await res.json()
  const c = $('#addressResults')
  if (!c) return
  if (!results.length) { c.style.display='block'; c.innerHTML=`<div style="padding:12px 14px;font-size:.83rem;color:var(--text-dim)">Nem találtunk ilyen címet.</div>`; return }
  c.style.display='block'
  c.innerHTML = results.map((r,i)=>`
    <div class="addr-r" data-i="${i}" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border);font-size:.85rem;display:flex;gap:10px;align-items:flex-start;transition:background .15s">
      <span style="color:var(--sky);flex-shrink:0">📍</span>
      <div><div style="font-weight:600;color:#e8edf8">${esc(r.display_name.split(',')[0])}</div>
      <div style="font-size:.78rem;color:var(--text-dim);margin-top:2px">${esc(r.display_name.split(',').slice(1).join(',').trim())}</div></div>
    </div>`).join('')
  c.querySelectorAll('.addr-r').forEach(el => {
    el.onmouseenter=()=>el.style.background='rgba(56,189,248,.08)'
    el.onmouseleave=()=>el.style.background='transparent'
    el.onclick=()=>{
      const r=results[+el.dataset.i]
      LOCATION={lat:+r.lat,lng:+r.lon,acc:null,method:'map'}
      if (!reportMap) initReportMap()
      placeMarker({lat:+r.lat,lng:+r.lon})
      reportMap.setView([+r.lat,+r.lon],17)
      updateLocUI()
      const inp=$('#addressSearch'); if(inp) inp.value=r.display_name.split(',').slice(0,2).join(',').trim()
      c.style.display='none'
    }
  })
}
_bind(document.getElementById('btnAddressSearch'), 'click',()=>{ const q=$('#addressSearch')?.value?.trim(); if(q) searchAddress(q) })
_bind(document.getElementById('addressSearch'), 'keydown',e=>{ if(e.key==='Enter'){e.preventDefault(); searchAddress(e.target.value.trim())} })
_bind(document.getElementById('addressSearch'), 'input',e=>{ clearTimeout(searchTimer); const q=e.target.value.trim(); if(q.length<3){const c=$('#addressResults');if(c)c.style.display='none';return}; searchTimer=setTimeout(()=>searchAddress(q),500) })
document.addEventListener('click',e=>{ if(!e.target.closest('#addressSearch')&&!e.target.closest('#addressResults')&&!e.target.closest('#btnAddressSearch')){const c=$('#addressResults');if(c)c.style.display='none'} })

// ────────────────────────────────────────────────────────────────────────
// KATEGÓRIÁK – bejelentés típusok betöltése a szerverről
// canManage(): van-e kezelési joga (admin/staff/municipality)
// canDelete(): törölheti-e az adott bejelentést
// ────────────────────────────────────────────────────────────────────────
// ── Kategóriák ────────────────────────────────────────────
async function loadCategories() {
  const sel=$('#categorySelect'); sel.innerHTML=''
  sel.appendChild(Object.assign(document.createElement('option'),{value:'',textContent:'— Válassz kategóriát —'}))
  try {
    const data = await api('categories_list.php')
    data.items?.forEach(c=>{ sel.appendChild(Object.assign(document.createElement('option'),{value:c.id,textContent:c.name})) })
    if (data.items?.length) sel.value=data.items[0].id
  } catch(e) { setText($('#createMsg'),e.error||'Kategóriák betöltése sikertelen') }
}

const canManage = () => ME && ['admin','staff','municipality'].includes(ME.role)
const canDelete = (r) => {
  if (!ME) return false
  if (canManage()) return true
  return String(r.user_id) === String(ME.id)
}

// ────────────────────────────────────────────────────────────────────────
// BEJELENTÉSEK KEZELÉSE
// deleteReport(): megerősítő modal után törli a bejelentést
// addStatusControls(): Módosítás és Törlés gombok hozzáadása a kártyához
// ────────────────────────────────────────────────────────────────────────
// ── Bejelentés törlése ────────────────────────────────────
async function deleteReport(id, reloadFn) {
  const ok = await showConfirmModal({
    icon: '🗑️',
    title: 'Biztosan törlöd ezt a bejelentést?',
    message: 'A bejelentés és az összes hozzá tartozó komment véglegesen eltűnik.',
    confirmText: 'Törlés',
    cancelText: 'Mégse',
    danger: true
  })
  if (!ok) return
  try {
    const result = await api('report_delete.php', 'POST', { report_id: Number(id) })
    if (result && result.ok) {
      if (typeof reloadFn === 'function') reloadFn()
    } else {
      alert(result?.error || 'Ismeretlen hiba')
    }
  } catch(e) {
    alert(e?.error || 'Törlési hiba')
  }
}

// ── Státusz kontrol ───────────────────────────────────────
function addStatusControls(li, r, reloadFn) {
  reloadFn = reloadFn || loadReports
  li._reloadFn = reloadFn
  li._reportId = r.id
  li._currentStatus = r.status

  const isOwner = ME && String(r.user_id) === String(ME.id)
  const isCitizenOwner = isOwner && ME?.role === 'citizen'
  const isManager = canManage()

  const wrap = document.createElement('div')
  wrap.style.cssText = 'display:flex;gap:8px;margin-top:10px;align-items:center;flex-wrap:wrap'

  // Csak lakos (citizen) saját bejelentésnél: MÓDOSÍTÁS gomb (törlés helyett)
  if (isCitizenOwner) {
    const editBtn = Object.assign(document.createElement('button'), {
      className: 'btn', type: 'button', textContent: '✏️ Módosítás'
    })
    editBtn.style.cssText = 'background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.35);color:#38bdf8;border-radius:8px;padding:6px 14px;font-size:.8rem;font-weight:700;cursor:pointer;font-family:inherit'
    editBtn.onclick = (e) => { e.stopPropagation(); openEditModal(r, reloadFn) }
    wrap.append(editBtn)
  }
  // Manager / admin / staff / municipality saját is: TÖRLÉS + (ha saját citizen-owner is volt, az editBtn már fent van)
  if (isManager) {
    const delBtn = Object.assign(document.createElement('button'), {
      className: 'btn btn-danger', type: 'button', textContent: '🗑 Törlés'
    })
    delBtn.style.cssText = 'background:rgba(244,63,94,.15);border:1px solid rgba(244,63,94,.4);color:#f87171;border-radius:8px;padding:6px 14px;font-size:.8rem;font-weight:700;cursor:pointer;font-family:inherit'
    delBtn.onclick = (e) => { e.stopPropagation(); deleteReport(r.id, reloadFn) }
    wrap.append(delBtn)
    // Managers who are also owner get edit too
    const editBtn2 = Object.assign(document.createElement('button'), {
      className: 'btn', type: 'button', textContent: '✏️ Módosítás'
    })
    editBtn2.style.cssText = 'background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.35);color:#38bdf8;border-radius:8px;padding:6px 14px;font-size:.8rem;font-weight:700;cursor:pointer;font-family:inherit'
    editBtn2.onclick = (e) => { e.stopPropagation(); openEditModal(r, reloadFn) }
    wrap.append(editBtn2)
  }

  if (wrap.children.length) li.appendChild(wrap)
}

// ────────────────────────────────────────────────────────────────────────
// BEJELENTÉS MÓDOSÍTÁSA MODAL
// Citizen saját bejelentését szerkesztheti: cím, kategória, helyszín, képek
// A mentés 3 lépésben történik: alap adatok → törölt képek → új képek
// ────────────────────────────────────────────────────────────────────────
// ── Bejelentés módosítása modal (citizen saját bejelentése) ───
async function openEditModal(r, reloadFn) {
  let categories = []
  try { const d = await api('categories_list.php'); categories = d.items || [] } catch(e) {}

  // Jelenlegi koordináták
  let editLat = r.latitude ? +r.latitude : 47.4979
  let editLng = r.longitude ? +r.longitude : 19.0402
  let editMap = null, editMarker = null

  // Meglévő képek (törlésre jelöltek)
  let existingImages = r.evidence_image
    ? r.evidence_image.split(',').map(s => s.trim()).filter(Boolean)
    : []
  let deletedImages = []

  // Új képek (JS tömb)
  let newFiles = []

  const overlay = document.createElement('div')
  overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.75);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;padding:16px;animation:cmFadeIn .15s ease;overflow-y:auto;'

  const box = document.createElement('div')
  box.style.cssText = 'background:linear-gradient(180deg,#0d1526 0%,#0a1020 100%);border:1px solid rgba(148,183,255,.18);border-radius:20px;padding:0;width:100%;max-width:520px;box-shadow:0 32px 80px rgba(0,0,0,.7);overflow:hidden;margin:auto;'

  box.innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 22px 16px;border-bottom:1px solid rgba(148,183,255,.1);">
      <div>
        <h3 style="margin:0 0 2px;font-size:1.05rem;color:#e8edf8;font-weight:800;">✏️ Bejelentés módosítása</h3>
        <div style="font-size:.75rem;color:var(--text-dim);">#${r.id} · ${esc(r.title)}</div>
      </div>
      <button id="editClose" style="background:rgba(255,255,255,.06);border:1px solid rgba(148,183,255,.18);border-radius:10px;width:34px;height:34px;color:#8da0c0;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">✕</button>
    </div>
    <div style="padding:20px 22px;display:flex;flex-direction:column;gap:16px;">

      <div>
        <label style="display:block;margin-bottom:6px;font-size:.78rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;">Kategória</label>
        <select id="editCategory" style="width:100%;border-radius:12px;">
          ${categories.map(c=>`<option value="${c.id}"${c.id==r.category_id?' selected':''}>${esc(c.name)}</option>`).join('')}
        </select>
      </div>

      <div>
        <label style="display:block;margin-bottom:6px;font-size:.78rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;">Bejelentés tárgya</label>
        <input id="editTitle" value="${esc(r.title)}" placeholder="Bejelentés tárgya" style="width:100%;"/>
      </div>

      <div>
        <label style="display:block;margin-bottom:8px;font-size:.78rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;">Helyszín – kattints vagy húzd a jelölőt</label>
        <div id="editMapEl" style="height:220px;border-radius:14px;overflow:hidden;border:1px solid rgba(148,183,255,.18);"></div>
        <div id="editLocInfo" style="margin-top:6px;font-size:.78rem;color:var(--sky);"></div>
      </div>

      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <label style="font-size:.78rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;">Képek</label>
          <label class="btn-evidence-upload" id="editEvidenceLabel" style="cursor:pointer;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <span id="editAddImgText">Kép hozzáadása</span>
            <input type="file" id="editEvidence" accept="image/*" multiple style="display:none;" />
          </label>
        </div>
        <div id="editImagesWrap" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(64px,1fr));gap:6px;min-height:10px;"></div>
      </div>

      <div id="editMsg" style="min-height:16px;font-size:.82rem;color:var(--sky);"></div>

      <div style="display:flex;gap:10px;padding-top:4px;">
        <button id="editSave" style="flex:1;padding:13px;border-radius:12px;background:linear-gradient(135deg,#1e40af,#1e3a8a);border:none;color:#fff;font-size:.9rem;font-weight:800;cursor:pointer;font-family:inherit;box-shadow:0 4px 16px rgba(30,64,175,.4);">💾 Mentés</button>
        <button id="editCancel" style="padding:13px 20px;border-radius:12px;background:rgba(255,255,255,.06);border:1px solid rgba(148,183,255,.18);color:#8da0c0;font-size:.9rem;font-weight:600;cursor:pointer;font-family:inherit;">Mégse</button>
      </div>
    </div>`

  overlay.appendChild(box)
  document.body.appendChild(overlay)

  // ── Térkép inicializálás ───────────────────────────────
  requestAnimationFrame(() => {
    editMap = L.map('editMapEl').setView([editLat, editLng], 16)
    L.tileLayer('https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}.png?api_key=bca32bab-2628-4306-a25b-8fa429a5d10b', {maxZoom:20, attribution:'© Stadia Maps'}).addTo(editMap)
    editMarker = L.marker([editLat, editLng], {draggable: true}).addTo(editMap)
    editMarker.on('dragend', ev => {
      editLat = ev.target.getLatLng().lat
      editLng = ev.target.getLatLng().lng
      updateEditLocInfo()
    })
    editMap.on('click', e => {
      editLat = e.latlng.lat; editLng = e.latlng.lng
      editMarker.setLatLng(e.latlng)
      updateEditLocInfo()
    })
    updateEditLocInfo()
  })

  function updateEditLocInfo() {
    const el = box.querySelector('#editLocInfo')
    if (el) el.textContent = `📍 ${editLat.toFixed(5)}, ${editLng.toFixed(5)}`
  }

  // ── Képek megjelenítése (meglévő + új) ───────────────
  function renderEditImages() {
    const wrap = box.querySelector('#editImagesWrap')
    if (!wrap) return
    wrap.innerHTML = ''

    // Meglévő képek
    existingImages.forEach(img => {
      if (deletedImages.includes(img)) return
      const card = document.createElement('div')
      card.className = 'evidence-thumb'
      card.style.cursor = 'default'
      const im = document.createElement('img')
      im.src = `${IMG}${img}`
      im.alt = img
      im.onclick = () => openLightbox(im.src)
      im.style.cursor = 'pointer'
      const del = document.createElement('button')
      del.className = 'evidence-thumb-del'
      del.title = 'Törlés'
      del.innerHTML = '✕'
      del.onclick = async ev => {
        ev.stopPropagation()
        const ok = await modernConfirm('Törlöd ezt a képet?', img)
        if (ok) { deletedImages.push(img); renderEditImages() }
      }
      const name = document.createElement('div')
      name.className = 'evidence-thumb-name'
      name.textContent = img
      card.append(im, del, name)
      wrap.appendChild(card)
    })

    // Új képek előnézete
    newFiles.forEach((file, idx) => {
      const reader = new FileReader()
      reader.onload = e => {
        const card = document.createElement('div')
        card.className = 'evidence-thumb'
        card.style.cursor = 'default'
        // "Új" badge
        const badge = document.createElement('div')
        badge.style.cssText = 'position:absolute;top:5px;left:5px;background:rgba(16,185,129,.85);color:#fff;font-size:.55rem;font-weight:800;padding:2px 5px;border-radius:4px;text-transform:uppercase;letter-spacing:.04em;'
        badge.textContent = 'Új'
        const im = document.createElement('img')
        im.src = e.target.result
        im.alt = file.name
        im.onclick = () => openLightbox(im.src)
        im.style.cursor = 'pointer'
        const del = document.createElement('button')
        del.className = 'evidence-thumb-del'
        del.title = 'Törlés'
        del.innerHTML = '✕'
        del.onclick = ev => { ev.stopPropagation(); newFiles.splice(idx, 1); renderEditImages() }
        const nm = document.createElement('div')
        nm.className = 'evidence-thumb-name'
        nm.textContent = file.name
        card.append(im, badge, del, nm)
        wrap.appendChild(card)
      }
      reader.readAsDataURL(file)
    })

    const total = (existingImages.length - deletedImages.length) + newFiles.length
    const addBtn = box.querySelector('#editAddImgText')
    if (addBtn) addBtn.textContent = total > 0 ? 'Kép hozzáadása' : 'Kép hozzáadása'
  }

  renderEditImages()

  // Új kép hozzáadása
  const editEvidenceInput = box.querySelector('#editEvidence')
  editEvidenceInput.addEventListener('change', () => {
    Array.from(editEvidenceInput.files).forEach(f => {
      const isDupe = newFiles.some(x => x.name === f.name && x.size === f.size)
      if (!isDupe) newFiles.push(f)
    })
    editEvidenceInput.value = ''
    renderEditImages()
  })

  const close = () => { if (editMap) editMap.remove(); overlay.remove() }
  box.querySelector('#editClose').onclick = close
  box.querySelector('#editCancel').onclick = close
  overlay.onclick = e => { if (e.target === overlay) close() }

  box.querySelector('#editSave').onclick = async () => {
    const title = box.querySelector('#editTitle').value.trim()
    const catId = +box.querySelector('#editCategory').value
    const msgEl = box.querySelector('#editMsg')
    if (!title) { msgEl.style.color='#f87171'; msgEl.textContent = '⚠ A cím kötelező!'; return }
    if (!catId) { msgEl.style.color='#f87171'; msgEl.textContent = '⚠ Válassz kategóriát!'; return }
    msgEl.style.color = 'var(--sky)'; msgEl.textContent = 'Mentés…'
    try {
      // 1. Alap adatok mentése
      await api('report_update.php', 'POST', {
        report_id: r.id, title, category_id: catId,
        latitude: editLat, longitude: editLng
      })
      // 2. Törölt képek törlése
      for (const img of deletedImages) {
        try { await api('report_evidence_delete.php', 'POST', { report_id: r.id, image: img }) } catch(e) {}
      }
      // 3. Új képek feltöltése
      if (newFiles.length) {
        msgEl.textContent = `Képek feltöltése… (0/${newFiles.length})`
        for (let i = 0; i < newFiles.length; i++) {
          await uploadEvidence(r.id, newFiles[i])
          msgEl.textContent = `Képek feltöltése… (${i+1}/${newFiles.length})`
        }
      }
      msgEl.style.color = '#34d399'; msgEl.textContent = '✓ Sikeresen mentve!'
      setTimeout(() => { close(); if (typeof reloadFn === 'function') reloadFn() }, 600)
    } catch(e) { msgEl.style.color='#f87171'; msgEl.textContent = e.error || 'Hiba történt' }
  }
}

// ────────────────────────────────────────────────────────────────────────
// KOMMENTEK – egy bejelentés alatti hozzászólások
// loadComments(): lekéri és megjeleníti a kommenteket
// Hash összehasonlítással kerüli el a felesleges újrarajzolást
// ────────────────────────────────────────────────────────────────────────
// ── Kommentek ─────────────────────────────────────────────
// Nyitott komment panelek nyilvántartása live frissítéshez
const openCommentPanels = new Map() // report_id → {el, lastHash}

async function loadComments(id, el) {
  try {
    const {items=[]} = await api(`comments_list.php?report_id=${id}`)
    const hash = items.map(c=>c.id+':'+c.comment).join('|')
    // Csak akkor újrarajzol ha változott
    if (el.dataset.commentHash === hash) return
    el.dataset.commentHash = hash
    el.innerHTML = items.length
      ? items.map(c=>`<div class="comment"><div class="comment-head"><b>${esc(c.author)}</b> <span class="muted small">(${esc(SZEREPKOR[c.author_role]??c.author_role)}) • ${esc(c.created_at)}</span></div><div style="margin-top:4px">${esc(c.comment)}</div></div>`).join('')
      : '<div class="muted small" style="padding:8px 0">Még nincs hozzászólás</div>'
  } catch(e) { el.innerHTML=`<div class="muted small">${esc(e.error||'Hiba')}</div>` }
}

function addCommentControls(li, r) {
  const isStaff=canManage()
  const isOwn=ME && String(r.user_id) === String(ME.id)
  const isCitizen=ME?.role==='citizen'
  const isCitizenOwn = isCitizen && isOwn
  if (!ME) return

  const reloadFn = li._reloadFn || loadReports

  const statusSelectHtml = isStaff ? `
    <div style="margin-top:12px">
      <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:var(--text-dim)">Állapot módosítása</label>
      <select data-status-sel style="width:100%">
        ${[['new','Új'],['in_progress','Folyamatban'],['resolved','Megoldva'],['rejected','Elutasítva']].map(([v,l])=>`<option value="${v}"${v===r.status?' selected':''}>${l}</option>`).join('')}
      </select>
    </div>` : ''

  const emojis = ['👍','✅','⚠️','🔧','📍','🚧','💡','🗑️','🚗','🌿']
  const emojiBar = `<div style="display:flex;flex-wrap:wrap;gap:4px;margin:8px 0 4px">
    ${emojis.map(e=>`<button type="button" data-emoji="${e}" style="background:rgba(255,255,255,.06);border:1px solid rgba(148,183,255,.15);border-radius:6px;padding:3px 7px;font-size:1rem;cursor:pointer" onmouseover="this.style.background='rgba(56,189,248,.15)'" onmouseout="this.style.background='rgba(255,255,255,.06)'">${e}</button>`).join('')}
  </div>`

  const placeholder = isCitizenOwn ? 'Írd meg, ha van további infód...' : isStaff ? 'pl. Köszönjük a bejelentést! (elhagyható)' : 'Hozzászólásod...'
  const btnClass = isCitizenOwn ? 'btn-secondary' : 'btn-primary'
  const toggleLabel = 'Részletek mutatása'

  const box=document.createElement('div')
  box.innerHTML=`<div class="row" style="margin-top:12px;gap:10px">
    <button class="btn btn-soft" data-a="toggle">${toggleLabel}</button>
    ${isStaff?'<button class="btn btn-soft" data-a="thanks">Köszönő üzenet</button>':''}
  </div>
  <div class="hidden" data-cc style="margin-top:12px">
    <div class="comment-list"></div>
    ${statusSelectHtml}
    <label style="margin:12px 0 4px;display:block">${isCitizenOwn?'Észrevételed':isStaff?'Ügyintézői megjegyzés':'Hozzászólás'}</label>
    ${emojiBar}
    <textarea rows="3" placeholder="${placeholder}"></textarea>
    <div class="row" style="margin-top:10px;gap:10px">
      <button class="btn ${btnClass}" data-a="send">Elküldés</button>
      <span class="muted small" data-m></span>
    </div>
  </div>`

  const cc=box.querySelector('[data-cc]')
  const list=box.querySelector('.comment-list')
  const ta=box.querySelector('textarea')
  const msgEl=box.querySelector('[data-m]')
  const statusSel=box.querySelector('[data-status-sel]')

  box.querySelectorAll('[data-emoji]').forEach(btn => {
    btn.onclick = () => {
      const pos = ta.selectionStart
      ta.value = ta.value.slice(0, pos) + btn.dataset.emoji + ' ' + ta.value.slice(pos)
      ta.selectionStart = ta.selectionEnd = pos + btn.dataset.emoji.length + 1
      ta.focus()
    }
  })

  box.querySelector('[data-a="toggle"]').onclick=async()=>{
    cc.classList.toggle('hidden')
    if(!cc.classList.contains('hidden')) {
      openCommentPanels.set(r.id, list)
      await loadComments(r.id, list)
    } else {
      openCommentPanels.delete(r.id)
    }
  }
  box.querySelector('[data-a="thanks"]')?.addEventListener('click',()=>{
    ta.value='Köszönjük a bejelentést! A hibát rögzítettük, és hamarosan intézkedünk.'
    if(statusSel) statusSel.value='in_progress'
    cc.classList.remove('hidden'); ta.focus()
  })

  box.querySelector('[data-a="send"]').onclick=async()=>{
    const txt=ta.value.trim()
    msgEl.textContent=''
    try {
      if (isStaff && statusSel && statusSel.value !== r.status) {
        await api('reports_update_status.php','POST',{report_id:r.id, status:statusSel.value})
        r.status = statusSel.value
      }
      if (txt) {
        await api('comments_create.php','POST',{report_id:r.id, comment:txt})
        ta.value=''
        await loadComments(r.id,list)
      }
      if (!txt && !isStaff) { msgEl.textContent='Írj be valamit!'; return }
      msgEl.textContent = isStaff && statusSel ? '✓ Mentve' : '✓ Elküldve'
      await reloadFn()
    } catch(e) { msgEl.textContent=e.error||'Hiba' }
  }
  li.appendChild(box)
}

// ────────────────────────────────────────────────────────────────────────
// BEJELENTÉS KÁRTYA – egy bejelentés megjelenítése a listában
// Tartalmazza: státusz badge, kategória, cím, leírás, képek, gombok, kommentek
// ────────────────────────────────────────────────────────────────────────
// ── Közös bejelentés kártya renderer ─────────────────────
function renderReportItem(r, reloadFn) {
  const li = document.createElement('li')
  const isManager = canManage()
  const authorHtml = isManager
    ? `<b class="clickable-author" data-uid="${r.user_id}" style="color:var(--sky);cursor:pointer;text-decoration:underline dotted;text-underline-offset:3px;" title="Felhasználó adatai">${esc(r.created_by)}</b>`
    : `<b style="color:var(--text-dim)">${esc(r.created_by)}</b>`
  // Support multiple evidence images (comma-separated or single)
  const images = r.evidence_image
    ? r.evidence_image.split(',').map(s=>s.trim()).filter(Boolean)
    : []
  // Képek: max 3 bélyegkép, ha több van "+X kép" gomb nyit galériát az ÖSSZES képpel
  const imagesHtml = images.length
    ? `<div class="report-images" data-imgs="${esc(images.join(','))}">${
        images.slice(0,3).map((img,i)=>
          `<div class="report-img-thumb" data-img-idx="${i}"><img src="${IMG}${esc(img)}" alt="Bizonyíték" loading="lazy"/></div>`
        ).join('')
      }${images.length > 3
        ? `<div class="report-img-count" data-img-idx="3">+${images.length-3} kép</div>`
        : ''
      }</div>`
    : ''
  li.innerHTML=`<div class="report-top">
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <span class="muted small" style="font-family:'DM Mono',monospace;font-size:.75rem">#${r.id}</span>
      <span class="chip chip--${r.status}">${stHu(r.status)}</span>
      <span class="chip">${esc(r.category||r.category_name||'')}</span>
    </div>
    <div class="muted small">${r.created_at}</div>
  </div>
  <div class="report-title">${esc(r.title)}</div>
  <div class="muted small" style="margin-top:4px">Beküldte: ${authorHtml}${r.address?` · <span>${esc(r.address)}</span>`:''}</div>
  ${r.description ? `<div style="margin-top:8px;font-size:0.84rem;color:var(--text-dim);line-height:1.55;padding:10px 12px;background:rgba(255,255,255,0.03);border-left:2px solid rgba(148,183,255,0.25);border-radius:0 8px 8px 0;">${esc(r.description)}</div>` : ''}
  ${imagesHtml}`

  // Clickable author → show user details modal
  if (isManager) {
    li.querySelector('.clickable-author')?.addEventListener('click', e => {
      e.stopPropagation()
      showUserDetailsModal(r.user_id, r.created_by)
    })
  }

  // Képek kattintás: bélyegkép → lightbox, "+X kép" gomb → galéria az összes képpel
  const imgContainer = li.querySelector('.report-images')
  if (imgContainer) {
    imgContainer.querySelectorAll('[data-img-idx]').forEach(el => {
      el.style.cursor = 'pointer'
      el.addEventListener('click', e => {
        e.stopPropagation()
        const idx = +el.dataset.imgIdx
        // Ha csak 1 kép van vagy bélyegképre kattintottak, lightbox; különben galéria
        if (images.length === 1) {
          openLightbox(IMG + images[0])
        } else {
          openGallery(images, idx)
        }
      })
    })
  }

  addStatusControls(li, r, reloadFn)
  addCommentControls(li, r)
  return li
}

// ────────────────────────────────────────────────────────────────────────
// FELHASZNÁLÓ ADATAI MODAL – manager rákattint a bejelentő nevére
// ────────────────────────────────────────────────────────────────────────
// ── Felhasználó adatai modal (manager kattint a nevére) ───
