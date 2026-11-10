<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Donations — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=2"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'Donation Records', active: 'donations' });
  if (!ok) return;
  if (!['staff', 'admin', 'superadmin'].includes(Shell.role)) {
    Shell.page().innerHTML = '<div class="empty"><b>Access denied.</b></div>'; return;
  }
  const page = Shell.page();
  const dd = await API.get('/api/staff/dropdowns');
  const locOptions = dd.locations.map(l => `<option value="${l.id}">${UI.esc(l.location_name)}</option>`).join('');
  const today = new Date().toISOString().slice(0, 10);
  const initStatus = (new URLSearchParams(location.search)).get('status') || '';

  page.innerHTML = `
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
        <div id="status-tabs" style="display:flex; flex-wrap:wrap; gap:8px;">
          <!-- Dynamically populated status pills with counts -->
        </div>
        <button class="btn" id="add-btn">+ Record Donation</button>
      </div>

      <div class="toolbar" style="margin-bottom:12px;">
        <select id="f-status" style="display:none;"><option value="">All statuses</option>
          <option value="Pending" ${initStatus === 'Pending' ? 'selected' : ''}>Pending (To Verify)</option>
          <option value="Verified" ${initStatus === 'Verified' ? 'selected' : ''}>Verified</option>
          <option value="Completed" ${initStatus === 'Completed' ? 'selected' : ''}>Completed</option>
          <option value="Rejected" ${initStatus === 'Rejected' ? 'selected' : ''}>Rejected</option></select>
        <select id="f-donor"><option value="">All donors</option>
          ${dd.donors.map(d => `<option value="${d.id}">${d.donor_code} — ${UI.esc(d.last_name)}, ${UI.esc(d.first_name)}</option>`).join('')}</select>
        <button class="btn slim ghost" id="refresh-btn">Refresh</button>
        <span class="spacer"></span>
        <span id="don-count-summary" style="font-size:12.5px; font-weight:700; color:#4b5563;"></span>
      </div>
      <div id="don-table"></div>
      <p class="muted small" style="margin-top:10px">Only <b>Verified</b> donations are added to blood inventory. Marking <b>Complete</b> confirms successful extraction and begins donor 56-day recovery.</p>
    </div>`;

  let currentStatus = initStatus;

  function renderStatusTabs(allList) {
    const pCount = allList.filter(d => d.status === 'Pending').length;
    const vCount = allList.filter(d => d.status === 'Verified' || d.status === 'Approved').length;
    const cCount = allList.filter(d => d.status === 'Completed').length;
    const rCount = allList.filter(d => d.status === 'Rejected').length;
    const tCount = allList.length;

    const tabs = [
      { key: '', label: 'All Donations', count: tCount, color: '#374151', bg: '#f3f4f6' },
      { key: 'Pending', label: '⏳ Pending (To Verify)', count: pCount, color: '#92400e', bg: '#fef3c7', alert: pCount > 0 },
      { key: 'Verified', label: '✅ Verified', count: vCount, color: '#166534', bg: '#dcfce7' },
      { key: 'Completed', label: '💉 Completed', count: cCount, color: '#1e40af', bg: '#dbeafe' },
      { key: 'Rejected', label: '❌ Rejected', count: rCount, color: '#991b1b', bg: '#fee2e2' },
    ];

    const container = document.getElementById('status-tabs');
    if (!container) return;
    container.innerHTML = tabs.map(t => {
      const active = (currentStatus === t.key);
      return `
        <button type="button" class="btn slim" style="
          border-radius:20px; font-size:12.5px; font-weight:${active ? '800' : '600'};
          background:${active ? (t.key === 'Pending' ? '#b45309' : (t.key === '' ? '#1f2937' : '#c0152e')) : t.bg};
          color:${active ? '#ffffff' : t.color};
          border:1px solid ${active ? 'transparent' : 'rgba(0,0,0,0.08)'};
          padding:6px 14px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;
          transition:all 0.15s ease;
        " onclick="setStatusTab('${t.key}')">
          <span>${t.label}</span>
          <span style="
            background:${active ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.08)'};
            color:${active ? '#ffffff' : t.color};
            padding:1px 6px; border-radius:10px; font-size:11px; font-weight:800;
          ">${t.count}</span>
        </button>
      `;
    }).join('');
  }

  window.setStatusTab = (st) => {
    currentStatus = st;
    document.getElementById('f-status').value = st;
    load();
  };

  async function load() {
    const qs = [];
    const dn = document.getElementById('f-donor').value;
    if (currentStatus) qs.push('status=' + currentStatus);
    if (dn) qs.push('donor_id=' + dn);
    const data = await API.get('/api/staff/donations' + (qs.length ? '?' + qs.join('&') : ''));
    
    // Also fetch all donations once without status filter to calculate counts accurately
    if (!window._cachedAllDonations || dn) {
      const allData = await API.get('/api/staff/donations' + (dn ? '?donor_id=' + dn : ''));
      window._cachedAllDonations = allData.donations || [];
    }
    renderStatusTabs(window._cachedAllDonations);

    const countSummary = document.getElementById('don-count-summary');
    if (countSummary) {
      countSummary.textContent = `${data.donations.length} record(s) ${currentStatus ? `(${currentStatus})` : 'total'}`;
    }

    document.getElementById('don-table').innerHTML = UI.table(
      ['ID', 'Date', 'Donor', 'Blood', 'Units', 'Location', 'Status', 'Recorded By', 'Actions'],
      data.donations.map(d => `<tr>
        <td>#${d.id}</td><td>${UI.fmtDate(d.donation_date)}</td>
        <td>${UI.esc(d.donor_code)} — ${UI.esc(d.last_name)}, ${UI.esc(d.first_name)}</td>
        <td>${UI.bloodChip(d.blood_type)}</td><td>${d.blood_units}</td>
        <td class="small">${UI.esc(d.donation_location)}</td>
        <td>${
          d.status === 'Cancelled' && d.approved_by_hospital ?
            `<span class="badge" style="background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; font-weight:600; white-space:nowrap;">✅ Verified by ${UI.esc(d.approved_by_hospital)}</span>` :
          (d.status === 'Verified' || d.status === 'Approved') && d.approved_by_hospital ?
            `${UI.statusBadge(d.status)}<br><small style="color:#1565c0; font-weight:600; font-size:11px;">By ${UI.esc(d.approved_by_hospital)}</small>` :
          UI.statusBadge(d.status)
        }</td>
        <td class="muted small">${UI.esc(d.recorded_by_user || '-')}</td>
        <td>${
          ['admin', 'superadmin'].includes(Shell.role) ?
            '<span class="muted small">Oversight Only</span>' :
          d.status === 'Cancelled' ?
            (d.approved_by_hospital ? `<small class="muted" style="font-size:11px; white-space:nowrap;">Fulfilled elsewhere</small>` : '-') :
          d.status === 'Pending' ? `
            <button class="btn slim" onclick="approveDon(${d.id},'${d.blood_type}',${d.blood_units})">Accept</button>
            <button class="btn slim danger-outline" onclick="rejectDon(${d.id})">Reject</button>` :
          (d.status === 'Verified' || d.status === 'Approved') ? `
            <button class="btn slim" style="background:#15803d;color:#fff;border:none;font-weight:600;padding:4px 10px;" onclick="completeDon(${d.id})">&#10003; Complete</button>` :
          (d.status === 'Completed') ? `
            <span class="badge green" style="font-weight:700;">&#10003; Completed</span>` :
          '-'
        }</td>
      </tr>`).join(''));
  }

  document.getElementById('add-btn').onclick = () => {
    if (!document.getElementById('f-donor').options.length) return;
    UI.fillBloodSelects(false);
    UI.modal('Record Donation', `
      <form id="don-add">
        <div class="form-grid">
          <div class="field full"><label>Donor *</label>
            <select name="donor_id" required>
              ${dd.donors.map(d => `<option value="${d.id}">${d.donor_code} — ${UI.esc(d.last_name)}, ${UI.esc(d.first_name)} (${d.blood_type})</option>`).join('')}
            </select></div>
          <div class="field"><label>Blood Type *</label><select name="blood_type" data-blood-types required></select></div>
          <div class="field"><label>Units *</label><input name="blood_units" type="number" min="1" required value="1"></div>
          <div class="field"><label>Donation Date *</label><input name="donation_date" type="date" max="${today}" required value="${today}"></div>
          <div class="field"><label>Donation Location *</label><input name="donation_location" required placeholder="Where the donation took place"></div>
        </div>
      </form>`,
      `<button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
       <button class="btn" onclick="saveDonation()">Save Donation</button>`);
  };

  window.saveDonation = async () => {
    const form = document.getElementById('don-add');
    if (!form.reportValidity()) return;
    const values = UI.formValues(form);
    UI.closeModal();
    if (!(await UI.confirm('Confirm Save', 'Are you sure you want to save this donation?'))) return;
    try {
      const d = await API.post('/api/staff/donations', values);
      UI.okToast(d.message); window._cachedAllDonations = null; load();
    } catch (e) { UI.errToast(e.message); }
  };

  window.approveDon = (id, bloodType, units) => {
    UI.modal(`Accept Donation — ${units} unit(s) of ${bloodType}`, `
      <p class="muted small" style="margin-bottom:10px">Verified blood becomes available inventory at the chosen location.</p>
      <div class="field"><label>Receiving Location *</label><select id="approve-loc">${locOptions}</select></div>
      <div class="field" style="margin-top:8px"><label>Expiration Date</label>
        <input id="approve-exp" type="date" value="${defaultExp()}"></div>`,
      `<button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
       <button class="btn" onclick="doapprove(${id})">Accept &amp; Add to Inventory</button>`);
  };

  window.doapprove = async (id) => {
    const payload = {
      location_id: Number(document.getElementById('approve-loc').value),
      expiration_date: document.getElementById('approve-exp').value
    };
    UI.closeModal();
    if (!(await UI.confirm('Confirm Approval', 'Are you sure you want to approve this donation and add it to inventory?'))) return;
    try {
      const d = await API.put(`/api/staff/donations/${id}/approve`, payload);
      UI.okToast(d.message); window._cachedAllDonations = null; load();
    } catch (e) { UI.errToast(e.message); }
  };

  window.rejectDon = async (id) => {
    if (!(await UI.confirm('Reject donation', 'Reject this donation? The blood will NOT be added to inventory.'))) return;
    try {
      const d = await API.put(`/api/staff/donations/${id}/reject`);
      UI.okToast(d.message); window._cachedAllDonations = null; load();
    } catch (e) { UI.errToast(e.message); }
  };

  window.completeDon = async (id) => {
    if (!(await UI.confirm('Complete Blood Donation', 'Mark this donation as Completed? This confirms the donor has successfully completed blood extraction and will begin their 56-day cooldown countdown.'))) return;
    try {
      const d = await API.put(`/api/staff/donations/${id}/complete`);
      UI.okToast(d.message); window._cachedAllDonations = null; load();
    } catch (e) { UI.errToast(e.message); }
  };

  function defaultExp() {
    const dt = new Date(); dt.setDate(dt.getDate() + 35);
    return dt.toISOString().slice(0, 10);
  }

  document.getElementById('refresh-btn').onclick = () => {
    window._cachedAllDonations = null;
    load();
  };
  document.getElementById('f-status').addEventListener('change', load);
  document.getElementById('f-donor').addEventListener('change', () => {
    window._cachedAllDonations = null;
    load();
  });
  load();
})();
</script>
<div id="toasts"></div>
</body>
</html>

