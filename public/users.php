<?php require __DIR__ . '/../php/guard.php'; requirePage(['admin', 'superadmin']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Official Staff Management — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .hospital-group-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    overflow: hidden;
  }
  .hospital-group-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 14px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
  }
  .hospital-group-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .hospital-group-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
  }
  .hospital-filter-pill {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #475569;
    transition: all 0.15s ease;
  }
  .hospital-filter-pill:hover, .hospital-filter-pill.active {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
  }
</style>
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=5"></script>
<script>
(async () => {
  const isSuper = Shell.role === 'superadmin';
  const pageTitle = isSuper ? 'Registered Admin & Staff by Hospital' : 'Official Hospital Staff';
  const ok = await Shell.init({ title: pageTitle, active: 'users' });
  if (!ok) return;

  if (!['admin', 'superadmin'].includes(Shell.role)) {
    Shell.page().innerHTML = '<div class="empty"><b>Access denied.</b> You do not have permission to view this page.</div>';
    return;
  }

  const page = Shell.page();
  const hospitalName = Shell.user.hospital_name || 'Hospital Facility';

  page.innerHTML = `
    <div class="dash-hero" style="margin-bottom:16px;">
      <div>
        <h1 style="font-size:20px; font-weight:800; margin:0 0 4px 0;">
          ${isSuper ? '🏥 Registered Admin &amp; Staff (Organized by Hospital)' : `Official Staff &mdash; ${UI.esc(hospitalName)}`}
        </h1>
        <p style="margin:0; font-size:13.5px; color:#4b5563;">
          ${isSuper
            ? 'Superadmin directory organizing and separating all registered administrators and hospital staff by their assigned facility.'
            : `Create and supervise official staff accounts for <b>${UI.esc(hospitalName)}</b>.`}
        </p>
      </div>
      <div>
        <span class="role-pill ${isSuper ? 'superadmin' : 'admin'}">${isSuper ? 'Superadmin Control' : 'Hospital Admin'}</span>
      </div>
    </div>

    <!-- Filter & Actions Toolbar -->
    <div class="card" style="margin-bottom:16px; padding:14px 18px;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:10px; flex:1; min-width:240px;">
          <input type="text" id="user-search" placeholder="Search by username or email..." style="width:100%; max-width:300px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
          ${isSuper ? `
            <select id="hospital-select-filter" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
              <option value="">All Facilities</option>
            </select>
          ` : ''}
          <button class="btn slim ghost" id="refresh-btn">🔄 Refresh</button>
        </div>
        <div>
          <button class="btn" id="add-btn" style="background:var(--red,#c0152e); color:#fff; font-weight:700;">
            ${isSuper ? '+ Create System Account' : '+ Create Official Staff'}
          </button>
        </div>
      </div>
    </div>

    <!-- Users Container -->
    <div id="user-container">
      <div class="card" style="text-align:center; padding:30px; color:#64748b;">Loading user accounts...</div>
    </div>
  `;

  let hospitalDropdownOptions = '';
  let locationsList = [];

  if (isSuper) {
    try {
      const locData = await API.get('/api/staff/locations');
      locationsList = locData.locations || [];
      hospitalDropdownOptions = locationsList.map(l => `<option value="${l.id}">${UI.esc(l.location_name)}</option>`).join('');
      
      const filterSelect = document.getElementById('hospital-select-filter');
      if (filterSelect) {
        filterSelect.innerHTML = `<option value="">All Facilities (${locationsList.length})</option>` +
          locationsList.map(l => `<option value="${l.id}">🏥 ${UI.esc(l.location_name)}</option>`).join('') +
          `<option value="central">🛡️ Network Central / Superadmins</option>`;
      }
    } catch (e) {}
  }

  let allUsersData = [];

  async function load() {
    try {
      const data = await API.get('/api/admin/users');
      allUsersData = data.users || [];
      renderUsers();
    } catch (e) {
      document.getElementById('user-container').innerHTML = `
        <div class="card" style="text-align:center; padding:30px; color:#b91c1c;">
          Error loading user accounts: ${UI.esc(e.message || 'Error')}
        </div>
      `;
    }
  }

  function renderUsers() {
    const container = document.getElementById('user-container');
    const searchVal = (document.getElementById('user-search')?.value || '').toLowerCase().trim();
    const hospFilterVal = document.getElementById('hospital-select-filter')?.value || '';

    let filtered = allUsersData.filter(u => {
      const matchSearch = !searchVal ||
        (u.username && u.username.toLowerCase().includes(searchVal)) ||
        (u.email && u.email.toLowerCase().includes(searchVal)) ||
        (u.hospital_name && u.hospital_name.toLowerCase().includes(searchVal)) ||
        (u.role && u.role.toLowerCase().includes(searchVal));

      if (!matchSearch) return false;

      if (isSuper && hospFilterVal) {
        if (hospFilterVal === 'central') {
          return !u.hospital_id || u.role === 'superadmin';
        } else {
          return String(u.hospital_id) === String(hospFilterVal);
        }
      }
      return true;
    });

    if (filtered.length === 0) {
      container.innerHTML = `
        <div class="card" style="text-align:center; padding:40px; color:#64748b;">
          <div style="font-size:32px; margin-bottom:8px;">👥</div>
          <h3 style="margin:0 0 6px 0; color:#1e293b;">No Accounts Found</h3>
          <p class="muted" style="margin:0;">No registered users match your search criteria.</p>
        </div>
      `;
      return;
    }

    // =========================================================================
    // 1. SUPERADMIN VIEW: SEPARATED BY HOSPITALS
    // =========================================================================
    if (isSuper) {
      // Group users by hospital
      const groups = {};

      filtered.forEach(u => {
        let key = u.hospital_id ? `hosp_${u.hospital_id}` : 'central';
        if (u.role === 'superadmin') key = 'central';

        if (!groups[key]) {
          groups[key] = {
            id: u.hospital_id || 0,
            name: (u.role === 'superadmin' || !u.hospital_name) ? '🛡️ Network Central / System Superadmins' : `🏥 ${u.hospital_name}`,
            isCentral: key === 'central',
            users: []
          };
        }
        groups[key].users.push(u);
      });

      // Render each hospital group separately
      container.innerHTML = Object.values(groups).map(g => {
        const admins = g.users.filter(x => x.role === 'admin').length;
        const staff = g.users.filter(x => x.role === 'staff').length;
        const supers = g.users.filter(x => x.role === 'superadmin').length;

        const cols = ['ID', 'Username', 'Email', 'Role', 'Status', 'Created', 'Actions'];
        const rowsHtml = g.users.map(u => `
          <tr>
            <td>#${u.id}</td>
            <td><b>${UI.esc(u.username)}</b></td>
            <td class="small muted">${UI.esc(u.email || '—')}</td>
            <td>
              <span class="badge ${u.role === 'superadmin' ? 'red' : (u.role === 'admin' ? 'blue' : 'dark')}">
                ${u.role === 'superadmin' ? '🛡️ Superadmin' : (u.role === 'admin' ? '👔 Admin' : '🩺 Staff')}
              </span>
            </td>
            <td>${UI.statusBadge(u.status)}</td>
            <td class="muted small">${UI.fmtDate(u.created_at)}</td>
            <td>
              ${u.role === 'superadmin' ? '<span class="muted small">Protected</span>' : `
                <button class="btn slim ghost" data-uname="${UI.esc(u.username)}" onclick="editUser(${u.id}, this.dataset.uname)">Edit</button>
                <button class="btn slim ${u.status === 'active' ? 'danger-outline' : 'blue'}" onclick="toggleUser(${u.id},'${u.status}')">
                  ${u.status === 'active' ? 'Deactivate' : 'Activate'}
                </button>
              `}
            </td>
          </tr>
        `).join('');

        return `
          <div class="hospital-group-card">
            <div class="hospital-group-header">
              <div class="hospital-group-title">
                ${UI.esc(g.name)}
              </div>
              <div class="hospital-group-meta">
                ${supers > 0 ? `<span class="chip" style="background:#fee2e2; color:#991b1b; font-weight:700;">${supers} Superadmin</span>` : ''}
                ${admins > 0 ? `<span class="chip" style="background:#dbeafe; color:#1e40af; font-weight:700;">👔 ${admins} Admin</span>` : ''}
                ${staff > 0 ? `<span class="chip" style="background:#f1f5f9; color:#334155; font-weight:700;">🩺 ${staff} Staff</span>` : ''}
                <span class="chip" style="background:#f8fafc; color:#64748b; font-weight:700;">Total: ${g.users.length}</span>
              </div>
            </div>
            <div class="table-wrap" style="border:none; margin:0;">
              <table>
                <thead>
                  <tr>
                    ${cols.map(c => `<th>${c}</th>`).join('')}
                  </tr>
                </thead>
                <tbody>
                  ${rowsHtml}
                </tbody>
              </table>
            </div>
          </div>
        `;
      }).join('');

    } else {
      // =========================================================================
      // 2. HOSPITAL ADMIN VIEW: ROLE COLUMN REMOVED PER REQUIREMENT
      // =========================================================================
      const cols = ['ID', 'Staff Username', 'Email', 'Status', 'Created', 'Actions'];
      const rowsHtml = filtered.map(u => `
        <tr>
          <td>#${u.id}</td>
          <td><b>${UI.esc(u.username)}</b></td>
          <td class="small muted">${UI.esc(u.email || '—')}</td>
          <td>${UI.statusBadge(u.status)}</td>
          <td class="muted small">${UI.fmtDate(u.created_at)}</td>
          <td>
            <button class="btn slim ghost" data-uname="${UI.esc(u.username)}" onclick="editUser(${u.id}, this.dataset.uname)">Edit</button>
            <button class="btn slim ${u.status === 'active' ? 'danger-outline' : 'blue'}" onclick="toggleUser(${u.id},'${u.status}')">
              ${u.status === 'active' ? 'Deactivate' : 'Activate'}
            </button>
          </td>
        </tr>
      `).join('');

      container.innerHTML = `
        <div class="card" style="padding:0; overflow:hidden;">
          <div class="table-wrap" style="border:none; margin:0;">
            <table>
              <thead>
                <tr>
                  ${cols.map(c => `<th>${c}</th>`).join('')}
                </tr>
              </thead>
              <tbody>
                ${rowsHtml}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }
  }

  // Live filter handlers
  document.getElementById('user-search').oninput = renderUsers;
  const hospSelectEl = document.getElementById('hospital-select-filter');
  if (hospSelectEl) hospSelectEl.onchange = renderUsers;
  document.getElementById('refresh-btn').onclick = load;

  // Create User Handler
  document.getElementById('add-btn').onclick = () => {
    if (isSuper) {
      UI.modal('Create System Account', `
        <form id="user-form">
          <div class="form-grid">
            <div class="field full"><label>Username *</label><input name="username" required placeholder="Min. 4 characters"></div>
            <div class="field full"><label>Email (Optional)</label><input name="email" type="email" placeholder="e.g. staff@hospital.com"></div>
            <div class="field full"><label>Password *</label><input name="password" type="password" required placeholder="8+ chars, uppercase, lowercase, number"></div>
            <div class="field"><label>Role *</label>
              <select name="role">
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="field"><label>Assigned Hospital / Facility *</label>
              <select name="hospital_id" required>
                <option value="">Select Hospital Facility</option>
                ${hospitalDropdownOptions}
              </select>
            </div>
          </div>
        </form>`,
        `<button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
         <button class="btn" onclick="saveUser()">Create Account</button>`);
    } else {
      // Hospital Admin creating Official Staff (No role selection)
      UI.modal(`Create Official Staff — ${UI.esc(hospitalName)}`, `
        <form id="user-form">
          <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; margin-bottom:12px; font-size:13px; color:#1e40af;">
            🏥 <b>Facility:</b> ${UI.esc(hospitalName)}<br>
            🩺 <b>Account Type:</b> Official Facility Staff
          </div>
          <div class="form-grid">
            <div class="field full"><label>Staff Username *</label><input name="username" required placeholder="Min. 4 characters (e.g. nurse_maria)"></div>
            <div class="field full"><label>Staff Official Email (Optional)</label><input name="email" type="email" placeholder="e.g. maria@${UI.esc(hospitalName.toLowerCase().replace(/[^a-z0-9]/g, ''))}.ph"></div>
            <div class="field full"><label>Initial Password *</label><input name="password" type="password" required placeholder="8+ chars with uppercase, lowercase & number"></div>
          </div>
        </form>`,
        `<button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
         <button class="btn" onclick="saveUser()" style="background:var(--red,#c0152e); color:#fff; font-weight:700;">+ Create Official Staff</button>`);
    }
  };

  window.saveUser = async () => {
    const form = document.getElementById('user-form');
    if (!form.reportValidity()) return;
    const values = UI.formValues(form);
    UI.closeModal();
    if (!(await UI.confirm('Confirm Account Creation', 'Are you sure you want to create this account?'))) return;
    try {
      const d = await API.post('/api/admin/users', values);
      UI.okToast(d.message);
      load();
    } catch (e) {
      UI.errToast(e.message);
    }
  };

  window.editUser = (id, username) => {
    UI.modal(`Edit Account — ${UI.esc(username)}`, `
      <form id="user-form">
        <div class="grid cols-1" style="display:grid;gap:12px">
          <div class="field"><label>New Username (leave blank to keep current)</label><input name="username" placeholder="${UI.esc(username)}"></div>
          <div class="field"><label>New Password (leave blank to keep current)</label><input name="password" type="password" placeholder="Min. 8 characters"></div>
        </div>
      </form>`,
      `<button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
       <button class="btn" onclick="updateUser(${id})">Save Changes</button>`);
  };

  window.updateUser = async (id) => {
    const values = {};
    const u = document.querySelector('#user-form [name=username]').value;
    const p = document.querySelector('#user-form [name=password]').value;
    if (u) values.username = u;
    if (p) values.password = p;
    if (!Object.keys(values).length) { UI.errToast('Nothing to change.'); return; }
    UI.closeModal();
    if (!(await UI.confirm('Confirm Update', 'Are you sure you want to save changes to this user?'))) return;
    try {
      const d = await API.put(`/api/admin/users/${id}`, values);
      UI.okToast(d.message);
      load();
    } catch (e) {
      UI.errToast(e.message);
    }
  };

  window.toggleUser = async (id, current) => {
    const verb = current === 'active' ? 'Deactivate' : 'Activate';
    if (!(await UI.confirm(verb + ' account', `${verb} this account? Deactivated users cannot log in.`))) return;
    try {
      const d = await API.put(`/api/admin/users/${id}`, { status: current === 'active' ? 'inactive' : 'active' });
      UI.okToast(d.message);
      load();
    } catch (e) {
      UI.errToast(e.message);
    }
  };

  await load();
})();
</script>
</body>
</html>
