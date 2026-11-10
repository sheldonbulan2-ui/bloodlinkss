<?php require __DIR__ . '/../php/guard.php'; publicPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .auth-card { max-width: 440px; margin: 0 auto; }
  
  /* Step indicator */
  .step-progress {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 18px 0 24px;
    position: relative;
  }
  .step-progress::before {
    content: '';
    position: absolute;
    top: 14px;
    left: 20px;
    right: 20px;
    height: 2px;
    background: rgba(225, 29, 72, 0.25);
    z-index: 1;
  }
  .step-node {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
  }
  .step-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
  }
  .step-node.active .step-circle {
    border-color: #c0152e;
    background: #c0152e;
    color: #fff;
    box-shadow: 0 0 10px rgba(192, 21, 46, 0.3);
  }
  .step-node.completed .step-circle {
    border-color: #16a34a;
    background: #16a34a;
    color: #fff;
  }
  .step-label {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    margin-top: 6px;
  }
  .step-node.active .step-label {
    color: #c0152e;
    font-weight: 700;
  }
  .step-node.completed .step-label {
    color: #16a34a;
  }

  /* OTP Boxes */
  .otp-inputs {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin: 20px 0;
  }
  .otp-inputs input {
    width: 44px;
    height: 52px;
    text-align: center;
    font-size: 22px;
    font-weight: 700;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    background: #ffffff;
    color: #0f172a;
    transition: all 0.2s;
  }
  .otp-inputs input:focus {
    outline: none;
    border-color: #c0152e;
    box-shadow: 0 0 0 3px rgba(192, 21, 46, 0.15);
    background: #ffffff;
  }
  .otp-meta {
    text-align: center;
    color: #64748b;
    font-size: 13px;
    margin: 8px 0;
  }
  .otp-timer {
    font-weight: 700;
    color: #c0152e;
  }
  .link-btn {
    background: none;
    border: none;
    color: #fda4af;
    font-weight: 600;
    cursor: pointer;
    font-size: 13px;
    padding: 0;
  }
  .link-btn[disabled] {
    color: #64748b;
    cursor: not-allowed;
  }

  /* Step containers */
  #step-2, #step-3 { display: none; }

  .success-banner {
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="brandline">
      <img src="images/logo.png" alt="BloodLink Logo" style="width:42px; height:42px; max-width:42px; max-height:42px; object-fit:contain; border-radius:10px; flex-shrink:0;">
      <h1>Reset Password</h1>
    </div>

    <!-- 3-Step Indicator -->
    <div class="step-progress">
      <div class="step-node active" id="node-1">
        <div class="step-circle">1</div>
        <div class="step-label">Identify</div>
      </div>
      <div class="step-node" id="node-2">
        <div class="step-circle">2</div>
        <div class="step-label">Verify OTP</div>
      </div>
      <div class="step-node" id="node-3">
        <div class="step-circle">3</div>
        <div class="step-label">New Password</div>
      </div>
    </div>

    <!-- STEP 1: Request Code -->
    <div id="step-1">
      <p class="sub">Enter your username or email address and we'll send a 6-digit OTP code to verify your account.</p>
      <form id="step1-form">
        <div class="field" style="margin-bottom:16px">
          <label>Username or Email</label>
          <input name="identifier" id="step1-identifier" required placeholder="e.g. sherlon or your email">
        </div>
        <button class="btn" id="step1-btn" style="width:100%">Send OTP Code</button>
      </form>
    </div>

    <!-- STEP 2: Verify OTP (Strict Step) -->
    <div id="step-2">
      <p class="sub">Enter the 6-digit verification code sent to<br><strong id="to-email" style="color:#111827">your email</strong></p>
      <form id="step2-form">
        <div class="otp-inputs" id="otp-boxes">
          <input inputmode="numeric" maxlength="1" aria-label="Digit 1" autocomplete="one-time-code">
          <input inputmode="numeric" maxlength="1" aria-label="Digit 2">
          <input inputmode="numeric" maxlength="1" aria-label="Digit 3">
          <input inputmode="numeric" maxlength="1" aria-label="Digit 4">
          <input inputmode="numeric" maxlength="1" aria-label="Digit 5">
          <input inputmode="numeric" maxlength="1" aria-label="Digit 6">
        </div>
        <p class="otp-meta">Code expires in <span class="otp-timer" id="timer">05:00</span></p>
        <button class="btn" id="step2-btn" style="width:100%; margin-top:8px">Verify OTP Code</button>
      </form>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; font-size:13px;">
        <button type="button" class="link-btn" id="resend-btn" disabled>Resend Code (<span id="cooldown">60</span>s)</button>
        <button type="button" class="link-btn" id="back-to-step1" style="color:#6b7280">Change Username</button>
      </div>
    </div>

    <!-- STEP 3: Set New Password (Available ONLY after OTP verification) -->
    <div id="step-3">
      <div class="success-banner">
        <span>✅</span>
        <span><strong>OTP Verified!</strong> Please enter and confirm your new password below.</span>
      </div>
      <form id="step3-form">
        <div class="field" style="margin-bottom:14px">
          <label>New Password</label>
          <div style="position:relative">
            <input id="new-password" name="password" type="password" required
              placeholder="Minimum 6 characters" style="padding-right:42px">
            <button type="button" class="password-toggle" data-target="new-password" aria-label="Show password">Show</button>
          </div>
        </div>
        <div class="field" style="margin-bottom:18px">
          <label>Confirm New Password</label>
          <div style="position:relative">
            <input id="confirm-password" name="confirm_password" type="password" required
              placeholder="Re-type new password" style="padding-right:42px">
            <button type="button" class="password-toggle" data-target="confirm-password" aria-label="Show password">Show</button>
          </div>
        </div>
        <button class="btn" id="step3-btn" style="width:100%">Update Password</button>
      </form>
    </div>

    <p class="small muted" style="text-align:center; margin-top:20px">
      <a href="login.php">Back to login</a>
    </p>
  </div>
</div>
<div id="toasts"></div>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script>
(function () {
  const step1 = document.getElementById('step-1');
  const step2 = document.getElementById('step-2');
  const step3 = document.getElementById('step-3');

  const node1 = document.getElementById('node-1');
  const node2 = document.getElementById('node-2');
  const node3 = document.getElementById('node-3');

  const step1Form = document.getElementById('step1-form');
  const step1Btn = document.getElementById('step1-btn');
  const step2Form = document.getElementById('step2-form');
  const step2Btn = document.getElementById('step2-btn');
  const step3Form = document.getElementById('step3-form');
  const step3Btn = document.getElementById('step3-btn');

  const resendBtn = document.getElementById('resend-btn');
  const backToStep1Btn = document.getElementById('back-to-step1');
  const cooldownEl = document.getElementById('cooldown');
  const timerEl = document.getElementById('timer');
  const boxes = Array.from(document.querySelectorAll('#otp-boxes input'));
  
  let currentIdentifier = '';
  let expiry = 0;
  let cd = 60;
  let cdIv = null;

  // Show/Hide password toggles
  document.querySelectorAll('.password-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.target);
      if (!input) return;
      const isPwd = input.type === 'password';
      input.type = isPwd ? 'text' : 'password';
      btn.textContent = isPwd ? 'Hide' : 'Show';
    });
  });

  // OTP box behaviours
  boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
      box.value = box.value.replace(/\D/g, '').slice(0, 1);
      if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
    });
    box.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !box.value && i > 0) boxes[i - 1].focus();
    });
    box.addEventListener('paste', (e) => {
      e.preventDefault();
      const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6).split('');
      digits.forEach((d, k) => { if (boxes[k]) boxes[k].value = d; });
      (boxes[Math.min(digits.length, boxes.length - 1)] || boxes[0]).focus();
    });
  });

  const getOtpCode = () => boxes.map((b) => b.value).join('');

  // Countdown timer
  function tick() {
    if (!expiry) return;
    const left = Math.max(0, Math.floor((expiry - Date.now()) / 1000));
    const m = String(Math.floor(left / 60)).padStart(2, '0');
    const s = String(left % 60).padStart(2, '0');
    timerEl.textContent = left <= 0 ? 'expired' : `${m}:${s}`;
  }
  setInterval(tick, 1000);

  function startCooldown() {
    cd = 60;
    resendBtn.disabled = true;
    cooldownEl.textContent = cd;
    resendBtn.innerHTML = 'Resend Code (<span id="cooldown">60</span>s)';
    const el = document.getElementById('cooldown');
    clearInterval(cdIv);
    cdIv = setInterval(() => {
      cd -= 1;
      if (el) el.textContent = cd;
      if (cd <= 0) {
        clearInterval(cdIv);
        resendBtn.disabled = false;
        resendBtn.textContent = 'Resend Code';
      }
    }, 1000);
  }

  function showStep2(maskedEmail) {
    document.getElementById('to-email').textContent = maskedEmail || 'your email';
    step1.style.display = 'none';
    step2.style.display = 'block';
    step3.style.display = 'none';

    node1.className = 'step-node completed';
    node2.className = 'step-node active';
    node3.className = 'step-node';

    expiry = Date.now() + 5 * 60 * 1000;
    tick();
    startCooldown();
    boxes.forEach(b => b.value = '');
    boxes[0].focus();
  }

  function showStep3() {
    step1.style.display = 'none';
    step2.style.display = 'none';
    step3.style.display = 'block';

    node1.className = 'step-node completed';
    node2.className = 'step-node completed';
    node3.className = 'step-node active';

    document.getElementById('new-password').focus();
  }

  backToStep1Btn.addEventListener('click', () => {
    step1.style.display = 'block';
    step2.style.display = 'none';
    step3.style.display = 'none';

    node1.className = 'step-node active';
    node2.className = 'step-node';
    node3.className = 'step-node';
  });

  // STEP 1: Request OTP
  step1Form.addEventListener('submit', async (e) => {
    e.preventDefault();
    currentIdentifier = document.getElementById('step1-identifier').value.trim();
    if (!currentIdentifier) {
      UI.errToast('Please enter your username or email.');
      return;
    }
    step1Btn.disabled = true;
    step1Btn.textContent = 'Sending OTP...';

    try {
      const res = await API.post('/api/auth/forgot-password', { identifier: currentIdentifier });
      UI.okToast(res.message || 'OTP code sent to your email.');
      if (res.otp_code) {
        console.log('Local/Demo OTP:', res.otp_code);
      }
      showStep2(res.email);
    } catch (err) {
      UI.errToast(err.message || 'Failed to send OTP code.');
    } finally {
      step1Btn.disabled = false;
      step1Btn.textContent = 'Send OTP Code';
    }
  });

  // STEP 2: Verify OTP
  step2Form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const code = getOtpCode();
    if (code.length !== 6) {
      UI.errToast('Please enter all 6 digits of the OTP code.');
      return;
    }

    step2Btn.disabled = true;
    step2Btn.textContent = 'Verifying OTP...';

    try {
      const res = await API.post('/api/auth/verify-reset-otp', { code: code });
      UI.okToast(res.message || 'OTP verified successfully!');
      setTimeout(() => {
        showStep3();
      }, 400);
    } catch (err) {
      UI.errToast(err.message || 'Incorrect OTP code. Please try again.');
      step2Btn.disabled = false;
      step2Btn.textContent = 'Verify OTP Code';
    }
  });

  // Resend OTP
  resendBtn.addEventListener('click', async () => {
    resendBtn.disabled = true;
    try {
      const res = await API.post('/api/auth/resend-reset-otp', {});
      UI.okToast(res.message || 'A new OTP code has been sent.');
      expiry = Date.now() + 5 * 60 * 1000;
      startCooldown();
    } catch (err) {
      UI.errToast(err.message || 'Failed to resend code.');
      resendBtn.disabled = false;
    }
  });

  // STEP 3: Set New Password
  step3Form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const newPass = document.getElementById('new-password').value;
    const confirmPass = document.getElementById('confirm-password').value;

    if (!newPass || !confirmPass) {
      UI.errToast('Please fill in both password fields.');
      return;
    }
    if (newPass.length < 6) {
      UI.errToast('Password must be at least 6 characters.');
      return;
    }
    if (newPass !== confirmPass) {
      UI.errToast('Passwords do not match.');
      return;
    }

    step3Btn.disabled = true;
    step3Btn.textContent = 'Updating Password...';

    try {
      const res = await API.post('/api/auth/reset-password', {
        password: newPass
      });
      UI.okToast(res.message || 'Password updated successfully! Redirecting...');
      setTimeout(() => {
        location.href = UI.url('/login.php');
      }, 1200);
    } catch (err) {
      UI.errToast(err.message || 'Failed to update password.');
      step3Btn.disabled = false;
      step3Btn.textContent = 'Update Password';
    }
  });
})();
</script>
</body>
</html>
