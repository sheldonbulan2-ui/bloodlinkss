<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Donate Blood — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=2"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'Donate Blood', active: 'donate' });
  if (!ok) return;
  const role = Shell.role;

  if (role !== 'donor' && role !== 'patient') {
    Shell.page().innerHTML = '<div class="empty"><b>Access denied.</b> You must be logged in as a donor or patient to book a donation.</div>';
    return;
  }

  const page = Shell.page();
  
  // Single Verified Hospital for both App and Web
  let locOptions = '<option value="San Mariano District Hospital" selected>🏥 San Mariano District Hospital &mdash; Brgy. Zone 1, San Mariano, Isabela</option>';

  const today = new Date().toISOString().slice(0, 10);

  // Check cooldown status
  let cooldownSec = 0;
  let nextEligibleDateStr = '';
  try {
    const statsData = await API.get('/api/donor/stats');
    if (statsData.stats && statsData.stats.in_cooldown) {
      cooldownSec = statsData.stats.cooldown_seconds || (statsData.stats.days_remaining * 86400);
      nextEligibleDateStr = statsData.stats.next_eligible_date;
    }
  } catch (e) { console.error(e); }

  const cooldownBannerHTML = cooldownSec > 0 ? `
    <div id="cooldown-banner" style="background:#fff1f2; border:2px solid #f43f5e; border-radius:12px; padding:18px; margin-bottom:20px; text-align:center;">
      <div style="font-size:32px;">⏳🩸</div>
      <h3 style="color:#be123c; margin:6px 0;">Donation Cooldown Active</h3>
      <p style="color:#4c0519; font-size:14px; margin:4px 0 12px 0;">
        Kasalukuyang nagpapahinga at nagbabalik ng hemoglobin ang iyong katawan. Maaari kang muling mag-donate sa darating na <strong>${UI.esc(nextEligibleDateStr)}</strong>.
      </p>
      <div id="countdown-display" style="font-size:22px; font-weight:bold; font-family:monospace; color:#9f1239; background:white; padding:10px 18px; border-radius:8px; display:inline-block; box-shadow:0 2px 6px rgba(0,0,0,0.06); letter-spacing:1px;">
        Calculating remaining time...
      </div>
    </div>
  ` : '';

  page.innerHTML = `
    <div class="card" style="max-width: 600px; margin: 0 auto;">
      <h2 style="margin-top:0">Book a Blood Donation</h2>
      ${cooldownBannerHTML}
      <div style="background-color: #fff4e5; border-left: 4px solid #ff9800; padding: 12px; margin-bottom: 20px; font-size: 14px; color: #663c00;">
        <strong>Notice:</strong> This form is strictly for reserving an appointment slot. The physical screening and blood extraction will be performed safely by medical professionals at the hospital location.
      </div>
      <p class="muted" style="margin-bottom:20px">Fill out the form below to schedule your blood donation.</p>
      
      <form id="donate-form">
        <div class="field" style="margin-bottom:14px">
          <label>Donation Location *</label>
          <select name="donation_location" required ${cooldownSec > 0 ? 'disabled' : ''}>
            ${locOptions}
          </select>
        </div>
        
        <div class="grid cols-2" style="gap:14px; margin-bottom:14px">
          <div class="field">
            <label>Donation Date *</label>
            <input type="date" name="donation_date" min="${today}" value="${today}" required ${cooldownSec > 0 ? 'disabled' : ''}>
          </div>
          <div class="field">
            <label>Time *</label>
            <select name="donation_time" required ${cooldownSec > 0 ? 'disabled' : ''}>
              <option value="">Select a time slot...</option>
              <option value="08:00:00">08:00 AM - 09:00 AM</option>
              <option value="09:00:00">09:00 AM - 10:00 AM</option>
              <option value="10:00:00">10:00 AM - 11:00 AM</option>
              <option value="11:00:00">11:00 AM - 12:00 PM</option>
              <option value="13:00:00">01:00 PM - 02:00 PM</option>
              <option value="14:00:00">02:00 PM - 03:00 PM</option>
              <option value="15:00:00">03:00 PM - 04:00 PM</option>
              <option value="16:00:00">04:00 PM - 05:00 PM</option>
            </select>
          </div>
        </div>

        <div class="field" style="margin-bottom:20px">
          <label>Current Health Status *</label>
          <select name="health_status" required ${cooldownSec > 0 ? 'disabled' : ''}>
            <option value="">Select...</option>
            <option value="Healthy">Healthy (No symptoms of illness)</option>
            <option value="Unwell">Currently feeling unwell</option>
            <option value="Recovering">Recovering from illness</option>
          </select>
        </div>

        <div style="margin-bottom:20px; font-size:14px;">
          <label style="font-weight:600; display:block; margin-bottom:8px;">Pre-Screening Eligibility (Required)</label>
          <label style="display:block; margin-bottom:6px;">
            <input type="checkbox" name="weight_check" value="1" required style="margin-right:6px;" ${cooldownSec > 0 ? 'disabled' : ''}>
            I weigh at least 50 kg / 110 lbs
          </label>
          <label style="display:block; margin-bottom:6px;">
            <input type="checkbox" name="sleep_check" value="1" required style="margin-right:6px;" ${cooldownSec > 0 ? 'disabled' : ''}>
            I have slept at least 6 hours
          </label>
          <label style="display:block;">
            <input type="checkbox" name="alcohol_check" value="1" required style="margin-right:6px;" ${cooldownSec > 0 ? 'disabled' : ''}>
            I have not consumed alcohol in the past 24 hours
          </label>
        </div>

        <button type="submit" class="btn" style="width:100%" ${cooldownSec > 0 ? 'disabled' : ''}>
          ${cooldownSec > 0 ? '⏳ In Cooldown Period' : 'Submit Donation Request'}
        </button>
      </form>
    </div>
  `;

  const urlParams = new URLSearchParams(window.location.search);
  const chosenLoc = urlParams.get('location') || urlParams.get('location_name');
  if (chosenLoc) {
    const locSelect = document.querySelector('select[name="donation_location"]');
    if (locSelect) locSelect.value = chosenLoc;
  }

  // Start live ticking countdown
  if (cooldownSec > 0) {
    function tick() {
      if (cooldownSec <= 0) {
        clearInterval(timer);
        const b = document.getElementById('cooldown-banner');
        if (b) b.remove();
        document.querySelectorAll('#donate-form input, #donate-form select, #donate-form button').forEach(el => el.disabled = false);
        const btn = document.querySelector('#donate-form button');
        if (btn) btn.textContent = 'Submit Donation Request';
        return;
      }
      const d = Math.floor(cooldownSec / 86400);
      const h = Math.floor((cooldownSec % 86400) / 3600);
      const m = Math.floor((cooldownSec % 3600) / 60);
      const s = cooldownSec % 60;
      const disp = document.getElementById('countdown-display');
      if (disp) {
        disp.textContent = `${d} araw : ${String(h).padStart(2,'0')} oras : ${String(m).padStart(2,'0')} min : ${String(s).padStart(2,'0')} seg`;
      }
      cooldownSec--;
    }
    tick();
    const timer = setInterval(tick, 1000);
  }

  document.getElementById('donate-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    btn.disabled = true;
    try {
      const data = await API.post('/api/donor/donate', UI.formValues(e.target));
      UI.modal('Maraming Salamat!', 
        `<div style="font-size:15px;line-height:1.5;">${data.message}</div>`,
        `<button class="btn" style="width:100%" onclick="location.href=UI.url('/donate.php')">Sige, Naintindihan ko</button>`
      );
    } catch (err) { 
      UI.errToast(err.message); 
      btn.disabled = false;
    }
  });

})();
</script>
<div id="toasts"></div>
</body>
</html>
