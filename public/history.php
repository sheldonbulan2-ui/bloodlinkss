<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Donation History — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=2"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'My Donation History', active: 'history' });
  if (!ok) return;
  if (Shell.role !== 'donor' && Shell.role !== 'patient') {
    Shell.page().innerHTML = '<div class="empty"><b>This page is for donor and patient accounts.</b></div>';
    return;
  }
  const page = Shell.page();
  try {
    const data = await API.get('/api/donor/history');
    const rows = data.donations.map(d => `
      <tr>
        <td>${UI.fmtDate(d.donation_date)}</td>
        <td>${UI.bloodChip(d.blood_type)}</td>
        <td>${d.blood_units}</td>
        <td>${UI.esc(d.donation_location)}</td>
        <td style="text-align:right">
          ${
            d.status === 'Cancelled' && d.approved_by_hospital ?
              `<span class="badge" style="background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; font-weight:600; white-space:nowrap;">✅ Verified by ${UI.esc(d.approved_by_hospital)}</span><br><small class="muted" style="font-size:11px;">Auto-closed (Fulfilled)</small>` :
            (d.status === 'Verified' || d.status === 'Approved' || d.status === 'Completed') && d.approved_by_hospital ?
              `${UI.statusBadge(d.status)}<br><small style="color:#15803d; font-weight:700; font-size:11px;">✅ By ${UI.esc(d.approved_by_hospital)}</small>` :
            UI.statusBadge(d.status)
          }
          ${d.reference_code ? `<br><small style="color:#888;font-size:11px;display:block;margin-top:4px;">Ref: ${UI.esc(d.reference_code)}</small>` : ''}
        </td>
      </tr>`).join('');
    page.innerHTML = `
      <div class="card">
        <div class="toolbar">
          <h2>All donations</h2>
          <span class="spacer"></span>
          <span class="muted small">"Approved" donations are added to blood inventory after staff review.</span>
        </div>
        ${UI.table(['Date', 'Blood Type', 'Units', 'Location', 'Status'], rows)}
      </div>`;
  } catch (e) {
    UI.errToast(e.message);
  }
})();
</script>
<div id="toasts"></div>
</body>
</html>

