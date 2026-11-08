<?php
/**
 * BloodLink Official Blood Requisition Form Template
 * Republic of the Philippines - Department of Health (DOH) NVBSP Form
 * Ready for printing (Ctrl + P) and physical submission to hospital blood banks.
 * Can serve as a blank printable template OR filled verified requisition when ?id=X is passed.
 */
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/helpers.php';

$id = (int) ($_GET['id'] ?? $_GET['req_id'] ?? 0);
$req = null;

if ($id > 0) {
    try {
        $stmt = db()->prepare("
            SELECT r.*, p.patient_code, p.hospital AS patient_hospital, p.contact_number AS p_contact
            FROM blood_requests r
            LEFT JOIN patients p ON p.id = r.patient_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $req = null;
    }
}

$refNo = $req['request_ref_no'] ?? ('REQ-' . date('Ymd') . '-' . str_pad((string)($req['id'] ?? ''), 4, '0', STR_PAD_LEFT));
$reqDate = !empty($req['request_date']) ? date('M d, Y', strtotime((string)$req['request_date'])) : date('M d, Y');
$facility = $req['location'] ?? $req['hospital_department'] ?? $req['patient_hospital'] ?? '';
$wardRoom = $req['ward_room'] ?? '';
$patientName = $req['patient_name'] ?? '';
$patientAge = $req['patient_age'] ?? '';
$patientSex = $req['patient_sex'] ?? '';
$patientMrn = $req['patient_mrn'] ?? $req['patient_code'] ?? '';
$diagnosis = $req['clinical_indication'] ?? $req['reason'] ?? '';
$bloodType = $req['blood_type'] ?? '';
$compType = $req['component_type'] ?? 'Whole Blood';
$units = (int) ($req['units_needed'] ?? 1);
$urgency = $req['urgency_level'] ?? $req['priority'] ?? 'Routine';
$requesterName = $req['requester_name'] ?? $patientName;
$requesterContact = $req['requester_contact'] ?? $req['contact_number'] ?? $req['p_contact'] ?? '';
$requesterRel = $req['requester_relationship'] ?? 'Self / Liaison';
$docName = $req['physician_name'] ?? '';
$docLic = $req['physician_license_no'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Official Blood Request Form — DOH-NVBSP Hospital Requisition</title>
<style>
  @page { size: A4 portrait; margin: 10mm; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    color: #111;
    background: #f8fafc;
    margin: 0;
    padding: 20px;
  }
  .print-btn-bar {
    max-width: 820px;
    margin: 0 auto 16px auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  }
  .form-container {
    max-width: 820px;
    margin: 0 auto;
    background: #fff;
    padding: 26px 32px;
    border: 2px solid #991b1b;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  }
  .header {
    text-align: center;
    border-bottom: 2px solid #991b1b;
    padding-bottom: 12px;
    margin-bottom: 14px;
  }
  .header h4 { margin: 0; font-size: 11px; text-transform: uppercase; color: #555; letter-spacing: 0.5px; }
  .header h2 { margin: 3px 0; font-size: 16px; color: #991b1b; text-transform: uppercase; }
  .header h3 { margin: 2px 0 6px 0; font-size: 13.5px; color: #111; }
  .code-bar {
    display: flex;
    justify-content: space-between;
    font-size: 11.5px;
    background: #fef2f2;
    padding: 6px 12px;
    border: 1px solid #fecaca;
    border-radius: 4px;
    margin-top: 6px;
  }
  .section-title {
    background: #c0152e;
    color: #fff;
    font-size: 12px;
    font-weight: bold;
    padding: 5px 10px;
    margin-top: 14px;
    margin-bottom: 8px;
    border-radius: 4px;
    text-transform: uppercase;
  }
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
  .field { margin-bottom: 8px; }
  .field label { display: block; font-size: 10.5px; font-weight: bold; color: #475569; text-transform: uppercase; margin-bottom: 3px; }
  .field .box {
    border: 1px solid #cbd5e1;
    min-height: 26px;
    padding: 5px 9px;
    font-size: 12.5px;
    border-radius: 4px;
    background: #fafafa;
    color: #0f172a;
    font-weight: 600;
  }
  .notice {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 11px;
    color: #92400e;
    margin-top: 16px;
    line-height: 1.4;
  }
  .sig-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 24px;
    text-align: center;
  }
  .sig-line {
    border-top: 1px solid #333;
    padding-top: 4px;
    font-size: 11.5px;
    font-weight: bold;
  }
  .sig-title { font-size: 10.5px; color: #666; }
  @media print {
    body { background: #fff; padding: 0; }
    .print-btn-bar { display: none !important; }
    .form-container { border: 1.5px solid #333; box-shadow: none; padding: 16px; }
  }
</style>
</head>
<body>

<div class="print-btn-bar">
  <div>
    <strong style="color: #991b1b; font-size: 15px;">
      <?= $req ? 'Official Verified Blood Request Document' : 'Official Blank Blood Request Form (Hospital Template)' ?>
    </strong>
    <div style="font-size: 12px; color: #666;">
      <?= $req ? 'Digital requisition copy for clinical verification and hospital release' : 'I-print o i-download ang form na ito para sa pag-request ng dugo sa ospital' ?>
    </div>
  </div>
  <div style="display: flex; gap: 8px;">
    <?php if ($req): ?>
      <span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 6px; display: inline-flex; align-items: center;">
        ✅ Digital NVBSP Record
      </span>
    <?php endif; ?>
    <button onclick="window.print()" style="background: #c0152e; color: #fff; border: none; padding: 8px 18px; font-size: 13px; font-weight: bold; border-radius: 6px; cursor: pointer;">
      🖨️ Print / Save as PDF
    </button>
  </div>
</div>

<div class="form-container">
  <!-- DOH NVBSP Header -->
  <div class="header">
    <h4>Republic of the Philippines &bull; Department of Health</h4>
    <h2>National Voluntary Blood Services Program (NVBSP)</h2>
    <h3>OFFICIAL BLOOD REQUISITION &amp; COMPATIBILITY TESTING SLIP</h3>
    <div class="code-bar">
      <span><b>FORM CODE:</b> DOH-NVBSP-HBB-01</span>
      <span><b>REFERENCE NO:</b> <?= htmlspecialchars($refNo) ?></span>
      <span><b>DATE:</b> <?= htmlspecialchars($reqDate) ?></span>
    </div>
  </div>

  <!-- Section A -->
  <div class="section-title">SECTION A: REQUESTING FACILITY &amp; WARD</div>
  <div class="grid-2">
    <div class="field">
      <label>Hospital / Healthcare Facility Name</label>
      <div class="box"><?= htmlspecialchars($facility ?: '—') ?></div>
    </div>
    <div class="field">
      <label>Ward / Room / Bed No.</label>
      <div class="box"><?= htmlspecialchars($wardRoom ?: '—') ?></div>
    </div>
  </div>

  <!-- Section B -->
  <div class="section-title">SECTION B: PATIENT INFORMATION</div>
  <div class="field">
    <label>Patient Full Name (Last, First, Middle)</label>
    <div class="box"><?= htmlspecialchars($patientName ?: '—') ?></div>
  </div>
  <div class="grid-3">
    <div class="field">
      <label>Age / Date of Birth</label>
      <div class="box"><?= htmlspecialchars($patientAge ? (string)$patientAge . ' years old' : '—') ?></div>
    </div>
    <div class="field">
      <label>Sex</label>
      <div class="box">
        <?php if ($req): ?>
          <?= htmlspecialchars($patientSex ?: '—') ?>
        <?php else: ?>
          [ ] Male &nbsp;&nbsp;&nbsp; [ ] Female
        <?php endif; ?>
      </div>
    </div>
    <div class="field">
      <label>Hospital MRN / Case No.</label>
      <div class="box"><?= htmlspecialchars($patientMrn ?: '—') ?></div>
    </div>
  </div>
  <div class="field">
    <label>Clinical Diagnosis / Indication for Transfusion</label>
    <div class="box" style="min-height: 38px;"><?= htmlspecialchars($diagnosis ?: '—') ?></div>
  </div>

  <!-- Section C -->
  <div class="section-title">SECTION C: BLOOD COMPONENT REQUIREMENTS</div>
  <div class="grid-3">
    <div class="field">
      <label>Patient Blood Type (ABO/Rh)</label>
      <div class="box" style="font-weight: 800; color: #b91c1c; font-size: 13.5px;"><?= htmlspecialchars($bloodType ?: '—') ?></div>
    </div>
    <div class="field">
      <label>Component Requested</label>
      <div class="box">
        <?php if ($req): ?>
          <?= htmlspecialchars($compType) ?>
        <?php else: ?>
          [ ] Whole Blood<br>
          [ ] Packed Red Blood Cells (PRBC)<br>
          [ ] Platelets / WBC
        <?php endif; ?>
      </div>
    </div>
    <div class="field">
      <label>Units Needed &amp; Urgency</label>
      <div class="box">
        <?php if ($req): ?>
          <b><?= $units ?> unit(s)</b> &bull; <?= htmlspecialchars($urgency) ?>
        <?php else: ?>
          Units: _______ bag(s)<br>
          [ ] Routine &nbsp; [ ] Urgent &nbsp; [ ] Emergency
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Section D -->
  <div class="section-title">SECTION D: REQUESTER / CARRIER DETAILS</div>
  <div class="grid-2">
    <div class="field">
      <label>Name of Requester / Family Member</label>
      <div class="box"><?= htmlspecialchars($requesterName ?: '—') ?></div>
    </div>
    <div class="field">
      <label>Contact Number / Relationship</label>
      <div class="box"><?= htmlspecialchars(($requesterContact ? $requesterContact . ' &bull; ' : '') . $requesterRel) ?></div>
    </div>
  </div>

  <!-- Section E -->
  <div class="section-title">SECTION E: ATTENDING PHYSICIAN AUTHORIZATION</div>
  <div class="sig-grid">
    <div>
      <div style="height: 36px; display: flex; align-items: flex-end; justify-content: center; font-style: italic; color: #1e40af; font-weight: bold;">
        <?= $docName ? 'Dr. ' . htmlspecialchars($docName) : '' ?>
      </div>
      <div class="sig-line">Attending Physician Signature over Printed Name</div>
      <div class="sig-title">PRC License No.: <?= htmlspecialchars($docLic ?: '_____________________') ?></div>
    </div>
    <div>
      <div style="height: 36px; display: flex; align-items: flex-end; justify-content: center; font-size: 11.5px; color: #047857; font-weight: bold;">
        <?= !empty($req['reviewed_by']) ? 'Verified by Staff ID #' . htmlspecialchars((string)$req['reviewed_by']) : '' ?>
      </div>
      <div class="sig-line">Hospital Blood Bank / Medical Technologist Receiving</div>
      <div class="sig-title">Date &amp; Time Received: <?= !empty($req['reviewed_at']) ? htmlspecialchars((string)$req['reviewed_at']) : '_____________________' ?></div>
    </div>
  </div>

  <!-- DOH Processing Fee & Instructions Notice -->
  <div class="notice">
    <strong>⚖️ PAALALA SA PAG-REQUEST AT MGA BAYARIN (R.A. 7719):</strong><br>
    Ang dugo mula sa voluntary donors ay 100% LIBRE. Ang standardized processing fee na sinisingil ng accredited hospital blood bank ay para lamang sa pagsusuri ng dugo (HIV, Hepa B &amp; C, Syphilis, Malaria testing), compatibility crossmatching, at cold-chain storage alinsunod sa DOH Administrative Order No. 2015-0045.<br>
    <b>Hakbang para sa Pasyente/Liaison:</b> Dalhin ang pirmadong form na ito kasama ang 5mL EDTA blood sample tube ng pasyente sa Blood Bank para sa confirmatory typing at crossmatching.
  </div>
</div>

</body>
</html>
