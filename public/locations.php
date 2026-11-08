<?php require __DIR__ . '/../php/guard.php'; requirePage(['superadmin']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hospitals & Facilities — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  .sec-heading {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 800;
    color: #1e293b;
    padding-bottom: 6px;
    margin-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .form-section-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 14px;
  }
  .pinpoint-hint {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 5px;
    display: block;
    line-height: 1.4;
  }
</style>
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=2"></script>
<script src="js/map.js"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'Hospitals & Medical Facilities', active: 'locations' });
  if (!ok) return;
  if (Shell.role !== 'superadmin') {
    Shell.page().innerHTML = '<div class="empty"><b>Access denied.</b> Hospital facility registration is strictly reserved for Superadmin.</div>';
    return;
  }
  const page = Shell.page();
  page.innerHTML = `
    <div class="dash-hero" style="margin-bottom:16px;">
      <div>
        <h1 style="font-size:20px; font-weight:800; margin:0 0 4px 0;">Official Hospital & Facility Network</h1>
        <p style="margin:0; font-size:13.5px; color:#4b5563;">Register verified hospitals with 100% precision GPS coordinates and provision official Hospital Administrators.</p>
      </div>
      <div>
        <span class="role-pill superadmin">Superadmin Authority</span>
      </div>
    </div>
    <div class="card">
      <div class="toolbar">
        <button class="btn slim ghost" id="refresh-btn">🔄 Refresh</button>
        <span class="spacer"></span>
        <button class="btn" id="add-btn" style="background:var(--red,#c0152e); color:#fff; font-weight:700;">+ Register Hospital &amp; Admin</button>
      </div>
      <div id="loc-table"></div>
    </div>
    <div class="card" style="margin-top:16px">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h2 style="margin:0;">100% Precise Live Network Map</h2>
        <span class="muted small">All registered hospitals immediately plot on public and emergency maps</span>
      </div>
      <div id="map" class="map-box" style="height:360px"></div>
    </div>`;

  async function load() {
    const data = await API.get('/api/staff/locations');
    
    document.getElementById('loc-table').innerHTML = UI.table(
      ['ID', 'Hospital / Center Name', 'Complete Address', 'GPS Coordinates', 'Hotline', 'Hospital Admins', 'Official Staff', 'Stock', 'Status', 'Actions'],
      data.locations.map(l => {
        const admins = l.all_admins || l.admin_username || '';
        const adminBadges = admins ? admins.split(', ').map(a => `<span class="badge blue" style="margin:2px 0;">👔 ${UI.esc(a)}</span>`).join(' ') : '<span class="muted small">—</span>';
        const staff = l.staff_usernames || '';
        const staffBadges = staff ? staff.split(', ').map(s => `<span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:600; margin:2px 0;">🏥 ${UI.esc(s)}</span>`).join(' ') : '<span class="muted small">No staff yet</span>';
        
        return `<tr>
          <td>#${l.id}</td>
          <td><b>${UI.esc(l.location_name)}</b></td>
          <td class="small">${UI.esc(l.address)}</td>
          <td class="small muted"><span class="badge ghost" style="font-family:monospace; font-size:11px;">${Number(l.latitude).toFixed(6)}, ${Number(l.longitude).toFixed(6)}</span></td>
          <td>${UI.esc(l.contact_number)}</td>
          <td>${adminBadges}</td>
          <td>${staffBadges}</td>
          <td><b>${l.available_units}</b> u</td>
          <td>${UI.statusBadge(l.available_status === 'Active' ? 'active' : 'inactive')}</td>
          <td>
            <button class="btn slim ghost" onclick='editLoc(${JSON.stringify(l).replace(/'/g, "&#39;")})'>Edit</button>
            <button class="btn slim ${l.available_status === 'Active' ? 'danger-outline' : 'blue'}" onclick="toggleLoc(${l.id},'${l.available_status}')">${l.available_status === 'Active' ? 'Deactivate' : 'Activate'}</button>
          </td></tr>`;
      }).join(''));

    const live = data.locations.filter(l => l.available_status === 'Active');
    if (BloodMap.map) {
      BloodMap.render(await toMapFormat(live));
    } else {
      await BloodMap.create('map');
      BloodMap.render(await toMapFormat(live));
    }
  }

  async function toMapFormat(locations) {
    const mapData = await API.get('/api/blood/map');
    return locations.map(l => {
      const m = mapData.locations.find(x => x.location_id === l.id);
      return { ...l, blood: (m && m.blood) || {} };
    });
  }

  function locForm(l, isEdit = false) {
    const lat = l.latitude ? Number(l.latitude).toFixed(6) : '17.083100';
    const lng = l.longitude ? Number(l.longitude).toFixed(6) : '122.189400';
    return `
      <form id="loc-form" style="max-height:75vh; overflow-y:auto; padding-right:4px;">
        <div class="form-section-card">
          <div class="sec-heading">🏥 1. Official Hospital / Facility Details</div>
          <div class="form-grid">
            <div class="field full"><label>Hospital / Blood Center Name *</label><input name="location_name" placeholder="e.g. San Mariano Community Hospital" required value="${UI.esc(l.location_name || '')}"></div>
            <div class="field full"><label>Complete Physical Address *</label><input name="address" placeholder="e.g. Brgy. Zone 1, San Mariano, Isabela" required value="${UI.esc(l.address || '')}"></div>
            <div class="field full"><label>Hospital Hotline / Emergency Contact *</label><input name="contact_number" placeholder="e.g. 0917-123-4567 / (078) 305-1111" required value="${UI.esc(l.contact_number || '')}"></div>
            
            <div class="field full">
              <label style="display:flex; justify-content:space-between; align-items:center;">
                <span>📍 Exact Map Position (100% Precision)</span>
                <button type="button" id="btn-gps-pinpoint" class="btn slim ghost" style="font-size:11px; padding:2px 8px;">🎯 Pinpoint My GPS</button>
              </label>
              <div id="picker-map" style="height:220px; border-radius:8px; border:2px solid #cbd5e1; margin-top:4px;"></div>
              <span class="pinpoint-hint">Click anywhere on the map or drag the pin to set the 100% exact GPS coordinates for this facility.</span>
            </div>

            <div class="field"><label>Latitude (Exact 6-Decimals) *</label><input id="loc-lat" name="latitude" required type="number" step="any" min="-90" max="90" value="${lat}"></div>
            <div class="field"><label>Longitude (Exact 6-Decimals) *</label><input id="loc-lng" name="longitude" required type="number" step="any" min="-180" max="180" value="${lng}"></div>
          </div>
        </div>

        ${!isEdit ? `
        <div class="form-section-card" style="background:#f0fdf4; border-color:#bbf7d0;">
          <div class="sec-heading" style="color:#166534; border-color:#bbf7d0;">👔 2. Official Hospital Administrator Account</div>
          <p class="small muted" style="margin:0 0 10px 0; color:#15803d;">This admin account will manage this hospital's inventory, requests, and create official hospital staff.</p>
          <div class="form-grid">
            <div class="field full"><label>Admin Username *</label><input name="admin_username" placeholder="e.g. smch_admin" required minlength="4"></div>
            <div class="field full"><label>Admin Official Email (Optional)</label><input name="admin_email" type="email" placeholder="e.g. admin@smch.gov.ph"></div>
            <div class="field full"><label>Admin Initial Password *</label><input name="admin_password" type="password" placeholder="8+ characters with uppercase, lowercase & number" required minlength="8"></div>
          </div>
        </div>
        ` : ''}
      </form>`;
  }

  function initPickerMap(initLat, initLng) {
    setTimeout(() => {
      const container = document.getElementById('picker-map');
      if (!container) return;
      const map = L.map('picker-map').setView([initLat, initLng], 14);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
      const marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);

      function updateInputs(pos) {
        document.getElementById('loc-lat').value = pos.lat.toFixed(6);
        document.getElementById('loc-lng').value = pos.lng.toFixed(6);
      }

      map.on('click', (e) => {
        marker.setLatLng(e.latlng);
        updateInputs(e.latlng);
      });

      marker.on('dragend', () => {
        updateInputs(marker.getLatLng());
      });

      const gpsBtn = document.getElementById('btn-gps-pinpoint');
      if (gpsBtn) {
        gpsBtn.onclick = () => {
          if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
              const lat = pos.coords.latitude;
              const lng = pos.coords.longitude;
              map.setView([lat, lng], 16);
              marker.setLatLng([lat, lng]);
              updateInputs({ lat, lng });
            }, err => {
              alert('Could not retrieve GPS location: ' + err.message);
            });
          }
        };
      }

      map.invalidateSize();
    }, 250);
  }

  document.getElementById('add-btn').onclick = () => {
    UI.modal('Register Legit Hospital & Admin', locForm({}, false),
      `<button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
       <button class="btn" onclick="saveLoc()" style="background:var(--red,#c0152e); color:#fff; font-weight:700;">Register &amp; Plot on Map</button>`);
    initPickerMap(17.083100, 122.189400);
  };

  window.editLoc = (l) => {
    UI.modal(`Edit Hospital Details — ${l.location_name}`, locForm(l, true),
      `<button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
       <button class="btn" onclick="saveLoc(${l.id})">Save Changes</button>`);
    initPickerMap(Number(l.latitude) || 17.083100, Number(l.longitude) || 122.189400);
  };

  window.saveLoc = async (id) => {
    const form = document.getElementById('loc-form');
    if (!form.reportValidity()) return;
    const values = UI.formValues(form);
    UI.closeModal();
    if (!(await UI.confirm('Confirm Registration', id ? 'Are you sure you want to update this hospital facility?' : 'Register this hospital facility and provision its official administrator account?'))) return;
    try {
      const d = id
        ? await API.put(`/api/staff/locations/${id}`, values)
        : await API.post('/api/staff/locations', values);
      UI.okToast(d.message); load();
    } catch (e) { UI.errToast(e.message); }
  };

  window.toggleLoc = async (id, current) => {
    const verb = current === 'Active' ? 'Deactivate' : 'Activate';
    if (!(await UI.confirm(verb + ' hospital', `${verb} this hospital facility? ${current === 'Active' ? 'Its blood units will be hidden from search and live maps.' : 'Its blood units and pin will appear on search and live maps again.'}`))) return;
    try { const d = await API.put(`/api/staff/locations/${id}/status`); UI.okToast(d.message); load(); }
    catch (e) { UI.errToast(e.message); }
  };

  document.getElementById('refresh-btn').onclick = load;
  load();
})();
</script>
<div id="toasts"></div>
</body>
</html>
