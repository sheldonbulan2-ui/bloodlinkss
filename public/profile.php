<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=2"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'My Profile', active: 'profile' });
  if (!ok) return;
  const page = Shell.page();
  const role = Shell.role;

  if (role !== 'donor' && role !== 'patient') {
    page.innerHTML = `
      <div class="grid cols-2">
        <div class="card">
          <h2>System Preferences</h2>
          <div class="field" style="margin-bottom:16px"><label>System Language</label>
            <select id="lang-select">
              <option value="en">English</option>
              <option value="tl">Tagalog</option>
            </select>
          </div>
          <p class="muted small">This setting saves your language preference on this device.</p>
        </div>
        <div class="card">
          <h2>Change Password</h2>
          <form id="pw-form-staff">
            <div class="field" style="margin-bottom:10px"><label>Current Password *</label>
              <div style="position:relative">
                <input id="cur-pw-staff" name="current" type="password" required style="padding-right:42px">
                <button type="button" class="password-toggle" data-target="cur-pw-staff" aria-label="Show password">Show</button>
              </div></div>
            <div class="field" style="margin-bottom:10px"><label>New Password *</label>
              <div style="position:relative">
                <input id="new-pw-staff" name="next" type="password" required placeholder="8+ chars, Aa + number" style="padding-right:42px">
                <button type="button" class="password-toggle" data-target="new-pw-staff" aria-label="Show password">Show</button>
              </div></div>
            <button class="btn ghost">Change Password</button>
          </form>
        </div>
      </div>
    `;

    document.getElementById('lang-select').value = localStorage.getItem('bl_lang') || 'en';
    document.getElementById('lang-select').addEventListener('change', (e) => {
      localStorage.setItem('bl_lang', e.target.value);
      UI.okToast('Language preference saved.');
      setTimeout(() => location.reload(), 1000);
    });
    
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

    document.getElementById('pw-form-staff').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = e.target.querySelector('button');
      btn.disabled = true;
      try {
        const data = await API.put('/api/auth/password', UI.formValues(e.target));
        UI.okToast(data.message);
        e.target.reset();
      } catch (err) { UI.errToast(err.message); }
      finally { btn.disabled = false; }
    });

    return;
  }

  const me = await API.get('/api/auth/me');
  const p = me.profile || {};
  const isDonor = role === 'donor';
  const bloodValue = isDonor ? p.blood_type : p.blood_type_needed;
  const idLabel = isDonor ? 'Donor ID' : 'Patient ID';
  const idValue = isDonor ? p.donor_code : p.patient_code;

  page.innerHTML = `
    <div class="grid cols-2">
      <div class="card">
        <h2>Profile Information</h2>
        <div style="text-align:center;margin-bottom:14px">${UI.bloodChip(bloodValue, true)}</div>
        <div class="table-wrap"><table>
          <tr><th class="muted">${idLabel}</th><td>${UI.esc(idValue)}</td></tr>
          <tr><th class="muted">Name</th><td>${UI.esc(p.first_name)} ${UI.esc(p.middle_name || '')} ${UI.esc(p.last_name)}</td></tr>
          <tr><th class="muted">Age</th><td>${UI.esc(p.age)}</td></tr>
          <tr><th class="muted">Sex</th><td>${UI.esc(p.sex)}</td></tr>
          <tr><th class="muted">Contact</th><td>${UI.esc(p.contact_number)}</td></tr>
          <tr><th class="muted">Email</th><td>${UI.esc(p.email || '-')}</td></tr>
          <tr><th class="muted">Address</th><td>${UI.esc(p.address)}</td></tr>
          ${isDonor
            ? `<tr><th class="muted">Registered</th><td>${UI.fmtDate(p.date_registered)}</td></tr>`
            : `<tr><th class="muted">Hospital</th><td>${UI.esc(p.hospital)}</td></tr>
               <tr><th class="muted">Medical Notes</th><td>${UI.esc(p.medical_notes || '-')}</td></tr>`}
        </table></div>
      </div>
      <div>
        <div class="card">
          <h2>Update Allowed Fields</h2>
          <form id="prof-form">
            <div class="field" style="margin-bottom:10px"><label>Contact Number *</label>
              <input name="contact_number" required value="${UI.esc(p.contact_number)}"></div>
            <div class="field" style="margin-bottom:10px"><label>Address *</label>
              <input name="address" required value="${UI.esc(p.address)}"></div>
            ${isDonor ? '' : `<div class="field" style="margin-bottom:10px"><label>Hospital *</label>
              <input name="hospital" required value="${UI.esc(p.hospital)}"></div>`}
            <button class="btn">Save Profile</button>
          </form>
        </div>
        <div class="card" style="margin-top:14px">
          <h2>Change Password</h2>
          <form id="pw-form">
            <div class="field" style="margin-bottom:10px"><label>Current Password *</label>
              <div style="position:relative">
                <input id="current-password" name="current" type="password" required style="padding-right:42px">
                <button type="button" class="password-toggle" data-target="current-password" aria-label="Show password">Show</button>
              </div></div>
            <div class="field" style="margin-bottom:10px"><label>New Password *</label>
              <div style="position:relative">
                <input id="new-password" name="next" type="password" required placeholder="8+ chars, Aa + number" style="padding-right:42px">
                <button type="button" class="password-toggle" data-target="new-password" aria-label="Show password">Show</button>
              </div></div>
            <button class="btn ghost">Change Password</button>
          </form>
        </div>
      </div>
    </div>`;

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

  document.getElementById('prof-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      const data = await API.put('/api/auth/profile', UI.formValues(e.target));
      UI.okToast(data.message);
    } catch (err) { UI.errToast(err.message); }
  });
  document.getElementById('pw-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    btn.disabled = true;
    try {
      const data = await API.put('/api/auth/password', UI.formValues(e.target));
      if (data.verify) {
        UI.okToast(data.message || 'Verification code sent to your email.');
        await promptPasswordOtp();
        e.target.reset();
      } else {
        UI.okToast(data.message);
        e.target.reset();
      }
    } catch (err) { UI.errToast(err.message); }
    finally { btn.disabled = false; }
  });

  // Small OTP dialog to confirm the password change via email code.
  function promptPasswordOtp() {
    return new Promise((resolve) => {
      const overlay = document.createElement('div');
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:9999';
      overlay.innerHTML = `
        <div style="background:#fff;border-radius:14px;padding:24px;max-width:360px;width:90%;text-align:center;box-shadow:0 12px 40px rgba(0,0,0,.25)">
          <h3 style="margin:0 0 6px;color:#b00020">Confirm Password Change</h3>
          <p style="color:#666;font-size:14px;margin:0 0 16px">Enter the 6-digit code we emailed you.</p>
          <input id="pw-otp" inputmode="numeric" maxlength="6" placeholder="000000"
            style="width:100%;text-align:center;letter-spacing:8px;font-size:24px;font-weight:700;padding:12px;border:1px solid #d9c2c6;border-radius:10px;color:#b00020">
          <div style="display:flex;gap:10px;margin-top:16px">
            <button id="pw-otp-cancel" class="btn ghost" style="flex:1">Cancel</button>
            <button id="pw-otp-confirm" class="btn" style="flex:1">Confirm</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      const input = overlay.querySelector('#pw-otp');
      input.focus();
      input.addEventListener('input', () => { input.value = input.value.replace(/\D/g, '').slice(0, 6); });
      const close = () => { overlay.remove(); resolve(); };
      overlay.querySelector('#pw-otp-cancel').addEventListener('click', close);
      overlay.querySelector('#pw-otp-confirm').addEventListener('click', async () => {
        const code = input.value;
        if (code.length !== 6) { UI.errToast('Please enter all 6 digits.'); return; }
        try {
          const d = await API.post('/api/auth/password-confirm', { code });
          UI.okToast(d.message || 'Password changed.');
          close();
        } catch (err) { UI.errToast(err.message); }
      });
    });
  }
})();
</script>
<div id="toasts"></div>
</body>
</html>

