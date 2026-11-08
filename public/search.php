<?php require __DIR__ . '/../php/guard.php'; publicPage(); $user = currentUser(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Blood — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<div class="app-shell">
  <div class="main-area" style="margin-left:0">
    <header class="topbar">
      <h1>&#128269; Search Available Blood</h1>
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
      <div class="card" style="margin-bottom:16px">
        <form id="search-form" class="form-grid">
          <div class="field">
            <label>Blood Type</label>
            <select name="blood_type" data-blood-types data-blood-all></select>
          </div>
          <div class="field">
            <label>Location</label>
            <select name="location_id" id="loc-filter"><option value="">All locations</option></select>
          </div>
          <div class="field" style="align-self:end">
            <button class="btn" id="search-btn">Search</button>
          </div>
        </form>
        <div id="totals" class="blood-line" style="margin-top:12px"></div>
      </div>

      <div class="grid cols-1">
        <div>
          <h2 style="margin-bottom:10px">Map</h2>
          <div id="map" class="map-box"></div>
        </div>
      </div>
      <!-- Hidden results container to prevent JS errors -->
      <div id="results" style="display: none;"></div>
    </main>
  </div>
</div>
<div id="toasts"></div>
<script src="js/api.js?v=<?= time() ?>"></script>
<script src="js/common.js?v=<?= time() ?>"></script>
<script src="js/map.js?v=<?= time() ?>"></script>
<script>
UI.fillBloodSelects(true);

function updateTotals(totals, selected) {
  const types = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
  document.getElementById('totals').innerHTML =
    '<span class="muted small" style="align-self:center">Current stock: </span>' +
    types.map(t => `<span class="chip" style="${selected === t ? 'outline:2px solid var(--red)' : ''}">${t}: ${totals[t] || 0}</span>`).join('');
}

async function loadLocations() {
  const data = await API.get('/api/blood/map');
  const sel = document.getElementById('loc-filter');
  for (const l of data.locations) {
    const o = document.createElement('option');
    o.value = l.location_id; o.textContent = l.location_name;
    sel.appendChild(o);
  }
}

async function runSearch() {
  const f = document.getElementById('search-form');
  const blood_type = f.blood_type.value;
  const location_id = f.location_id.value;
  const url = `/api/blood/search?blood_type=${encodeURIComponent(blood_type)}` +
    (location_id ? `&location_id=${location_id}` : '');
  const data = await API.get(url);
  updateTotals(data.totals, blood_type === 'ALL' ? null : blood_type);
  renderResults(data, blood_type);
}

function renderResults(data, bloodType) {
  const el = document.getElementById('results');
  if (data.locations.length === 0) {
    el.innerHTML = `<div class="empty"><b>No ${UI.esc(bloodType === 'ALL' ? '' : bloodType + ' ')}blood is currently available${bloodType !== 'ALL' ? ' at this location' : ''}.</b>
      <span class="muted">Please try another blood type or check back later.</span></div>`;
    BloodMap.render([]);
    return;
  }
  el.innerHTML = data.locations.map(loc => {
    const chips = loc.units.map(u =>
      `<span class="chip">${UI.esc(u.blood_type)} — ${u.units} unit(s)</span>`).join('');
    return `<div class="result-card">
      <div class="head">
        <div>
          <h3>${UI.esc(loc.location_name)}</h3>
          <div class="small muted">${UI.esc(loc.address)}</div>
          <div class="small muted">Contact: ${UI.esc(loc.contact_number)}</div>
        </div>
        <span class="badge green">Available</span>
      </div>
      <div class="blood-line">${chips}</div>
    </div>`;
  }).join('');
  // Show ONLY matching markers (highlighted) when a type is chosen.
  BloodMap.render(data.locations, bloodType === 'ALL' ? null : bloodType);
}

document.getElementById('search-form').addEventListener('submit', (e) => {
  e.preventDefault();
  runSearch().catch(err => UI.errToast(err.message));
});

loadLocations();
BloodMap.create('map').then(() => {
  // Fix the map rendering bug by invalidating size after it is attached
  setTimeout(() => {
    if (BloodMap.map) BloodMap.map.invalidateSize();
  }, 250);
  runSearch();
  BloodMap.detailsHandler = (id) => {
    // Scroll intentionally ignored since results are hidden now.
  };
});
</script>
</body>
</html>

