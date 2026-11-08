<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Settings — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=2"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'System Settings', active: 'settings' });
  if (!ok) return;
  if (!['admin', 'superadmin'].includes(Shell.role)) {
    Shell.page().innerHTML = '<div class="empty"><b>Access denied.</b></div>'; return;
  }
  const isSuper = Shell.role === 'superadmin';
  const page = Shell.page();
  const data = await API.get('/api/admin/settings');
  const s = data.settings;

  page.innerHTML = `
    <div class="grid cols-2">
      <div class="card">
        <h2>General Settings</h2>
        ${isSuper ? `
          <form id="set-form">
            <div class="field" style="margin-bottom:10px"><label>System Name</label>
              <input name="system_name" value="${UI.esc(s.system_name || 'BloodLink')}"></div>
            <div class="form-grid">
              <div class="field"><label>Map Center Latitude</label>
                <input name="map_center_lat" type="number" step="any" value="${UI.esc(s.map_center_lat || '17.03')}"></div>
              <div class="field"><label>Map Center Longitude</label>
                <input name="map_center_lng" type="number" step="any" value="${UI.esc(s.map_center_lng || '121.9')}"></div>
              <div class="field"><label>Map Default Zoom</label>
                <input name="default_zoom" type="number" min="3" max="18" value="${UI.esc(s.default_zoom || '10')}"></div>
              <div class="field"><label>Allow Registration</label>
                <select name="allow_registration">
                  <option value="1" ${s.allow_registration !== '0' ? 'selected' : ''}>Enabled</option>
                  <option value="0" ${s.allow_registration === '0' ? 'selected' : ''}>Disabled</option>
                </select></div>
            </div>
            <button class="btn" style="margin-top:12px">Save Settings</button>
          </form>`
        : `
          <p class="muted" style="margin-bottom:10px">Only the Super Admin can modify system settings.</p>
          <table>${Object.entries(s).map(([k, v]) =>
            `<tr><th class="muted" style="text-align:left">${k.replace(/_/g,' ')}</th><td>${UI.esc(v)}</td></tr>`).join('')}</table>`}
      </div>
      <div class="card">
        <h2>Database Backup &amp; Restore</h2>
        ${isSuper ? `
          <p class="muted small" style="margin-bottom:10px">Download a full SQL export of the database. To restore it, import the downloaded <code>.sql</code> file in phpMyAdmin (XAMPP).</p>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn ghost" href="${UI.url('/api/admin/backup')}">&#11015; Download Backup (.sql)</a>
          </div>`
        : '<p class="muted">Only the Super Admin can back up the database.</p>'}
      </div>
    </div>`;

  if (isSuper) {
    document.getElementById('set-form')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const d = await API.put('/api/admin/settings', UI.formValues(e.target));
        UI.okToast(d.message);
      } catch (err) { UI.errToast(err.message); }
    });
  }
})();
</script>
<div id="toasts"></div>
</body>
</html>

