<?php require __DIR__ . '/../php/guard.php'; requirePage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blood Requests &amp; Requisitions — BloodLink</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .form-section-header {
    background: linear-gradient(135deg, rgba(225, 29, 72, 0.3), rgba(159, 18, 57, 0.4));
    border: 1px solid rgba(225, 29, 72, 0.4);
    color: #ffffff;
    padding: 10px 16px;
    font-size: 13.5px;
    font-weight: 800;
    letter-spacing: 0.5px;
    border-radius: 8px;
    margin-top: 18px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 0 12px rgba(225, 29, 72, 0.15);
  }
  .form-section-header:first-of-type { margin-top: 4px; }
  .badge-doc-verified { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); font-weight: 700; padding: 3px 8px; border-radius: 6px; font-size: 11px; }
  .badge-doc-correction { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); font-weight: 700; padding: 3px 8px; border-radius: 6px; font-size: 11px; }
  .badge-doc-rejected { background: rgba(225, 29, 72, 0.18); color: #fda4af; border: 1px solid rgba(225, 29, 72, 0.4); font-weight: 700; padding: 3px 8px; border-radius: 6px; font-size: 11px; }
  .badge-doc-pending { background: rgba(255, 255, 255, 0.06); color: #94a3b8; border: 1px solid rgba(255, 255, 255, 0.12); font-weight: 700; padding: 3px 8px; border-radius: 6px; font-size: 11px; }
  .fee-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin-top: 14px;
  }
  .fee-table { width: 100%; font-size: 12.5px; border-collapse: collapse; margin-top: 8px; color: #0f172a; }
  .fee-table td, .fee-table th { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
  .fee-table th { text-align: left; background: #f8fafc; color: #475569; font-size: 11.5px; border-bottom: 1px solid #e2e8f0; }
</style>
</head>
<body>
<script src="js/api.js"></script>
<script src="js/common.js"></script>
<script src="js/shell.js?v=2"></script>
<script>
(async () => {
  const ok = await Shell.init({ title: 'Blood Requests & Verification', active: 'requests' });
  if (!ok) return;
  const isOps = ['staff', 'admin', 'superadmin'].includes(Shell.role);
  if (isOps) { 
    renderStaff(); 
  } else if (Shell.role === 'patient' || Shell.role === 'donor') { 
    renderPatient(); 
  } else { 
    Shell.page().innerHTML = '<div class="empty"><b>Blood requests are managed by registered users and staff.</b></div>'; 
  }

  /* ================= PATIENT VIEW ================= */
  async function renderPatient() {
    const page = Shell.page();
    page.innerHTML = `
      <div class="grid cols-2" style="grid-template-columns: 1.25fr 0.75fr; gap: 20px;">
        <div class="card" style="box-shadow: 0 4px 20px rgba(0,0,0,0.5); border-radius: 14px;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid rgba(225, 29, 72, 0.4); padding-bottom:12px; margin-bottom:14px;">
            <div>
              <h2 style="margin:0; font-size:19px; color:#ffffff;">BLOOD REQUEST FORM</h2>
              <small class="muted" style="font-size:11.5px; display:block; margin-top:2px; color:#94a3b8;">
                TEMPLATE &mdash; Official NVBSP / Hospital Requisition &amp; Verification Workflow
              </small>
            </div>
            <span style="background:rgba(225, 29, 72, 0.18); color:#fda4af; border:1px solid rgba(225, 29, 72, 0.35); font-size:10.5px; font-weight:800; padding:4px 8px; border-radius:4px; text-transform:uppercase;">
              Sec. A &ndash; E Requisition
            </span>
          </div>

          <form id="req-form">
            <!-- SECTION A -->
            <div class="form-section-header">
              <span>A. REQUESTING FACILITY</span>
              <small style="font-weight:400; font-size:11px;">Hospital &amp; Ward Details</small>
            </div>
            <div class="form-grid">
              <div class="field">
                <label>Hospital / Facility *</label>
                <select name="location" id="req-location-select" required>
                  <option value="">Loading locations...</option>
                </select>
              </div>
              <div class="field">
                <label>Department / Ward</label>
                <input name="hospital_department" placeholder="e.g. ICU, Surgery, OB-GYN, ER">
              </div>
              <div class="field">
                <label>Facility Address</label>
                <input name="facility_address" id="req-facility-address" placeholder="Hospital address">
              </div>
              <div class="field">
                <label>Facility Contact Number</label>
                <input name="facility_contact" id="req-facility-contact" placeholder="Blood bank / station phone">
              </div>
              <div class="field full">
                <label>Request Reference No. (Auto-generated or Hospital Tracking #)</label>
                <input name="request_ref_no" id="req-ref-no" placeholder="e.g. REQ-2026-XXXX">
              </div>
            </div>

            <!-- SECTION B -->
            <div class="form-section-header">
              <span>B. PATIENT INFORMATION</span>
              <small style="font-weight:400; font-size:11px;">Admitted Patient Details</small>
            </div>
            <div class="form-grid">
              <div class="field full">
                <label>Patient Full Name *</label>
                <input name="patient_name" id="req-patient-name" placeholder="Full name of recipient" required>
              </div>
              <div class="field">
                <label>Date of Birth / Age</label>
                <div style="display:flex; gap:8px;">
                  <input name="patient_dob" type="date" id="req-patient-dob" style="flex:1;">
                  <input name="patient_age" type="number" min="0" max="120" placeholder="Age" id="req-patient-age" style="width:75px;">
                </div>
              </div>
              <div class="field">
                <label>Sex (if required)</label>
                <select name="patient_sex" id="req-patient-sex">
                  <option value="">Select Sex</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="field">
                <label>Hospital Patient / MRN No.</label>
                <input name="patient_mrn" placeholder="Medical Record No. (if assigned)">
              </div>
              <div class="field">
                <label>Ward / Room No.</label>
                <input name="ward_room" placeholder="e.g. Room 304 / Bed 2">
              </div>
              <div class="field full">
                <label>Diagnosis / Clinical Indication *</label>
                <textarea name="clinical_indication" id="req-clinical-indication" required placeholder="Diagnosis and reason for transfusion (e.g. Severe Anemia, Post-partum Hemorrhage, Scheduled Coronary Artery Bypass, Dialysis)"></textarea>
              </div>
            </div>

            <!-- SECTION C -->
            <div class="form-section-header">
              <span>C. BLOOD / COMPONENT REQUEST</span>
              <small style="font-weight:400; font-size:11px;">Product Specifications</small>
            </div>
            <div class="form-grid">
              <div class="field">
                <label>Blood Component Needed *</label>
                <select name="component_type" id="req-component-type" required>
                  <option value="Whole Blood (Buong Dugo)">Whole Blood (Buong Dugo)</option>
                  <option value="Packed Red Blood Cells (PRBC)">Packed Red Blood Cells (PRBC)</option>
                  <option value="Platelet Concentrate / Fresh Frozen Plasma / Cryoprecipitate">Platelet Concentrate / Fresh Frozen Plasma / Cryoprecipitate</option>
                </select>
              </div>
              <div class="field">
                <label>ABO/Rh Blood Type *</label>
                <select name="blood_type" id="req-blood-type" required>
                  <option value="A+">A+</option>
                  <option value="A-">A-</option>
                  <option value="B+">B+</option>
                  <option value="B-">B-</option>
                  <option value="AB+">AB+</option>
                  <option value="AB-">AB-</option>
                  <option value="O+">O+</option>
                  <option value="O-">O-</option>
                </select>
              </div>
              <div class="field">
                <label>Number of Units (1 - 10) *</label>
                <input name="units_needed" id="req-units-needed" type="number" min="1" max="10" required value="1">
              </div>
              <div class="field">
                <label>Date &amp; Time Needed *</label>
                <input name="needed_datetime" id="req-needed-dt" type="datetime-local" required>
              </div>
              <div class="field">
                <label>Urgency Level *</label>
                <select name="urgency_level" id="req-urgency-level">
                  <option value="Routine">Routine (Elective / Scheduled)</option>
                  <option value="Urgent">Urgent (Within 4-8 Hours)</option>
                  <option value="Emergency">Emergency (Immediate / Life Threatening)</option>
                </select>
              </div>
              <div class="field">
                <label>Special Requirements / Notes</label>
                <input name="special_requirements" placeholder="e.g. Leukoreduced, Irradiated, Paediatric Pack">
              </div>
            </div>

            <!-- SECTION D -->
            <div class="form-section-header">
              <span>D. REQUESTER / CONTACT PERSON</span>
              <small style="font-weight:400; font-size:11px;">Claimant &amp; Liaison</small>
            </div>
            <div class="form-grid">
              <div class="field">
                <label>Requester Full Name *</label>
                <input name="requester_name" id="req-requester-name" required placeholder="Person processing request">
              </div>
              <div class="field">
                <label>Relationship / Role *</label>
                <input name="requester_relationship" id="req-requester-rel" required placeholder="e.g. Self, Spouse, Parent, Child, Relative">
              </div>
              <div class="field">
                <label>Contact Phone Number *</label>
                <input name="contact_number" id="req-contact-number" required placeholder="09XX-XXX-XXXX">
              </div>
              <div class="field">
                <label>Email Address (optional)</label>
                <input name="requester_email" id="req-requester-email" type="email" placeholder="For status notifications">
              </div>
            </div>

            <!-- SECTION E -->
            <div class="form-section-header">
              <span>E. PHYSICIAN AUTHORIZATION &amp; UPLOAD</span>
              <small style="font-weight:400; font-size:11px;">Verification Attachment</small>
            </div>
            <div class="form-grid">
              <div class="field">
                <label>Physician Full Name *</label>
                <input name="physician_name" id="req-physician-name" required placeholder="Attending Doctor's Name">
              </div>
              <div class="field">
                <label>License / PRC ID No.</label>
                <input name="physician_license_no" placeholder="PRC License Number">
              </div>
              <div class="field full">
                <label>Upload Doctor's Requisition Slip / Prescription Image (JPG, PNG, PDF)</label>
                <div style="display:flex; gap:10px; align-items:center;">
                  <input type="file" id="req-file-input" accept="image/*,.pdf" style="flex:1;">
                  <button type="button" class="btn slim ghost" id="btn-upload-file">Upload Doc</button>
                </div>
                <input type="hidden" name="medical_document_url" id="req-doc-url">
                <div id="upload-status-badge" style="margin-top:6px; font-size:12px;"></div>
              </div>
              <div class="field full" style="background:#f1f5f9; padding:10px 14px; border-radius:8px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; color:#1e293b; margin:0;">
                  <input type="checkbox" name="physician_attestation" id="req-attestation" required style="width:18px; height:18px;">
                  <span>I attest that this blood request is prescribed by an authorized clinician and all provided details are true and accurate.</span>
                </label>
              </div>
            </div>

            <button class="btn" id="btn-submit-req" style="margin-top:18px; width:100%; font-size:15px; padding:12px; font-weight:700;">
              Submit Official Blood Request
            </button>
          </form>
        </div>

        <div>
          <!-- FEE TRANSPARENCY CARD -->
          <div class="card fee-card">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #cbd5e1; padding-bottom:8px;">
              <h3 style="margin:0; font-size:15.5px; color:#0f172a;">💳 DOH Approved Fee Transparency</h3>
              <span style="font-size:10.5px; background:#dbeafe; color:#1e40af; padding:2px 6px; border-radius:4px; font-weight:700;">R.A. 7719 Regulated</span>
            </div>
            <p class="muted small" style="margin:8px 0 6px; font-size:11.5px; line-height:1.45;">
              Pursuant to the <b>National Voluntary Blood Services Act (R.A. 7719)</b> and <b>DOH A.O. No. 2015-0045</b>, blood donation is 100% voluntary and <b>blood itself is completely FREE</b>. The non-profit fees below cover strictly mandatory testing for 5 Transfusion-Transmissible Infections (HIV, Hepatitis B, Hepatitis C, Syphilis, Malaria), component preparation, and sterile collection bag supplies.
            </p>
            <div id="fee-breakdown-box">
              <table class="fee-table">
                <thead>
                  <tr><th>Selected Component</th><th>DOH Processing</th><th>Infectious Testing</th><th>Total / Unit</th></tr>
                </thead>
                <tbody id="fee-table-body">
                  <tr><td colspan="4" class="muted" style="text-align:center;">Loading fee schedule...</td></tr>
                </tbody>
              </table>
              <div id="fee-total-calc" style="margin-top:10px; padding:10px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; font-size:13px; font-weight:700; color:#166534; display:flex; justify-content:space-between;">
                <span>Estimated Maximum Regulated Fee:</span>
                <span id="fee-calc-amount">₱0.00</span>
              </div>
            </div>
          </div>

          <!-- CHECK AVAILABILITY -->
          <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 8px; font-size:15px;">Check Stock Before Requesting</h3>
            <p class="muted small" style="margin-bottom:12px;">Check current stock availability across regional hospitals in real time.</p>
            <a href="${UI.url('/search.php')}" class="btn ghost slim">🔍 Search Availability</a>
            <a href="${UI.url('/map.php')}" class="btn ghost slim" style="margin-left:6px;">📍 Live Blood Map</a>
          </div>
        </div>
      </div>

      <!-- MY REQUEST HISTORY -->
      <div class="card" style="margin-top:20px; box-shadow:0 4px 14px rgba(0,0,0,0.06); border-radius:12px;">
        <h2 style="font-size:18px;">My Blood Requests &amp; Verification Status</h2>
        <div id="req-list"></div>
      </div>
    `;

    // Initialize Auto-generated Ref No
    document.getElementById('req-ref-no').value = 'REQ-' + (new Date()).getFullYear() + '-' + Math.random().toString(36).substring(2, 7).toUpperCase();

    // Default needed datetime to tomorrow
    const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000);
    const tzOffset = tomorrow.getTimezoneOffset() * 60000;
    const localISOTime = (new Date(tomorrow - tzOffset)).toISOString().slice(0, 16);
    document.getElementById('req-needed-dt').value = localISOTime;

    // Load DOH Fee Schedule
    let feeSchedule = [];
    try {
      const fData = await API.get('/api/blood/fees');
      feeSchedule = fData.fees || [];
      renderFeeCalculation();
    } catch (e) { console.error('Failed to load fees', e); }

    function renderFeeCalculation() {
      const compSelect = document.getElementById('req-component-type');
      const unitsInput = document.getElementById('req-units-needed');
      const selectedComp = compSelect ? compSelect.value : 'Whole Blood';
      const units = unitsInput ? Math.max(1, parseInt(unitsInput.value) || 1) : 1;

      let compName = 'Whole Blood (Buong Dugo)';
      let procFee = 1800.00;
      let testFee = 300.00;
      let unitTotal = 2100.00;

      const lower = selectedComp.toLowerCase();
      if (lower.includes('packed red blood') || lower.includes('prbc')) {
        compName = 'Packed Red Blood Cells (PRBC)';
        procFee = 2000.00;
        testFee = 300.00;
        unitTotal = 2300.00;
      } else if (lower.includes('platelet') || lower.includes('plasma') || lower.includes('cryo') || lower.includes('wbc')) {
        compName = 'Platelet / FFP / Cryoprecipitate';
        procFee = 1200.00;
        testFee = 300.00;
        unitTotal = 1500.00;
      }

      if (feeSchedule && feeSchedule.length > 0) {
        const found = feeSchedule.find(f => {
          const fn = (f.component_name || '').toLowerCase();
          if (lower.includes('prbc') || lower.includes('packed red')) return fn.includes('red') || fn.includes('prbc');
          if (lower.includes('platelet') || lower.includes('plasma') || lower.includes('cryo')) return fn.includes('platelet') || fn.includes('plasma') || fn.includes('cryo');
          return fn.includes('whole');
        });
        if (found) {
          compName = found.component_name;
          procFee = Number(found.doh_approved_fee) || procFee;
          testFee = Number(found.testing_fee) || testFee;
          unitTotal = Number(found.total_fee) || unitTotal;
        }
      }

      const tbody = document.getElementById('fee-table-body');
      const totalAmountEl = document.getElementById('fee-calc-amount');

      if (tbody) {
        tbody.innerHTML = `
          <tr>
            <td><b>${UI.esc(compName)}</b></td>
            <td>₱${procFee.toLocaleString('en-US', {minimumFractionDigits:2})}</td>
            <td>₱${testFee.toLocaleString('en-US', {minimumFractionDigits:2})}</td>
            <td><b>₱${unitTotal.toLocaleString('en-US', {minimumFractionDigits:2})}</b></td>
          </tr>
        `;
      }
      const grandTotal = unitTotal * units;
      if (totalAmountEl) {
        totalAmountEl.textContent = `₱${grandTotal.toLocaleString('en-US', {minimumFractionDigits:2})} (${units} bag${units > 1 ? 's' : ''} @ ₱${unitTotal.toLocaleString('en-US', {minimumFractionDigits:2})})`;
      }
    }

    document.getElementById('req-component-type').addEventListener('change', renderFeeCalculation);
    document.getElementById('req-units-needed').addEventListener('input', renderFeeCalculation);

    // File Upload handling
    document.getElementById('btn-upload-file').addEventListener('click', async () => {
      const fileInput = document.getElementById('req-file-input');
      if (!fileInput.files || fileInput.files.length === 0) {
        UI.errToast('Please select a file to upload.');
        return;
      }
      const file = fileInput.files[0];
      const formData = new FormData();
      formData.append('document', file);

      const statusBadge = document.getElementById('upload-status-badge');
      statusBadge.innerHTML = '<span style="color:#2563eb;">⏳ Uploading prescription document...</span>';

      try {
        const resp = await fetch('api/upload.php', { method: 'POST', body: formData });
        const res = await resp.json();
        if (res.success && res.file_url) {
          document.getElementById('req-doc-url').value = res.file_url;
          statusBadge.innerHTML = `<span style="color:#16a34a; font-weight:700;">✅ Uploaded successfully! (<a href="${res.file_url}" target="_blank">View attached file</a>)</span>`;
          UI.okToast('Prescription document attached successfully.');
        } else {
          statusBadge.innerHTML = `<span style="color:#dc2626;">❌ Upload failed: ${UI.esc(res.message || 'Error uploading')}</span>`;
        }
      } catch (err) {
        statusBadge.innerHTML = `<span style="color:#dc2626;">❌ Upload error: ${UI.esc(err.message)}</span>`;
      }
    });

    // Populate Patient Data and Locations
    async function loadPatientList() {
      const data = await API.get('/api/patient/requests');
      if (data.patient) {
        const p = data.patient;
        if (!document.getElementById('req-patient-name').value) {
          document.getElementById('req-patient-name').value = `${p.first_name} ${p.last_name}`.trim();
        }
        if (!document.getElementById('req-contact-number').value) {
          document.getElementById('req-contact-number').value = p.contact_number || '';
        }
        if (p.blood_type_needed && document.getElementById('req-blood-type')) {
          document.getElementById('req-blood-type').value = p.blood_type_needed;
        }
        if (p.age && document.getElementById('req-patient-age')) {
          document.getElementById('req-patient-age').value = p.age;
        }
        if (p.sex && document.getElementById('req-patient-sex')) {
          document.getElementById('req-patient-sex').value = p.sex;
        }
        if (!document.getElementById('req-requester-name').value) {
          document.getElementById('req-requester-name').value = `${p.first_name} ${p.last_name}`.trim();
        }
      }

      const locSelect = document.getElementById('req-location-select');
      if (locSelect && Array.isArray(data.locations)) {
        locSelect.innerHTML = data.locations.map(l => `<option value="${UI.esc(l.location_name)}" data-addr="${UI.esc(l.address || '')}" selected>${UI.esc(l.location_name)}</option>`).join('');
        if (data.locations.length > 0) {
          document.getElementById('req-facility-address').value = data.locations[0].address || 'Brgy. Zone 1, San Mariano, Isabela';
          if (document.getElementById('req-facility-contact')) {
            document.getElementById('req-facility-contact').value = data.locations[0].contact_number || '0917-555-4321';
          }
        }
      }

      // Render Patient Request Table
      const listEl = document.getElementById('req-list');
      const reqs = data.requests || [];
      if (reqs.length === 0) {
        listEl.innerHTML = '<div class="empty">No requests submitted yet. Complete Sections A &ndash; E above to submit a requisition.</div>';
        return;
      }

      listEl.innerHTML = UI.table(
        ['Ref No', 'Date', 'Patient Name', 'Blood & Product', 'Units', 'Urgency', 'Facility', 'Doc Verification (Sec. F)', 'Final Status', 'Remarks / Actions'],
        reqs.map(r => {
          const docBadge = r.document_status === 'Verified' ? `<span class="badge-doc-verified">✅ Verified</span>` :
                           r.document_status === 'Needs Correction' ? `<span class="badge-doc-correction">⚠️ Needs Correction</span>` :
                           r.document_status === 'Rejected' ? `<span class="badge-doc-rejected">❌ Rejected</span>` :
                           `<span class="badge-doc-pending">⏳ Pending Review</span>`;

          return `<tr>
            <td><b>${UI.esc(r.request_ref_no || ('#' + r.id))}</b></td>
            <td>${UI.fmtDate(r.request_date)}</td>
            <td><b>${UI.esc(r.patient_name || '-')}</b><br><small class="muted">${UI.esc(r.hospital_department || r.ward_room || '')}</small></td>
            <td>${UI.bloodChip(r.blood_type)}<br><small class="muted">${UI.esc(r.component_type || 'Whole Blood')}</small></td>
            <td><b>${r.units_needed}</b></td>
            <td>${UI.statusBadge(r.urgency_level || r.priority)}</td>
            <td><small>${UI.esc(r.location || '-')}</small></td>
            <td>${docBadge}</td>
            <td>${UI.statusBadge(r.final_status || r.status)}</td>
            <td>
              ${r.correction_notes ? `<div style="font-size:11px; color:#854d0e; background:#fef9c3; padding:3px 6px; border-radius:4px; margin-bottom:4px;"><b>Note:</b> ${UI.esc(r.correction_notes)}</div>` : ''}
              ${(r.document_status !== 'Verified' && r.status === 'Pending') ? `<button class="btn slim" style="background:#059669; color:#fff; margin-bottom:4px; font-size:11.5px;" onclick="openVerificationUploadModal(${r.id}, '${UI.esc(r.request_ref_no || ('#' + r.id))}', '${UI.esc(r.patient_name || 'Patient')}')">📤 Upload Slip</button><br>` : ''}${r.status === 'Pending' ? `<button class="btn slim danger-outline" onclick="cancelMyReq(${r.id})">Cancel</button>` : ''}
            </td>
          </tr>`;
        }).join('')
      );
    }

    window.cancelMyReq = async (id) => {
      if (!(await UI.confirm('Cancel Request', 'Are you sure you want to cancel this pending blood request?'))) return;
      try {
        const res = await API.put(`/api/patient/requests/${id}/cancel`, {});
        UI.okToast(res.message);
        loadPatientList();
      } catch (err) { UI.errToast(err.message); }
    };

    document.getElementById('req-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = e.target;
      const btn = document.getElementById('btn-submit-req');
      if (btn.disabled) return;

      const payload = UI.formValues(form);
      payload.reason = payload.clinical_indication || 'Clinical transfusion requirement';

      if (!(await UI.confirm('Submit Blood Request', 'Confirm submission of this Official Blood Request Form to the hospital blood bank?'))) return;
      btn.disabled = true;
      btn.textContent = 'Submitting...';

      try {
        const res = await API.post('/api/patient/requests', payload);
        UI.okToast(res.message || 'Request submitted successfully.');
        const newReqId = res.id || res.request_id || 0;
        const refNo = res.request_ref_no || payload.request_ref_no || ('#' + (newReqId || 'Pending'));
        const patName = payload.patient_name || 'Patient';
        form.reset();
        document.getElementById('upload-status-badge').innerHTML = '';
        loadPatientList();

        // Immediate Post-Submit Flow: Show Generated Official Form (PDF) + 3-Step Process Guide + Direct Proceed to Upload Verification (No Skip / Cancel)
        showPostSubmitDownloadModal(newReqId, refNo, patName);
      } catch (err) {
        UI.errToast(err.message || 'Error submitting request.');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Submit Official Blood Request';
      }
    });

    loadPatientList();
  }

  /* ================= STAFF / ADMIN VIEW ================= */
  async function renderStaff() {
    const page = Shell.page();
    const isStaffOnly = (Shell.role === 'staff');
    const dd = await API.get('/api/staff/dropdowns');
    const locOptions = dd.locations.map(l => `<option value="${l.id}">${UI.esc(l.location_name)}</option>`).join('');

    const blankFormCardHtml = isStaffOnly ? `
      <!-- Staff Official Blank Form Upload Card (Feature 6 - Applicable to Staff Only) -->
      <div class="card" style="margin-bottom: 16px; border: 1px solid rgba(56, 189, 248, 0.3); background: rgba(56, 189, 248, 0.06); border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.4);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div>
            <h3 style="margin:0; font-size:15px; color:#7dd3fc; display:flex; align-items:center; gap:6px;">
              📄 Official Blank Blood Request Form (Hospital Requisition Template)
            </h3>
            <small class="muted" style="font-size:12px; display:block; margin-top:2px; color:#94a3b8;">
              Blanko at napi-print na Blood Request Form na awtomatikong ida-download ng pasyente o requester matapos mag-submit.
            </small>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <a id="view-blank-form-btn" href="official_blood_request_form.php" target="_blank" class="btn slim" style="background:#0284c7; color:#fff; text-decoration:none; padding:6px 12px; font-size:12px;">
              👁️ View / Download Current Form
            </a>
            <label class="btn slim" style="background:#059669; color:#fff; cursor:pointer; padding:6px 12px; font-size:12px; margin:0;">
              📤 Upload Blank Form File
              <input type="file" id="blank-form-input" accept=".pdf,.png,.jpg,.jpeg,.docx" style="display:none;">
            </label>
          </div>
        </div>
        <div id="blank-form-upload-msg" style="margin-top:8px; font-size:12px; font-weight:600; display:none;"></div>
      </div>
    ` : '';

    page.innerHTML = `
      ${blankFormCardHtml}

      <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid rgba(225, 29, 72, 0.2); padding-bottom:10px;">
          <div>
            <h2 style="margin:0; font-size:19px; color:#ffffff;">Blood Requests &amp; Section F Verification Portal</h2>
            <small class="muted" style="color:#94a3b8;">Hospital Blood Bank Review, Compatibility Testing, and Inventory Allocation</small>
          </div>
          <div class="toolbar" style="margin:0;">
            <select id="f-status">
              <option value="">All statuses</option>
              ${['Pending', 'Needs Correction', 'Verified', 'Approved', 'Partially Fulfilled', 'Completed', 'Rejected', 'Cancelled'].map(s => `<option>${s}</option>`).join('')}
            </select>
            <button class="btn slim ghost" id="refresh-btn">🔄 Refresh</button>
          </div>
        </div>
        <div id="req-table"></div>
      </div>
      <datalist id="loc-list">${locOptions}</datalist>
    `;

    window._requestsCache = {};

    // Check current uploaded form URL
    fetch(UI.url('/api/test_pingpong.php?action=get_blank_form'))
      .then(r => r.json())
      .then(d => {
        if (d && d.form_url) {
          const btn = document.getElementById('view-blank-form-btn');
          if (btn) btn.href = d.form_url;
        }
      }).catch(() => {});

    // Blank form upload handler
    const blankInput = document.getElementById('blank-form-input');
    if (blankInput) {
      blankInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const msgEl = document.getElementById('blank-form-upload-msg');
        if (msgEl) {
          msgEl.style.display = 'block';
          msgEl.style.color = '#2563eb';
          msgEl.textContent = 'Uploading blank form (' + file.name + ')...';
        }
        const fd = new FormData();
        fd.append('form_file', file);
        try {
          const res = await fetch(UI.url('/api/test_pingpong.php?action=upload_blank_form'), {
            method: 'POST',
            body: fd
          });
          const json = await res.json();
          if (json.success) {
            UI.okToast(json.message || 'Blank form uploaded successfully!');
            if (msgEl) {
              msgEl.style.color = '#15803d';
              msgEl.textContent = '✅ Form uploaded! Current file: ' + json.file_name;
            }
            const btn = document.getElementById('view-blank-form-btn');
            if (btn && json.form_url) btn.href = json.form_url;
          } else {
            UI.errToast(json.message || 'Upload failed');
            if (msgEl) {
              msgEl.style.color = '#dc2626';
              msgEl.textContent = '❌ Upload failed: ' + (json.message || 'Server error');
            }
          }
        } catch (err) {
          UI.errToast(err.message || 'Upload failed');
        }
      });
    }

    async function loadStaffList() {
      const status = document.getElementById('f-status').value;
      const data = await API.get('/api/staff/requests' + (status ? `?status=${status}` : ''));
      window._requestsCache = {};
      (data.requests || []).forEach(r => { window._requestsCache[r.id] = r; });

      document.getElementById('req-table').innerHTML = UI.table(
        ['ID / Ref No', 'Date', 'Patient & Ward', 'Component & Blood', 'Units', 'Urgency', 'Prescription Doc', 'Sec. F Verification', 'Final Status', 'Actions'],
        (data.requests || []).map(r => {
          const docBadge = r.document_status === 'Verified' ? `<span class="badge-doc-verified">✅ Verified</span>` :
                           r.document_status === 'Needs Correction' ? `<span class="badge-doc-correction">⚠️ Needs Correction</span>` :
                           r.document_status === 'Rejected' ? `<span class="badge-doc-rejected">❌ Rejected</span>` :
                           `<span class="badge-doc-pending">⏳ Pending Review</span>`;

          const patName = r.patient_name || (r.first_name + ' ' + r.last_name);

          return `<tr>
            <td><b>${UI.esc(r.request_ref_no || ('#' + r.id))}</b></td>
            <td>${UI.fmtDate(r.request_date)}</td>
            <td>
              <a href="javascript:void(0)" onclick="openSectionFModal(${r.id})" style="color:#1e40af; font-weight:700; text-decoration:none;">
                ${UI.esc(patName)}
              </a>
              <br><small class="muted">${UI.esc(r.hospital_department || r.ward_room || r.location || '-')}</small>
            </td>
            <td>${UI.bloodChip(r.blood_type)}<br><small class="muted">${UI.esc(r.component_type || 'Whole Blood')}</small></td>
            <td><b>${r.units_needed}</b></td>
            <td>${UI.statusBadge(r.urgency_level || r.priority)}</td>
            <td>${
              r.medical_document_url ?
                `<button type="button" class="btn slim ghost" style="padding:2px 8px; font-size:11px;" onclick="openSectionFModal(${r.id})">🔍 View Slip</button>` :
                `<span class="muted small">No file</span>`
            }</td>
            <td>${docBadge}</td>
            <td>${UI.statusBadge(r.final_status || r.status)}</td>
            <td>
              <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                <button class="btn slim ghost" style="color:#1e40af; border-color:#bfdbfe; font-weight:700;" onclick="openSectionFModal(${r.id})">📋 Section F Review</button>
                ${['Pending', 'Processing', 'Needs Correction', 'Verified'].includes(r.status) && Shell.role === 'staff' ? `
                  <button class="btn slim green" style="background:#059669; color:#fff; font-weight:700; border:none; padding:3px 9px;" onclick="fulfillModal(${r.id}, '${r.blood_type}', ${r.units_needed}, '${r.component_type || 'Whole Blood'}')">💉 Fulfill</button>
                ` : ''}
              </div>
            </td>
          </tr>`;
        }).join('')
      );
    }

    window.openSectionFModal = (id) => {
      const r = window._requestsCache[id];
      if (!r) return;
      const patName = r.patient_name || (r.first_name + ' ' + r.last_name);
      const docImg = r.medical_document_url;
      const viewDocUrl = UI.url(`view_doc.php?id=${r.id}`);

      const content = `
        <div style="display:grid; grid-template-columns: 320px 1fr; gap:20px; align-items:start;">
          <!-- LEFT COLUMN: Requisition Document Preview -->
          <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:16px; display:flex; flex-direction:column; gap:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span style="font-size:13px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:6px;">
                📄 Doctor's Requisition Slip
              </span>
              <a href="${viewDocUrl}" target="_blank" class="btn slim ghost" style="font-size:11px; padding:3px 8px; border-radius:6px; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                ↗ Full Size / Print
              </a>
            </div>
            
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; min-height:280px; max-height:420px; display:flex; align-items:center; justify-content:center; position:relative;">
              ${docImg ? `
                <img src="${docImg}" 
                     style="max-width:100%; max-height:400px; object-fit:contain; cursor:pointer;" 
                     onclick="window.open('${viewDocUrl}', '_blank')" 
                     alt="Requisition Slip" 
                     onerror="this.parentElement.innerHTML='<div style=\'padding:32px 16px; text-align:center; color:#64748b; font-size:12px;\'><div style=\'font-size:32px; margin-bottom:8px;\'>📋</div><b style=\'color:#0f172a; font-size:13px;\'>Official Digital Requisition Active</b><p style=\'margin:6px 0 14px; font-size:11.5px; color:#64748b; line-height:1.4;\'>Uploaded slip archived. Clinical record is verified in the NVBSP registry.</p><a href=\'${viewDocUrl}\' target=\'_blank\' class=\'btn slim\' style=\'background:#0284c7; color:#fff; text-decoration:none; font-size:11.5px; padding:6px 12px;\'>📄 View Official Requisition Form</a></div>';"/>
              ` : `
                <div style="padding:32px 16px; text-align:center; color:#64748b; font-size:12px;">
                  <div style="font-size:32px; margin-bottom:8px;">📋</div>
                  <b style="color:#0f172a; font-size:13px;">Official Digital Requisition</b>
                  <p style="margin:6px 0 14px; font-size:11.5px; color:#64748b; line-height:1.4;">
                    Physical requisition template generated and ready for hospital clinical review.
                  </p>
                  <a href="${viewDocUrl}" target="_blank" class="btn slim" style="background:#0284c7; color:#fff; text-decoration:none; font-size:11.5px; padding:6px 12px;">
                    📄 View Official Requisition Form
                  </a>
                </div>
              `}
            </div>

            <div style="font-size:11.5px; color:#334155; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; display:flex; justify-content:space-between; align-items:center;">
              <span><b>Ref No:</b> ${UI.esc(r.request_ref_no || ('#' + r.id))}</span>
              <span style="font-size:11px;">📅 ${UI.fmtDate(r.request_date)}</span>
            </div>
          </div>

          <!-- RIGHT COLUMN: Case Summary & Section F Form -->
          <div style="display:flex; flex-direction:column; gap:14px;">
            <!-- Sections A - E Summary Card -->
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
              <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:12px;">
                <span style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">
                  Patient &amp; Clinical Requisition Overview
                </span>
                <span style="display:inline-flex; gap:6px;">
                  ${UI.statusBadge(r.urgency_level || r.priority)}
                  ${r.document_status === 'Verified' ? '<span class="badge-doc-verified">✅ Document Verified</span>' :
                    r.document_status === 'Needs Correction' ? '<span class="badge-doc-correction">⚠️ Needs Correction</span>' :
                    r.document_status === 'Rejected' ? '<span class="badge-doc-rejected">❌ Document Disallowed</span>' :
                    '<span class="badge-doc-pending">⏳ Pending Review</span>'}
                </span>
              </div>

              <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px 16px; font-size:12.5px; color:#0f172a;">
                <div><span class="muted" style="font-size:11.5px; display:block;">Patient Name:</span> <b>${UI.esc(patName)}</b> (${UI.esc(r.patient_sex || '-')} &bull; Age: ${UI.esc(r.patient_age || '-')})</div>
                <div><span class="muted" style="font-size:11.5px; display:block;">Hospital MRN / Case No:</span> <b>${UI.esc(r.patient_mrn || r.patient_code || 'N/A')}</b></div>
                <div><span class="muted" style="font-size:11.5px; display:block;">Facility / Location:</span> <b>${UI.esc(r.location || '-')}</b></div>
                <div><span class="muted" style="font-size:11.5px; display:block;">Department &amp; Ward / Room:</span> <b>${UI.esc(r.hospital_department || r.ward_room || '-')}</b></div>
                <div><span class="muted" style="font-size:11.5px; display:block;">Requested Product:</span> ${UI.bloodChip(r.blood_type)} <b style="margin-left:4px;">${UI.esc(r.component_type || 'Whole Blood')}</b> (${r.units_needed} units)</div>
                <div><span class="muted" style="font-size:11.5px; display:block;">Attending Physician:</span> <b>Dr. ${UI.esc(r.physician_name || '-')}</b> (PRC: ${UI.esc(r.physician_license_no || '-')})</div>
                <div><span class="muted" style="font-size:11.5px; display:block;">Requester / Liaison:</span> <b>${UI.esc(r.requester_name || patName)}</b> (${UI.esc(r.requester_relationship || 'Self')})</div>
                <div><span class="muted" style="font-size:11.5px; display:block;">Contact Number:</span> <b>${UI.esc(r.contact_number || r.patient_contact || '-')}</b></div>
              </div>

              <div style="margin-top:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:12px;">
                <b style="color:#c0152e;">Clinical Indication / Diagnosis:</b> 
                <span style="color:#334155;">${UI.esc(r.clinical_indication || r.reason || 'None specified')}</span>
              </div>
            </div>

            <!-- Section F Verification Card -->
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-top:3px solid #c0152e; border-radius:14px; padding:18px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                <div>
                  <h4 style="margin:0; color:#c0152e; font-size:13.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">
                    F. Blood Bank / Hospital Staff Verification
                  </h4>
                  <small class="muted" style="font-size:11.5px; color:#64748b;">Document audit, crossmatch confirmation, and blood unit allocation</small>
                </div>
                <span style="font-size:11px; background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; padding:3px 8px; border-radius:6px; font-weight:700;">
                  Official Staff Only
                </span>
              </div>

              <div style="display:flex; flex-direction:column; gap:12px;">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                  <div>
                    <label style="display:block; font-size:11.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                      Document Status *
                    </label>
                    <select id="modal-sec-f-docstatus" style="width:100%; padding:9px 12px; font-size:13px; border:1.5px solid #cbd5e1; border-radius:8px; background:#ffffff; color:#0f172a;">
                      <option value="Verified" ${r.document_status === 'Verified' ? 'selected' : ''}>Verified (Document Accepted)</option>
                      <option value="Needs Correction" ${r.document_status === 'Needs Correction' ? 'selected' : ''}>Needs Correction (Return to Requester)</option>
                      <option value="Rejected" ${r.document_status === 'Rejected' ? 'selected' : ''}>Rejected (Document Disallowed)</option>
                      <option value="Pending" ${r.document_status === 'Pending' ? 'selected' : ''}>Pending Review</option>
                    </select>
                  </div>
                  <div>
                    <label style="display:block; font-size:11.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                      Units &amp; Component Confirmed
                    </label>
                    <div style="display:flex; gap:8px;">
                      <input id="modal-sec-f-units" type="number" min="1" max="${r.units_needed}" value="${r.units_confirmed || r.units_needed}" style="width:70px; padding:9px 10px; font-size:13px; border:1.5px solid #cbd5e1; border-radius:8px; background:#ffffff; color:#0f172a; text-align:center; font-weight:700;">
                      <input id="modal-sec-f-comp" value="${UI.esc(r.component_confirmed || r.component_type || 'Whole Blood')}" style="flex:1; padding:9px 12px; font-size:13px; border:1.5px solid #cbd5e1; border-radius:8px; background:#ffffff; color:#0f172a;">
                    </div>
                  </div>
                </div>

                <div>
                  <label style="display:block; font-size:11.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                    Compatibility &amp; Testing Notes (Authorized Staff Only)
                  </label>
                  <textarea id="modal-sec-f-compat" style="width:100%; min-height:55px; padding:9px 12px; font-size:12.5px; border:1.5px solid #cbd5e1; border-radius:8px; background:#ffffff; color:#0f172a; font-family:inherit; resize:vertical;" placeholder="Crossmatch result (Major/Minor), Serology clearance, Bag segment IDs...">${UI.esc(r.compatibility_notes || '')}</textarea>
                </div>

                <div>
                  <label style="display:block; font-size:11.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                    Correction Notes / Rejection Remarks <span style="font-weight:400; color:#64748b;">(Visible to Patient in App)</span>
                  </label>
                  <textarea id="modal-sec-f-correction" style="width:100%; min-height:50px; padding:9px 12px; font-size:12.5px; border:1.5px solid #cbd5e1; border-radius:8px; background:#ffffff; color:#0f172a; font-family:inherit; resize:vertical;" placeholder="If 'Needs Correction' or 'Rejected', detail exact issue here...">${UI.esc(r.correction_notes || r.rejection_reason || '')}</textarea>
                </div>

                <div>
                  <label style="display:block; font-size:11.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                    Staff Verifier Signature / ID
                  </label>
                  <input id="modal-sec-f-staff-sig" value="${UI.esc(r.staff_signature_id || (Shell.user.username + ' (Staff ID #' + Shell.user.id + ' - ' + (Shell.hospital?.location_name || 'Hospital Staff') + ')'))}" style="width:100%; padding:9px 12px; font-size:12.5px; border:1.5px solid #cbd5e1; border-radius:8px; background:#ffffff; color:#0f172a;">
                </div>
              </div>

              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; border-top:1px solid #e2e8f0; padding-top:14px; flex-wrap:wrap; gap:10px;">
                <button type="button" class="btn" style="background:#0284c7; color:#fff; padding:8px 18px; font-size:13px; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:6px;" onclick="saveSectionF(${r.id})">
                  💾 Save Section F Verification
                </button>
                ${['admin', 'superadmin'].includes(Shell.role) ? '' : `
                <button type="button" class="btn green" style="background:#16a34a; color:#fff; padding:8px 20px; font-size:13px; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:6px;" onclick="fulfillModal(${r.id}, '${r.blood_type}', ${r.units_needed}, '${r.component_type || 'Whole Blood'}')">
                  💉 Fulfill &amp; Release
                </button>
                `}
              </div>
            </div>
          </div>
        </div>
          </div>
        </div>
          </div>
        </div>
      `;

      UI.modal(`Requisition Case #${r.id} — ${patName}`, content, `<button class="btn ghost" onclick="UI.closeModal()">Close</button>`, 'wide');
    };

    window.saveSectionF = async (id) => {
      const docStatus = document.getElementById('modal-sec-f-docstatus').value;
      const unitsConfirmed = Number(document.getElementById('modal-sec-f-units').value);
      const compConfirmed = document.getElementById('modal-sec-f-comp').value;
      const compatNotes = document.getElementById('modal-sec-f-compat').value;
      const corrNotes = document.getElementById('modal-sec-f-correction').value;
      const staffSig = document.getElementById('modal-sec-f-staff-sig').value;

      try {
        const res = await API.put(`/api/staff/requests/${id}/verify`, {
          document_status: docStatus,
          units_confirmed: unitsConfirmed,
          component_confirmed: compConfirmed,
          compatibility_notes: compatNotes,
          correction_notes: corrNotes,
          staff_signature_id: staffSig
        });
        UI.okToast(res.message);
        UI.closeModal();
        loadStaffList();
      } catch (err) { UI.errToast(err.message); }
    };

    window.fulfillModal = (id, bType, units, comp) => {
      UI.modal(`Fulfill Blood Requisition #${id}`, `
        <div style="padding:4px;">
          <p class="muted small" style="margin-bottom:10px;">
            Blood units will be allocated from available hospital stock. Atomic inventory checks strictly prevent negative stock.
          </p>
          <div class="field">
            <label>Release from Location / Hospital *</label>
            <select id="fulfill-loc-select">${locOptions}</select>
          </div>
          <div class="field">
            <label>Fulfillment Mode *</label>
            <select id="fulfill-mode-select">
              <option value="FULL">Full Allocation (${units} Units)</option>
              <option value="PARTIAL">Partial Allocation (Specify below)</option>
            </select>
          </div>
          <div class="field" id="partial-units-field" style="display:none;">
            <label>Units to Release Now *</label>
            <input type="number" id="fulfill-partial-units" min="1" max="${units}" value="1">
          </div>
        </div>
      `, `
        <button class="btn ghost" onclick="UI.closeModal()">Cancel</button>
        <button class="btn" onclick="executeFulfill(${id})">Confirm Release</button>
      `);

      document.getElementById('fulfill-mode-select').addEventListener('change', (e) => {
        document.getElementById('partial-units-field').style.display = e.target.value === 'PARTIAL' ? 'block' : 'none';
      });
    };

    window.executeFulfill = async (id) => {
      const locId = Number(document.getElementById('fulfill-loc-select').value);
      const mode = document.getElementById('fulfill-mode-select').value;
      const partialUnits = Number(document.getElementById('fulfill-partial-units')?.value || 1);

      UI.closeModal();
      if (!(await UI.confirm('Confirm Inventory Allocation', 'Deduct blood units from inventory now?'))) return;

      try {
        if (mode === 'PARTIAL') {
          const res = await API.put(`/api/staff/requests/${id}/status`, {
            status: 'Partially Fulfilled',
            location_id: locId,
            units_confirmed: partialUnits
          });
          UI.okToast(res.message);
        } else {
          const res = await API.put(`/api/staff/requests/${id}/status`, {
            status: 'Approved',
            location_id: locId
          });
          UI.okToast(res.message);
        }
        loadStaffList();
      } catch (err) { UI.errToast(err.message); }
    };

    document.getElementById('f-status').addEventListener('change', loadStaffList);
    document.getElementById('refresh-btn').addEventListener('click', loadStaffList);
    loadStaffList();
  }
})();
</script>
<div id="toasts"></div>
</body>
</html>
