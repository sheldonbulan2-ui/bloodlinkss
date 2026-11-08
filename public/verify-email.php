<?php require __DIR__ . '/../php/guard.php'; publicPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Email — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .otp-inputs{display:flex;gap:10px;justify-content:center;margin:22px 0}
  .otp-inputs input{width:46px;height:56px;text-align:center;font-size:24px;font-weight:700;
    border:1px solid #d9c2c6;border-radius:10px;background:#fff;color:#b00020}
  .otp-inputs input:focus{outline:none;border-color:#b00020;box-shadow:0 0 0 3px #fdeff1}
  .otp-meta{text-align:center;color:#666;font-size:14px;margin:6px 0}
  .otp-timer{font-weight:700;color:#b00020}
  .link-btn{background:none;border:none;color:#b00020;font-weight:600;cursor:pointer;font-size:14px}
  .link-btn[disabled]{color:#aaa;cursor:not-allowed}
</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="brandline"><img src="images/logo.png" alt="BloodLink Logo" style="width:42px; height:42px; max-width:42px; max-height:42px; object-fit:contain; border-radius:10px; flex-shrink:0;"><h1>Verify Email</h1></div>
    <p class="sub">Enter the 6-digit code sent to<br><strong id="to-email">your email</strong></p>

    <form id="otp-form" autocomplete="one-time-code">
      <div class="otp-inputs" id="otp-boxes">
        <input inputmode="numeric" maxlength="1" aria-label="Digit 1">
        <input inputmode="numeric" maxlength="1" aria-label="Digit 2">
        <input inputmode="numeric" maxlength="1" aria-label="Digit 3">
        <input inputmode="numeric" maxlength="1" aria-label="Digit 4">
        <input inputmode="numeric" maxlength="1" aria-label="Digit 5">
        <input inputmode="numeric" maxlength="1" aria-label="Digit 6">
      </div>
      <p class="otp-meta">Code expires in <span class="otp-timer" id="timer">05:00</span></p>
      <button class="btn" id="verify-btn" style="width:100%;margin-top:8px">Verify Email</button>
    </form>

    <p class="otp-meta" style="margin-top:16px">
      Didn't get it?
      <button type="button" class="link-btn" id="resend-btn" disabled>Resend Code (<span id="cooldown">60</span>s)</button>
    </p>
    <p class="small muted" style="text-align:center;margin-top:10px">
      <a href="login.php">Back to login</a>
    </p>
  </div>
</div>
<div id="toasts"></div>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script>
(function () {
  const boxes = Array.from(document.querySelectorAll('#otp-boxes input'));
  const form = document.getElementById('otp-form');
  const verifyBtn = document.getElementById('verify-btn');
  const resendBtn = document.getElementById('resend-btn');
  const cooldownEl = document.getElementById('cooldown');
  const timerEl = document.getElementById('timer');

  // Show the address passed from register/login immediately (if any),
  // then confirm the pending session with the server.
  const passedEmail = new URLSearchParams(location.search).get('e') || '';
  if (passedEmail) document.getElementById('to-email').textContent = passedEmail;
  API.get('/api/auth/pending')
    .then((d) => { if (d.email) document.getElementById('to-email').textContent = d.email; })
    .catch(() => { location.href = UI.url('/login.php'); });

  // --- OTP box behaviour: autofocus, single digit, paste, backspace ---
  boxes[0].focus();
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

  const readCode = () => boxes.map((b) => b.value).join('');

  // --- Backend is authoritative; this timer is display-only ---
  let expiry = Date.now() + 5 * 60 * 1000;
  function tick() {
    const left = Math.max(0, Math.floor((expiry - Date.now()) / 1000));
    const m = String(Math.floor(left / 60)).padStart(2, '0');
    const s = String(left % 60).padStart(2, '0');
    timerEl.textContent = `${m}:${s}`;
    if (left <= 0) timerEl.textContent = 'expired';
  }
  tick(); setInterval(tick, 1000);

  // --- Resend cooldown ---
  let cd = 60;
  function startCooldown() {
    cd = 60; resendBtn.disabled = true; cooldownEl.textContent = cd;
    const iv = setInterval(() => {
      cd -= 1; cooldownEl.textContent = cd;
      if (cd <= 0) { clearInterval(iv); resendBtn.disabled = false; resendBtn.innerHTML = 'Resend Code'; }
    }, 1000);
  }
  startCooldown();

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const code = readCode();
    if (code.length !== 6) { UI.errToast('Please enter all 6 digits.'); return; }
    verifyBtn.disabled = true; verifyBtn.textContent = 'Verifying...';
    try {
      const d = await API.post('/api/auth/verify-otp', { code });
      UI.okToast(d.message || 'Email verified successfully! Please log in.');
      setTimeout(() => location.href = UI.url('/login.php?verified=1'), 800);
    } catch (err) {
      UI.errToast(err.message);
      verifyBtn.disabled = false; verifyBtn.textContent = 'Verify Email';
      boxes.forEach((b) => (b.value = '')); boxes[0].focus();
    }
  });

  resendBtn.addEventListener('click', async () => {
    resendBtn.disabled = true;
    try {
      const d = await API.post('/api/auth/resend-otp', {});
      UI.okToast(d.message || 'A new code was sent.');
      expiry = Date.now() + 5 * 60 * 1000;
      startCooldown();
    } catch (err) {
      UI.errToast(err.message);
      resendBtn.disabled = false;
    }
  });
})();
</script>
</body>
</html>
