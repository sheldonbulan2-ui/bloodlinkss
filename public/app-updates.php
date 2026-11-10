<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>App Update Management — Superadmin</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .ver-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 22px 26px;
    margin-bottom: 22px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  }
  .ver-hero h1 {
    font-size: 21px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 4px 0;
  }
  .ver-hero p {
    margin: 0;
    font-size: 13.5px;
    color: #64748b;
  }
  .release-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 22px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
  }
  .release-card.active-rel {
    border-left: 5px solid #16a34a;
    background: #f0fdf4;
  }
  .release-card.inactive-rel {
    border-left: 5px solid #cbd5e1;
    opacity: 0.88;
  }
  .release-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
  }
  .release-title {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .notes-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    font-size: 13.5px;
    color: #334155;
    line-height: 1.55;
    margin: 12px 0;
    white-space: pre-wrap;
  }
  .release-meta {
    font-size: 12px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }
    border-radius: 10px;
    padding: 14px 16px;
    font-size: 13.5px;
    color: #f1f5f9;
    line-height: 1.55;
    margin: 12px 0;
    white-space: pre-wrap;
  }
  .release-meta {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  @media (max-width: 600px) {
    .ver-hero {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
      padding: 16px;
    }
    .ver-hero .btn {
      width: 100%;
      justify-content: center;
    }
  }
</style>
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=4"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'App Update Management', active: 'app-updates' });
  if (!ok) return;

  const page = Shell.page();

  // Strict role check: Superadmin only
  if (Shell.role !== 'superadmin') {
    page.innerHTML = `
      <div class="card" style="text-align:center; padding:50px 20px;">
        <div style="font-size:40px; margin-bottom:10px;">🔒</div>
        <h2 style="color:#b91c1c; margin-bottom:8px;">Access Restricted</h2>
        <p class="muted">In-App Update &amp; Version Management is strictly reserved for the Superadmin.</p>
        <a href="dashboard.php" class="btn" style="background:#c0152e; color:#fff; margin-top:12px;">Return to Dashboard</a>
      </div>
    `;
    return;
  }

  page.innerHTML = `
    <!-- Hero Header -->
    <div class="ver-hero">
      <div>
        <h1>🚀 In-App Update &amp; Version Management</h1>
        <p>Publish updated Android APK packages, configure target version codes, and broadcast release notes directly to mobile users.</p>
      </div>
      <div>
        <button class="btn" id="btn-open-publish" style="background:#16a34a; color:#fff; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
          <span>➕</span> Publish New Version
        </button>
      </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid cols-4" style="gap:14px; margin-bottom:20px;">
      <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#16a34a; font-weight:700; text-transform:uppercase;">Active Release</div>
        <div id="stat-active-ver" style="font-size:22px; font-weight:800; color:#0f172a; margin-top:4px;">Loading...</div>
      </div>
      <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#2563eb; font-weight:700; text-transform:uppercase;">Target Version Code</div>
        <div id="stat-active-code" style="font-size:22px; font-weight:800; color:#2563eb; margin-top:4px;">-</div>
      </div>
      <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase;">Total Published</div>
        <div id="stat-total-pub" style="font-size:22px; font-weight:800; color:#0f172a; margin-top:4px;">0</div>
      </div>
      <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
        <div style="font-size:12px; color:#d97706; font-weight:700; text-transform:uppercase;">Mobile In-App Prompt</div>
        <div style="font-size:22px; font-weight:800; color:#d97706; margin-top:4px;">Live 📲</div>
      </div>
    </div>

    <!-- Version Feed & History -->
    <div class="card" style="margin-bottom:24px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
        <div>
          <h2 style="margin:0; font-size:16.5px; font-weight:800;">Published Mobile App Releases</h2>
          <small class="muted">Users with older version codes receive an automatic update modal upon logging in.</small>
        </div>
        <button class="btn ghost slim" id="btn-refresh-list">↻ Refresh</button>
      </div>

      <div id="versions-container">
        <div style="text-align:center; padding:30px; color:#64748b;">Loading versions...</div>
      </div>
    </div>

    <!-- Publish Modal -->
    <div class="modal-back" id="publish-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center; padding:14px;">
      <div class="modal" style="background:#fff; border-radius:16px; width:100%; max-width:560px; box-shadow:0 20px 40px rgba(0,0,0,0.2); overflow:hidden;">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; background:#f9fafb;">
          <h3 style="margin:0; font-size:16.5px; font-weight:800; color:#0f172a;">🚀 Publish Mobile App Update</h3>
          <button id="btn-close-modal" style="border:none; background:transparent; font-size:22px; color:#64748b; cursor:pointer;">&times;</button>
        </div>

        <form id="form-publish" style="padding:20px;">
          <div class="grid cols-2" style="gap:12px; margin-bottom:12px;">
            <div>
              <label for="f-ver-name" style="font-weight:700; font-size:13px; display:block; margin-bottom:4px;">Version Name *</label>
              <input type="text" id="f-ver-name" placeholder="e.g. 1.1.0 or 1.0.1" required style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; box-sizing:border-box;">
              <small class="muted" style="font-size:11px;">Visible version string shown to users</small>
            </div>
            <div>
              <label for="f-ver-code" style="font-weight:700; font-size:13px; display:block; margin-bottom:4px;">Version Code (Integer) *</label>
              <input type="number" id="f-ver-code" placeholder="e.g. 2" min="1" step="1" required style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; box-sizing:border-box;">
              <small class="muted" style="font-size:11px;">Must be higher than current build (e.g. 2, 3)</small>
            </div>
          </div>

          <div style="margin-bottom:14px;">
            <label for="f-notes" style="font-weight:700; font-size:13px; display:block; margin-bottom:4px;">Update Info / Release Notes *</label>
            <textarea id="f-notes" placeholder="Explain why this update is needed (e.g. Added In-App Announcements, faster cloud connection timeouts, emergency blood requisition fixes...)" required style="width:100%; min-height:100px; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px; box-sizing:border-box; font-family:inherit;"></textarea>
            <small class="muted" style="font-size:11px;">Displayed inside the In-App Update Modal so users understand the upgrade reason.</small>
          </div>

          <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; padding:14px; margin-bottom:14px;">
            <label for="f-apk-file" style="font-weight:700; font-size:13px; display:block; margin-bottom:4px;">Upload New APK Package (.apk)</label>
            <input type="file" id="f-apk-file" accept=".apk,application/vnd.android.package-archive" style="font-size:13px;">
            <small class="muted" style="font-size:11.5px; display:block; margin-top:6px;">
              Leave blank to automatically link the latest compiled APK on the server.
            </small>
          </div>

          <div style="margin-bottom:18px;">
            <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13px; color:#1e293b;">
              <input type="checkbox" id="f-mandatory" style="width:auto; margin:0;">
              <span>Enforce as Mandatory Update (User cannot skip)</span>
            </label>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn ghost" id="btn-cancel-modal">Cancel</button>
            <button type="submit" class="btn" id="btn-submit-publish" style="background:#16a34a; color:#fff; font-weight:700;">
              🚀 Publish &amp; Activate Update
            </button>
          </div>
        </form>
      </div>
    </div>
  `;

  const modal = document.getElementById('publish-modal');
  const openModal = () => { modal.style.display = 'flex'; document.getElementById('f-ver-name').focus(); };
  const closeModal = () => { modal.style.display = 'none'; document.getElementById('form-publish').reset(); };

  document.getElementById('btn-open-publish').onclick = openModal;
  document.getElementById('btn-close-modal').onclick = closeModal;
  document.getElementById('btn-cancel-modal').onclick = closeModal;
  modal.onclick = (e) => { if (e.target === modal) closeModal(); };

  async function loadVersions() {
    try {
      const res = await API.get('/api/app-versions');
      const versions = res.versions || [];
      renderVersions(versions);
    } catch (err) {
      document.getElementById('versions-container').innerHTML = `
        <div style="text-align:center; padding:24px; color:#b91c1c;">
          Error loading versions: ${UI.esc(err.message || 'Network error')}
        </div>
      `;
    }
  }

  function renderVersions(versions) {
    const container = document.getElementById('versions-container');
    document.getElementById('stat-total-pub').textContent = versions.length;

    const active = versions.find(v => Number(v.is_active) === 1);
    if (active) {
      document.getElementById('stat-active-ver').textContent = `v${active.version_name}`;
      document.getElementById('stat-active-code').textContent = `Build ${active.version_code}`;
      // Suggest next version code in modal
      document.getElementById('f-ver-code').value = Number(active.version_code) + 1;
    } else {
      document.getElementById('stat-active-ver').textContent = 'None Active';
      document.getElementById('stat-active-code').textContent = '-';
      document.getElementById('f-ver-code').value = 1;
    }

    if (versions.length === 0) {
      container.innerHTML = `
        <div style="text-align:center; padding:40px 14px; color:#64748b;">
          <div style="font-size:32px; margin-bottom:8px;">📦</div>
          <div style="font-weight:700; color:#0f172a;">No Versions Published Yet</div>
          <p style="font-size:13px; margin:4px 0 14px 0;">Publish your first Android app release to enable automatic In-App update prompts.</p>
          <button class="btn slim" onclick="document.getElementById('btn-open-publish').click()" style="background:#16a34a; color:#fff;">+ Publish First Version</button>
        </div>
      `;
      return;
    }

    container.innerHTML = versions.map(v => {
      const isActive = Number(v.is_active) === 1;
      const isMandatory = Number(v.is_mandatory) === 1;
      const sizeMB = (v.file_size_bytes > 0) ? (v.file_size_bytes / (1024 * 1024)).toFixed(1) + ' MB' : '~10.2 MB';
      const uploader = v.uploaded_by_name ? `Uploaded by ${UI.esc(v.uploaded_by_name)}` : 'System Release';
      const date = v.formatted_date || v.created_at || '';

      return `
        <div class="release-card ${isActive ? 'active-rel' : 'inactive-rel'}">
          <div class="release-header">
            <div>
              <div class="release-title">
                <span>Version ${UI.esc(v.version_name)}</span>
                <span class="chip" style="background:#f1f5f9; color:#334155; font-size:11.5px; font-weight:800;">Build ${v.version_code}</span>
                ${isActive ? '<span class="badge green" style="font-size:11px; font-weight:800;">ACTIVE PRODUCTION RELEASE</span>' : '<span class="badge" style="background:#e2e8f0; color:#475569; font-size:11px;">Archived Build</span>'}
                ${isMandatory ? '<span class="badge red" style="font-size:11px;">MANDATORY</span>' : ''}
              </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
              ${!isActive ? `
                <button class="btn ghost slim btn-activate" data-id="${v.id}" style="color:#16a34a; font-weight:700; font-size:12px;">
                  Set as Active
                </button>
                <button class="btn danger ghost slim btn-delete" data-id="${v.id}" style="font-size:12px;">
                  Delete
                </button>
              ` : `
                <span style="font-size:12px; font-weight:700; color:#16a34a;">✓ Serving Mobile Users</span>
              `}
              <a href="${UI.url(v.apk_file_url)}" target="_blank" class="btn ghost slim" style="font-size:12px;">
                ⬇️ Download APK
              </a>
            </div>
          </div>

          <div class="notes-box">
            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:4px; letter-spacing:0.5px;">Update Info / Release Notes:</div>
            ${UI.esc(v.release_notes)}
          </div>

          <div class="release-meta">
            <span>📦 File Size: <b>${sizeMB}</b></span>
            <span>&bull;</span>
            <span>👤 ${uploader}</span>
            <span>&bull;</span>
            <span>📅 ${UI.esc(date)}</span>
          </div>
        </div>
      `;
    }).join('');

    // Attach event listeners
    container.querySelectorAll('.btn-activate').forEach(btn => {
      btn.onclick = async () => {
        const id = btn.dataset.id;
        try {
          btn.disabled = true;
          const res = await API.post(`/api/app-versions/${id}/activate`, {});
          if (res.ok) {
            UI.toast('Active release updated.');
            loadVersions();
          } else {
            UI.toast(res.message || 'Error activating version', 'error');
            btn.disabled = false;
          }
        } catch (e) {
          UI.toast(e.message || 'Error activating version', 'error');
          btn.disabled = false;
        }
      };
    });

    container.querySelectorAll('.btn-delete').forEach(btn => {
      btn.onclick = async () => {
        const id = btn.dataset.id;
        if (!confirm('Are you sure you want to delete this version record?')) return;
        try {
          btn.disabled = true;
          const res = await API.delete(`/api/app-versions/${id}`);
          if (res.ok) {
            UI.toast('Version record deleted.');
            loadVersions();
          } else {
            UI.toast(res.message || 'Error deleting version', 'error');
            btn.disabled = false;
          }
        } catch (e) {
          UI.toast(e.message || 'Error deleting version', 'error');
          btn.disabled = false;
        }
      };
    });
  }

  // Handle Form Submission (Publish new version)
  document.getElementById('form-publish').onsubmit = async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-publish');
    const versionName = document.getElementById('f-ver-name').value.trim();
    const versionCode = document.getElementById('f-ver-code').value.trim();
    const releaseNotes = document.getElementById('f-notes').value.trim();
    const isMandatory = document.getElementById('f-mandatory').checked ? '1' : '0';
    const fileInput = document.getElementById('f-apk-file');

    if (!versionName || !versionCode || !releaseNotes) {
      UI.toast('Please fill out all required fields.', 'error');
      return;
    }

    try {
      btn.disabled = true;
      btn.textContent = 'Publishing APK...';

      const formData = new FormData();
      formData.append('version_name', versionName);
      formData.append('version_code', versionCode);
      formData.append('release_notes', releaseNotes);
      formData.append('is_mandatory', isMandatory);
      formData.append('csrf_token', API.csrf());

      if (fileInput.files.length > 0) {
        formData.append('apk_file', fileInput.files[0]);
      }

      const res = await fetch(UI.url('/api/app-versions'), {
        method: 'POST',
        headers: {
          'X-CSRF-Token': API.csrf()
        },
        body: formData
      }).then(r => r.json());

      if (res.ok) {
        UI.toast('New version published! Mobile app users will now receive the update prompt 🚀');
        closeModal();
        loadVersions();
      } else {
        UI.toast(res.message || 'Failed to publish version.', 'error');
      }
    } catch (err) {
      UI.toast(err.message || 'Error publishing version.', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = '🚀 Publish & Activate Update';
    }
  };

  document.getElementById('btn-refresh-list').onclick = loadVersions;
  await loadVersions();
})();
</script>
</body>
</html>
