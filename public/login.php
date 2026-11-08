<?php require __DIR__ . '/../php/guard.php'; publicPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Official Portal Login — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .back-home-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: rgba(255, 255, 255, 0.92);
    font-size: 13px;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.14);
    padding: 7px 16px;
    border-radius: 20px;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.22);
    text-decoration: none;
    margin-bottom: 16px;
    transition: all 0.2s ease;
  }
  .back-home-link:hover {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
    text-decoration: none;
    transform: translateX(-3px);
  }
</style>
</head>
<body>
<div class="auth-wrap" style="flex-direction:column; padding: 24px 16px;">
  <div style="width:100%; max-width:440px; display:flex; justify-content:flex-start;">
    <a href="index.php" class="back-home-link">&larr; Back to Landing Page</a>
  </div>

  <div class="auth-card" style="max-width:440px; padding:30px 26px;">
    <!-- Brand Header -->
    <div class="brandline">
      <img src="images/logo.png" alt="BloodLink Logo" style="width:42px; height:42px; max-width:42px; max-height:42px; object-fit:contain; border-radius:10px; flex-shrink:0;">
      <h1 style="font-size:23px; font-weight:800; color:#0f172a; margin:0;">BloodLink</h1>
    </div>
    <p class="sub" style="margin-bottom:18px; font-size:13px; color:#64748b;">Healthcare Network Authentication</p>

    <!-- Portal Welcome Banner -->
    <div style="background:#f8fafc; border:1px solid #e2e8f0; color:#475569; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:12px; text-align:center;">
      🏥 <b>Universal BloodLink Access:</b> Donors, Patients, Staff, and Administrators can log in or register below.
    </div>

    <!-- Success & Facility Banners -->
    <div id="reg-success-banner" style="display:none; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:10px; padding:12px 14px; margin-bottom:16px; font-size:12.5px; text-align:left; line-height:1.5;">
      🎉 <b>Registration Complete!</b> Your staff account is ready. Please log in below.
    </div>
    <div id="hospital-banner" style="display:none; background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12px; text-align:left;">
      🏥 Selected Facility: <b id="hospital-name-text"></b>
    </div>

    <!-- ROLE SELECTOR TABS (Staff, Admin, Superadmin) -->
    <div class="role-tabs-wrap">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <span style="font-size:11.5px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">
          Select Portal Role:
        </span>
      </div>

      <div class="role-tabs" id="role-tabs-container">
        <button type="button" class="role-tab-btn active" data-role="staff">
          <span class="tab-ico">🩺</span>
          <span>Hospital Staff</span>
        </button>
        <button type="button" class="role-tab-btn" data-role="admin">
          <span class="tab-ico">🏥</span>
          <span>Hospital Admin</span>
        </button>
        <button type="button" class="role-tab-btn" data-role="superadmin">
          <span class="tab-ico">🛡️</span>
          <span>Super Admin</span>
        </button>
        <button type="button" class="role-tab-btn" data-role="donor">
          <span class="tab-ico">🩸</span>
          <span>Blood Donor</span>
        </button>
        <button type="button" class="role-tab-btn" data-role="patient">
          <span class="tab-ico">🚑</span>
          <span>Patient</span>
        </button>
      </div>
    </div>

    <!-- Active Portal Indicator Badge -->
    <div id="role-portal-badge" class="role-portal-badge staff">
      <span id="role-badge-icon" style="font-size:20px; flex-shrink:0;">🩺</span>
      <div>
        <b id="role-badge-title" style="display:block; font-size:13px; color:inherit;">Hospital Staff Portal</b>
        <span id="role-badge-desc" style="font-size:11.5px; opacity:0.9;">Process patient blood requests, donor screenings &amp; blood unit inventory.</span>
      </div>
    </div>

    <!-- Login Form -->
    <form id="login-form">
      <input type="hidden" name="portal" id="portal-input" value="staff">

      <div class="field" style="margin-bottom:14px">
        <label id="username-field-label" style="font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px; display:block;">
          Username or Hospital Email
        </label>
        <input name="username" id="login-username" autocomplete="username" required placeholder="e.g. staff_nurse or nurse@hospital.ph" style="padding:10px 12px; font-size:13.5px; border-radius:8px; border:1px solid #cbd5e1;">
      </div>

      <div class="field" style="margin-bottom:14px">
        <label style="font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px; display:block;">Password</label>
        <div style="position:relative">
          <input id="login-password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password" style="padding:10px 42px 10px 12px; font-size:13.5px; border-radius:8px; border:1px solid #cbd5e1; width:100%;">
          <button type="button" class="password-toggle" data-target="login-password" aria-label="Show password" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:#64748b; font-size:11.5px; font-weight:700; cursor:pointer; padding:4px;">Show</button>
        </div>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; font-size:12.5px;">
        <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; color:#374151; font-weight:500; user-select:none;">
          <input type="checkbox" name="remember" id="remember-me" style="width:15px; height:15px; accent-color:#c0152e; cursor:pointer;">
          <span>Remember me</span>
        </label>
        <a href="forgot-password.php" class="small muted" style="text-decoration:none; color:#c0152e; font-weight:600;">Forgot password?</a>
      </div>

      <button id="login-submit-btn" class="btn" style="width:100%; padding:12px; font-size:14px; font-weight:700; border-radius:9px; background:#c0152e; color:#fff; border:none;">
        Log In to Staff Portal
      </button>

      <div id="login-error" style="color:#b91c1c; background:#fee2e2; border:1px solid #fecaca; border-radius:8px; padding:10px 12px; font-size:12.5px; text-align:center; margin-top:14px; display:none; font-weight:600; line-height:1.4;"></div>
    </form>

    <div id="register-footer-prompt" style="text-align:center; margin-top:18px; border-top:1px solid #f1f5f9; padding-top:14px; font-size:12.5px; color:#64748b;">
      <span>Need an authorized hospital staff account?</span><br>
      <span class="small muted" style="font-size:11.5px;">Please coordinate with your Hospital Administrator or Superadmin.</span>
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

// Role pre-selection handled in initial role block

const portalInput = document.getElementById('portal-input');
const portalBadge = document.getElementById('role-portal-badge');
const badgeIcon = document.getElementById('role-badge-icon');
const badgeTitle = document.getElementById('role-badge-title');
const badgeDesc = document.getElementById('role-badge-desc');
const submitBtn = document.getElementById('login-submit-btn');
const usernameLabel = document.getElementById('username-field-label');
const usernameInput = document.getElementById('login-username');
const passwordInput = document.getElementById('login-password');
const rememberCheckbox = document.getElementById('remember-me');
const regFooter = document.getElementById('register-footer-prompt');

const ROLE_CONFIGS = {
  staff: {
    title: 'Hospital Staff Portal',
    desc: 'Process urgent patient blood requests, verify donor screenings & update inventory.',
    icon: '🩺',
    badgeClass: 'staff',
    btnText: 'Log In to Staff Portal',
    placeholder: 'Enter staff username or email',
    userLabel: 'Staff Username or Official Email',
    footer: `<span>Need an authorized hospital staff account?</span><br><span class="small muted" style="font-size:11.5px;">Please coordinate with your Hospital Administrator.</span>`
  },
  admin: {
    title: 'Hospital Administrator Portal',
    desc: 'Supervise hospital inventory, manage official staff accounts & audit reports.',
    icon: '🏥',
    badgeClass: 'admin',
    btnText: 'Log In as Hospital Admin',
    placeholder: 'Enter admin username or email',
    userLabel: 'Hospital Admin Username or Email',
    footer: `<span>Facility onboarding inquiry?</span><br><span class="small muted" style="font-size:11.5px;">Contact the BloodLink Network Superadmin.</span>`
  },
  superadmin: {
    title: 'System Super Administrator',
    desc: 'Multi-hospital network governance, master user roster & security audit trails.',
    icon: '🛡️',
    badgeClass: 'superadmin',
    btnText: 'Log In as Super Admin',
    placeholder: 'Enter superadmin username or email',
    userLabel: 'Superadmin Username or Email',
    footer: `<span style="color:#b91c1c; font-weight:700;">Restricted Security Area</span><br><span class="small muted" style="font-size:11.5px;">All access attempts are cryptographically logged.</span>`
  },
  donor: {
    title: 'Blood Donor Portal',
    desc: 'Schedule blood donations, track donation records, and view verified hospital blood banks.',
    icon: '🩸',
    badgeClass: 'donor',
    btnText: 'Log In as Blood Donor',
    placeholder: 'Enter donor username or email',
    userLabel: 'Donor Username or Email',
    footer: `<span>New blood donor?</span> <a href="register.php?role=donor" style="font-weight:700; color:#c0152e;">Create an account</a>`
  },
  patient: {
    title: 'Patient & Requester Portal',
    desc: 'Submit blood requisitions, track hospital verification, and monitor fulfillment status.',
    icon: '🚑',
    badgeClass: 'patient',
    btnText: 'Log In as Patient',
    placeholder: 'Enter patient username or email',
    userLabel: 'Patient Username or Email',
    footer: `<span>Need blood urgently?</span> <a href="register.php?role=patient" style="font-weight:700; color:#c0152e;">Register as a Patient</a>`
  }
};

function setPortal(roleKey) {
  if (!ROLE_CONFIGS[roleKey]) roleKey = 'staff';
  portalInput.value = roleKey;

  const cfg = ROLE_CONFIGS[roleKey];
  
  // Update badge UI
  portalBadge.className = 'role-portal-badge ' + cfg.badgeClass;
  badgeIcon.textContent = cfg.icon;
  badgeTitle.textContent = cfg.title;
  badgeDesc.textContent = cfg.desc;
  submitBtn.textContent = cfg.btnText;
  usernameLabel.textContent = cfg.userLabel;
  usernameInput.placeholder = cfg.placeholder;
  regFooter.innerHTML = cfg.footer;

  // Update tabs active state
  document.querySelectorAll('.role-tab-btn').forEach(btn => {
    if (btn.dataset.role === roleKey) {
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
    }
  });
}

// Click on role tabs
document.querySelectorAll('.role-tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    setPortal(btn.dataset.role);
    document.getElementById('login-error').style.display = 'none';
  });
});

// Check query param for initial role
const requestedRole = (qParams.get('role') || '').toLowerCase();
if (requestedRole === 'admin') {
  setPortal('admin');
} else if (requestedRole === 'superadmin') {
  setPortal('superadmin');
} else if (requestedRole === 'donor') {
  setPortal('donor');
} else if (requestedRole === 'patient') {
  setPortal('patient');
} else {
  setPortal('staff');
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

  const origBtnText = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Verifying credentials...';

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
    btn.textContent = origBtnText;
  }
});
</script>
</body>
</html>
