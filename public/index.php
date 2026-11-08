<?php require __DIR__ . '/../php/guard.php'; publicPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BloodLink — Blood Donation, Inventory & Availability Mapping</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="manifest" href="manifest.json">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="BloodLink">
<link rel="apple-touch-icon" href="images/logo.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  /* Interactive Blood Map & Search Modal */
  .blood-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.78);
    backdrop-filter: blur(6px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    opacity: 0;
    transition: opacity 0.25s ease;
  }
  .blood-modal-overlay.open {
    display: flex;
    opacity: 1;
  }
  .blood-modal-container {
    background: #fff;
    width: 100%;
    max-width: 1060px;
    max-height: 92vh;
    border-radius: 20px;
    box-shadow: 0 25px 60px -15px rgba(0,0,0,0.4);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
  }
  @keyframes modalSlideUp {
    from { transform: translateY(24px) scale(0.97); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
  }
  .blood-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
  }
  .blood-modal-header h2 {
    font-size: 19px;
    font-weight: 800;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .blood-modal-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
  }
  .modal-chips-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-top: 12px;
  }
  .modal-chip {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
    padding: 5px 12px;
    border-radius: 18px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .modal-chip:hover, .modal-chip.active {
    background: #c0152e;
    color: #fff;
    border-color: #c0152e;
    box-shadow: 0 2px 6px rgba(192, 21, 46, 0.25);
  }
</style>
</head>
<body>
<div class="hero">
  <nav>
    <div class="brand-text" style="display:flex; align-items:center; gap:10px;">
      <img src="images/logo.png" alt="BloodLink Logo" style="width:34px; height:34px; object-fit:contain; border-radius:8px; background:#fff; padding:2px; box-shadow:0 2px 6px rgba(0,0,0,0.15);">
      <span style="font-weight:800; letter-spacing:-0.3px;">BloodLink</span>
      <span style="font-size:11px; font-weight:700; background:rgba(255,255,255,0.18); border:1px solid rgba(255,255,255,0.25); padding:2px 8px; border-radius:12px; margin-left:4px; display:inline-block;">Hospital &amp; Regional Network</span>
    </div>
    <span class="spacer"></span>
    <a href="#" onclick="openMapModal('map'); return false;" style="color:#fff; font-weight:600; text-decoration:none; margin-right:14px; font-size:13.5px;">📍 Live Map</a>
    <a href="download.php" target="_blank" style="color:#fff; font-weight:600; text-decoration:none; margin-right:18px; font-size:13.5px;">📱 Mobile App</a>
    <a href="login.php" class="btn white" style="font-weight:800; padding:8px 18px; font-size:13.5px; border-radius:9px; box-shadow:0 4px 14px rgba(0,0,0,0.2);">🔐 Portal Login</a>
  </nav>

  <div class="center">
    <h1 style="max-width:850px;">Find blood. <span>Give blood.</span> Save lives.</h1>
    <p style="max-width:680px;">BloodLink connects Hospital Staff, Administrators, Donors, and Patients in one secure Philippine healthcare platform. Real-time inventory tracking, verified donor screening, and cold-chain compliance.</p>
    
    <div class="actions" style="display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;">
      <a href="login.php" class="btn white" style="cursor:pointer; font-weight:800; padding:12px 24px; text-decoration:none; font-size:15px;">
        🔐 Access Official Portal
      </a>
      <button type="button" class="btn" onclick="openMapModal('search')" style="cursor:pointer; font-weight:700; padding:12px 20px; background:rgba(255,255,255,0.16); border:1px solid rgba(255,255,255,0.3);">
        🔍 Search Blood Inventory
      </button>
      <button type="button" class="btn" onclick="openMapModal('map')" style="cursor:pointer; font-weight:700; padding:12px 20px; background:rgba(255,255,255,0.16); border:1px solid rgba(255,255,255,0.3);">
        📍 Live Philippines Map
      </button>
    </div>

    <!-- Official Portal Access Selection Grid -->
    <div class="portal-cards-grid">
      <a href="login.php?role=staff" class="portal-card">
        <div>
          <div class="portal-card-header">
            <span class="portal-card-icon">🩺</span>
            <span class="portal-card-badge staff">Staff Access</span>
          </div>
          <h3>Hospital Staff Portal</h3>
          <p>Process urgent patient blood requests, screen incoming donors, record vitals, and release verified blood packs.</p>
        </div>
        <div class="portal-card-action">
          Log in as Hospital Staff &rarr;
        </div>
      </a>

      <a href="login.php?role=admin" class="portal-card">
        <div>
          <div class="portal-card-header">
            <span class="portal-card-icon">🏥</span>
            <span class="portal-card-badge admin">Management</span>
          </div>
          <h3>Hospital Administrator</h3>
          <p>Supervise facility inventory on hand, manage official staff accounts, review shift reports, and audit cold-chain compliance.</p>
        </div>
        <div class="portal-card-action">
          Log in as Hospital Admin &rarr;
        </div>
      </a>

      <a href="login.php?role=superadmin" class="portal-card">
        <div>
          <div class="portal-card-header">
            <span class="portal-card-icon">🛡️</span>
            <span class="portal-card-badge superadmin">Governance</span>
          </div>
          <h3>System Super Administrator</h3>
          <p>Multi-hospital network governance, master user roster, role permission matrices, system settings, and complete audit trails.</p>
        </div>
        <div class="portal-card-action">
          Log in as Super Admin &rarr;
        </div>
      </a>
    </div>

    <!-- Mobile App Exclusive Notice for Donors & Patients -->
    <div style="margin:24px auto 0; max-width:760px; width:100%; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.22); backdrop-filter:blur(10px); border-radius:14px; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; text-align:left;">
      <div style="display:flex; align-items:center; gap:12px;">
        <span style="font-size:30px;">📱</span>
        <div>
          <b style="font-size:14px; color:#fff; display:block;">Blood Donors &amp; Patients</b>
          <span style="font-size:12px; color:rgba(255,255,255,0.88);">Blood donation appointments and patient requests are handled exclusively through the official BloodLink Android App.</span>
        </div>
      </div>
      <div>
        <a href="download.php" target="_blank" class="btn slim white" style="font-size:12.5px; padding:8px 18px; font-weight:800; text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
          ⬇️ Download Android App (v1.0)
        </a>
      </div>
    </div>
  </div>
</div>

<!-- INTERACTIVE BLOOD MAP & SEARCH MODAL VIEWER -->
<div id="blood-viewer-modal" class="blood-modal-overlay">
  <div class="blood-modal-container">
    <div class="blood-modal-header">
      <h2 id="modal-viewer-title">
        <span>📍</span> Live Blood Availability Map (Philippines)
      </h2>
      <div style="display:flex; align-items:center; gap:10px;">
        <a id="modal-fullscreen-link" href="map.php" target="_blank" class="btn ghost slim" style="font-size:12px; padding:6px 12px; text-decoration:none;">
          ↗️ Full Page
        </a>
        <button type="button" class="btn ghost slim" onclick="closeMapModal()" style="font-size:18px; line-height:1; padding:6px 12px; border-radius:8px; cursor:pointer;" aria-label="Close">
          &times;
        </button>
      </div>
    </div>

    <div class="blood-modal-body">
      <!-- Filter controls -->
      <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:14px; padding:16px 18px; margin-bottom:18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <label for="modal-blood-filter" style="font-size:13.5px; font-weight:700; color:#1f2937;">
              Filter Blood Type:
            </label>
            <select id="modal-blood-filter" style="padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; font-weight:600; background:#fff; width:180px;">
              <option value="">All Types (Available Stock)</option>
              <option value="A+">A+ (A Positive)</option>
              <option value="A-">A- (A Negative)</option>
              <option value="B+">B+ (B Positive)</option>
              <option value="B-">B- (B Negative)</option>
              <option value="AB+">AB+ (AB Positive)</option>
              <option value="AB-">AB- (AB Negative)</option>
              <option value="O+">O+ (O Positive)</option>
              <option value="O-">O- (O Negative)</option>
            </select>
          </div>
          <div style="font-size:13px; color:#4b5563;">
            <span id="modal-centers-count" style="font-weight:700; color:#c0152e;">0</span> centers with verified stock
          </div>
        </div>

        <div class="modal-chips-bar">
          <span style="font-size:12px; font-weight:600; color:#6b7280;">Quick Select:</span>
          <button type="button" class="modal-chip active" data-type="">All</button>
          <button type="button" class="modal-chip" data-type="A+">A+</button>
          <button type="button" class="modal-chip" data-type="A-">A-</button>
          <button type="button" class="modal-chip" data-type="B+">B+</button>
          <button type="button" class="modal-chip" data-type="B-">B-</button>
          <button type="button" class="modal-chip" data-type="AB+">AB+</button>
          <button type="button" class="modal-chip" data-type="AB-">AB-</button>
          <button type="button" class="modal-chip" data-type="O+">O+</button>
          <button type="button" class="modal-chip" data-type="O-">O-</button>
        </div>
      </div>

      <!-- Leaflet Map Container -->
      <div style="position:relative; margin-bottom:20px;">
        <div id="modal-map" style="height:440px; border-radius:14px; border:2px solid #e5e7eb; overflow:hidden;"></div>
        <div style="position:absolute; bottom:12px; left:12px; z-index:1000; background:rgba(255,255,255,0.94); padding:6px 12px; border-radius:8px; font-size:11px; color:#374151; box-shadow:0 2px 8px rgba(0,0,0,0.12); border:1px solid #e5e7eb;">
          💡 <b>Tip:</b> Click any hospital pin to view details and proceed directly to Login or Register!
        </div>
      </div>

      <!-- Hospital Cards List -->
      <div>
        <h3 style="font-size:16px; font-weight:800; color:#111827; margin-bottom:12px;">
          🏥 Verified Partner Blood Centers
        </h3>
        <div id="modal-hospital-list" class="grid cols-3" style="gap:14px;"></div>
      </div>
    </div>
  </div>
</div>

<script src="js/api.js?v=<?= time() ?>"></script>
<script src="js/common.js?v=<?= time() ?>"></script>
<script src="js/map.js?v=<?= time() ?>"></script>
<script>
let allModalLocations = [];
let modalDataLoaded = false;

async function loadModalData() {
  if (modalDataLoaded && allModalLocations.length > 0) return;
  try {
    const data = await API.get('/api/blood/map');
    allModalLocations = data.locations || [];
    modalDataLoaded = true;
  } catch (err) {
    console.error('Failed to load blood locations', err);
  }
}

async function openMapModal(mode) {
  const modal = document.getElementById('blood-viewer-modal');
  const title = document.getElementById('modal-viewer-title');
  const fullLink = document.getElementById('modal-fullscreen-link');
  const filter = document.getElementById('modal-blood-filter');

  if (mode === 'search') {
    title.innerHTML = '<span>🔍</span> Live Blood Search & Hospital Availability';
    fullLink.href = 'search.php';
  } else {
    title.innerHTML = '<span>📍</span> Live Blood Availability Map (Philippines)';
    fullLink.href = 'map.php';
  }

  modal.classList.add('open');
  document.body.style.overflow = 'hidden';

  await loadModalData();
  await BloodMap.create('modal-map');

  renderModalAvailability();

  setTimeout(() => {
    if (BloodMap.map) BloodMap.map.invalidateSize();
    if (mode === 'search') filter.focus();
  }, 250);
}

function closeMapModal() {
  const modal = document.getElementById('blood-viewer-modal');
  modal.classList.remove('open');
  document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('blood-viewer-modal').addEventListener('click', (e) => {
  if (e.target.id === 'blood-viewer-modal') closeMapModal();
});

// Close on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeMapModal();
});

function renderModalAvailability() {
  const filterType = document.getElementById('modal-blood-filter').value;
  const listEl = document.getElementById('modal-hospital-list');
  const countEl = document.getElementById('modal-centers-count');

  // 100% Accuracy: Only show hospitals that currently have verified available units > 0
  const filtered = allModalLocations.filter(loc => {
    const validStocks = Object.entries(loc.blood || {}).filter(([bt, units]) => Number(units) > 0);
    if (filterType) {
      return Number(loc.blood[filterType] || 0) > 0;
    }
    return validStocks.length > 0;
  });

  if (countEl) countEl.textContent = filtered.length;
  BloodMap.render(filtered, filterType || null);

  // Update quick chip active states
  document.querySelectorAll('.modal-chip').forEach(btn => {
    if (btn.dataset.type === filterType) btn.classList.add('active');
    else btn.classList.remove('active');
  });

  if (filtered.length === 0) {
    listEl.innerHTML = `<div class="card" style="grid-column: 1 / -1; text-align:center; padding:32px 16px;">
      <h4 style="color:#6b7280; font-size:15px; margin-bottom:4px;">No Centers with Stock Available</h4>
      <p class="muted small">Currently, no blood center has ${filterType ? `<b>${UI.esc(filterType)}</b>` : 'available'} blood in stock. Please try another blood type or check back later.</p>
    </div>`;
    return;
  }

  listEl.innerHTML = filtered.map(loc => {
    const validChips = Object.entries(loc.blood || {})
      .filter(([bt, units]) => Number(units) > 0)
      .map(([bt, units]) => {
        const isHighlight = filterType === bt;
        return `<span class="chip" style="${isHighlight ? 'outline:2px solid var(--red); background:#c0152e; color:#fff;' : ''}">${UI.esc(bt)} &mdash; <b>${units}</b> unit(s)</span>`;
      })
      .join('');

    const locNameEnc = encodeURIComponent(loc.location_name || '');
    return `<div class="card" style="display:flex; flex-direction:column; justify-content:space-between; border-left:4px solid #c0152e; padding:14px;">
      <div>
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:4px;">
          <h4 style="margin:0; font-size:14.5px; font-weight:700; color:#111827;">🏥 ${UI.esc(loc.location_name)}</h4>
          <button type="button" class="btn ghost slim" onclick="modalFocusHospital(${loc.location_id})" style="font-size:10.5px; padding:2px 7px; flex-shrink:0;">
            📍 Focus
          </button>
        </div>
        <div class="small muted" style="margin-bottom:6px; font-size:11.5px; line-height:1.4;">
          📍 ${UI.esc(loc.address)}<br>📞 ${UI.esc(loc.contact_number || '-')}
        </div>
        <div class="blood-line" style="margin-bottom:10px;">${validChips}</div>
      </div>
      <div style="display:flex; gap:6px; margin-top:8px; border-top:1px solid #f3f4f6; padding-top:8px;">
        <a href="login.php?location_id=${loc.location_id}&location_name=${locNameEnc}" class="btn slim" style="flex:1; text-align:center; font-size:11px; text-decoration:none; padding:6px;">
          🔑 Log In
        </a>
        <a href="register.php?location_id=${loc.location_id}&location_name=${locNameEnc}" class="btn ghost slim" style="flex:1; text-align:center; font-size:11px; text-decoration:none; padding:6px;">
          📝 Register
        </a>
      </div>
    </div>`;
  }).join('');
}

function modalFocusHospital(locationId) {
  const mapContainer = document.getElementById('modal-map');
  if (mapContainer) {
    mapContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => {
      BloodMap.openLocation(locationId);
    }, 300);
  }
}

// Filter dropdown change
document.getElementById('modal-blood-filter').addEventListener('change', renderModalAvailability);

// Quick chips click
document.querySelectorAll('.modal-chip').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('modal-blood-filter').value = btn.dataset.type;
    renderModalAvailability();
  });
});
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js').catch(() => {});
  });
}
</script>
</body>
</html>
