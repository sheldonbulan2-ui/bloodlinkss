<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Feedback &amp; Reviews — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .fb-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
    border: 1px solid #fef3c7;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(217, 119, 6, 0.05);
  }
  .fb-hero h1 {
    font-size: 21px;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 4px 0;
  }
  .fb-hero p {
    margin: 0;
    font-size: 13.5px;
    color: #64748b;
  }
  .fb-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 14px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
  }
  .fb-card.status-new {
    border-left: 5px solid #f59e0b;
    background: #fffdfa;
  }
  .fb-card.status-reviewed {
    border-left: 5px solid #10b981;
  }
  .star-rating {
    color: #f59e0b;
    font-size: 16px;
    letter-spacing: 2px;
  }
  .fb-quote {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    margin: 10px 0;
    font-size: 13.5px;
    color: #334155;
    line-height: 1.5;
  }
</style>
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=5"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'User Feedback & Reviews', active: 'feedbacks' });
  if (!ok) return;

  const page = Shell.page();
  page.innerHTML = `
    <!-- Hero Header -->
    <div class="fb-hero">
      <div>
        <h1>⭐ Donor &amp; Patient Feedback &amp; Reviews</h1>
        <p>Monitor real mobile app user ratings, hospital service feedback, and continuous improvement suggestions.</p>
      </div>
      <div>
        <button class="btn ghost slim" id="btn-refresh-fb">↻ Refresh Reviews</button>
      </div>
    </div>

    <!-- Submit Feedback Form for Donors / Patients -->
    ${['donor', 'patient'].includes(Shell.role) ? `
      <div class="card" style="margin-bottom:20px; border:1px solid #fed7aa; background:#fffdfa;">
        <h2 style="font-size:16px; margin-top:0; color:#c2410c;">💬 Send Feedback &amp; Suggestions to System Admins</h2>
        <p class="muted small" style="margin-bottom:14px;">How was your blood donation or request experience? Your ratings and suggestions help us improve BloodLink.</p>
        <form id="submit-feedback-form">
          <div class="grid cols-2" style="gap:12px; margin-bottom:12px;">
            <div class="field">
              <label>Service Rating *</label>
              <select name="rating" required style="padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1;">
                <option value="5">⭐⭐⭐⭐⭐ 5 Stars - Excellent</option>
                <option value="4">⭐⭐⭐⭐ 4 Stars - Very Good</option>
                <option value="3">⭐⭐⭐ 3 Stars - Average</option>
                <option value="2">⭐⭐ 2 Stars - Needs Improvement</option>
                <option value="1">⭐ 1 Star - Poor</option>
              </select>
            </div>
            <div class="field">
              <label>Category *</label>
              <select name="category" required style="padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1;">
                <option value="General Service">General Experience</option>
                <option value="Blood Donation Process">Blood Donation Drive / Appointment</option>
                <option value="Blood Request Process">Emergency Requisition Speed</option>
                <option value="Hospital Coordination">Hospital Staff &amp; Center Assistance</option>
                <option value="Website & App Experience">Web Platform / Mobile Interface</option>
              </select>
            </div>
          </div>
          <div class="field" style="margin-bottom:12px;">
            <label>Your Feedback &amp; Experience *</label>
            <textarea name="message" required placeholder="Write your comments and experience here..." style="width:100%; min-height:65px; padding:10px 12px; border-radius:8px; border:1px solid #cbd5e1;"></textarea>
          </div>
          <div class="field" style="margin-bottom:14px;">
            <label>Improvement Suggestions (Optional)</label>
            <textarea name="suggestion" placeholder="Share any specific features, improvements, or recommendations..." style="width:100%; min-height:50px; padding:10px 12px; border-radius:8px; border:1px solid #cbd5e1;"></textarea>
          </div>
          <button type="submit" class="btn" style="background:#c0152e; color:#fff; font-weight:700;">Submit Feedback 🚀</button>
        </form>
      </div>
    ` : ''}


    <!-- KPI Summary Grid -->
    <div class="grid cols-4" style="gap:14px; margin-bottom:20px;">
      <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#d97706; font-weight:700; text-transform:uppercase;">Average Rating</div>
        <div id="stat-avg-rating" style="font-size:24px; font-weight:800; color:#b45309; margin-top:4px;">5.0 ⭐</div>
      </div>
      <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#2563eb; font-weight:700; text-transform:uppercase;">Total Feedbacks</div>
        <div id="stat-total-fb" style="font-size:24px; font-weight:800; color:#1e293b; margin-top:4px;">0</div>
      </div>
      <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#dc2626; font-weight:700; text-transform:uppercase;">From Blood Donors</div>
        <div id="stat-donors-fb" style="font-size:24px; font-weight:800; color:#dc2626; margin-top:4px;">0</div>
      </div>
      <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#059669; font-weight:700; text-transform:uppercase;">From Patients</div>
        <div id="stat-patients-fb" style="font-size:24px; font-weight:800; color:#059669; margin-top:4px;">0</div>
      </div>
    </div>

    <!-- Toolbar Filters -->
    <div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
      <div style="display:flex; gap:10px; flex:1; min-width:260px;">
        <input type="text" id="fb-search" placeholder="Search feedback message or user..." style="width:100%; max-width:320px; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
        <select id="fb-rating-filter" style="padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
          <option value="">All Ratings</option>
          <option value="5">⭐⭐⭐⭐⭐ (5 Stars)</option>
          <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
          <option value="3">⭐⭐⭐ (3 Stars)</option>
          <option value="2">⭐⭐ (2 Stars)</option>
          <option value="1">⭐ (1 Star)</option>
        </select>
        <select id="fb-role-filter" style="padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
          <option value="">All Users</option>
          <option value="donor">Donors</option>
          <option value="patient">Patients</option>
        </select>
      </div>
    </div>

    <!-- Feedback List Container -->
    <div id="fb-list">
      <div class="card" style="text-align:center; padding:30px; color:#64748b;">Loading feedbacks...</div>
    </div>
  `;

  let feedbacksData = [];

  async function loadFeedbacks() {
    try {
      const res = await API.get('/api/feedback');
      feedbacksData = res.feedbacks || [];
      const stats = res.stats || {};
      document.getElementById('stat-avg-rating').textContent = (stats.average_rating || 5.0) + ' ⭐';
      document.getElementById('stat-total-fb').textContent = stats.total_feedback || feedbacksData.length;
      document.getElementById('stat-donors-fb').textContent = stats.donor_feedback || feedbacksData.filter(x => x.role === 'donor').length;
      document.getElementById('stat-patients-fb').textContent = stats.patient_feedback || feedbacksData.filter(x => x.role === 'patient').length;
      renderFeedbacks();
    } catch (err) {
      document.getElementById('fb-list').innerHTML = `
        <div class="card" style="text-align:center; padding:30px; color:#b91c1c;">
          Failed to load feedbacks: ${UI.esc(err.message || 'Error')}
        </div>
      `;
    }
  }

  function renderFeedbacks() {
    const listEl = document.getElementById('fb-list');
    const searchVal = document.getElementById('fb-search').value.toLowerCase().trim();
    const ratingVal = document.getElementById('fb-rating-filter').value;
    const roleVal = document.getElementById('fb-role-filter').value;

    const filtered = feedbacksData.filter(item => {
      const matchSearch = !searchVal ||
        (item.message && item.message.toLowerCase().includes(searchVal)) ||
        (item.suggestion && item.suggestion.toLowerCase().includes(searchVal)) ||
        (item.full_name && item.full_name.toLowerCase().includes(searchVal)) ||
        (item.username && item.username.toLowerCase().includes(searchVal));
      const matchRating = !ratingVal || Number(item.rating) === Number(ratingVal);
      const matchRole = !roleVal || item.role === roleVal;
      return matchSearch && matchRating && matchRole;
    });

    if (filtered.length === 0) {
      listEl.innerHTML = `
        <div class="card" style="text-align:center; padding:40px; color:#64748b;">
          <div style="font-size:32px; margin-bottom:8px;">💬</div>
          <h3 style="margin:0 0 4px 0; color:#1e293b;">No Feedback Matches</h3>
          <p class="muted" style="margin:0;">No reviews found under the selected filters.</p>
        </div>
      `;
      return;
    }

    listEl.innerHTML = filtered.map(fb => {
      const isNew = (fb.status || '').toLowerCase() === 'new';
      const stars = '★'.repeat(Math.max(1, Math.min(5, Number(fb.rating)))) + '☆'.repeat(5 - Math.max(1, Math.min(5, Number(fb.rating))));
      const roleBadge = fb.role === 'donor'
        ? '<span class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:700;">🩸 Donor</span>'
        : '<span class="badge" style="background:#dbeafe; color:#1e40af; font-weight:700;">🚑 Patient</span>';

      return `
        <div class="fb-card ${isNew ? 'status-new' : 'status-reviewed'}">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
            <div>
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span class="star-rating">${stars}</span>
                <span style="font-weight:700; color:#334155; font-size:13px;">${fb.rating}/5.0</span>
                <span class="chip" style="background:#f1f5f9; color:#475569; font-size:11.5px; font-weight:700;">${UI.esc(fb.category || 'General')}</span>
                ${roleBadge}
              </div>
              <div style="font-size:14px; font-weight:800; color:#0f172a;">
                ${UI.esc(fb.full_name || fb.username || 'Mobile User')}
              </div>
            </div>
            <div>
              ${isNew ? `
                <button class="btn slim ghost btn-mark-reviewed" data-id="${fb.id}" style="font-size:12px; color:#059669; border-color:#059669;">
                  ✓ Mark as Reviewed
                </button>
              ` : `
                <span class="badge" style="background:#d1fae5; color:#065f46; font-size:11.5px; font-weight:700;">✓ Reviewed</span>
              `}
            </div>
          </div>

          <div class="fb-quote">
            <div style="font-weight:600; color:#1e293b; margin-bottom:4px;">"${UI.esc(fb.message || '')}"</div>
            ${fb.suggestion && fb.suggestion !== fb.message ? `<div style="font-size:12.5px; color:#64748b; margin-top:6px;"><b>Suggestion:</b> ${UI.esc(fb.suggestion)}</div>` : ''}
          </div>

          <div style="font-size:11.5px; color:#94a3b8; display:flex; gap:12px;">
            <span>📅 Submitted: ${UI.fmtDate(fb.created_at)}</span>
            <span>&bull;</span>
            <span>Status: <b>${UI.esc(fb.status || 'New')}</b></span>
          </div>
        </div>
      `;
    }).join('');

    listEl.querySelectorAll('.btn-mark-reviewed').forEach(btn => {
      btn.onclick = async () => {
        const id = btn.dataset.id;
        try {
          btn.disabled = true;
          const res = await API.put(`/api/feedback/${id}`, { status: 'Reviewed' });
          if (res.ok) {
            UI.toast('Feedback marked as Reviewed.');
            loadFeedbacks();
          } else {
            UI.toast(res.message || 'Error updating status', 'error');
            btn.disabled = false;
          }
        } catch (e) {
          UI.toast(e.message || 'Error updating status', 'error');
          btn.disabled = false;
        }
      };
    });
  }

  document.getElementById('fb-search').oninput = renderFeedbacks;
  document.getElementById('fb-rating-filter').onchange = renderFeedbacks;
  document.getElementById('fb-role-filter').onchange = renderFeedbacks;
  document.getElementById('btn-refresh-fb').onclick = loadFeedbacks;

  
  const subForm = document.getElementById('submit-feedback-form');
  if (subForm) {
    subForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = subForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Submitting...';
      try {
        const payload = UI.formValues(subForm);
        payload.rating = Number(payload.rating);
        const res = await API.post('/api/feedback', payload);
        UI.okToast(res.message || 'Feedback submitted successfully!');
        subForm.reset();
        loadFeedbacks();
      } catch (err) {
        UI.errToast(err.message || 'Error submitting feedback');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Submit Feedback 🚀';
      }
    });
  }

  await loadFeedbacks();
})();
</script>
</body>
</html>
