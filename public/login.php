<?php require __DIR__ . '/../php/guard.php'; publicPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Official Portal Login — BloodLink Healthcare Network</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .portal-nav-pill-group {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  @media (max-width: 600px) {
    .hide-mobile { display: none !important; }
  }
</style>
</head>
<body>
<div class="portal-page-wrap">
  <!-- Ambient Red Nebula Glow Orbs -->
  <div class="hero-glow-orb hero-glow-1"></div>
  <div class="hero-glow-orb hero-glow-2"></div>
  <div class="hero-glow-orb hero-glow-3"></div>

  <!-- Subtle Floating Bokeh Erythrocytes -->
  <svg class="rbc-bokeh rbc-1" viewBox="0 0 100 60"><ellipse cx="50" cy="30" rx="46" ry="24" fill="#881337" opacity="0.45" /></svg>
  <svg class="rbc-bokeh rbc-2" viewBox="0 0 100 60"><ellipse cx="50" cy="30" rx="46" ry="24" fill="#881337" opacity="0.45" /></svg>
  <svg class="rbc-bokeh rbc-3" viewBox="0 0 100 60"><ellipse cx="50" cy="30" rx="46" ry="24" fill="#881337" opacity="0.45" /></svg>
  <svg class="rbc-bokeh rbc-4" viewBox="0 0 100 60"><ellipse cx="50" cy="30" rx="46" ry="24" fill="#881337" opacity="0.45" /></svg>

  <!-- =========================================================================
       VIEW 1: SELECT YOUR HEALTHCARE PORTAL (IMAGE 2)
       ========================================================================= -->
  <div id="portal-selector-view" style="display:flex; flex-direction:column; flex:1;">
    <!-- Top Navigation -->
    <header class="portal-topbar">
      <a href="index.php" class="portal-back-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to Landing Page
      </a>

      <div class="portal-brand-center">
        <img src="images/logo.png" alt="BloodLink Logo">
        <span>BloodLink</span>
      </div>

      <div class="portal-sec-pill">
        <span class="sec-indicator-dot"></span>
        Secure &amp; Encrypted
      </div>
    </header>

    <!-- Main Header -->
    <div class="portal-header-block">
      <div class="portal-eyebrow">YOUR HEALTH. OUR PRIORITY</div>
      <h1 class="portal-main-heading">Select Your Healthcare <span>Portal</span></h1>
      <p class="portal-sub-desc">Access secure dashboards, real-time inventory tracking, and patient services across the network.</p>
    </div>

    <!-- 4 Glassmorphic Cards Grid -->
    <div class="portal-selector-grid">
      <!-- 1. Hospital Staff -->
      <div class="portal-card-glass" onclick="selectRole('staff')">
        <span class="portal-badge-pill clinical">CLINICAL ACCESS</span>
        <div class="portal-circle-icon">
          <span style="font-size:26px;">🩺</span>
        </div>
        <h3>Hospital Staff</h3>
        <p>Process patient requests, record visits, and screen incoming donors.</p>
        <div class="portal-arrow-circle">&rarr;</div>
      </div>

      <!-- 2. Hospital Admin -->
      <div class="portal-card-glass" onclick="selectRole('admin')">
        <span class="portal-badge-pill management">MANAGEMENT</span>
        <div class="portal-circle-icon">
          <span style="font-size:26px;">📋</span>
        </div>
        <h3>Hospital Admin</h3>
        <p>Supervise facility inventory, review staff reports, and manage official staff.</p>
        <div class="portal-arrow-circle">&rarr;</div>
      </div>

      <!-- 3. Super Admin -->
      <div class="portal-card-glass" onclick="selectRole('superadmin')">
        <span class="portal-badge-pill governance">GOVERNANCE</span>
        <div class="portal-circle-icon">
          <span style="font-size:26px;">🛡️</span>
        </div>
        <h3>Super Admin</h3>
        <p>Manage multi-hospital network, governments, roles, permissions, and security.</p>
        <div class="portal-arrow-circle">&rarr;</div>
      </div>

      <!-- 4. Donors & Patients -->
      <div class="portal-card-glass" onclick="selectRole('donor')">
        <span class="portal-badge-pill public">PUBLIC SERVICES</span>
        <div class="portal-circle-icon">
          <span style="font-size:26px;">❤️</span>
        </div>
        <h3>Donors &amp; Patients</h3>
        <p>Book blood donation appointments, track requests, and check availability.</p>
        <div class="portal-arrow-circle">&rarr;</div>
      </div>
    </div>

    <!-- Bottom Trust Bar -->
    <div class="hero-trust-bar" style="margin-top:auto;">
      <span class="trust-item">
        <svg class="trust-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        Enterprise-Grade Security
      </span>
      <span>&bull;</span>
      <span class="trust-item">
        <svg class="trust-icon" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
        Real-Time Updates
      </span>
      <span>&bull;</span>
      <span class="trust-item">
        <svg class="trust-icon" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
        A Healthier Philippines
      </span>
    </div>
  </div>

  <!-- =========================================================================
       VIEW 2: PRISTINE WHITE PORTAL LOGIN CARD (IMAGE 3)
       ========================================================================= -->
  <div id="portal-login-view" style="display:none; flex-direction:column; flex:1;">
    <!-- Top Navigation -->
    <header class="portal-topbar">
      <button type="button" onclick="showPortalSelector()" class="portal-back-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Change Portal Role
      </button>

      <div class="portal-brand-center">
        <img src="images/logo.png" alt="BloodLink Logo">
        <span>BloodLink</span>
      </div>

      <div style="width:140px;" class="hide-mobile"></div>
    </header>

    <!-- Stage Container -->
    <div class="login-stage-container">
      <div class="portal-login-card">
        <!-- Success & Facility Banner -->
        <div id="reg-success-banner" style="display:none; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:12px; text-align:left; line-height:1.45;">
          🎉 <b>Registration Complete!</b> Your account is ready. Please log in below.
        </div>
        <div id="hospital-banner" style="display:none; background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; border-radius:8px; padding:8px 12px; margin-bottom:14px; font-size:11.5px; text-align:left;">
          🏥 Selected Facility: <b id="hospital-name-text"></b>
        </div>

        <!-- Role Badge & Header -->
        <div class="card-role-header">
          <span id="card-badge" class="card-badge-pill clinical">🔒 CLINICAL ACCESS</span>
          <div id="card-avatar" class="card-round-avatar clinical">🩺</div>
          <h2 id="card-portal-title">Hospital Staff Portal</h2>
          <p id="card-portal-sub" class="role-sub">Log in to process requests and manage screening data.</p>

          <!-- Donor vs Patient Toggle Switch (Only visible for donor/patient roles) -->
          <div id="donor-patient-switch" class="donor-patient-toggle" style="display:none;">
            <button type="button" id="tab-toggle-donor" class="toggle-tab-btn active" onclick="switchPublicRole('donor')">Blood Donor</button>
            <button type="button" id="tab-toggle-patient" class="toggle-tab-btn" onclick="switchPublicRole('patient')">Patient</button>
          </div>
        </div>

        <!-- Login Form -->
        <form id="login-form">
          <input type="hidden" name="portal" id="portal-input" value="staff">

          <div class="portal-input-wrap">
            <label id="username-field-label" class="portal-field-label">Email, Username or ID</label>
            <input name="username" id="login-username" class="portal-input" autocomplete="username" required placeholder="e.g., staff_username or email">
          </div>

          <div class="portal-input-wrap">
            <label class="portal-field-label">Password</label>
            <input id="login-password" name="password" type="password" class="portal-input with-toggle" autocomplete="current-password" required placeholder="••••••••••••">
            <button type="button" class="input-eye-btn password-toggle" data-target="login-password" aria-label="Show password">Show</button>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; font-size:12.5px;">
            <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; color:#475569; font-weight:500; user-select:none;">
              <input type="checkbox" name="remember" id="remember-me" style="width:15px; height:15px; accent-color:#c0152e; cursor:pointer;">
              <span>Remember me</span>
            </label>
            <a href="forgot-password.php" style="text-decoration:none; color:#c0152e; font-weight:600;">Forgot password?</a>
          </div>

          <button id="login-submit-btn" class="portal-btn-submit">
            <span id="submit-btn-text">Log In to Clinical Portal</span> &rarr;
          </button>

          <div id="login-error" style="color:#b91c1c; background:#fee2e2; border:1px solid #fecaca; border-radius:8px; padding:10px 12px; font-size:12px; text-align:center; margin-top:14px; display:none; font-weight:600; line-height:1.4;"></div>
        </form>

        <!-- Dynamic Security Audit Notice & Help Footer -->
        <div id="audit-notice-container" class="audit-notice-box">
          Need help? Contact Hospital Administrator.
        </div>

        <div id="register-footer-prompt" class="portal-login-footer"></div>
      </div>
    </div>
  </div>
</div>

<div id="toasts"></div>
<script src="js/api.js?v=<?= time() ?>"></script>
<script src="js/common.js?v=<?= time() ?>"></script>
<script>
const qParams = new URLSearchParams(location.search);
const hName = qParams.get('location_name');
if (hName) {
  const b = document.getElementById('hospital-banner');
  const t = document.getElementById('hospital-name-text');
  if (b && t) {
    b.style.display = 'block';
    t.textContent = decodeURIComponent(hName);
  }
}

if (qParams.get('registered') === '1' || qParams.get('verified') === '1') {
  const regB = document.getElementById('reg-success-banner');
  if (regB) regB.style.display = 'block';
}

const portalInput = document.getElementById('portal-input');
const cardBadge = document.getElementById('card-badge');
const cardAvatar = document.getElementById('card-avatar');
const cardTitle = document.getElementById('card-portal-title');
const cardSub = document.getElementById('card-portal-sub');
const submitBtnText = document.getElementById('submit-btn-text');
const usernameLabel = document.getElementById('username-field-label');
const usernameInput = document.getElementById('login-username');
const passwordInput = document.getElementById('login-password');
const rememberCheckbox = document.getElementById('remember-me');
const auditBox = document.getElementById('audit-notice-container');
const regFooter = document.getElementById('register-footer-prompt');
const donorPatientSwitch = document.getElementById('donor-patient-switch');

const PORTAL_SPECS = {
  staff: {
    badge: '🔒 CLINICAL ACCESS',
    badgeClass: 'clinical',
    avatar: '🩺',
    title: 'Hospital Staff Portal',
    sub: 'Log in to process requests and manage screening data.',
    submit: 'Log In to Clinical Portal',
    placeholder: 'e.g., staff_username or email',
    userLabel: 'Email, Username or ID',
    audit: 'Need help? Contact Hospital Administrator.',
    footer: `<span>Authorized hospital personnel only.</span>`
  },
  admin: {
    badge: '🔒 MANAGEMENT',
    badgeClass: 'management',
    avatar: '📋',
    title: 'Hospital Admin Portal',
    sub: 'Log in to supervise inventory and staff accounts.',
    submit: 'Log In to Admin Portal',
    placeholder: 'e.g., admin_username or email',
    userLabel: 'Email, Username or ID',
    audit: '<b>FACILITY AUDIT NOTICE:</b> All authentication attempts, actions, and network activity are strictly monitored, logged, and audited under hospital network protocols.',
    footer: `<span>Facility onboarding inquiry? Contact Network Superadmin.</span>`
  },
  superadmin: {
    badge: '🔒 GOVERNANCE',
    badgeClass: 'governance',
    avatar: '🛡️',
    title: 'Super Admin Portal',
    sub: 'Log in to manage network security and governance.',
    submit: 'Log In to Governance Portal',
    placeholder: 'e.g., admin_id or email',
    userLabel: 'Email, Username or ID',
    audit: '<b>SECURITY AUDIT NOTICE:</b> All authentication attempts, pattern alarms and timestamped access logs strictly monitored, logged, and audited under hospital network protocols.',
    footer: `<span style="color:#b91c1c; font-weight:700;">Restricted Governance Area</span>`
  },
  donor: {
    badge: '🔒 PUBLIC SERVICES',
    badgeClass: 'public',
    avatar: '❤️',
    title: 'Donors & Patients Portal',
    sub: 'Log in to check availability and track donation history.',
    submit: 'Log In to Public Portal',
    placeholder: 'e.g., username, mobile or email',
    userLabel: 'Email, Username or ID',
    audit: '',
    footer: `New Volunteer Donor? <a href="register.php?role=donor" style="font-weight:700; color:#c0152e; text-decoration:none;">Register Here</a>`
  },
  patient: {
    badge: '🔒 PUBLIC SERVICES',
    badgeClass: 'public',
    avatar: '🚑',
    title: 'Donors & Patients Portal',
    sub: 'Log in to check blood availability and submit requisition slips.',
    submit: 'Log In to Public Portal',
    placeholder: 'e.g., username, mobile or email',
    userLabel: 'Email, Username or ID',
    audit: '',
    footer: `Urgent Requisition? <a href="register.php?role=patient" style="font-weight:700; color:#c0152e; text-decoration:none;">Register as Patient</a>`
  }
};

let currentActiveRole = 'staff';

function showPortalSelector() {
  document.getElementById('portal-login-view').style.display = 'none';
  document.getElementById('portal-selector-view').style.display = 'flex';
  const url = new URL(window.location);
  url.searchParams.delete('role');
  window.history.replaceState({}, '', url);
}

function selectRole(roleKey) {
  if (!PORTAL_SPECS[roleKey]) roleKey = 'staff';
  currentActiveRole = roleKey;
  portalInput.value = roleKey;

  const spec = PORTAL_SPECS[roleKey];

  // Set card badge
  cardBadge.textContent = spec.badge;
  cardBadge.className = 'card-badge-pill ' + spec.badgeClass;

  // Set avatar icon
  cardAvatar.textContent = spec.avatar;
  cardAvatar.className = 'card-round-avatar ' + spec.badgeClass;

  // Set title & sub
  cardTitle.textContent = spec.title;
  cardSub.textContent = spec.sub;

  // Set button text
  submitBtnText.textContent = spec.submit;

  // Set input fields
  usernameLabel.textContent = spec.userLabel;
  usernameInput.placeholder = spec.placeholder;

  // Toggle switch visibility for donor/patient
  if (roleKey === 'donor' || roleKey === 'patient') {
    donorPatientSwitch.style.display = 'flex';
    document.getElementById('tab-toggle-donor').className = 'toggle-tab-btn' + (roleKey === 'donor' ? ' active' : '');
    document.getElementById('tab-toggle-patient').className = 'toggle-tab-btn' + (roleKey === 'patient' ? ' active' : '');
  } else {
    donorPatientSwitch.style.display = 'none';
  }

  // Audit notice
  if (spec.audit) {
    auditBox.innerHTML = spec.audit;
    auditBox.style.display = 'block';
  } else {
    auditBox.style.display = 'none';
  }

  // Footer prompt
  regFooter.innerHTML = spec.footer;

  // Show login view, hide selector view
  document.getElementById('portal-selector-view').style.display = 'none';
  document.getElementById('portal-login-view').style.display = 'flex';

  // Update URL without reload
  const url = new URL(window.location);
  url.searchParams.set('role', roleKey);
  window.history.replaceState({}, '', url);

  document.getElementById('login-error').style.display = 'none';
  usernameInput.focus();
}

function switchPublicRole(subRole) {
  selectRole(subRole);
}

// Check initial query parameter
const initialRole = (qParams.get('role') || '').toLowerCase();
if (initialRole && PORTAL_SPECS[initialRole]) {
  selectRole(initialRole);
} else if (qParams.get('registered') || qParams.get('user')) {
  selectRole('staff');
} else {
  // Show Image 2 (Selector View)
  document.getElementById('portal-selector-view').style.display = 'flex';
  document.getElementById('portal-login-view').style.display = 'none';
}

// Auto-fill from registration or remember-me
const preUser = qParams.get('user');
const prePass = qParams.get('pass');
if (preUser) {
  usernameInput.value = preUser;
  if (prePass) passwordInput.value = prePass;
  rememberCheckbox.checked = true;
} else {
  const savedUser = localStorage.getItem('bloodlink_remember_username');
  if (savedUser) {
    usernameInput.value = savedUser;
    rememberCheckbox.checked = true;
  }
}

// Password toggle
document.querySelectorAll('.password-toggle').forEach((btn) => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.textContent = isPassword ? 'Hide' : 'Show';
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
  });
});

// Submit login form
document.getElementById('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const f = e.target;
  const btn = document.getElementById('login-submit-btn');
  const errDiv = document.getElementById('login-error');
  errDiv.style.display = 'none';

  if (rememberCheckbox.checked) {
    localStorage.setItem('bloodlink_remember_username', usernameInput.value.trim());
  } else {
    localStorage.removeItem('bloodlink_remember_username');
  }

  const origBtnText = submitBtnText.textContent;
  btn.disabled = true;
  submitBtnText.textContent = 'Verifying credentials...';

  try {
    const payload = UI.formValues(f);
    const data = await API.post('/api/auth/login', payload);
    UI.okToast(data.message || 'Login successful. Redirecting...');
    setTimeout(() => {
      location.href = UI.url(data.verify ? ('/verify-email.php?e=' + encodeURIComponent(data.email || '')) : '/dashboard.php');
    }, 350);
  } catch (err) {
    errDiv.textContent = err.message || 'Authentication failed. Please verify your credentials.';
    errDiv.style.display = 'block';
    btn.disabled = false;
    submitBtnText.textContent = origBtnText;
  }
});
</script>
</body>
</html>
