<?php
/**
 * php/routes/patient.php
 * ----------------------
 * Patient self-service: submit blood requests, list own requests,
 * cancel own pending request. Patients never touch inventory.
 * Ownership is always re-checked against the session (no IDOR).
 */
declare(strict_types=1);

$action = strtolower($segments[1] ?? '');
$user = requireRoles(['patient', 'donor']);

$statement = db()->prepare("SELECT * FROM patients WHERE user_id = ? AND status = 'active'");
$statement->execute([$user['id']]);
$patient = $statement->fetch();
if (!$patient) {
    // If user is registered as donor, look up donor profile and create/link patient record
    $dStmt = db()->prepare('SELECT first_name, middle_name, last_name, blood_type, age, sex, contact_number, address FROM donors WHERE user_id = ?');
    $dStmt->execute([$user['id']]);
    $dnr = $dStmt->fetch();
    $fName = $dnr['first_name'] ?? ($user['username'] ?? 'User');
    $mName = $dnr['middle_name'] ?? null;
    $lName = $dnr['last_name'] ?? '';
    $bType = (!empty($dnr['blood_type']) && validBloodType($dnr['blood_type'])) ? $dnr['blood_type'] : 'O+';
    $age = !empty($dnr['age']) ? (int)$dnr['age'] : null;
    $sex = !empty($dnr['sex']) ? $dnr['sex'] : null;
    $contact = $dnr['contact_number'] ?? null;
    $addr = $dnr['address'] ?? null;
    $pCode = nextCode('patients', 'patient_code', 'PAT');

    try {
        db()->prepare("INSERT INTO patients (user_id, patient_code, first_name, middle_name, last_name, age, sex, contact_number, address, blood_type_needed, hospital, medical_notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Community / General', 'Self-registered requestor', 'active')")
            ->execute([$user['id'], $pCode, $fName, $mName, $lName, $age, $sex, $contact, $addr, $bType]);
        $statement->execute([$user['id']]);
        $patient = $statement->fetch();
    } catch (Throwable $e) {
        appLog('autoCreatePatientForDonor', $e);
    }
}
if (!$patient) jsonResponse(['ok' => false, 'message' => 'Patient profile could not be initialized.'], 404);

/* ----------------------------------------------- my requests (list) */
if ($action === 'requests' && $method === 'GET' && !isset($segments[2])) {
    $rows = db()->prepare(
        'SELECT r.*, u.username AS processed_by_user
           FROM blood_requests r LEFT JOIN users u ON u.id = r.processed_by
          WHERE r.patient_id = ? ORDER BY r.created_at DESC, r.id DESC'
    );
    $rows->execute([$patient['id']]);

    $locs = db()->query("SELECT id, location_name, address FROM blood_locations WHERE available_status = 'Active' ORDER BY location_name ASC")->fetchAll();

    jsonResponse([
        'ok' => true,
        'requests' => $rows->fetchAll(),
        'patient' => $patient,
        'locations' => $locs
    ]);
}

/* --------------------------------------------- new request (create) */
if ($action === 'requests' && $method === 'POST') {
    ensureHospitalSchema();
    $missing = requireFields($body, ['blood_type', 'units_needed', 'reason']);
    if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);
    if (!validBloodType((string) $body['blood_type'])) {
        jsonResponse(['ok' => false, 'message' => 'Please select a valid blood type.'], 400);
    }
    if (!validPositiveInt($body['units_needed'])) {
        jsonResponse(['ok' => false, 'message' => 'Units needed must be a positive whole number.'], 400);
    }
    $units = (int) $body['units_needed'];
    if ($units < 1 || $units > 10) {
        jsonResponse(['ok' => false, 'message' => 'Units needed must be between 1 and 10.'], 400);
    }
    $reason = trim((string) $body['reason']);
    if (mb_strlen($reason) > 500) {
        jsonResponse(['ok' => false, 'message' => 'Please keep the reason under 500 characters.'], 400);
    }

    $validComponents = ['Whole Blood', 'Packed Red Blood Cells (PRBC)', 'Platelets', 'Fresh Frozen Plasma (FFP)', 'Cryoprecipitate', 'Other'];
    $component = in_array((string) ($body['component_type'] ?? ''), $validComponents, true)
        ? (string) $body['component_type'] : 'Whole Blood';

    $patientName = trim((string) ($body['patient_name'] ?? ''));
    if ($patientName === '') {
        $patientName = trim($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']);
    }

    // Section A fields
    $hospDept = trim((string)($body['hospital_department'] ?? ''));
    $facilityAddr = trim((string)($body['facility_address'] ?? ''));
    $facilityContact = trim((string)($body['facility_contact'] ?? ''));
    $requestRefNo = trim((string)($body['request_ref_no'] ?? ''));
    if ($requestRefNo === '') {
        $requestRefNo = 'REQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    // Section B fields
    $patientDob = !empty($body['patient_dob']) ? date('Y-m-d', strtotime((string)$body['patient_dob'])) : null;
    $patientAge = !empty($body['patient_age']) && is_numeric($body['patient_age']) ? (int)$body['patient_age'] : null;
    $patientMrn = trim((string)($body['patient_mrn'] ?? ''));
    $patientSex = in_array((string)($body['patient_sex'] ?? ''), ['Male', 'Female'], true) ? (string)$body['patient_sex'] : null;
    $wardRoom = trim((string)($body['ward_room'] ?? ''));
    $clinicalIndication = trim((string)($body['clinical_indication'] ?? ''));
    if ($clinicalIndication === '') {
        $clinicalIndication = $reason;
    }

    // Section C fields
    $urgencyLevel = in_array((string)($body['urgency_level'] ?? ''), ['Routine', 'Urgent', 'Emergency'], true)
        ? (string)$body['urgency_level'] : (in_array((string)($body['priority'] ?? ''), ['Critical', 'Urgent'], true) ? 'Urgent' : 'Routine');
    $specialRequirements = trim((string)($body['special_requirements'] ?? ''));

    // Section D fields
    $requesterName = trim((string)($body['requester_name'] ?? ''));
    if ($requesterName === '') {
        $requesterName = trim($patient['first_name'] . ' ' . $patient['last_name']);
    }
    $requesterRelationship = trim((string)($body['requester_relationship'] ?? 'Self / Requester'));
    $requesterContact = trim((string)($body['requester_contact'] ?? ''));
    if ($requesterContact === '') $requesterContact = $contactNumber;
    $requesterEmail = trim((string)($body['requester_email'] ?? ''));

    // Section E fields
    $physicianName = trim((string)($body['physician_name'] ?? ''));
    $physicianLicenseNo = trim((string)($body['physician_license_no'] ?? ''));
    $physicianSignature = trim((string)($body['physician_signature'] ?? ''));
    $physicianSignedAt = (!empty($physicianSignature) || !empty($physicianName)) ? date('Y-m-d H:i:s') : null;
    $medicalDocUrl = trim((string)($body['medical_document_url'] ?? ''));

    $neededDateTime = null;
    if (!empty($body['needed_datetime'])) {
        $ts = strtotime((string) $body['needed_datetime']);
        if ($ts !== false) {
            $neededDateTime = date('Y-m-d H:i:s', $ts);
        }
    }

    $contactNumber = trim((string) ($body['contact_number'] ?? ''));
    if ($contactNumber === '') {
        $contactNumber = (string) $patient['contact_number'];
    }

    $priority = in_array((string) ($body['priority'] ?? ''), ['Normal', 'Urgent', 'Critical'], true)
        ? (string) $body['priority'] : ($urgencyLevel === 'Emergency' ? 'Critical' : ($urgencyLevel === 'Urgent' ? 'Urgent' : 'Normal'));

    // 1. Anti-spam 30-Second Cooldown Check
    $lastReqStmt = db()->prepare("SELECT created_at, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS sec_diff FROM blood_requests WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $lastReqStmt->execute([$patient['id']]);
    $lastReq = $lastReqStmt->fetch();
    if ($lastReq && isset($lastReq['sec_diff']) && (int)$lastReq['sec_diff'] < 30) {
        $waitSec = 30 - (int)$lastReq['sec_diff'];
        jsonResponse([
            'ok' => false,
            'message' => "Kailangan maghintay ng {$waitSec} segundo bago muling makapag-request ng dugo (Anti-spam cooldown).",
            'cooldown_remaining' => $waitSec
        ], 429);
    }

    // 2. Daily Limit Check (Generous 100 requests per day for active testing & operations)
    $dailyStmt = db()->prepare("SELECT COUNT(DISTINCT COALESCE(group_id, id)) FROM blood_requests WHERE patient_id = ? AND created_at >= CURRENT_DATE");
    $dailyStmt->execute([$patient['id']]);
    $dailyCount = (int) $dailyStmt->fetchColumn();
    if ($dailyCount >= 100) {
        jsonResponse([
            'ok' => false,
            'message' => "Naabot na ang limitasyon ng request para sa araw na ito. Mangyaring makipag-ugnayan sa ospital.",
            'daily_limit_reached' => true
        ], 429);
    }

    $database = db();
    $groupId = 'REQ-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
    $isBroadcast = !empty($body['broadcast_all']) || ($body['location'] ?? '') === 'ALL_HOSPITALS';
    $selectedHospIds = !empty($body['hospital_ids']) && is_array($body['hospital_ids']) ? $body['hospital_ids'] : [];

    $targetHospitals = [];
    if ($isBroadcast) {
        $targetHospitals = $database->query("SELECT id, location_name FROM blood_locations WHERE available_status = 'Active' ORDER BY location_name")->fetchAll();
    } elseif (!empty($selectedHospIds)) {
        $inMarks = implode(',', array_fill(0, count($selectedHospIds), '?'));
        $hStmt = $database->prepare("SELECT id, location_name FROM blood_locations WHERE id IN ($inMarks) AND available_status = 'Active'");
        $hStmt->execute($selectedHospIds);
        $targetHospitals = $hStmt->fetchAll();
    } elseif (ctype_digit((string)($body['location_id'] ?? ''))) {
        $hStmt = $database->prepare("SELECT id, location_name FROM blood_locations WHERE id = ? AND available_status = 'Active'");
        $hStmt->execute([(int) $body['location_id']]);
        $row = $hStmt->fetch();
        if ($row) $targetHospitals = [$row];
    }

    if (empty($targetHospitals)) {
        $locStr = trim((string) ($body['location'] ?? '')) !== '' ? mb_substr(trim((string) $body['location']), 0, 255) : (string) $patient['hospital'];
        $database->prepare(
            "INSERT INTO blood_requests(
                patient_id, patient_name, blood_type, component_type, units_needed, needed_datetime, priority, reason, location, contact_number,
                status, group_id, target_hospital_id,
                hospital_department, facility_address, facility_contact, request_ref_no,
                patient_dob, patient_age, patient_mrn, patient_sex, ward_room, clinical_indication,
                urgency_level, special_requirements,
                requester_name, requester_relationship, requester_contact, requester_email,
                physician_name, physician_license_no, physician_signature, physician_signed_at,
                medical_document_url, document_status, final_status
             ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                'Pending', ?, NULL,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, 'Pending', 'Pending'
             )"
        )->execute([
            $patient['id'], $patientName, (string) $body['blood_type'], $component, $units, $neededDateTime, $priority, $reason, $locStr, $contactNumber,
            $groupId,
            $hospDept, $facilityAddr, $facilityContact, $requestRefNo,
            $patientDob, $patientAge, $patientMrn, $patientSex, $wardRoom, $clinicalIndication,
            $urgencyLevel, $specialRequirements,
            $requesterName, $requesterRelationship, $requesterContact, $requesterEmail,
            $physicianName, $physicianLicenseNo, $physicianSignature, $physicianSignedAt,
            $medicalDocUrl
        ]);
        $countCreated = 1;
    } else {
        $countCreated = 0;
        $insertStmt = $database->prepare(
            "INSERT INTO blood_requests(
                patient_id, patient_name, blood_type, component_type, units_needed, needed_datetime, priority, reason, location, contact_number,
                status, group_id, target_hospital_id,
                hospital_department, facility_address, facility_contact, request_ref_no,
                patient_dob, patient_age, patient_mrn, patient_sex, ward_room, clinical_indication,
                urgency_level, special_requirements,
                requester_name, requester_relationship, requester_contact, requester_email,
                physician_name, physician_license_no, physician_signature, physician_signed_at,
                medical_document_url, document_status, final_status
             ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                'Pending', ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, 'Pending', 'Pending'
             )"
        );
        foreach ($targetHospitals as $th) {
            $insertStmt->execute([
                $patient['id'], $patientName, (string) $body['blood_type'], $component, $units, $neededDateTime, $priority, $reason, $th['location_name'], $contactNumber,
                $groupId, (int) $th['id'],
                $hospDept, $facilityAddr, $facilityContact, $requestRefNo,
                $patientDob, $patientAge, $patientMrn, $patientSex, $wardRoom, $clinicalIndication,
                $urgencyLevel, $specialRequirements,
                $requesterName, $requesterRelationship, $requesterContact, $requesterEmail,
                $physicianName, $physicianLicenseNo, $physicianSignature, $physicianSignedAt,
                $medicalDocUrl
            ]);
            $countCreated++;
        }
    }

    logAction((int) $user['id'], 'Request Submitted',
        $patient['patient_code'] . ' requested ' . $units . ' unit(s) of ' . $component . ' (' . $body['blood_type'] . ') for ' . $patientName . ' (Sent to ' . $countCreated . ' hospital(s))');
        
    notifyStaff('New Blood Request', "Patient {$patient['patient_code']} requested {$units} unit(s) of {$component} ({$body['blood_type']}) for {$patientName}. Broadcasted to {$countCreated} hospital(s). Priority: {$priority}");
    
    $newReqId = (int) $database->lastInsertId();
    jsonResponse([
        'ok' => true,
        'id' => $newReqId,
        'request_id' => $newReqId,
        'request_ref_no' => $requestRefNo,
        'message' => $countCreated > 1
            ? "Urgent request broadcasted to {$countCreated} hospitals! The first hospital to approve will fulfill your request and auto-close the others."
            : 'Blood request submitted. Staff will review it shortly.',
        'group_id' => $groupId,
        'hospital_count' => $countCreated
    ]);
}

/* ------------------------------------------- cancel my own request */
if ($action === 'requests' && $method === 'PUT'
    && ctype_digit((string) ($segments[2] ?? '')) && strtolower($segments[3] ?? '') === 'cancel') {
    $request = db()->prepare('SELECT * FROM blood_requests WHERE id = ? AND patient_id = ?');
    $request->execute([(int) $segments[2], $patient['id']]);
    $row = $request->fetch();
    if (!$row) jsonResponse(['ok' => false, 'message' => 'Request not found.'], 404);
    if ($row['status'] !== 'Pending') {
        jsonResponse(['ok' => false, 'message' => 'Only pending requests can be cancelled.'], 400);
    }

    db()->prepare("UPDATE blood_requests SET status = 'Cancelled', processed_by = ? WHERE id = ?")
        ->execute([$user['id'], $row['id']]);
    logAction((int) $user['id'], 'Request Cancelled',
        $patient['patient_code'] . ' cancelled request #' . $row['id']);
    jsonResponse(['ok' => true, 'message' => 'Request cancelled.']);
}
