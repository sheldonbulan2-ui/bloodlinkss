<?php require __DIR__ . '/../php/guard.php'; publicPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  /* Wizard Step Bar */
  .wizard-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(225, 29, 72, 0.25);
    border-radius: 14px;
    padding: 12px 18px;
    position: relative;
  }
  .wizard-step {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #94a3b8;
    position: relative;
    z-index: 2;
    transition: all 0.25s ease;
  }
  .wizard-step .num {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #cbd5e1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    transition: all 0.25s ease;
  }
  .wizard-step.active {
    color: #fda4af;
  }
  .wizard-step.active .num {
    background: #e11d48;
    color: #ffffff;
    box-shadow: 0 0 12px rgba(225, 29, 72, 0.6);
  }
  .wizard-step.completed {
    color: #34d399;
  }
  .wizard-step.completed .num {
    background: #059669;
    color: #ffffff;
  }
  .wizard-sep {
    flex: 1;
    height: 2px;
    background: rgba(225, 29, 72, 0.2);
    margin: 0 10px;
  }

  /* Page Sections */
  .step-page {
    display: none;
    animation: fadeIn 0.25s ease;
  }
  .step-page.active {
    display: block;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .reg-card {
    background: rgba(14, 2, 5, 0.65);
    border: 1px solid rgba(225, 29, 72, 0.2);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.3);
    margin-bottom: 20px;
    color: #f8fafc;
  }
  .reg-card-header {
    margin-bottom: 18px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(225, 29, 72, 0.2);
  }
  .reg-card-header h2 {
    font-size: 18px;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 4px 0;
  }
  .reg-card-header p {
    font-size: 13px;
    color: #94a3b8;
    margin: 0;
  }

  .step-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 20px;
  }

  .checklist-box {
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(225, 29, 72, 0.2);
    border-radius: 10px;
    padding: 14px 16px;
    margin-top: 14px;
  }
  .checklist-title {
    font-size: 12px;
    font-weight: 700;
    color: #fda4af;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .check-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: #94a3b8;
    margin-bottom: 6px;
    transition: all 0.2s ease;
  }
  .check-item:last-child {
    margin-bottom: 0;
  }
  .check-item .check-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 17px;
    height: 17px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #94a3b8;
    font-size: 10px;
    font-weight: 800;
    transition: all 0.2s ease;
  }
  .check-item.valid {
    color: #34d399;
    font-weight: 600;
  }
  .check-item.valid .check-icon {
    background: #059669;
    color: #ffffff;
  }
  .badge-optional {
    background: rgba(255, 255, 255, 0.08);
    color: #cbd5e1;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 6px;
    margin-left: 4px;
  }
  .address-preview {
    background: rgba(56, 189, 248, 0.1);
    border: 1px solid rgba(56, 189, 248, 0.3);
    color: #7dd3fc;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    margin-top: 12px;
  }
</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card wide">
    <div class="brandline">
      <img src="images/logo.png" alt="BloodLink Logo" style="width:42px; height:42px; max-width:42px; max-height:42px; object-fit:contain; border-radius:10px; flex-shrink:0;">
      <h1>Create Account</h1>
    </div>
    <p class="sub">Sign up to request blood or become a lifesaver</p>

    <!-- Warm Wizard Step Progress -->
    <div class="wizard-bar">
      <div class="wizard-step active" id="wiz-step-1">
        <span class="num">&bull;</span>
        <span>About You</span>
      </div>
      <div class="wizard-sep"></div>
      <div class="wizard-step" id="wiz-step-2">
        <span class="num">&bull;</span>
        <span>Your Location</span>
      </div>
      <div class="wizard-sep"></div>
      <div class="wizard-step" id="wiz-step-3">
        <span class="num">&bull;</span>
        <span>Security</span>
      </div>
    </div>

    <div id="hospital-banner" style="display:none; background:rgba(56, 189, 248, 0.1); border:1px solid rgba(56, 189, 248, 0.3); color:#7dd3fc; border-radius:10px; padding:12px 16px; margin-bottom:16px; font-size:13px; text-align:left;">
      🏥 Selected Center: <b id="hospital-name-text" style="color:#ffffff;"></b><br>
      <span style="font-size:12px; color:#38bdf8;">Register below to request blood or schedule a donation with this center.</span>
    </div>

    <form id="reg-form">
      <!-- ================= ABOUT YOU ================= -->
      <div class="step-page active" id="page-step-1">
        <div class="reg-card">
          <div class="reg-card-header">
            <h2>About You</h2>
            <p>Tell us how you would like to participate in saving lives.</p>
          </div>
          <div class="form-grid">
            <div class="field full">
              <label>I am registering as *</label>
              <select name="role" id="role-select" required>
                <option value="donor">Donor &mdash; I want to give blood</option>
                <option value="patient">Patient &mdash; I need blood</option>
              </select>
            </div>
            <div class="field">
              <label>First Name *</label>
              <input name="first_name" id="reg-fname" required pattern="^[a-zA-Z\s\-ñÑ]+$" title="Letters only." placeholder="e.g. Juan">
            </div>
            <div class="field">
              <label>Middle Name <span class="badge-optional">Optional</span></label>
              <input name="middle_name" id="reg-mname" pattern="^[a-zA-Z\s\-ñÑ]+$" title="Letters only." placeholder="e.g. Ramos">
            </div>
            <div class="field">
              <label>Last Name *</label>
              <input name="last_name" id="reg-lname" required pattern="^[a-zA-Z\s\-ñÑ]+$" title="Letters only." placeholder="e.g. Dela Cruz">
            </div>
            <div class="field">
              <label>Age *</label>
              <input name="age" id="reg-age" type="number" min="18" max="65" required placeholder="e.g. 24">
            </div>
            <div class="field">
              <label>Sex *</label>
              <select name="sex" id="reg-sex" required>
                <option value="">Select Sex...</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="field">
              <label>Contact Number *</label>
              <input name="contact_number" id="reg-phone" required placeholder="0992xxxxxxxx" pattern="^(09|\+639)\d{9}$" title="Must be a valid Philippine mobile number (e.g. 0992xxxxxxxx)">
            </div>
            <div class="field full">
              <label>Email (Gmail) *</label>
              <input name="email" id="reg-email" type="email" required placeholder="yourname@gmail.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Valid email address">
            </div>
            <div class="field" id="blood-type-field">
              <label>Blood Type *</label>
              <select name="blood_type" id="blood-type-select" data-blood-types required></select>
            </div>
            <div class="field" id="hospital-field" style="display:none;">
              <label>Hospital / Clinic Name</label>
              <input name="hospital" id="reg-hospital" placeholder="Where you are treated">
            </div>
            <div class="field full" id="notes-field" style="display:none;">
              <label>Medical Notes <span class="badge-optional">Optional</span></label>
              <textarea name="medical_notes" id="reg-notes" placeholder="Any medical details or diagnoses..."></textarea>
            </div>
          </div>

          <div class="step-actions" style="justify-content: flex-end;">
            <button type="button" class="btn" id="btn-next-step1" style="padding:10px 28px; font-weight:700;">
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- ================= YOUR LOCATION ================= -->
      <div class="step-page" id="page-step-2">
        <div class="reg-card">
          <div class="reg-card-header">
            <h2>Your Location</h2>
            <p>Select your Philippine province, city, and barangay for quick blood coordination.</p>
          </div>
          <div class="form-grid">
            <div class="field">
              <label>Province *</label>
              <select id="addr-province">
                <option value="">-- Select Province --</option>
              </select>
            </div>
            <div class="field">
              <label>Municipality / City *</label>
              <select id="addr-municipality" disabled>
                <option value="">-- Select Province First --</option>
              </select>
            </div>
            <div class="field">
              <label>Barangay *</label>
              <select id="addr-barangay" disabled>
                <option value="">-- Select Municipality First --</option>
              </select>
            </div>
            <div class="field" id="custom-barangay-box" style="display:none;">
              <label>Specify Barangay *</label>
              <input type="text" id="addr-barangay-custom" placeholder="Type barangay name">
            </div>
            <div class="field">
              <label>Purok / Street / House No. <span class="badge-optional">Optional</span></label>
              <input type="text" id="addr-purok" placeholder="e.g. Purok 3, Rizal St.">
            </div>
            <input type="hidden" name="address" id="full-address-input">
          </div>

          <div class="address-preview" id="address-preview-box" style="display:none;">
            📍 <b>Full Address:</b> <span id="address-preview-text"></span>
          </div>

          <div class="step-actions">
            <button type="button" class="btn ghost" id="btn-back-step2" style="padding:10px 22px;">
              Back
            </button>
            <button type="button" class="btn" id="btn-next-step2" style="padding:10px 28px; font-weight:700;">
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- ================= ACCOUNT SECURITY ================= -->
      <div class="step-page" id="page-step-3">
        <div class="reg-card">
          <div class="reg-card-header">
            <h2>Account Security</h2>
            <p>Create your username and a protected password to secure your account.</p>
          </div>
          <div class="form-grid">
            <div class="field full">
              <label>Username *</label>
              <input name="username" id="reg-username" minlength="4" placeholder="Min. 4 characters (letters, numbers, underscore)">
            </div>
            <div class="field">
              <label>Enter Password *</label>
              <div style="position:relative">
                <input id="reg-password" name="password" type="password" placeholder="0992xxxxxxxx" style="padding-right:42px">
                <button type="button" class="password-toggle" data-target="reg-password" aria-label="Show password">Show</button>
              </div>
            </div>
            <div class="field">
              <label>Confirm Password *</label>
              <div style="position:relative">
                <input id="reg-confirm-password" name="confirm_password" type="password" placeholder="0992xxxxxxxx" style="padding-right:42px">
                <button type="button" class="password-toggle" data-target="reg-confirm-password" aria-label="Show password">Show</button>
              </div>
            </div>
          </div>

          <!-- Real-time Password Protection Checklist -->
          <div class="checklist-box">
            <div class="checklist-title">🛡️ Password Requirements</div>
            <div class="check-item" id="chk-len">
              <span class="check-icon" id="chk-len-icon">&#9675;</span>
              <span>At least 6 characters (e.g. 0992xxxxxxxx)</span>
            </div>
            <div class="check-item" id="chk-comb">
              <span class="check-icon" id="chk-comb-icon">&#9675;</span>
              <span>Includes combination of characters or numbers</span>
            </div>
            <div class="check-item" id="chk-match">
              <span class="check-icon" id="chk-match-icon">&#9675;</span>
              <span>Passwords match</span>
            </div>
          </div>

          <div class="step-actions">
            <button type="button" class="btn ghost" id="btn-back-step3" style="padding:10px 22px;">
              Back
            </button>
            <button type="submit" class="btn" id="reg-submit-btn" style="padding:11px 32px; font-weight:700;">
              Create Account
            </button>
          </div>
          <div id="reg-msg" style="font-size:13px; text-align:center; margin-top:14px; display:none; font-weight:600;"></div>
        </div>
      </div>
    </form>

    <p class="small muted" style="text-align:center;margin-top:16px">
      Already have an account? <a href="login.php" style="font-weight:700; color:#c0152e;">Log in</a>
    </p>
  </div>
</div>

<div id="toasts"></div>
<script src="js/api.js?v=<?= time() ?>"></script>
<script src="js/common.js?v=<?= time() ?>"></script>
<script src="js/ph_address.js?v=<?= time() ?>"></script>
<script>
// Show/Hide password toggles
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

UI.fillBloodSelects();

// Role-based visibility
function toggleFields() {
  const role = document.getElementById('role-select').value;
  const isDonor = role === 'donor';
  document.getElementById('hospital-field').style.display = isDonor ? 'none' : 'block';
  document.getElementById('notes-field').style.display = isDonor ? 'none' : 'block';

  const ageInput = document.getElementById('reg-age');
  if (isDonor) {
    ageInput.min = 18;
    ageInput.max = 65;
    ageInput.title = 'Age must be between 18 and 65 for donors';
  } else {
    ageInput.min = 1;
    ageInput.max = 120;
    ageInput.title = 'Please enter a valid age (1-120)';
  }
}
document.getElementById('role-select').addEventListener('change', toggleFields);
toggleFields();

// Selected center banner from query string
const regParams = new URLSearchParams(location.search);
const regHName = regParams.get('location_name');
if (regHName) {
  const b = document.getElementById('hospital-banner');
  const t = document.getElementById('hospital-name-text');
  if (b && t) {
    b.style.display = 'block';
    t.textContent = decodeURIComponent(regHName);
    const hospInput = document.getElementById('reg-hospital');
    if (hospInput) hospInput.value = decodeURIComponent(regHName);
  }
}

// ==================== WIZARD STEP NAVIGATION ====================
let currentStep = 1;

function goToStep(step) {
  currentStep = step;
  document.querySelectorAll('.step-page').forEach(p => p.classList.remove('active'));
  document.getElementById('page-step-' + step).classList.add('active');

  // Update wizard bar
  for (let s = 1; s <= 3; s++) {
    const wEl = document.getElementById('wiz-step-' + s);
    wEl.classList.remove('active', 'completed');
    if (s < step) wEl.classList.add('completed');
    else if (s === step) wEl.classList.add('active');
  }

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Validation Step 1
function validateStep1() {
  const role = document.getElementById('role-select').value;
  const fname = document.getElementById('reg-fname').value.trim();
  const lname = document.getElementById('reg-lname').value.trim();
  const age = Number(document.getElementById('reg-age').value);
  const sex = document.getElementById('reg-sex').value;
  const phone = document.getElementById('reg-phone').value.trim();
  const email = document.getElementById('reg-email').value.trim();
  const btype = document.getElementById('blood-type-select').value;

  if (!fname) { UI.errToast('Please enter your First Name.'); document.getElementById('reg-fname').focus(); return false; }
  if (!lname) { UI.errToast('Please enter your Last Name.'); document.getElementById('reg-lname').focus(); return false; }
  if (!age || age < 1) { UI.errToast('Please enter a valid Age.'); document.getElementById('reg-age').focus(); return false; }
  if (role === 'donor' && (age < 18 || age > 65)) {
    UI.errToast('Medical requirement: Blood donors must be between 18 and 65 years old.');
    document.getElementById('reg-age').focus();
    return false;
  }
  if (!sex) { UI.errToast('Please select your Sex.'); document.getElementById('reg-sex').focus(); return false; }
  if (!phone || !/^(09|\+639)\d{9}$/.test(phone.replace(/[\s\-]/g, ''))) {
    UI.errToast('Please enter a valid Philippine mobile number (e.g. 09123456789).');
    document.getElementById('reg-phone').focus();
    return false;
  }
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    UI.errToast('Please enter a valid Email address.');
    document.getElementById('reg-email').focus();
    return false;
  }
  if (!btype) { UI.errToast('Please select your Blood Type.'); document.getElementById('blood-type-select').focus(); return false; }

  return true;
}

// Validation Step 2
function validateStep2() {
  updateAddressPreview();
  const full = document.getElementById('full-address-input').value;
  const prov = document.getElementById('addr-province').value;
  const muni = document.getElementById('addr-municipality').value;
  const brgy = document.getElementById('addr-barangay').value;

  if (!prov) { UI.errToast('Please select your Province.'); document.getElementById('addr-province').focus(); return false; }
  if (!muni) { UI.errToast('Please select your Municipality / City.'); document.getElementById('addr-municipality').focus(); return false; }
  if (!brgy || (brgy === '__OTHER__' && !document.getElementById('addr-barangay-custom').value.trim())) {
    UI.errToast('Please select or specify your Barangay.');
    document.getElementById('addr-barangay').focus();
    return false;
  }
  if (!full) {
    UI.errToast('Please complete your address.');
    return false;
  }
  return true;
}

// Next and Back Button Handlers
document.getElementById('btn-next-step1').addEventListener('click', () => {
  if (validateStep1()) goToStep(2);
});

document.getElementById('btn-back-step2').addEventListener('click', () => {
  goToStep(1);
});

document.getElementById('btn-next-step2').addEventListener('click', () => {
  if (validateStep2()) goToStep(3);
});

document.getElementById('btn-back-step3').addEventListener('click', () => {
  goToStep(2);
});

// ==================== ADDRESS CASCADING DROPDOWNS ====================
const provSelect = document.getElementById('addr-province');
const muniSelect = document.getElementById('addr-municipality');
const brgySelect = document.getElementById('addr-barangay');
const customBrgyBox = document.getElementById('custom-barangay-box');
const customBrgyInput = document.getElementById('addr-barangay-custom');
const purokInput = document.getElementById('addr-purok');
const fullAddressInput = document.getElementById('full-address-input');
const addressPreviewBox = document.getElementById('address-preview-box');
const addressPreviewText = document.getElementById('address-preview-text');

if (window.PHAddress) {
  const provinces = PHAddress.getProvinces();
  provinces.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p;
    opt.textContent = p;
    provSelect.appendChild(opt);
  });
}

provSelect.addEventListener('change', () => {
  const prov = provSelect.value;
  muniSelect.innerHTML = '<option value="">-- Select Municipality / City --</option>';
  brgySelect.innerHTML = '<option value="">-- Select Municipality First --</option>';
  brgySelect.disabled = true;
  customBrgyBox.style.display = 'none';
  customBrgyInput.value = '';

  if (!prov) {
    muniSelect.disabled = true;
    updateAddressPreview();
    return;
  }

  const munis = PHAddress.getMunicipalities(prov);
  munis.forEach(m => {
    const opt = document.createElement('option');
    opt.value = m;
    opt.textContent = m;
    muniSelect.appendChild(opt);
  });
  muniSelect.disabled = false;
  updateAddressPreview();
});

muniSelect.addEventListener('change', () => {
  const prov = provSelect.value;
  const muni = muniSelect.value;
  brgySelect.innerHTML = '<option value="">-- Select Barangay --</option>';
  customBrgyBox.style.display = 'none';
  customBrgyInput.value = '';

  if (!muni) {
    brgySelect.disabled = true;
    updateAddressPreview();
    return;
  }

  const brgys = PHAddress.getBarangays(prov, muni);
  brgys.forEach(b => {
    const opt = document.createElement('option');
    opt.value = b;
    opt.textContent = b;
    brgySelect.appendChild(opt);
  });

  const otherOpt = document.createElement('option');
  otherOpt.value = '__OTHER__';
  otherOpt.textContent = '✏️ Other / Specify Barangay...';
  brgySelect.appendChild(otherOpt);

  brgySelect.disabled = false;
  updateAddressPreview();
});

brgySelect.addEventListener('change', () => {
  if (brgySelect.value === '__OTHER__') {
    customBrgyBox.style.display = 'block';
    customBrgyInput.focus();
  } else {
    customBrgyBox.style.display = 'none';
    customBrgyInput.value = '';
  }
  updateAddressPreview();
});

customBrgyInput.addEventListener('input', updateAddressPreview);
purokInput.addEventListener('input', updateAddressPreview);

function updateAddressPreview() {
  const prov = provSelect.value;
  const muni = muniSelect.value;
  let brgy = brgySelect.value;
  if (brgy === '__OTHER__') {
    brgy = customBrgyInput.value.trim();
  }
  const purok = purokInput.value.trim();

  if (prov && muni && brgy) {
    const full = PHAddress.formatFullAddress(purok, brgy, muni, prov);
    fullAddressInput.value = full;
    addressPreviewText.textContent = full;
    addressPreviewBox.style.display = 'block';
  } else {
    fullAddressInput.value = '';
    addressPreviewBox.style.display = 'none';
  }
}

// ==================== REAL-TIME PASSWORD CHECKLIST ====================
const pwdInput = document.getElementById('reg-password');
const confirmPwdInput = document.getElementById('reg-confirm-password');

function validatePasswordCriteria() {
  const p = pwdInput.value || '';
  const cp = confirmPwdInput.value || '';

  const isLen = p.length >= 6;
  const isComb = p.length >= 6 && (/[a-zA-Z]/.test(p) || /\d/.test(p));
  const isMatch = p.length > 0 && p === cp;

  updateCheckItem('chk-len', isLen);
  updateCheckItem('chk-comb', isComb);
  updateCheckItem('chk-match', isMatch);

  return isLen && isComb && isMatch;
}

function updateCheckItem(id, valid) {
  const row = document.getElementById(id);
  const icon = document.getElementById(id + '-icon');
  if (!row || !icon) return;

  if (valid) {
    row.classList.add('valid');
    icon.innerHTML = '&#10003;';
  } else {
    row.classList.remove('valid');
    icon.innerHTML = '&#9675;';
  }
}

pwdInput.addEventListener('input', validatePasswordCriteria);
confirmPwdInput.addEventListener('input', validatePasswordCriteria);

// ==================== FORM SUBMISSION ====================
document.getElementById('reg-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const f = e.target;
  const btn = document.getElementById('reg-submit-btn');
  const msgDiv = document.getElementById('reg-msg');
  msgDiv.style.display = 'none';

  if (!validateStep1()) { goToStep(1); return; }
  if (!validateStep2()) { goToStep(2); return; }

  const username = document.getElementById('reg-username').value.trim();
  if (username.length < 4) {
    UI.errToast('Username must be at least 4 characters.');
    document.getElementById('reg-username').focus();
    return;
  }

  if (!validatePasswordCriteria()) {
    UI.errToast('Please satisfy all password protection requirements.');
    pwdInput.focus();
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Creating account...';

  try {
    const values = UI.formValues(f);
    values.address = fullAddressInput.value;

    const data = await API.post('/api/auth/register', values);
    msgDiv.textContent = '🎉 Account created successfully! Directing to login...';
    msgDiv.style.color = 'var(--green, #2e7d32)';
    msgDiv.style.display = 'block';

    setTimeout(() => {
      location.href = UI.url('/login.php?registered=1&user=' + encodeURIComponent(values.username) + '&pass=' + encodeURIComponent(values.password));
    }, 800);
  } catch (err) {
    msgDiv.textContent = err.message;
    msgDiv.style.color = 'var(--red, #d32f2f)';
    msgDiv.style.display = 'block';
    btn.disabled = false;
    btn.textContent = 'Create Account';
  }
});
</script>
</body>
</html>
