/**
 * public/js/map.js
 * ----------------
 * Leaflet map helper used by the public map/search pages,
 * donor "nearby" view, and staff blood-locations page.
 * Markers + popups are built from live API data so the map
 * ALWAYS reflects the current inventory (business rule #10/#11).
 */
const BloodMap = {
  map: null,
  markers: [],
  detailsHandler: null, // optional callback(locationId) for "View Details"

  // Strict bounding box for the Philippines (no other countries allowed)
  philippinesBounds: [
    [4.5, 116.0],  // Southwest corner (Tawi-Tawi / Palawan sea)
    [21.5, 127.0]  // Northeast corner (Batanes)
  ],

  /** Create the map inside #containerId. Locked strictly to the Philippines. */
  async create(containerId) {
    let center = [17.03, 121.9], zoom = 9;
    try {
      const s = await API.get('/api/settings'); // public read-only settings
      if (s.settings) {
        if (s.settings.map_center_lat) center[0] = Number(s.settings.map_center_lat);
        if (s.settings.map_center_lng) center[1] = Number(s.settings.map_center_lng);
        if (s.settings.default_zoom) zoom = Number(s.settings.default_zoom);
      }
    } catch (e) { /* use defaults */ }

    if (BloodMap.map) {
      BloodMap.destroy();
    }

    // Strict Philippines-only configuration
    BloodMap.map = L.map(containerId, {
      center: center,
      zoom: zoom,
      minZoom: 6,           // Cannot zoom out to see other countries
      maxZoom: 18,
      maxBounds: BloodMap.philippinesBounds,
      maxBoundsViscosity: 1.0 // 100% hard barrier: completely blocks panning outside the Philippines
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      minZoom: 6,
      maxZoom: 18,
      bounds: BloodMap.philippinesBounds,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(BloodMap.map);

    BloodMap.map.setMaxBounds(BloodMap.philippinesBounds);

    // Invalidate size shortly after mounting to avoid tile miscalculations
    setTimeout(() => {
      if (BloodMap.map) BloodMap.map.invalidateSize();
    }, 250);

    return BloodMap.map;
  },

  markerMap: {},

  /** Render markers for locations returned by /api/blood/map or /api/blood/search. */
  render(locations, highlightType) {
    for (const m of BloodMap.markers) BloodMap.map.removeLayer(m);
    BloodMap.markers = [];
    BloodMap.markerMap = {};

    for (const loc of locations) {
      const blood = loc.blood || {};
      // 100% Accuracy: Only show blood types with units > 0
      const validEntries = Object.entries(blood).filter(([t, u]) => Number(u) > 0);
      const total = validEntries.reduce((s, [, u]) => s + Number(u), 0);

      // Skip hospitals that have 0 total available units
      if (total <= 0) continue;
      if (highlightType && (!blood[highlightType] || Number(blood[highlightType]) <= 0)) continue;

      const rows = validEntries
        .map(([t, u]) => `<div style="display:flex;justify-content:space-between;padding:2px 0;"><span><b>${UI.esc(t)}</b></span><span style="color:#c0152e;font-weight:700;">${u} unit(s)</span></div>`).join('');

      const countLabel = highlightType ? `${highlightType}: ${blood[highlightType] || 0} unit(s)` : `${total} units avail.`;
      const icon = L.divIcon({
        className: '',
        html: `<div style="background:${highlightType ? '#c0152e' : '#8f0f22'};color:#fff;
          padding:5px 10px;border-radius:14px;border:2px solid #fff;
          box-shadow:0 3px 8px rgba(0,0,0,.35);font-size:11px;font-weight:700;white-space:nowrap;cursor:pointer;">
          🏥 ${UI.esc(countLabel)}</div>`,
        iconSize: [null, 28], iconAnchor: [45, 26]
      });

      const locNameEnc = encodeURIComponent(loc.location_name || '');
      const marker = L.marker([loc.latitude, loc.longitude], { icon }).addTo(BloodMap.map);
      const role = (typeof Shell !== 'undefined' && Shell && Shell.role) ? Shell.role : (window.BL_CURRENT_USER_ROLE || '');
      let popupBtns = '';
      if (role === 'patient') {
        popupBtns = `
          <a href="requests.php?location=${locNameEnc}&location_id=${loc.location_id}" style="display:block; text-align:center; padding:7px 10px; font-size:12px; font-weight:700; color:#fff; background:#c0152e; border-radius:6px; text-decoration:none;">
            🚑 Request Blood from this Hospital
          </a>`;
      } else if (role === 'donor') {
        popupBtns = `
          <a href="donate.php?location=${locNameEnc}&location_id=${loc.location_id}" style="display:block; text-align:center; padding:7px 10px; font-size:12px; font-weight:700; color:#fff; background:#15803d; border-radius:6px; text-decoration:none;">
            🩸 Donate Blood at this Hospital
          </a>`;
      } else if (role) {
        popupBtns = `
          <a href="requests.php?location=${locNameEnc}&location_id=${loc.location_id}" style="display:block; text-align:center; padding:6px 10px; font-size:11px; font-weight:700; color:#fff; background:#c0152e; border-radius:6px; text-decoration:none;">
            📋 View Hospital Requests
          </a>`;
      } else {
        popupBtns = `
          <a href="login.php?location_id=${loc.location_id}&location_name=${locNameEnc}" style="display:block; text-align:center; padding:7px 10px; font-size:11px; font-weight:700; color:#fff; background:#c0152e; border-radius:6px; text-decoration:none;">
            🔑 Log In (Request / Donate)
          </a>
          <a href="register.php?location_id=${loc.location_id}&location_name=${locNameEnc}" style="display:block; text-align:center; padding:6px 10px; font-size:11px; font-weight:600; color:#1f2937; background:#f3f4f6; border:1px solid #d1d5db; border-radius:6px; text-decoration:none;">
            📝 Create New Account
          </a>`;
      }

      marker.bindPopup(`
        <div style="min-width:230px; font-family:sans-serif; line-height:1.4;">
          <div style="font-weight:700; font-size:14px; color:#111827; margin-bottom:2px;">🏥 ${UI.esc(loc.location_name)}</div>
          <div style="font-size:11px; color:#6b7280; margin-bottom:8px;">📍 ${UI.esc(loc.address)}<br>📞 ${UI.esc(loc.contact_number || '-')}</div>
          <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:6px 8px; margin-bottom:10px; max-height:120px; overflow-y:auto; font-size:12px;">
            <div style="font-size:11px; font-weight:600; color:#4b5563; margin-bottom:4px; border-bottom:1px solid #eee; padding-bottom:2px;">Available Stock (100% Verified):</div>
            ${rows}
          </div>
          <div style="display:flex; flex-direction:column; gap:5px;">
            ${popupBtns}
          </div>
        </div>`);
      BloodMap.markers.push(marker);
      BloodMap.markerMap[loc.location_id] = marker;
    }

    if (BloodMap.markers.length === 1) {
      BloodMap.map.setView(BloodMap.markers[0].getLatLng(), 12);
    } else if (BloodMap.markers.length > 1) {
      const g = L.featureGroup(BloodMap.markers);
      BloodMap.map.fitBounds(g.getBounds().pad(0.15), { maxZoom: 13 });
    } else {
      BloodMap.map.setView([17.03, 121.9], 9);
    }
    setTimeout(() => { if (BloodMap.map) BloodMap.map.invalidateSize(); }, 150);
  },

  openLocation(locationId) {
    const m = BloodMap.markerMap[locationId];
    if (m && BloodMap.map) {
      BloodMap.map.setView(m.getLatLng(), 14);
      m.openPopup();
    }
  },

  viewDetails(locationId) {
    if (BloodMap.detailsHandler) BloodMap.detailsHandler(locationId);
    else BloodMap.openLocation(locationId);
  },

  destroy() {
    for (const m of BloodMap.markers) BloodMap.map.removeLayer(m);
    BloodMap.markers = [];
    BloodMap.markerMap = {};
    if (BloodMap.map) { BloodMap.map.remove(); BloodMap.map = null; }
  }
};

