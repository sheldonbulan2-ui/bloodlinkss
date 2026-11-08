<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blood Inventory — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js?v=<?= time() ?>"></script>
<script src="js/shell.js?v=2"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'Blood Inventory', active: 'inventory' });
  if (!ok) return;
  if (!['staff', 'admin', 'superadmin'].includes(Shell.role)) {
    Shell.page().innerHTML = '<div class="empty"><b>Access denied.</b></div>'; return;
  }
  const page = Shell.page();
  const dd = await API.get('/api/staff/dropdowns');
  const locOptions = dd.locations.map(l => `<option value="${l.id}">${UI.esc(l.location_name)}</option>`).join('');

  const isStaff = Shell.role === 'staff';
  const hospitalName = Shell.user.hospital_name || 'Assigned Hospital';
  const hospitalId = Shell.user.hospital_id || '';

  const locFilterHtml = isStaff
    ? `<span class="badge" style="background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; font-size:12.5px; padding:6px 12px; border-radius:8px; font-weight:700;">🏥 ${UI.esc(hospitalName)}</span><input type="hidden" id="f-loc" value="${hospitalId}">`
    : `<select id="f-loc"><option value="">All locations</option>${locOptions}</select>`;

  page.innerHTML = `
    <div class="card">
      <div class="toolbar" style="gap:10px; flex-wrap:wrap;">
        <select id="f-type" data-blood-types data-blood-all></select>
        ${locFilterHtml}
        <select id="f-status"><option value="">All statuses</option>
          <option>Available</option><option>Reserved</option><option>Used</option>
          <option>Expired</option><option>Unavailable</option></select>
        <button class="btn slim ghost" id="refresh-btn">🔄 Refresh</button>
        <span class="spacer"></span>
        <button class="btn" id="add-btn" style="background:var(--red,#c0152e); color:#fff; font-weight:700;">+ Add Blood Batch</button>
      </div>
      <div id="inv-table"></div>
      <p class="muted small" style="margin-top:10px">Expired batches are automatically marked <b>Expired</b> and hidden from search/map. Remove a batch only after marking it Used, Expired or Unavailable.</p>
    </div>`;
  UI.fillBloodSelects(true);
  document.getElementById('f-type').value = 'ALL';

  function fmtExp(d) {
    const days = Math.floor((new Date(d) - new Date().setHours(0,0,0,0)) / 86400000);
    if (days < 0) return `${UI.fmtDate(d)} <span class="badge red">expired</span>`;
    if (days <= 7) return `${UI.fmtDate(d)} <span class="badge orange">${days} day(s) left</span>`;
    return UI.fmtDate(d);
  }

  async function load() {
    const qs = [];
    const t = document.getElementById('f-type').value;
    const l = document.getElementById('f-loc') ? document.getElementById('f-loc').value : '';
    const s = document.getElementById('f-status').value;
    if (t && t !== 'ALL') qs.push('blood_type=' + encodeURIComponent(t));
    if (l) qs.push('location_id=' + l);
    if (s) qs.push('status=' + s);
    const data = await API.get('/api/staff/inventory' + (qs.length ? '?' + qs.join('&') : ''));
    document.getElementById('inv-table').innerHTML = UI.table(
      ['ID', 'Blood Type', 'Component', 'Units', 'Location', 'Expires', 'Status', 'Updated', 'Actions'],
      data.inventory.map(i => `<tr>
        <td>#${i.id}</td>
        <td>${UI.bloodChip(i.blood_type)}</td>
        <td><b>${UI.esc(i.component_type || 'Whole Blood')}</b></td>
        <td><b>${i.blood_units}</b></td>
        <td>${UI.esc(i.location_name)}</td>
        <td>${fmtExp(i.expiration_date)}</td>
        <td>${UI.statusBadge(i.status)}</td>
        <td class="muted small">${UI.fmtDateTime(i.updated_at)}</td>
        <td>
          <button class="btn slim ghost" onclick='editBatch(${JSON.stringify(i).replace(/'/g, "&#39;")})'>Edit</button>
          ${['Expired','Used','Unavailable'].includes(i.status)
            ? `<button class="btn slim danger-outline" onclick="removeBatch(${i.id})">Remove</button>` : ''}
        </td></tr>`).join(''));
  }

  document.getElementById('add-btn').onclick = () => {
    const def = new Date(); def.setDate(def.getDate() + 35);
    const bloodTypeOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']
      .map(t => `<option value="${t}">${t}</option>`).join('');

    const locationFieldHtml = isStaff
      ? `<div class="field"><label>Location</label><input type="text" value="${UI.esc(hospitalName)}" disabled><input type="hidden" name="location_id" value="${hospitalId}"></div>`
      : `<div class="field"><label>Location *</label><select name="location_id" required>${locOptions}</select></div>`;

    UI.modal('Add Blood Batch', `
      <form id="inv-add">
        <div class="form-grid">
          <div class="field"><label>Blood Type *</label>
            <select name="blood_type" data-blood-types required>
              <option value="">Select Blood Type</option>
              ${bloodTypeOptions}
            </select>
          </div>
          <div class="field"><label>Component *</label>
            <select name="component_type" id="modal-comp-type" required onchange="updateDefaultExpiry(this.value)">
              <option value="Whole Blood">Whole Blood (35 days)</option>
              <option value="Packed Red Blood Cells (PRBC)">Packed Red Blood Cells (PRBC) (42 days)</option>
              <option value="Platelets">Platelets (Platelet Concentrate) (5 days)</option>
              <option value="Fresh Frozen Plasma (FFP)">Fresh Frozen Plasma (FFP) (365 days)</option>
              <option value="Cryoprecipitate">Cryoprecipitate (365 days)</option>
            </select>
          </div>
          <div class="field"><label>Units *</label><input name="blood_units" type="number" min="1" required value="1"></div>
          ${locationFieldHtml}
          <div class="field"><label>Expiration Date *</label><input name="expiration_date" type="date" required value="${def.toISOString().slice(0,10)}"></div>
          <div class="field full"><label>Status</label>
            <select name="status"><option>Available</option><option>Reserved</option><option>Unavailable</option></select></div>
        </div>
      </form>`,
      `<button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
       <button class="btn" onclick="saveBatch()" style="background:var(--red,#c0152e); color:#fff; font-weight:700;">+ Add Batch</button>`);
  };

  window.saveBatch = async () => {
    const form = document.getElementById('inv-add');
    if (!form.reportValidity()) return;
    const values = UI.formValues(form);
    UI.closeModal();
    if (!(await UI.confirm('Confirm Save', 'Are you sure you want to add this blood batch?'))) return;
    try {
      const d = await API.post('/api/staff/inventory', values);
      UI.okToast(d.message); load();
    } catch (e) { UI.errToast(e.message); }
  };

  window.editBatch = (i) => {
    UI.modal(`Edit Batch #${i.id} — ${i.blood_type}`, `
      <form id="inv-edit">
        <div class="form-grid">
          <div class="field"><label>Units *</label><input name="blood_units" type="number" min="0" required value="${i.blood_units}"></div>
          <div class="field"><label>Status</label>
            <select name="status">
              ${['Available','Reserved','Used','Expired','Unavailable'].map(s => `<option ${s === i.status ? 'selected' : ''}>${s}</option>`).join('')}
            </select></div>
          <div class="field full"><label>Expiration Date</label><input name="expiration_date" type="date" value="${i.expiration_date}"></div>
        </div>
      </form>`,
      `<button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
       <button class="btn" onclick="updateBatch(${i.id})">Save Changes</button>`);
  };

  window.updateBatch = async (id) => {
    const form = document.getElementById('inv-edit');
    if (!form.reportValidity()) return;
    const values = UI.formValues(form);
    UI.closeModal();
    if (!(await UI.confirm('Confirm Update', 'Are you sure you want to save changes to this batch?'))) return;
    try {
      const d = await API.put(`/api/staff/inventory/${id}`, values);
      UI.okToast(d.message); load();
    } catch (e) { UI.errToast(e.message); }
  };

  window.removeBatch = async (id) => {
    if (!(await UI.confirm('Remove batch', 'Permanently remove this batch from inventory?'))) return;
    try {
      const d = await API.del(`/api/staff/inventory/${id}`);
      UI.okToast(d.message); load();
    } catch (e) { UI.errToast(e.message); }
  };

  ['refresh-btn'].forEach(id => document.getElementById(id).onclick = load);
  ['f-type', 'f-loc', 'f-status'].forEach(id => document.getElementById(id).addEventListener('change', load));
  load();
})();
</script>
<div id="toasts"></div>
</body>
</html>

