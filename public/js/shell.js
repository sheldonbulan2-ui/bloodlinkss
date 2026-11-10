/**
 * public/js/shell.js
 * ------------------
 * Enterprise Layout Shell (Categorized Dark Sidebar + Clean Topbar + Responsive Viewport)
 * Highly organized by role: Hospital Staff, Hospital Administrator, Super Admin, Donor & Patient.
 */
const SVG_ICONS = {
  home: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>`,
  users: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>`,
  drop: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>`,
  user: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`,
  calendar: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>`,
  box: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>`,
  doc: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>`,
  map: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`,
  chart: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>`,
  clock: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`,
  gear: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>`,
  bell: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>`,
  megaphone: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H2v6h4l5 4V5z"></path><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>`,
  update: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>`,
  shield: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>`,
  search: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>`,
  chat: `<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>`,
  logout: `<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>`
};

const NAV_GROUPS = {
  // =========================================================================
  // 1. SUPERADMIN (System Master Governance & Multi-Facility Network)
  // =========================================================================
  superadmin: [
    {
      category: 'GOVERNANCE & USERS',
      items: [
        ['dashboard', 'Master Intelligence', 'home', UI.url('/dashboard.php')],
        ['users', 'System Users & Staff', 'users', UI.url('/users.php')],
        ['roles', 'Roles & Permissions', 'shield', UI.url('/role-permissions.php')]
      ]
    },
    {
      category: 'NETWORK OPERATIONS',
      items: [
        ['requests', 'Blood Requests', 'doc', UI.url('/requests.php')],
        ['donations', 'Donations & Screening', 'calendar', UI.url('/donations.php')],
        ['inventory', 'Regional Inventory', 'box', UI.url('/inventory.php')],
        ['locations', 'Partner Facilities', 'map', UI.url('/locations.php')]
      ]
    },
    {
      category: 'POPULATION ROSTER',
      items: [
        ['donors', 'Registered Donors', 'drop', UI.url('/donors.php')],
        ['patients', 'Patient Directory', 'user', UI.url('/patients.php')]
      ]
    },
    {
      category: 'AUDIT & SYSTEM',
      items: [
        ['reports', 'Master Audit Reports', 'chart', UI.url('/reports.php')],
        ['logs', 'Security & Audit Logs', 'clock', UI.url('/logs.php')],
        ['announcements', 'Announcements & Broadcasts', 'megaphone', UI.url('/announcements.php')],
        ['app-updates', 'App Version Updates', 'update', UI.url('/app-updates.php')],
        ['settings', 'System Settings', 'gear', UI.url('/settings.php')]
      ]
    }
  ],

  // =========================================================================
  // 2. ADMIN (Hospital Facility Management & Oversight)
  // =========================================================================
  admin: [
    {
      category: 'HOSPITAL MANAGEMENT',
      items: [
        ['dashboard', 'Admin Overview', 'home', UI.url('/dashboard.php')],
        ['users', 'Official Hospital Staff', 'users', UI.url('/users.php')],
        ['announcements', 'Hospital Announcements', 'megaphone', UI.url('/announcements.php')]
      ]
    },
    {
      category: 'BLOOD OPERATIONS',
      items: [
        ['inventory', 'Facility Blood Inventory', 'box', UI.url('/inventory.php')],
        ['requests', 'Blood Requisitions', 'doc', UI.url('/requests.php')],
        ['donations', 'Donor Verifications', 'calendar', UI.url('/donations.php')]
      ]
    },
    {
      category: 'PATIENTS & DONORS',
      items: [
        ['donors', 'Registered Donors', 'drop', UI.url('/donors.php')],
        ['patients', 'Patient Directory', 'user', UI.url('/patients.php')]
      ]
    },
    {
      category: 'FACILITY AUDIT & SYSTEM',
      items: [
        ['reports', 'Hospital Reports', 'chart', UI.url('/reports.php')],
        ['logs', 'Facility Audit Logs', 'clock', UI.url('/logs.php')],
        ['settings', 'Hospital Settings', 'gear', UI.url('/settings.php')]
      ]
    }
  ],

  // =========================================================================
  // 3. STAFF (Hospital Operations Command Desk)
  // =========================================================================
  staff: [
    {
      category: 'OPERATIONS DESK',
      items: [
        ['dashboard', 'Staff Dashboard', 'home', UI.url('/dashboard.php')],
        ['requests', 'Blood Requests', 'doc', UI.url('/requests.php')],
        ['donations', 'Donor Donations', 'calendar', UI.url('/donations.php')],
        ['inventory', 'Blood Stock on Hand', 'box', UI.url('/inventory.php')],
        ['announcements', 'Announcements & Alerts', 'megaphone', UI.url('/announcements.php')]
      ]
    },
    {
      category: 'ROSTER & DIRECTORY',
      items: [
        ['donors', 'Registered Donors', 'drop', UI.url('/donors.php')],
        ['patients', 'Patient Directory', 'user', UI.url('/patients.php')]
      ]
    },
    {
      category: 'DAILY REPORTS & ALERTS',
      items: [
        ['reports', 'Shift Reports', 'chart', UI.url('/reports.php')]
      ]
    }
  ],

  // =========================================================================
  // 4. DONOR (Blood Donor Portal)
  // =========================================================================
  donor: [
    {
      category: 'DONOR PORTAL',
      items: [
        ['dashboard', 'Donor Dashboard', 'home', UI.url('/dashboard.php')],
        ['profile', 'My Donor Profile', 'user', UI.url('/profile.php')]
      ]
    },
    {
      category: 'BLOOD SERVICES',
      items: [
        ['donate', 'Schedule Blood Donation', 'drop', UI.url('/donate.php')],
        ['requests', 'Emergency Blood Request', 'doc', UI.url('/requests.php')],
        ['history', 'Donation Records', 'calendar', UI.url('/history.php')]
      ]
    },
    {
      category: 'FACILITY FINDER',
      items: [
        ['search', 'Live Blood Search', 'search', UI.url('/search.php')],
        ['map', 'Regional Blood Map', 'map', UI.url('/map.php')]
      ]
    },
    {
      category: 'COMMUNITY & SUPPORT',
      items: [
        ['announcements', 'Announcements & Alerts', 'megaphone', UI.url('/announcements.php')],
        ['feedbacks', 'Feedback & Suggestions', 'chat', UI.url('/feedbacks.php')]
      ]
    }
  ],

  // =========================================================================
  // 5. PATIENT (Patient & Requester Portal)
  // =========================================================================
  patient: [
    {
      category: 'PATIENT PORTAL',
      items: [
        ['dashboard', 'Patient Dashboard', 'home', UI.url('/dashboard.php')],
        ['profile', 'My Medical Profile', 'user', UI.url('/profile.php')]
      ]
    },
    {
      category: 'BLOOD REQUISITION',
      items: [
        ['requests', 'Submit Blood Request', 'doc', UI.url('/requests.php')],
        ['history', 'My Blood Requests', 'calendar', UI.url('/history.php')],
        ['donate', 'Schedule Blood Donation', 'drop', UI.url('/donate.php')]
      ]
    },
    {
      category: 'AVAILABILITY FINDER',
      items: [
        ['search', 'Live Blood Search', 'search', UI.url('/search.php')],
        ['map', 'Regional Blood Map', 'map', UI.url('/map.php')]
      ]
    },
    {
      category: 'COMMUNITY & SUPPORT',
      items: [
        ['announcements', 'Announcements & Alerts', 'megaphone', UI.url('/announcements.php')],
        ['feedbacks', 'Feedback & Suggestions', 'chat', UI.url('/feedbacks.php')]
      ]
    }
  ]
};


const Shell = {
  user: null, role: null, profile: null, counts: null, hospital: null,

  async init({ title = 'BloodLink', active = '' }) {
    try {
      const me = await API.get('/api/auth/me');
      Shell.user = me.user;
      Shell.role = me.user.role;
      Shell.profile = me.profile;
      Shell.hospital = me.hospital || null;
      Shell.counts = me.counts || { requests: 0, donations: 0 };

      // Donor and Patient web access fully supported
    } catch (e) {
      location.href = UI.url('/login.php');
      return false;
    }

    const role = Shell.role;
    const groups = NAV_GROUPS[role] || [];
    const name = (Shell.profile && (Shell.profile.first_name || Shell.profile.last_name)
      ? `${Shell.profile.first_name} ${Shell.profile.last_name}`.trim() : Shell.user.username);
    const initials = name.split(' ').filter(Boolean).map((w) => w[0]).join('').slice(0, 2).toUpperCase() || 'U';

    const reqBadgeCount = Number(Shell.counts?.requests || 0);
    const donBadgeCount = Number(Shell.counts?.donations || 0);

    const renderedNav = groups.map(g => `
      <div class="nav-group">
        ${g.category ? `<div class="nav-category-header">${g.category}</div>` : ''}
        <div class="nav-group-items">
          ${g.items.map(([key, label, icon, href, hasBadge]) => {
            const isActive = (key === active);
            let badgeHtml = '';
            if (key === 'requests' && reqBadgeCount > 0) {
              badgeHtml = `<span class="nav-badge danger" id="sidebar-requests-count" title="${reqBadgeCount} pending blood request(s)">${reqBadgeCount}</span>`;
            } else if (key === 'donations' && donBadgeCount > 0) {
              badgeHtml = `<span class="nav-badge amber" id="sidebar-donations-count" title="${donBadgeCount} pending donation(s)">${donBadgeCount}</span>`;
            } else if (hasBadge) {
              badgeHtml = `<span class="nav-badge" id="sidebar-notif-count" style="display:none">0</span>`;
            }

            return `
              <a href="${href}" class="nav-item ${isActive ? 'active' : ''}">
                ${isActive ? '<span class="nav-active-bar"></span>' : ''}
                <span class="nav-icon">${SVG_ICONS[icon] || ''}</span>
                <span class="nav-label">${label}</span>
                ${badgeHtml}
              </a>
            `;
          }).join('')}
        </div>
      </div>
    `).join('');

    const hospBadgeHtml = Shell.hospital && Shell.hospital.name ? `
      <span class="chip" style="background:rgba(225, 29, 72, 0.12); color:#fda4af; font-size:11px; font-weight:700; border:1px solid rgba(225, 29, 72, 0.3); max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
        🏥 ${UI.esc(Shell.hospital.name)}
      </span>
    ` : '';

    document.body.innerHTML = `
      <div class="app-shell">
        <aside class="sidebar">
          <div class="brand">
            <img src="images/logo.png" alt="BloodLink Logo">
            <div style="flex:1; min-width:0;">
              <b>BloodLink</b>
              <small>${Shell.roleLabel(role)}</small>
            </div>
            <button class="sidebar-close-btn" id="sidebar-close-btn" aria-label="Close navigation" title="Close menu">&times;</button>
          </div>
          <nav>
            ${renderedNav}
          </nav>
          <div class="sidebar-footer">
            <div class="sidebar-user-card" id="sidebar-user-card">
              <div class="user-avatar-circle">${UI.esc(initials)}</div>
              <div class="user-meta">
                <div class="user-name-title" title="${UI.esc(name)}">${UI.esc(name)}</div>
                <div class="user-role-subtitle">${Shell.roleLabel(role)}</div>
              </div>
              <a href="#" id="logout-link" class="user-logout-btn" title="Logout">
                ${SVG_ICONS.logout}
              </a>
            </div>
            <div class="sidebar-copyright">
              &copy; 2026 BloodLink Healthcare Network
            </div>
          </div>
        </aside>
        <div class="main-area">
          <header class="topbar">
            <button class="nav-toggle" id="nav-toggle" aria-label="Open menu" title="Menu">&#9776;</button>
            <div style="display:flex; align-items:center; gap:10px; flex:1; min-width:0;">
              <h1 style="font-size:16.5px; font-weight:800; color:var(--ink); margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                ${UI.esc(title)}
              </h1>
              <span class="role-pill ${role}" style="font-size:10.5px; padding:2px 9px;">
                ${Shell.roleBadgeIcon(role)} ${Shell.roleLabel(role)}
              </span>
              ${hospBadgeHtml}
            </div>

            <div class="who">
              <span class="avatar">${UI.esc(initials)}</span>
              <div>
                <b style="color:var(--ink); font-size:13px; display:block; max-width:140px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${UI.esc(name)}</b>
                <span style="font-size:11px; color:var(--muted);">${Shell.roleLabel(role)}</span>
              </div>
            </div>
          </header>
          <main class="content" id="page"></main>
        </div>
      </div>
      <div class="nav-overlay" id="nav-overlay"></div>
      <div id="toasts"></div>`;

    const navToggle = document.getElementById('nav-toggle');
    const navOverlay = document.getElementById('nav-overlay');
    const sidebarEl = document.querySelector('.sidebar');
    const closeNav = () => { sidebarEl.classList.remove('open'); navOverlay.classList.remove('show'); };
    const openNav = () => { sidebarEl.classList.add('open'); navOverlay.classList.add('show'); };
    if (navToggle) navToggle.onclick = () => (sidebarEl.classList.contains('open') ? closeNav() : openNav());
    const closeBtn = document.getElementById('sidebar-close-btn');
    if (closeBtn) closeBtn.onclick = closeNav;
    if (navOverlay) navOverlay.onclick = closeNav;
    sidebarEl.querySelectorAll('nav a').forEach((a) => a.addEventListener('click', closeNav));

    // Keyboard support: Escape closes mobile sidebar
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && sidebarEl.classList.contains('open')) {
        closeNav();
      }
    });

    const logoutBtn = document.getElementById('logout-link');
    if (logoutBtn) {
      logoutBtn.onclick = async (e) => {
        e.preventDefault();
        try { await API.post('/api/auth/logout'); } catch (_) {}
        location.href = UI.url('/login.php');
      };
    }

    // Web notifications removed per UX requirements
    return true;
  },

  roleLabel(role) {
    return {
      donor: 'Blood Donor',
      patient: 'Patient / Requester',
      staff: 'Hospital Staff',
      admin: 'Hospital Admin',
      superadmin: 'Super Admin'
    }[role] || role;
  },

  roleBadgeIcon(role) {
    return {
      donor: '🩸',
      patient: '🚑',
      staff: '🩺',
      admin: '🏥',
      superadmin: '🛡️'
    }[role] || '👤';
  },

  page() { return document.getElementById('page'); },

  async refreshBell() {
    // Web notifications removed per UX requirements
  }
};
