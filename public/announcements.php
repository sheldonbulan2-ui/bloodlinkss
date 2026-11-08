<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcements &amp; Broadcasts — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .ann-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
    border: 1px solid #fee2e2;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(192, 21, 46, 0.05);
  }
  .ann-hero h1 {
    font-size: 22px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 4px 0;
  }
  .ann-hero p {
    margin: 0;
    font-size: 13.5px;
    color: #4b5563;
  }
  .ann-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    transition: all 0.2s ease;
    position: relative;
  }
  .ann-card.urgent {
    border-left: 5px solid #dc2626;
    background: #fffafa;
  }
  .ann-card.normal {
    border-left: 5px solid #2563eb;
  }
  .ann-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
  }
  .ann-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
    flex-wrap: wrap;
    gap: 8px;
  }
  .ann-title {
    font-size: 17px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .ann-meta {
    font-size: 12px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }
  .ann-body {
    font-size: 14px;
    color: #374151;
    line-height: 1.6;
    margin: 12px 0 14px 0;
    white-space: pre-wrap;
  }
  .badge-urgent {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
    font-size: 11px;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 6px;
    text-transform: uppercase;
  }
  .badge-category {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
  }
  .badge-audience {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
  }
  .modal-form label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 6px;
  }
  .modal-form input, .modal-form select, .modal-form textarea {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    margin-bottom: 14px;
    font-family: inherit;
  }
  .modal-form textarea {
    min-height: 110px;
    resize: vertical;
  }

  @media (max-width: 600px) {
    .ann-hero {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
      padding: 16px;
    }
    .ann-hero .btn {
      width: 100%;
      justify-content: center;
    }
    .ann-card {
      padding: 14px;
    }
    .ann-title {
      font-size: 15px;
    }
  }
</style>
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=3"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'Announcements', active: 'announcements' });
  if (!ok) return;

  let hospitalOptionsHtml = '';
  if (Shell.role === 'superadmin') {
    try {
      const locRes = await API.get('/api/staff/locations');
      const locs = locRes.locations || [];
      hospitalOptionsHtml = `
        <div style="margin-bottom:12px;">
          <label for="f-hospital" style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Publish Under Facility (Optional)</label>
          <select id="f-hospital" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
            <option value="">🏥 All Facilities (Network Central Broadcast)</option>
            ${locs.map(l => `<option value="${l.id}">${UI.esc(l.location_name)}</option>`).join('')}
          </select>
        </div>
      `;
    } catch (_) {}
  }

  const page = Shell.page();
  page.innerHTML = `
    <div class="ann-hero">
      <div>
        <h1>📢 Hospital Announcements &amp; Broadcasts</h1>
        <p>Post and manage announcements, urgent blood shortages, and donation drive updates for donors and patients.</p>
      </div>
      <div>
        <button class="btn" id="btn-create-ann" style="background:#c0152e; color:#fff; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
          <span>➕</span> New Announcement
        </button>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="grid cols-3" style="gap:14px; margin-bottom:20px;">
      <div class="stat-card" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#6b7280; font-weight:700; text-transform:uppercase;">Total Announcements</div>
        <div id="stat-total" style="font-size:26px; font-weight:800; color:#111827; margin-top:4px;">0</div>
      </div>
      <div class="stat-card" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#dc2626; font-weight:700; text-transform:uppercase;">Urgent Broadcasts</div>
        <div id="stat-urgent" style="font-size:26px; font-weight:800; color:#dc2626; margin-top:4px;">0</div>
      </div>
      <div class="stat-card" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#059669; font-weight:700; text-transform:uppercase;">Mobile Notification Reach</div>
        <div style="font-size:26px; font-weight:800; color:#059669; margin-top:4px;">Active 📲</div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
      <div style="display:flex; gap:10px; flex:1; min-width:240px;">
        <input type="text" id="ann-search" placeholder="Search announcements..." style="width:100%; max-width:320px; padding:9px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
        <select id="ann-cat-filter" style="padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
          <option value="">All Categories</option>
          <option value="Urgent Blood Shortage">Urgent Blood Shortage</option>
          <option value="Voluntary Blood Drive">Voluntary Blood Drive</option>
          <option value="Facility Schedule / Advisory">Facility Schedule / Advisory</option>
          <option value="Emergency Broadcast">Emergency Broadcast</option>
          <option value="General">General</option>
        </select>
      </div>
      <button class="btn ghost slim" id="btn-refresh" title="Refresh">↻ Refresh</button>
    </div>

    <!-- Announcements Feed -->
    <div id="ann-list">
      <div class="card" style="text-align:center; padding:40px 20px; color:#6b7280;">
        Loading announcements...
      </div>
    </div>

    <!-- Create Announcement Modal -->
    <div class="modal-back" id="create-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center; padding:14px;">
      <div class="modal" style="background:#fff; border-radius:16px; width:100%; max-width:540px; box-shadow:0 20px 40px rgba(0,0,0,0.2); overflow:hidden;">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; background:#f9fafb;">
          <h3 style="margin:0; font-size:16px; font-weight:800; color:#111827;">📢 Broadcast New Announcement</h3>
          <button id="btn-close-modal" style="border:none; background:transparent; font-size:20px; color:#6b7280; cursor:pointer;">&times;</button>
        </div>
        <form id="form-create-ann" class="modal-form" style="padding:20px;">
          <div>
            <label for="f-title">Announcement Title *</label>
            <input type="text" id="f-title" placeholder="e.g. Urgent Need for Type O+ Blood" required maxlength="255">
          </div>

          ${hospitalOptionsHtml}

          <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
              <label for="f-category">Category</label>
              <select id="f-category">
                <option value="Urgent Blood Shortage">Urgent Blood Shortage</option>
                <option value="Voluntary Blood Drive">Voluntary Blood Drive</option>
                <option value="Facility Schedule / Advisory">Facility Schedule / Advisory</option>
                <option value="Emergency Broadcast">Emergency Broadcast</option>
                <option value="General">General</option>
              </select>
            </div>
            <div>
              <label for="f-audience">Target Audience</label>
              <select id="f-audience">
                <option value="all">Everyone (Donors &amp; Patients)</option>
                <option value="donor">Donors Only</option>
                <option value="patient">Patients Only</option>
              </select>
            </div>
          </div>

          <div style="margin-bottom:14px;">
            <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; color:#b91c1c;">
              <input type="checkbox" id="f-urgent" style="width:auto; margin:0;">
              <span>🚨 Mark as High-Priority Urgent Broadcast</span>
            </label>
            <small style="display:block; color:#6b7280; font-size:11.5px; margin-top:2px;">
              Urgent broadcasts are flagged prominently with an emergency alert badge.
            </small>
          </div>

          <div>
            <label for="f-message">Message Content *</label>
            <textarea id="f-message" placeholder="Provide complete details including required blood types, dates, hospital department, contact guidelines, etc..." required></textarea>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
            <button type="button" class="btn ghost" id="btn-cancel-modal">Cancel</button>
            <button type="submit" class="btn" id="btn-submit-ann" style="background:#c0152e; color:#fff; font-weight:700;">
              🚀 Publish &amp; Broadcast
            </button>
          </div>
        </form>
      </div>
    </div>
  `;

  let announcementsData = [];

  const modal = document.getElementById('create-modal');
  const openModal = () => { modal.style.display = 'flex'; document.getElementById('f-title').focus(); };
  const closeModal = () => { modal.style.display = 'none'; document.getElementById('form-create-ann').reset(); };

  document.getElementById('btn-create-ann').onclick = openModal;
  document.getElementById('btn-close-modal').onclick = closeModal;
  document.getElementById('btn-cancel-modal').onclick = closeModal;
  modal.onclick = (e) => { if (e.target === modal) closeModal(); };

  async function loadAnnouncements() {
    try {
      const res = await API.get('/api/announcements');
      announcementsData = res.announcements || [];
      renderList();
    } catch (err) {
      document.getElementById('ann-list').innerHTML = `
        <div class="card" style="text-align:center; padding:30px; color:#b91c1c;">
          Failed to load announcements: ${UI.esc(err.message || 'Network error')}
        </div>
      `;
    }
  }

  function renderList() {
    const listEl = document.getElementById('ann-list');
    const searchVal = document.getElementById('ann-search').value.toLowerCase().trim();
    const catVal = document.getElementById('ann-cat-filter').value;

    const filtered = announcementsData.filter(item => {
      const matchSearch = !searchVal || 
        (item.title && item.title.toLowerCase().includes(searchVal)) ||
        (item.message && item.message.toLowerCase().includes(searchVal)) ||
        (item.hospital_name && item.hospital_name.toLowerCase().includes(searchVal)) ||
        (item.created_by_name && item.created_by_name.toLowerCase().includes(searchVal));
      const matchCat = !catVal || item.category === catVal;
      return matchSearch && matchCat;
    });

    // Update stats
    document.getElementById('stat-total').textContent = announcementsData.length;
    document.getElementById('stat-urgent').textContent = announcementsData.filter(a => Number(a.is_urgent) === 1).length;

    if (filtered.length === 0) {
      listEl.innerHTML = `
        <div class="card" style="text-align:center; padding:50px 20px;">
          <div style="font-size:36px; margin-bottom:8px;">📢</div>
          <h3 style="font-size:16px; font-weight:700; color:#111827; margin:0 0 6px 0;">No Announcements Found</h3>
          <p style="font-size:13px; color:#6b7280; margin:0 0 16px 0;">No broadcasts match your active filter. Post a new announcement to reach users.</p>
          <button class="btn slim" onclick="document.getElementById('btn-create-ann').click()" style="background:#c0152e; color:#fff;">+ Post First Announcement</button>
        </div>
      `;
      return;
    }

    listEl.innerHTML = filtered.map(item => {
      const isUrgent = Number(item.is_urgent) === 1;
      const hosp = item.hospital_name ? `🏥 ${UI.esc(item.hospital_name)}` : '🏥 Network Central';
      const author = item.created_by_name ? `✍️ ${UI.esc(item.created_by_name)}` : '';
      const date = item.formatted_date || item.created_at || '';
      
      let audienceLabel = '👥 Everyone';
      if (item.target_audience === 'donor') audienceLabel = '🩸 Donors Only';
      if (item.target_audience === 'patient') audienceLabel = '🚑 Patients Only';

      return `
        <div class="ann-card ${isUrgent ? 'urgent' : 'normal'}" data-id="${item.id}">
          <div class="ann-header">
            <div>
              <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:6px;">
                ${isUrgent ? '<span class="badge-urgent">🚨 URGENT BROADCAST</span>' : ''}
                <span class="badge-category">${UI.esc(item.category || 'General')}</span>
                <span class="badge-audience">${audienceLabel}</span>
              </div>
              <h2 class="ann-title">
                ${UI.esc(item.title)}
              </h2>
            </div>
            <div>
              <button class="btn danger slim btn-delete-ann" data-id="${item.id}" style="padding:4px 10px; font-size:12px;">
                🗑️ Delete
              </button>
            </div>
          </div>

          <div class="ann-body">${UI.esc(item.message)}</div>

          <div class="ann-meta">
            <span>${hosp}</span>
            ${author ? `<span>&bull;</span><span>${author}</span>` : ''}
            <span>&bull;</span>
            <span>📅 ${UI.esc(date)}</span>
          </div>
        </div>
      `;
    }).join('');

    // Attach delete handlers
    listEl.querySelectorAll('.btn-delete-ann').forEach(btn => {
      btn.onclick = async () => {
        const id = btn.dataset.id;
        if (!confirm('Are you sure you want to delete and remove this announcement?')) return;
        try {
          btn.disabled = true;
          btn.textContent = 'Deleting...';
          const res = await API.delete(`/api/announcements/${id}`);
          if (res.ok) {
            UI.toast('Announcement deleted successfully.');
            loadAnnouncements();
          } else {
            UI.toast(res.message || 'Failed to delete announcement.', 'error');
            btn.disabled = false;
            btn.textContent = '🗑️ Delete';
          }
        } catch (err) {
          UI.toast(err.message || 'Error deleting announcement.', 'error');
          btn.disabled = false;
          btn.textContent = '🗑️ Delete';
        }
      };
    });
  }

  // Search and filter listeners
  document.getElementById('ann-search').oninput = renderList;
  document.getElementById('ann-cat-filter').onchange = renderList;
  document.getElementById('btn-refresh').onclick = loadAnnouncements;

  // Form submission
  document.getElementById('form-create-ann').onsubmit = async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-ann');
    const title = document.getElementById('f-title').value.trim();
    const message = document.getElementById('f-message').value.trim();
    const category = document.getElementById('f-category').value;
    const target_audience = document.getElementById('f-audience').value;
    const is_urgent = document.getElementById('f-urgent').checked;
    const hospital_id = document.getElementById('f-hospital') ? document.getElementById('f-hospital').value : '';

    if (!title || !message) {
      UI.toast('Title and Message content are required.', 'error');
      return;
    }

    try {
      btn.disabled = true;
      btn.textContent = 'Broadcasting...';
      const res = await API.post('/api/announcements', {
        title,
        message,
        category,
        target_audience,
        is_urgent,
        hospital_id
      });

      if (res.ok) {
        UI.toast('Announcement published and broadcasted to users! 📢');
        closeModal();
        loadAnnouncements();
      } else {
        UI.toast(res.message || 'Failed to publish announcement.', 'error');
      }
    } catch (err) {
      UI.toast(err.message || 'Error publishing announcement.', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = '🚀 Publish & Broadcast';
    }
  };

  // Initial load
  await loadAnnouncements();
})();
</script>
</body>
</html>
