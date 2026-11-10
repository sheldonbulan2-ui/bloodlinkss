<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Roles & Permissions — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=2"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'Roles & Permissions', active: 'roles' });
  if (!ok) return;
  if (Shell.role !== 'superadmin') {
    Shell.page().innerHTML = '<div class="empty"><b>Only the Super Admin can manage roles and permissions.</b></div>'; return;
  }
  const page = Shell.page();
  const data = await API.get('/api/admin/permissions');
  const matrix = { staff: {} };
  for (const r of data.staff_only) matrix.staff[r.module] = !!r.can_access;

  const db = data.staff_admin_users || [];
  page.innerHTML = `
    <div class="grid cols-2">
      <div class="card">
        <h2>Hospital Staff Module Permissions</h2>
        <p class="muted small" style="margin-bottom:10px">Configure access permissions strictly for official hospital staff. Superadmins and Hospital Admins maintain supervisory access.</p>
        <div class="table-wrap"><table>
          <thead><tr><th>Module</th><th>Staff Access Allowed</th></tr></thead>
          <tbody>${data.modules.map(m => `<tr>
            <td><b>${m}</b></td>
            <td><input type="checkbox" data-role="staff" data-mod="${m}" ${matrix.staff[m] ? 'checked' : ''}></td>
          </tr>`).join('')}</tbody>
        </table></div>
        <button class="btn" id="save-btn" style="margin-top:10px">Save Staff Permissions</button>
      </div>
      <div class="card">
        <h2>Staff &amp; Admin Accounts</h2>
        <p class="muted small" style="margin-bottom:10px">Active accounts governed by these security access rules.</p>
        ${UI.table(['Username', 'Role', 'Status'],
          db.map(u => `<tr><td>${UI.esc(u.username)}</td><td><span class="badge ${u.role === 'superadmin' ? 'red' : (u.role === 'admin' ? 'blue' : 'dark')}">${u.role}</span></td><td>${UI.statusBadge(u.status)}</td></tr>`).join(''))}
      </div>
    </div>`;

  document.getElementById('save-btn').onclick = async () => {
    const payload = { staff: {} };
    for (const cb of document.querySelectorAll('input[type=checkbox][data-role=staff]')) {
      payload.staff[cb.dataset.mod] = cb.checked;
    }
    try {
      const d = await API.put('/api/admin/permissions', payload);
      UI.okToast(d.message);
    } catch (e) { UI.errToast(e.message); }
  };
})();
</script>
<div id="toasts"></div>
</body>
</html>

