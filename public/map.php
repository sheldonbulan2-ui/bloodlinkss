<?php require __DIR__ . '/../php/guard.php'; publicPage(); $user = currentUser(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blood Map — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<div class="app-shell">
  <div class="main-area" style="margin-left:0">
    <header class="topbar">
      <h1>&#128205; Blood Availability Map</h1>
      <select id="map-filter" style="width:180px"><option value="">Filter by blood type</option></select>
      <span id="nav-links">
        <?php if ($user): ?>
          <a href="dashboard.php" class="btn slim">Back to Dashboard</a>
        <?php else: ?>
          <a href="index.php" class="btn ghost slim">Home</a>
          <a href="login.php" class="btn ghost slim">Login</a>
          <a href="register.php" class="btn slim">Register</a>
        <?php endif; ?>
      </span>
    </header>
    <main class="content">
      <div class="toolbar">
        <span class="muted small">Click a marker to see available blood, contact number, and details.</span>
      </div>
      <div id="map" class="map-box" style="height:600px"></div>
      <div id="list" class="grid cols-3" style="margin-top:16px"></div>
    </main>
  </div>
</div>
<div id="toasts"></div>
<script src="js/api.js?v=<?= time() ?>"></script>
<script src="js/common.js?v=<?= time() ?>"></script>
<script src="js/map.js?v=<?= time() ?>"></script>
<script>
window.BL_CURRENT_USER_ROLE = "<?= $user ? $user['role'] : '' ?>";
let allLocations = [];

function applyFilter() {
  const t = document.getElementById('map-filter').value;
  const list = document.getElementById('list');
  
  // 100% Accuracy: Only show hospitals that currently have verified available units > 0
  let locs = allLocations.filter(l => {
    const valid = Object.entries(l.blood || {}).filter(([bt, u]) => Number(u) > 0);
    if (t) {
      return Number(l.blood[t] || 0) > 0;
    }
    return valid.length > 0;
  });

  BloodMap.render(locs, t || null);

  if (locs.length === 0) {
    list.innerHTML = `<div class="card" style="grid-column: 1 / -1; text-align:center; padding:32px 16px;">
      <h3 style="color:#6b7280; margin-bottom:6px;">No Centers with Available Blood</h3>
      <p class="muted small">Currently, no active blood center has ${t ? `<b>${UI.esc(t)}</b>` : 'available'} blood in stock. Please check back later or contact a hospital directly.</p>
    </div>`;
    return;
  }

  list.innerHTML = locs.map(l => {
    const validChips = Object.entries(l.blood)
      .filter(([bt, u]) => Number(u) > 0)
      .map(([bt, u]) => `<span class="chip" style="${t === bt ? 'outline:2px solid var(--red); font-weight:700;' : ''}">${UI.esc(bt)} &mdash; <b>${u}</b> unit(s)</span>`)
      .join('');

    const locNameEnc = encodeURIComponent(l.location_name || '');
    return `<div class="card" style="display:flex; flex-direction:column; justify-content:space-between; border-left:4px solid #c0152e; transition:transform 0.2s, box-shadow 0.2s;">
      <div>
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
          <h3 style="margin-bottom:4px; font-size:16px;">🏥 ${UI.esc(l.location_name)}</h3>
          <button type="button" class="btn ghost slim" onclick="BloodMap.openLocation(${l.location_id})" style="font-size:11px; padding:3px 8px; flex-shrink:0;">
            📍 Focus
          </button>
        </div>
        <div class="small muted" style="margin-bottom:8px;">📍 ${UI.esc(l.address)} &middot; 📞 ${UI.esc(l.contact_number || '-')}</div>
      let cardActionBtns = '';
      const curRole = window.BL_CURRENT_USER_ROLE || '';
      if (curRole === 'patient') {
        cardActionBtns = `<a href="requests.php?location=${locNameEnc}&location_id=${l.location_id}" class="btn slim" style="flex:1; text-align:center; font-size:12px; font-weight:700; text-decoration:none;">🚑 Request Blood</a>`;
      } else if (curRole === 'donor') {
        cardActionBtns = `<a href="donate.php?location=${locNameEnc}&location_id=${l.location_id}" class="btn slim" style="flex:1; text-align:center; font-size:12px; font-weight:700; background:#15803d; text-decoration:none;">🩸 Donate Blood</a>`;
      } else {
        cardActionBtns = `
          <a href="login.php?location_id=${l.location_id}&location_name=${locNameEnc}" class="btn slim" style="flex:1; text-align:center; font-size:11px; text-decoration:none;">
            🔑 Log In
          </a>
          <a href="register.php?location_id=${l.location_id}&location_name=${locNameEnc}" class="btn ghost slim" style="flex:1; text-align:center; font-size:11px; text-decoration:none;">
            📝 Register
          </a>`;
      }

      return `<div class="card" style="display:flex; flex-direction:column; justify-content:space-between; border-left:4px solid #c0152e; transition:transform 0.2s, box-shadow 0.2s;">
      <div>
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
          <h3 style="margin-bottom:4px; font-size:16px;">🏥 ${UI.esc(l.location_name)}</h3>
          <button type="button" class="btn ghost slim" onclick="BloodMap.openLocation(${l.location_id})" style="font-size:11px; padding:3px 8px; flex-shrink:0;">
            📍 Focus
          </button>
        </div>
        <div class="small muted" style="margin-bottom:8px;">📍 ${UI.esc(l.address)} &middot; 📞 ${UI.esc(l.contact_number || '-')}</div>
        <div class="blood-line" style="margin-bottom:12px;">${validChips}</div>
      </div>
      <div style="display:flex; gap:8px; margin-top:10px; border-top:1px solid #f3f4f6; padding-top:10px;">
        ${cardActionBtns}
      </div>
    </div>`;
  }).join('');
}

document.getElementById('map-filter').addEventListener('change', applyFilter);

(async () => {
  const sel = document.getElementById('map-filter');
  for (const t of ['A+','A-','B+','B-','AB+','AB-','O+','O-']) {
    const o = document.createElement('option'); o.value = t; o.textContent = t; sel.appendChild(o);
  }
  try {
    const data = await API.get('/api/blood/map');
    allLocations = data.locations;
    await BloodMap.create('map');
    applyFilter();
  } catch (e) {
    const box = document.getElementById('map');
    if (box) box.innerHTML = '<p style="padding:24px;text-align:center;color:#b00020">'
      + 'Could not load the blood map. Please try again later.</p>';
  }
})();
</script>
</body>
</html>

