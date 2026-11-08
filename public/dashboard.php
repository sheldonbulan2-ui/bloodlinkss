<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — BloodLink Medical Network</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  /* =========================================================================
     CLEAN ENTERPRISE MEDICAL DASHBOARD
     ========================================================================= */
  .dash-hero-container {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 22px 26px;
    margin-bottom: 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    position: relative;
    overflow: hidden;
  }
  .dash-hero-container::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    background: #c0152e;
  }
  .dash-hero-title {
    font-size: 21px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 6px 0;
    letter-spacing: -0.3px;
  }
  .dash-hero-subtitle {
    margin: 0;
    font-size: 13.5px;
    color: #64748b;
    line-height: 1.5;
  }
  .dash-hero-badges {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
    flex-shrink: 0;
  }

  /* Metric KPI Cards */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 22px;
  }
  .kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
  }
  .kpi-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
  }
  .kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
  }
  .kpi-label {
    font-size: 12.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .kpi-icon-wrap {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
  }
  .kpi-num {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
    margin-bottom: 6px;
  }
  .kpi-footer {
    font-size: 11.5px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .kpi-red .kpi-icon-wrap { background: #fef2f2; color: #dc2626; }
  .kpi-amber .kpi-icon-wrap { background: #fffbeb; color: #d97706; }
  .kpi-teal .kpi-icon-wrap { background: #ecfdf5; color: #059669; }
  .kpi-navy .kpi-icon-wrap { background: #f1f5f9; color: #2563eb; }

  /* Section Header */
  .section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
  }
  .section-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* 8-Blood-Type Inventory Matrix */
  .blood-matrix {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 10px;
    margin-bottom: 22px;
  }
  .blood-cell {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 10px;
    text-align: center;
    transition: all 0.2s ease;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
  }
  .blood-cell:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  }
  .blood-type-tag {
    font-size: 15px;
    font-weight: 900;
    color: #c0152e;
    background: #fdf2f4;
    border: 1px solid #fecdd3;
    padding: 3px 8px;
    border-radius: 8px;
    display: inline-block;
    margin-bottom: 8px;
  }
  .blood-units-val {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
  }
  .blood-units-lbl {
    font-size: 10.5px;
    color: #64748b;
    margin-top: 2px;
    text-transform: uppercase;
    font-weight: 600;
  }
  .blood-status-pill {
    font-size: 9.5px;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    margin-top: 6px;
    text-transform: uppercase;
  }
  .status-optimal { background: #dcfce7; color: #166534; }
  .status-low { background: #fef3c7; color: #92400e; }
  .status-empty { background: #fee2e2; color: #991b1b; }

  /* Action Cards Grid */
  .action-hub {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin-bottom: 22px;
  }
  .action-tile {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 12px;
    text-align: center;
    text-decoration: none;
    color: #1e293b;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  }
  .action-tile:hover {
    border-color: #c0152e;
    color: #c0152e;
    background: #fffafa;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(192, 21, 46, 0.08);
  }
  .action-tile-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: all 0.2s ease;
  }
  .action-tile:hover .action-tile-icon {
    background: #fee2e2;
  }
  .action-tile-text {
    font-size: 12.5px;
    font-weight: 700;
    line-height: 1.25;
  }

  /* Two Column Clean Desk */
  .desk-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-bottom: 22px;
  }
  .desk-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
  }
  .desk-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
  }
  .desk-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }
  .desk-row:first-of-type {
    padding-top: 4px;
  }

  /* Responsive Rules for Small Screens */
  @media (max-width: 1024px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .blood-matrix { grid-template-columns: repeat(4, 1fr); }
    .action-hub { grid-template-columns: repeat(3, 1fr); }
    .desk-split { grid-template-columns: 1fr; }
  }
  @media (max-width: 600px) {
    .dash-hero-container {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
      padding: 16px;
    }
    .dash-hero-badges { align-items: flex-start; }
    .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .blood-matrix { grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .action-hub { grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .desk-panel { padding: 16px; }
  }
  @media (max-width: 390px) {
    .kpi-grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>
<script src="js/api.js?v=<?= time() ?>"></script>
<script src="js/common.js?v=<?= time() ?>"></script>
<script src="js/shell.js?v=4"></script>
<script src="js/map.js?v=<?= time() ?>"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'Dashboard', active: 'dashboard' });
  if (!ok) return;

  const role = Shell.role;
  const page = Shell.page();

  if (role === 'superadmin') {
    renderSuperadminDashboard();
  } else if (role === 'admin') {
    renderAdminDashboard();
  } else if (role === 'staff') {
    renderStaffDashboard();
  } else if (role === 'donor') {
    renderDonorDashboard();
  } else if (role === 'patient') {
    renderPatientDashboard();
  } else {
    page.innerHTML = `
      <div class="card" style="text-align:center; padding:40px 20px;">
        <h2>Session Error</h2>
        <p class="muted">Your account role could not be recognized. Please log in again.</p>
        <a href="login.php" class="btn" style="background:#c0152e; color:#fff;">Return to Login</a>
      </div>
    `;
  }

  // =========================================================================
  // 1. SUPERADMIN DASHBOARD (Master Intelligence Center)
  // =========================================================================
  async function renderSuperadminDashboard() {
    const d = (await API.get('/api/dashboard')).dashboard;
    const c = d.counts || {};
    const fs = d.feature_stats || { most_used: [], least_used: [], total_actions: 0 };
    const fbs = d.recent_feedbacks || [];
    const fbStats = d.feedback_stats || { total: 0, avg_rating: 5.0 };
    const rc = d.role_counts || {};

    page.innerHTML = `
      <!-- Hero Header -->
      <div class="dash-hero-container">
        <div>
          <h1 class="dash-hero-title">Master Intelligence &amp; Governance Center</h1>
          <p class="dash-hero-subtitle">System-wide health metrics, multi-facility blood stock oversight, feature adoption, and audit governance.</p>
        </div>
        <div class="dash-hero-badges">
          <span class="role-pill superadmin">🛡️ Super Admin</span>
          <span style="font-size:11.5px; color:#64748b; font-weight:600;">System Active &middot; All Systems Operational</span>
        </div>
      </div>

      <!-- KPI Grid -->
      <div class="kpi-grid">
        <div class="kpi-card kpi-navy">
          <div class="kpi-header">
            <span class="kpi-label">System Accounts</span>
            <div class="kpi-icon-wrap">👥</div>
          </div>
          <div class="kpi-num">${d.users_total || 0}</div>
          <div class="kpi-footer">Active across all roles</div>
        </div>

        <div class="kpi-card kpi-red">
          <div class="kpi-header">
            <span class="kpi-label">Regional Blood Stock</span>
            <div class="kpi-icon-wrap">🩸</div>
          </div>
          <div class="kpi-num">${c.total_units || 0}</div>
          <div class="kpi-footer">Total verified units available</div>
        </div>

        <div class="kpi-card kpi-teal">
          <div class="kpi-header">
            <span class="kpi-label">Partner Facilities</span>
            <div class="kpi-icon-wrap">🏥</div>
          </div>
          <div class="kpi-num">${c.locations || 0}</div>
          <div class="kpi-footer">Connected hospital centers</div>
        </div>

        <div class="kpi-card kpi-amber">
          <div class="kpi-header">
            <span class="kpi-label">Emergency Requests</span>
            <div class="kpi-icon-wrap">⚠️</div>
          </div>
          <div class="kpi-num">${c.pending_requests || 0}</div>
          <div class="kpi-footer">Pending hospital triage</div>
        </div>
      </div>

      <!-- Action Hub -->
      <div class="action-hub">
        <a href="${UI.url('/users.php')}" class="action-tile">
          <div class="action-tile-icon">👥</div>
          <div class="action-tile-text">User Accounts</div>
        </a>
        <a href="${UI.url('/role-permissions.php')}" class="action-tile">
          <div class="action-tile-icon">🛡️</div>
          <div class="action-tile-text">Roles &amp; Access</div>
        </a>
        <a href="${UI.url('/locations.php')}" class="action-tile">
          <div class="action-tile-icon">🏥</div>
          <div class="action-tile-text">Hospital Centers</div>
        </a>
        <a href="${UI.url('/announcements.php')}" class="action-tile">
          <div class="action-tile-icon">📢</div>
          <div class="action-tile-text">Broadcast Alert</div>
        </a>
        <a href="${UI.url('/reports.php')}" class="action-tile">
          <div class="action-tile-icon">📊</div>
          <div class="action-tile-text">Master Reports</div>
        </a>
        <a href="${UI.url('/logs.php')}" class="action-tile">
          <div class="action-tile-icon">📜</div>
          <div class="action-tile-text">Security Logs</div>
        </a>
      </div>

      <!-- Account Role Distribution -->
      <div class="desk-panel" style="margin-bottom:22px;">
        <div class="section-heading">
          <h2 class="section-title">👥 Network Population Distribution</h2>
          <span style="font-size:12px; color:#64748b;">${d.users_inactive || 0} Inactive accounts</span>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
          <span class="chip" style="background:#fee2e2; color:#991b1b; font-weight:700;">🛡️ Superadmins: <b>${rc.superadmin || 1}</b></span>
          <span class="chip" style="background:#f3e8ff; color:#6b21a8; font-weight:700;">👔 Admins: <b>${rc.admin || 0}</b></span>
          <span class="chip" style="background:#dbeafe; color:#1e40af; font-weight:700;">🩺 Hospital Staff: <b>${rc.staff || 0}</b></span>
          <span class="chip" style="background:#dcfce7; color:#166534; font-weight:700;">🩸 Blood Donors: <b>${rc.donor || 0}</b></span>
          <span class="chip" style="background:#ffedd5; color:#9a3412; font-weight:700;">🚑 Patients: <b>${rc.patient || 0}</b></span>
        </div>
      </div>

      <!-- Feature Usage Adoption -->
      <div class="desk-split">
        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title" style="color:#15803d;">🔥 Top Adopted Features</h2>
            <span class="badge" style="background:#dcfce7; color:#166534;">Most Active</span>
          </div>
          <div>
            ${(fs.most_used || []).map((f, i) => `
              <div style="margin-bottom:12px;">
                <div style="display:flex; justify-content:space-between; font-size:13px; font-weight:700; margin-bottom:5px;">
                  <span>${f.icon} #${i + 1} ${UI.esc(f.name)}</span>
                  <span style="color:#15803d;">${f.count} uses (${f.percentage}%)</span>
                </div>
                <div style="width:100%; height:7px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                  <div style="height:100%; background:linear-gradient(90deg, #15803d, #22c55e); width:${f.percentage}%;"></div>
                </div>
              </div>
            `).join('') || '<p class="muted small">Gathering analytics...</p>'}
          </div>
        </div>

        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title" style="color:#b45309;">❄️ Low Engagement Features</h2>
            <span class="badge" style="background:#fef3c7; color:#92400e;">Needs Promotion</span>
          </div>
          <div>
            ${(fs.least_used || []).map((f, i) => `
              <div style="margin-bottom:12px;">
                <div style="display:flex; justify-content:space-between; font-size:13px; font-weight:700; margin-bottom:5px;">
                  <span>${f.icon} ${UI.esc(f.name)}</span>
                  <span style="color:#b45309;">${f.count} uses (${f.percentage}%)</span>
                </div>
                <div style="width:100%; height:7px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                  <div style="height:100%; background:linear-gradient(90deg, #f59e0b, #fbbf24); width:${Math.max(f.percentage, 4)}%;"></div>
                </div>
              </div>
            `).join('') || '<p class="muted small">Gathering analytics...</p>'}
          </div>
        </div>
      </div>

      <!-- Map & Logs Row -->
      <div class="desk-split">
        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">🗺️ Verified Partner Hospital Network</h2>
          </div>
          <div id="dash-map" class="map-box" style="height:340px; border-radius:10px;"></div>
        </div>

        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">📜 Live Security &amp; Audit Trail</h2>
            <a href="${UI.url('/logs.php')}" class="btn ghost slim">Full Log</a>
          </div>
          <div>
            ${(d.recent_logs || []).slice(0, 6).map(x => `
              <div class="desk-row">
                <div>
                  <div style="font-weight:700; font-size:13px; color:#0f172a;">${UI.esc(x.action)}</div>
                  <div style="font-size:12px; color:#64748b;">${UI.esc(x.description || '')}</div>
                </div>
                <span style="font-size:11px; color:#94a3b8; font-weight:600;">${UI.fmtDate(x.created_at)}</span>
              </div>
            `).join('') || '<p class="muted small">No recent logs found.</p>'}
          </div>
        </div>
      </div>
    `;

    renderDashboardMap('dash-map');
  }

  // =========================================================================
  // 2. ADMIN DASHBOARD (Hospital Facility Oversight Center)
  // =========================================================================
  async function renderAdminDashboard() {
    const d = (await API.get('/api/dashboard')).dashboard;
    const c = d.counts || {};
    const hospName = d.hospital ? d.hospital.name : 'Hospital Facility Oversight';

    page.innerHTML = `
      <!-- Hero Header -->
      <div class="dash-hero-container">
        <div>
          <h1 class="dash-hero-title">Facility Administration &mdash; ${UI.esc(hospName)}</h1>
          <p class="dash-hero-subtitle">Comprehensive supervisory dashboard for staff assignments, inventory control, and emergency fulfillment.</p>
        </div>
        <div class="dash-hero-badges">
          <span class="role-pill admin">🏥 Hospital Admin</span>
          <span style="font-size:11.5px; color:#64748b; font-weight:600;">Oversight: ${UI.esc(hospName)}</span>
        </div>
      </div>

      <!-- KPI Grid -->
      <div class="kpi-grid">
        <div class="kpi-card kpi-navy">
          <div class="kpi-header">
            <span class="kpi-label">Hospital Staff</span>
            <div class="kpi-icon-wrap">🩺</div>
          </div>
          <div class="kpi-num">${c.staff || 0}</div>
          <div class="kpi-footer">Active operational personnel</div>
        </div>

        <div class="kpi-card kpi-red">
          <div class="kpi-header">
            <span class="kpi-label">Blood Stock on Hand</span>
            <div class="kpi-icon-wrap">🩸</div>
          </div>
          <div class="kpi-num">${c.total_units || 0}</div>
          <div class="kpi-footer">Total verified available units</div>
        </div>

        <div class="kpi-card kpi-amber">
          <div class="kpi-header">
            <span class="kpi-label">Requisitions in Queue</span>
            <div class="kpi-icon-wrap">⚠️</div>
          </div>
          <div class="kpi-num">${c.pending_requests || 0}</div>
          <div class="kpi-footer">Awaiting staff fulfillment</div>
        </div>

        <div class="kpi-card kpi-teal">
          <div class="kpi-header">
            <span class="kpi-label">Registered Donors</span>
            <div class="kpi-icon-wrap">👥</div>
          </div>
          <div class="kpi-num">${c.donors || 0}</div>
          <div class="kpi-footer">Active blood donor registry</div>
        </div>
      </div>

      <!-- Action Hub -->
      <div class="action-hub">
        <a href="${UI.url('/users.php')}" class="action-tile">
          <div class="action-tile-icon">🩺</div>
          <div class="action-tile-text">Facility Staff</div>
        </a>
        <a href="${UI.url('/inventory.php')}" class="action-tile">
          <div class="action-tile-icon">📦</div>
          <div class="action-tile-text">Blood Inventory</div>
        </a>
        <a href="${UI.url('/requests.php')}" class="action-tile">
          <div class="action-tile-icon">📋</div>
          <div class="action-tile-text">Requisitions</div>
        </a>
        <a href="${UI.url('/donations.php')}" class="action-tile">
          <div class="action-tile-icon">🩸</div>
          <div class="action-tile-text">Donations</div>
        </a>
        <a href="${UI.url('/announcements.php')}" class="action-tile">
          <div class="action-tile-icon">📢</div>
          <div class="action-tile-text">Announcements</div>
        </a>
        <a href="${UI.url('/reports.php')}" class="action-tile">
          <div class="action-tile-icon">📊</div>
          <div class="action-tile-text">Hospital Reports</div>
        </a>
      </div>

      <!-- 8-Blood-Type Inventory Matrix -->
      <div class="desk-panel" style="margin-bottom:22px;">
        <div class="section-heading">
          <h2 class="section-title">🩸 Available Blood Stock by Type &mdash; ${UI.esc(hospName)}</h2>
          <a href="${UI.url('/inventory.php')}" class="btn ghost slim">Manage Inventory</a>
        </div>
        <div class="blood-matrix">
          ${renderBloodMatrix(d.by_type || [])}
        </div>
      </div>

      <!-- Two Column Workstation -->
      <div class="desk-split">
        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">📋 Recent Patient Requisitions</h2>
            <a href="${UI.url('/requests.php')}" class="btn ghost slim">View All</a>
          </div>
          <div>
            ${renderRecentRequests(d.recent_requests || [])}
          </div>
        </div>

        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">🩸 Incoming Donations to Verify</h2>
            <a href="${UI.url('/donations.php?status=Pending')}" class="btn ghost slim">View Queue</a>
          </div>
          <div>
            ${renderRecentDonations(d.recent_donations || [])}
          </div>
        </div>
      </div>
    `;
  }

  // =========================================================================
  // 3. STAFF DASHBOARD (Hospital Operational Command Desk)
  // =========================================================================
  async function renderStaffDashboard() {
    const d = (await API.get('/api/dashboard')).dashboard;
    const c = d.counts || {};
    const hospName = d.hospital ? d.hospital.name : 'Hospital Facility';

    page.innerHTML = `
      <!-- Hero Header -->
      <div class="dash-hero-container">
        <div>
          <h1 class="dash-hero-title">Staff Command Desk &mdash; ${UI.esc(hospName)}</h1>
          <p class="dash-hero-subtitle">Active shift command center: triage blood requests, verify donor medical screenings, and maintain stocks.</p>
        </div>
        <div class="dash-hero-badges">
          <span class="role-pill staff">🩺 Hospital Staff</span>
          <span style="font-size:11.5px; color:#64748b; font-weight:600;">Shift Desk Active</span>
        </div>
      </div>

      <!-- Operational KPIs -->
      <div class="kpi-grid">
        <div class="kpi-card kpi-amber">
          <div class="kpi-header">
            <span class="kpi-label">Pending Requests</span>
            <div class="kpi-icon-wrap">📋</div>
          </div>
          <div class="kpi-num">${c.pending_requests || 0}</div>
          <div class="kpi-footer">Requisitions requiring validation</div>
        </div>

        <div class="kpi-card kpi-amber">
          <div class="kpi-header">
            <span class="kpi-label">Donations to Verify</span>
            <div class="kpi-icon-wrap">⏳</div>
          </div>
          <div class="kpi-num">${c.pending_donations || 0}</div>
          <div class="kpi-footer">Screenings awaiting check-in</div>
        </div>

        <div class="kpi-card kpi-red">
          <div class="kpi-header">
            <span class="kpi-label">Verified Units on Hand</span>
            <div class="kpi-icon-wrap">🩸</div>
          </div>
          <div class="kpi-num">${c.total_units || 0}</div>
          <div class="kpi-footer">Ready for patient release</div>
        </div>

        <div class="kpi-card kpi-teal">
          <div class="kpi-header">
            <span class="kpi-label">Approved &amp; Active</span>
            <div class="kpi-icon-wrap">✅</div>
          </div>
          <div class="kpi-num">${c.approved_requests || 0}</div>
          <div class="kpi-footer">Fulfillments in progress</div>
        </div>
      </div>

      <!-- Action Hub -->
      <div class="action-hub">
        <a href="${UI.url('/requests.php')}" class="action-tile">
          <div class="action-tile-icon">📋</div>
          <div class="action-tile-text">Process Requests</div>
        </a>
        <a href="${UI.url('/donations.php')}" class="action-tile">
          <div class="action-tile-icon">✅</div>
          <div class="action-tile-text">Verify Donations</div>
        </a>
        <a href="${UI.url('/inventory.php')}" class="action-tile">
          <div class="action-tile-icon">📦</div>
          <div class="action-tile-text">Blood Stock</div>
        </a>
        <a href="${UI.url('/announcements.php')}" class="action-tile">
          <div class="action-tile-icon">📢</div>
          <div class="action-tile-text">Announcements</div>
        </a>
        <a href="${UI.url('/donors.php')}" class="action-tile">
          <div class="action-tile-icon">🩸</div>
          <div class="action-tile-text">Donor Directory</div>
        </a>
        <a href="${UI.url('/reports.php')}" class="action-tile">
          <div class="action-tile-icon">📊</div>
          <div class="action-tile-text">Shift Reports</div>
        </a>
      </div>

      <!-- 8-Blood-Type Inventory Matrix -->
      <div class="desk-panel" style="margin-bottom:22px;">
        <div class="section-heading">
          <h2 class="section-title">🩸 Real-Time Blood Stock &mdash; ${UI.esc(hospName)}</h2>
          <a href="${UI.url('/inventory.php')}" class="btn ghost slim">+ Add Units</a>
        </div>
        <div class="blood-matrix">
          ${renderBloodMatrix(d.by_type || [])}
        </div>
      </div>

      <!-- Two Column Operational Desk -->
      <div class="desk-split">
        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">📋 Pending Patient Requests</h2>
            <a href="${UI.url('/requests.php')}" class="btn ghost slim">Process Queue</a>
          </div>
          <div>
            ${renderRecentRequests(d.recent_requests || [])}
          </div>
        </div>

        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">🩸 Incoming Donations to Verify</h2>
            <a href="${UI.url('/donations.php?status=Pending')}" class="btn ghost slim">Verify Queue</a>
          </div>
          <div>
            ${renderRecentDonations(d.recent_donations || [])}
          </div>
        </div>
      </div>
    `;
  }

  // =========================================================================
  // HELPER RENDERERS FOR CLEAN REUSABLE COMPONENTS
  // =========================================================================
  function renderBloodMatrix(byType) {
    const types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    return types.map(t => {
      const match = byType.find(x => x.blood_type === t);
      const units = match ? Number(match.units) : 0;
      let statusClass = 'status-empty';
      let statusText = 'Critical';
      if (units >= 10) {
        statusClass = 'status-optimal';
        statusText = 'Optimal';
      } else if (units > 0) {
        statusClass = 'status-low';
        statusText = 'Low';
      }

      return `
        <div class="blood-cell">
          <span class="blood-type-tag">${t}</span>
          <div class="blood-units-val">${units}</div>
          <div class="blood-units-lbl">Units</div>
          <span class="blood-status-pill ${statusClass}">${statusText}</span>
        </div>
      `;
    }).join('');
  }

  function renderRecentRequests(requests) {
    if (!requests || requests.length === 0) {
      return `
        <div style="text-align:center; padding:28px 14px; color:#94a3b8;">
          <div style="font-size:26px; margin-bottom:6px;">📋</div>
          <div style="font-weight:700; color:#475569; font-size:13.5px;">No Pending Requests</div>
          <div style="font-size:12px;">All patient requisitions are currently fulfilled.</div>
        </div>
      `;
    }

    return requests.slice(0, 5).map(r => `
      <div class="desk-row">
        <div>
          <div style="font-weight:700; font-size:13.5px; color:#0f172a;">
            ${UI.esc(r.first_name || '')} ${UI.esc(r.last_name || 'Requester')}
          </div>
          <div style="font-size:12px; color:#64748b; margin-top:2px;">
            ${r.units_needed} unit(s) ${UI.bloodChip(r.blood_type)} &bull; 
            Priority: <b style="color:${(r.priority || '').toLowerCase() === 'urgent' ? '#dc2626' : '#2563eb'};">${UI.esc(r.priority || 'Normal')}</b>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
          ${UI.statusBadge(r.status)}
          <a href="${UI.url('/requests.php')}" class="btn slim ghost" style="padding:4px 8px; font-size:11.5px;">Review</a>
        </div>
      </div>
    `).join('');
  }

  function renderRecentDonations(donations) {
    if (!donations || donations.length === 0) {
      return `
        <div style="text-align:center; padding:28px 14px; color:#94a3b8;">
          <div style="font-size:26px; margin-bottom:6px;">🩸</div>
          <div style="font-weight:700; color:#475569; font-size:13.5px;">No Pending Donations</div>
          <div style="font-size:12px;">Incoming donor queue is clear.</div>
        </div>
      `;
    }

    return donations.slice(0, 5).map(dn => `
      <div class="desk-row">
        <div>
          <div style="font-weight:700; font-size:13.5px; color:#0f172a;">
            ${UI.esc(dn.first_name || '')} ${UI.esc(dn.last_name || 'Donor')}
          </div>
          <div style="font-size:12px; color:#64748b; margin-top:2px;">
            ${dn.blood_units} unit(s) ${UI.bloodChip(dn.blood_type)} &bull; 
            Date: ${UI.fmtDate(dn.donation_date)}
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
          ${UI.statusBadge(dn.status)}
          <a href="${UI.url('/donations.php?status=Pending')}" class="btn slim ghost" style="padding:4px 8px; font-size:11.5px;">Verify</a>
        </div>
      </div>
    `).join('');
  }

  async function renderDashboardMap(containerId) {
    const el = document.getElementById(containerId);
    if (!el || typeof L === 'undefined') return;
    try {
      const map = L.map(containerId).setView([12.8797, 121.7740], 6);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      const locs = (await API.get('/api/blood/locations')).locations || [];
      locs.forEach(l => {
        if (l.latitude && l.longitude) {
          L.marker([parseFloat(l.latitude), parseFloat(l.longitude)])
            .addTo(map)
            .bindPopup(`<b>${UI.esc(l.name)}</b><br>${UI.esc(l.address)}`);
        }
      });
    } catch (e) { /* non-blocking map failure */ }
  }

  // =========================================================================
  // 4. DONOR DASHBOARD (Self-Service Blood Donor Portal)
  // =========================================================================
  async function renderDonorDashboard() {
    let stats = {}, donations = [], myReqs = [];
    try {
      const sData = await API.get('/api/donor/stats');
      stats = sData.stats || {};
    } catch (_) {}
    try {
      const hData = await API.get('/api/donor/history');
      donations = hData.donations || [];
    } catch (_) {}
    try {
      const rData = await API.get('/api/patient/requests');
      myReqs = rData.requests || [];
    } catch (_) {}

    const name = (Shell.profile && (Shell.profile.first_name || Shell.profile.last_name))
      ? `${Shell.profile.first_name} ${Shell.profile.last_name}`.trim()
      : (Shell.user.username || 'Blood Donor');
    const bType = stats.blood_type || Shell.profile?.blood_type || 'O+';
    const totalDons = Number(stats.total_donations || donations.length || 0);
    const totalUnits = Number(stats.blood_units || 0);
    const livesSaved = Math.max(totalDons, totalUnits) * 3;
    const inCooldown = !!stats.in_cooldown;
    const cooldownDate = stats.next_eligible_date || '';

    page.innerHTML = `
      <!-- Donor Hero -->
      <div class="dash-hero-container">
        <div>
          <h1 class="dash-hero-title">Welcome back, ${UI.esc(name)}! 🩸</h1>
          <p class="dash-hero-subtitle">
            Your generous blood donations make an immediate, life-saving difference for patients in emergency trauma, surgery, and maternal care.
          </p>
        </div>
        <div class="dash-hero-badges">
          <span class="role-badge staff" style="background:#fef2f2; color:#b91c1c; border-color:#fecaca;">
            🩸 Verified Blood Donor
          </span>
          <span style="font-size:12px; font-weight:700; color:#475569;">
            Blood Type: ${UI.bloodChip(bType)}
          </span>
        </div>
      </div>

      <!-- Cooldown Alert Banner if active -->
      ${inCooldown ? `
        <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:12px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
          <div style="display:flex; align-items:center; gap:12px;">
            <div style="font-size:26px;">⏳</div>
            <div>
              <b style="color:#be123c; font-size:14px;">Donation Cooldown In Effect</b>
              <div style="color:#881337; font-size:12.5px;">Your body is regenerating hemoglobin. You will be eligible to donate again on <b>${UI.esc(cooldownDate)}</b>.</div>
            </div>
          </div>
          <span class="badge" style="background:#f43f5e; color:#fff; font-weight:700; padding:6px 12px;">Cooldown Active</span>
        </div>
      ` : `
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
          <div style="display:flex; align-items:center; gap:12px;">
            <div style="font-size:26px;">✅</div>
            <div>
              <b style="color:#15803d; font-size:14px;">Eligible to Donate Blood Today!</b>
              <div style="color:#166534; font-size:12.5px;">You have no active donation cooldowns. You can schedule a donation slot at any partner medical center.</div>
            </div>
          </div>
          <a href="${UI.url('/donate.php')}" class="btn" style="background:#16a34a; color:#fff; font-weight:700; padding:7px 16px;">Schedule Now &rarr;</a>
        </div>
      `}

      <!-- 4 KPI Metrics Cards -->
      <div class="kpi-grid">
        <div class="kpi-card kpi-red">
          <div class="kpi-header">
            <span class="kpi-label">Total Donations</span>
            <div class="kpi-icon-wrap">🩸</div>
          </div>
          <div class="kpi-num">${totalDons}</div>
          <div class="kpi-footer">Completed blood drives &amp; center visits</div>
        </div>

        <div class="kpi-card kpi-navy">
          <div class="kpi-header">
            <span class="kpi-label">Units Extracted</span>
            <div class="kpi-icon-wrap">📦</div>
          </div>
          <div class="kpi-num">${totalUnits || totalDons}</div>
          <div class="kpi-footer">Standard 450 mL whole blood units</div>
        </div>

        <div class="kpi-card kpi-teal">
          <div class="kpi-header">
            <span class="kpi-label">Estimated Lives Saved</span>
            <div class="kpi-icon-wrap">❤️</div>
          </div>
          <div class="kpi-num">${livesSaved}</div>
          <div class="kpi-footer">Up to 3 patient recipients per unit</div>
        </div>

        <div class="kpi-card kpi-amber">
          <div class="kpi-header">
            <span class="kpi-label">Next Eligible Date</span>
            <div class="kpi-icon-wrap">📅</div>
          </div>
          <div class="kpi-num" style="font-size:18px; line-height:28px;">${inCooldown ? UI.esc(cooldownDate) : 'Today'}</div>
          <div class="kpi-footer">${inCooldown ? 'Mandatory 3-month safety interval' : 'Cleared for appointment'}</div>
        </div>
      </div>

      <!-- Quick Action Hub -->
      <div class="action-hub">
        <a href="${UI.url('/donate.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#fef2f2; color:#c0152e;">📅</div>
          <div class="action-tile-text">Book Donation</div>
        </a>
        <a href="${UI.url('/requests.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#eff6ff; color:#1d4ed8;">🚑</div>
          <div class="action-tile-text">Request Blood</div>
        </a>
        <a href="${UI.url('/history.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#f0fdf4; color:#15803d;">📜</div>
          <div class="action-tile-text">Donation Records</div>
        </a>
        <a href="${UI.url('/search.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#fdf4ff; color:#9333ea;">🔍</div>
          <div class="action-tile-text">Live Blood Search</div>
        </a>
        <a href="${UI.url('/map.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#fefce8; color:#a16207;">📍</div>
          <div class="action-tile-text">Blood Map (PH)</div>
        </a>
        <a href="${UI.url('/feedbacks.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#f5f3ff; color:#6366f1;">💬</div>
          <div class="action-tile-text">Feedback</div>
        </a>
      </div>

      <!-- Main Content Grid -->
      <div class="desk-grid">
        <!-- Left: Donation History -->
        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">🩸 My Blood Donation History</h2>
            <a href="${UI.url('/donate.php')}" class="btn ghost slim">Book Appointment</a>
          </div>

          ${donations.length === 0 ? `
            <div style="text-align:center; padding:36px 16px; color:#64748b;">
              <div style="font-size:28px; margin-bottom:8px;">🩸</div>
              <div style="font-weight:700; color:#0f172a; font-size:14px;">No Donations Recorded Yet</div>
              <p style="font-size:12.5px; max-width:320px; margin:4px auto 14px;">Schedule your first blood donation at a verified hospital partner today!</p>
              <a href="${UI.url('/donate.php')}" class="btn slim" style="background:#c0152e; color:#fff;">Schedule First Donation</a>
            </div>
          ` : `
            <div style="display:flex; flex-direction:column; gap:10px;">
              ${donations.slice(0, 5).map(d => `
                <div class="desk-row">
                  <div>
                    <div style="font-weight:700; font-size:13.5px; color:#0f172a;">
                      ${UI.esc(d.donation_location || 'Hospital Center')}
                    </div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">
                      ${UI.bloodChip(d.blood_type)} ${d.blood_units} unit(s) &bull; Date: ${UI.fmtDate(d.donation_date)}
                      ${d.reference_code ? `&bull; Ref: <code style="font-size:11px;">${UI.esc(d.reference_code)}</code>` : ''}
                    </div>
                  </div>
                  <div style="text-align:right;">
                    ${UI.statusBadge(d.status)}
                    ${d.approved_by_hospital ? `<div style="font-size:11px; color:#15803d; font-weight:700; margin-top:3px;">✅ Verified by Hospital</div>` : ''}
                  </div>
                </div>
              `).join('')}
            </div>
            <div style="text-align:center; margin-top:14px;">
              <a href="${UI.url('/history.php')}" class="small muted" style="font-weight:600; text-decoration:underline;">View All Donation Records &rarr;</a>
            </div>
          `}
        </div>

        <!-- Right: Donor Preparation Guidelines & Blood Locations -->
        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">📋 Pre-Donation Checklist</h2>
            <span class="badge" style="background:#eff6ff; color:#1e40af; font-size:11px;">DOH / NVBSP</span>
          </div>

          <div style="display:flex; flex-direction:column; gap:12px; font-size:13px; color:#334155; line-height:1.5;">
            <div style="display:flex; gap:10px; align-items:flex-start;">
              <span style="font-size:18px;">⚖️</span>
              <div><b>Weight Requirement:</b> Donors must weigh at least 50 kg (110 lbs) for whole blood donation.</div>
            </div>
            <div style="display:flex; gap:10px; align-items:flex-start;">
              <span style="font-size:18px;">😴</span>
              <div><b>Adequate Rest:</b> At least 6 to 8 hours of uninterrupted sleep the night before donation.</div>
            </div>
            <div style="display:flex; gap:10px; align-items:flex-start;">
              <span style="font-size:18px;">🚫</span>
              <div><b>No Alcohol:</b> Avoid consuming alcoholic beverages for at least 24 hours prior to donation.</div>
            </div>
            <div style="display:flex; gap:10px; align-items:flex-start;">
              <span style="font-size:18px;">💧</span>
              <div><b>Hydration &amp; Meal:</b> Drink plenty of water and eat a light, non-fatty meal before proceeding to the center.</div>
            </div>
          </div>

          <div style="margin-top:20px; border-top:1px solid #e2e8f0; padding-top:14px;">
            <h3 style="font-size:13.5px; font-weight:800; color:#0f172a; margin:0 0 10px 0;">🗺️ Verified Partner Hospitals</h3>
            <div id="donor-map" class="map-box" style="height:190px; border-radius:10px; border:1px solid #cbd5e1;"></div>
          </div>
        </div>
      </div>
    `;

    renderDashboardMap('donor-map');
  }

  // =========================================================================
  // 5. PATIENT DASHBOARD (Self-Service Patient / Requisition Portal)
  // =========================================================================
  async function renderPatientDashboard() {
    let requests = [], patient = {}, donations = [];
    try {
      const rData = await API.get('/api/patient/requests');
      requests = rData.requests || [];
      patient = rData.patient || {};
    } catch (_) {}
    try {
      const hData = await API.get('/api/donor/history');
      donations = hData.donations || [];
    } catch (_) {}

    const name = (Shell.profile && (Shell.profile.first_name || Shell.profile.last_name))
      ? `${Shell.profile.first_name} ${Shell.profile.last_name}`.trim()
      : (patient.first_name ? `${patient.first_name} ${patient.last_name || ''}`.trim() : (Shell.user.username || 'Patient'));
    const bNeeded = patient.blood_type_needed || Shell.profile?.blood_type || 'O+';
    const totalReqs = requests.length;
    const pendingReqs = requests.filter(r => ['Pending', 'Needs Correction'].includes(r.status)).length;
    const approvedReqs = requests.filter(r => ['Approved', 'Fulfilled', 'Verified', 'Completed'].includes(r.status)).length;

    page.innerHTML = `
      <!-- Patient Hero -->
      <div class="dash-hero-container">
        <div>
          <h1 class="dash-hero-title">Welcome, ${UI.esc(name)} 🚑</h1>
          <p class="dash-hero-subtitle">
            Submit emergency blood requisitions, upload physician prescription slips, and monitor hospital verification in real time.
          </p>
        </div>
        <div class="dash-hero-badges">
          <span class="role-badge staff" style="background:#fff7ed; color:#c2410c; border-color:#ffedd5;">
            🚑 Patient / Requester Portal
          </span>
          <span style="font-size:12px; font-weight:700; color:#475569;">
            Blood Needed: ${UI.bloodChip(bNeeded)}
          </span>
        </div>
      </div>

      <!-- 4 KPI Metrics Cards -->
      <div class="kpi-grid">
        <div class="kpi-card kpi-red">
          <div class="kpi-header">
            <span class="kpi-label">My Requisitions</span>
            <div class="kpi-icon-wrap">📋</div>
          </div>
          <div class="kpi-num">${totalReqs}</div>
          <div class="kpi-footer">Total official blood requests filed</div>
        </div>

        <div class="kpi-card kpi-amber">
          <div class="kpi-header">
            <span class="kpi-label">Pending Review</span>
            <div class="kpi-icon-wrap">⏳</div>
          </div>
          <div class="kpi-num">${pendingReqs}</div>
          <div class="kpi-footer">${pendingReqs > 0 ? 'Under Section F hospital triage' : 'No active pending triage'}</div>
        </div>

        <div class="kpi-card kpi-teal">
          <div class="kpi-header">
            <span class="kpi-label">Approved &amp; Ready</span>
            <div class="kpi-icon-wrap">✅</div>
          </div>
          <div class="kpi-num">${approvedReqs}</div>
          <div class="kpi-footer">Fulfilled or released requests</div>
        </div>

        <div class="kpi-card kpi-navy">
          <div class="kpi-header">
            <span class="kpi-label">Required Type</span>
            <div class="kpi-icon-wrap">🩸</div>
          </div>
          <div class="kpi-num" style="font-size:24px;">${bNeeded}</div>
          <div class="kpi-footer">Recipient matching priority</div>
        </div>
      </div>

      <!-- Quick Action Hub -->
      <div class="action-hub">
        <a href="${UI.url('/requests.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#fef2f2; color:#c0152e;">📋</div>
          <div class="action-tile-text">New Request</div>
        </a>
        <a href="${UI.url('/donate.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#eff6ff; color:#1d4ed8;">🩸</div>
          <div class="action-tile-text">Donate Blood</div>
        </a>
        <a href="${UI.url('/history.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#f0fdf4; color:#15803d;">📜</div>
          <div class="action-tile-text">My Requests</div>
        </a>
        <a href="${UI.url('/search.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#fdf4ff; color:#9333ea;">🔍</div>
          <div class="action-tile-text">Search Blood</div>
        </a>
        <a href="${UI.url('/map.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#fefce8; color:#a16207;">📍</div>
          <div class="action-tile-text">Hospital Map</div>
        </a>
        <a href="${UI.url('/feedbacks.php')}" class="action-tile">
          <div class="action-tile-icon" style="background:#f5f3ff; color:#6366f1;">💬</div>
          <div class="action-tile-text">Feedback</div>
        </a>
      </div>

      <!-- Main Content Grid -->
      <div class="desk-grid">
        <!-- Left: My Blood Requisitions Queue -->
        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">📋 My Active Blood Requisitions</h2>
            <a href="${UI.url('/requests.php')}" class="btn ghost slim">Submit New Request</a>
          </div>

          ${requests.length === 0 ? `
            <div style="text-align:center; padding:36px 16px; color:#64748b;">
              <div style="font-size:28px; margin-bottom:8px;">📋</div>
              <div style="font-weight:700; color:#0f172a; font-size:14px;">No Blood Requests Filed</div>
              <p style="font-size:12.5px; max-width:320px; margin:4px auto 14px;">If you or your patient needs blood urgently, submit a formal requisition slip.</p>
              <a href="${UI.url('/requests.php')}" class="btn slim" style="background:#c0152e; color:#fff;">Submit Requisition Slip</a>
            </div>
          ` : `
            <div style="display:flex; flex-direction:column; gap:10px;">
              ${requests.slice(0, 5).map(r => `
                <div class="desk-row">
                  <div>
                    <div style="font-weight:700; font-size:13.5px; color:#0f172a;">
                      Ref: ${UI.esc(r.request_ref_no || ('#' + r.id))} &bull; ${UI.esc(r.patient_name || 'Patient')}
                    </div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">
                      ${UI.bloodChip(r.blood_type)} ${r.units_needed} unit(s) &bull; Urgency: <b>${UI.esc(r.urgency_level || r.priority || 'Normal')}</b> &bull; Date: ${UI.fmtDate(r.request_date)}
                    </div>
                  </div>
                  <div style="text-align:right;">
                    ${UI.statusBadge(r.status)}
                    <div style="font-size:11px; margin-top:3px; color:#475569;">
                      Sec. F: ${r.document_status === 'Verified' ? '<span style="color:#15803d; font-weight:700;">Verified</span>' : '<span style="color:#b45309; font-weight:700;">In Review</span>'}
                    </div>
                  </div>
                </div>
              `).join('')}
            </div>
            <div style="text-align:center; margin-top:14px;">
              <a href="${UI.url('/requests.php')}" class="small muted" style="font-weight:600; text-decoration:underline;">View All Requisitions &rarr;</a>
            </div>
          `}
        </div>

        <!-- Right: Blood Requisition Flow & Hospital Hotlines -->
        <div class="desk-panel">
          <div class="section-heading">
            <h2 class="section-title">🏥 Hospital Requisition Steps</h2>
            <span class="badge" style="background:#fef3c7; color:#92400e; font-size:11px;">Standard Protocol</span>
          </div>

          <div style="display:flex; flex-direction:column; gap:14px; font-size:13px; color:#334155; line-height:1.5;">
            <div style="display:flex; gap:10px;">
              <div style="width:24px; height:24px; border-radius:50%; background:#c0152e; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:11px; flex-shrink:0;">1</div>
              <div><b>Submit Online Request:</b> Fill in Sections A through E with patient information, attending physician, and hospital details.</div>
            </div>
            <div style="display:flex; gap:10px;">
              <div style="width:24px; height:24px; border-radius:50%; background:#0284c7; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:11px; flex-shrink:0;">2</div>
              <div><b>Upload Doctor's Slip:</b> Attach a clear photo or PDF scan of the official physician requisition slip.</div>
            </div>
            <div style="display:flex; gap:10px;">
              <div style="width:24px; height:24px; border-radius:50%; background:#16a34a; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:11px; flex-shrink:0;">3</div>
              <div><b>Section F Verification &amp; Release:</b> Hospital Medical Technologist verifies compatibility and reserves the units for pickup.</div>
            </div>
          </div>

          <div style="margin-top:20px; border-top:1px solid #e2e8f0; padding-top:14px;">
            <h3 style="font-size:13.5px; font-weight:800; color:#0f172a; margin:0 0 10px 0;">📍 Connected Hospital Blood Centers</h3>
            <div id="patient-map" class="map-box" style="height:190px; border-radius:10px; border:1px solid #cbd5e1;"></div>
          </div>
        </div>
      </div>
    `;

    renderDashboardMap('patient-map');
  }

})();
</script>
</body>
</html>
