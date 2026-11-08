<?php
/**
 * php/routes/donor.php
 * --------------------
 * Donor self-service: own donation history, own stats, nearby centers.
 * A donor can only ever see their OWN records (no id is accepted from
 * the client, the donor row is looked up from the session).
 */
declare(strict_types=1);

$action = strtolower($segments[1] ?? '');
$user = requireRoles(['donor', 'patient']);

$statement = db()->prepare('SELECT id, blood_type, first_name FROM donors WHERE user_id = ?');
$statement->execute([$user['id']]);
$donor = $statement->fetch();
if (!$donor) {
    // If user is registered as patient, look up patient profile and create/link donor record
    $pStmt = db()->prepare('SELECT first_name, middle_name, last_name, blood_type_needed, age, sex, contact_number, address FROM patients WHERE user_id = ?');
    $pStmt->execute([$user['id']]);
    $pat = $pStmt->fetch();
    $fName = $pat['first_name'] ?? ($user['username'] ?? 'User');
    $mName = $pat['middle_name'] ?? null;
    $lName = $pat['last_name'] ?? '';
    $bType = (!empty($pat['blood_type_needed']) && validBloodType($pat['blood_type_needed'])) ? $pat['blood_type_needed'] : 'O+';
    $age = !empty($pat['age']) ? (int)$pat['age'] : 25;
    $sex = !empty($pat['sex']) ? $pat['sex'] : 'Male';
    $contact = $pat['contact_number'] ?? '';
    $addr = $pat['address'] ?? '';
    $email = $user['email'] ?? '';
    $dCode = nextCode('donors', 'donor_code', 'DON');

    try {
        db()->prepare('INSERT INTO donors (user_id, donor_code, first_name, middle_name, last_name, age, sex, contact_number, address, blood_type, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$user['id'], $dCode, $fName, $mName, $lName, $age, $sex, $contact, $addr, $bType, $email]);
        $statement->execute([$user['id']]);
        $donor = $statement->fetch();
    } catch (Throwable $e) {
        appLog('autoCreateDonorForPatient', $e);
    }
}
if (!$donor) jsonResponse(['ok' => false, 'message' => 'Donor profile could not be initialized.'], 404);

/* ------------------------------------------------- my donations */
if ($action === 'history' && $method === 'GET') {
    $rows = db()->prepare(
        'SELECT id, blood_type, donation_date, blood_units, donation_location, status, reference_code, created_at, group_id, approved_by_hospital, notes
           FROM donations WHERE donor_id = ? ORDER BY donation_date DESC, id DESC'
    );
    $rows->execute([$donor['id']]);
    jsonResponse(['ok' => true, 'donations' => $rows->fetchAll()]);
}

/* -------------------------------------- pre-donation eligibility screening */
if ($action === 'screen' && $method === 'POST') {
    $weight = (float)($body['weight_kg'] ?? 0);
    $compType = trim((string)($body['component_type'] ?? 'Whole Blood'));
    $minWeight = ($compType === 'Whole Blood' ? 50.0 : 45.0);
    $sleep = (int)($body['sleep_hours'] ?? 0);
    $alcohol = !empty($body['alcohol_24h']);
    $tattoos = !empty($body['tattoo_piercing_12m']);
    $meds = !empty($body['medications_7d']);
    $fever = !empty($body['fever_cough_today']);
    $pregnant = !empty($body['pregnancy_nursing']);

    $reasons = [];
    if ($weight < $minWeight) {
        $reasons[] = "Minimum body weight of {$minWeight} kg is required (entered: {$weight} kg).";
    }
    if ($sleep < 6) {
        $reasons[] = "Must have at least 6 hours of sleep prior to donation (entered: {$sleep} hrs).";
    }
    if ($alcohol) {
        $reasons[] = "Must have zero alcohol intake within 24 hours prior to donation.";
    }
    if ($tattoos) {
        $reasons[] = "Deferral: Any tattoo, piercing, or acupuncture within the last 12 months requires a waiting period.";
    }
    if ($meds) {
        $reasons[] = "Deferral: Certain medications (e.g. antibiotics, blood thinners) require medical clearance.";
    }
    if ($fever) {
        $reasons[] = "Temporary Deferral: Must be free of fever, flu, or acute symptoms for at least 14 days.";
    }
    if ($pregnant) {
        $reasons[] = "Deferral: Pregnant and lactating individuals are temporarily deferred for maternal safety.";
    }

    $passed = empty($reasons);
    $deferralReason = $passed ? null : implode('; ', $reasons);
    $deferralUntil = null;
    if ($fever) {
        $deferralUntil = date('Y-m-d', strtotime('+14 days'));
    } elseif ($tattoos) {
        $deferralUntil = date('Y-m-d', strtotime('+12 months'));
    } elseif ($alcohol) {
        $deferralUntil = date('Y-m-d', strtotime('+24 hours'));
    }

    $screenStmt = db()->prepare(
        "INSERT INTO donor_screenings (
            donor_id, user_id, component_type, weight_kg, passed_screening, deferral_reason, deferral_until, screening_answers
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $screenStmt->execute([
        $donor['id'],
        $user['id'],
        $compType,
        $weight,
        $passed ? 1 : 0,
        $deferralReason,
        $deferralUntil,
        json_encode($body)
    ]);

    jsonResponse([
        'ok' => true,
        'eligible' => $passed,
        'reasons' => $reasons,
        'message' => $passed
            ? "Congratulations! You meet the initial health criteria for $compType donation."
            : "Screening Notice: You are temporarily deferred from donating. Reason: " . implode(' ', $reasons),
        'deferral_until' => $deferralUntil
    ]);
}

/* ------------------------------------------------- donate blood */
if ($action === 'donate' && $method === 'POST') {
    $date = trim((string) ($body['donation_date'] ?? ''));
    $time = trim((string) ($body['donation_time'] ?? ''));
    $healthStatus = trim((string) ($body['health_status'] ?? ''));
    $location = trim((string) ($body['donation_location'] ?? ''));
    $weight = (int) ($body['weight_check'] ?? 0);
    $sleep = (int) ($body['sleep_check'] ?? 0);
    $alcohol = (int) ($body['alcohol_check'] ?? 0);

    if (!$date || !$location || !$healthStatus || !$time) {
        jsonResponse(['ok' => false, 'message' => 'Please fill all required fields.'], 400);
    }
    if ($healthStatus !== 'Healthy') {
        jsonResponse(['ok' => false, 'message' => 'You must be healthy to donate.'], 400);
    }
    if (!$weight || !$sleep || !$alcohol) {
        jsonResponse(['ok' => false, 'message' => 'You do not meet the minimum eligibility criteria to donate.'], 400);
    }

    // Cooldown verification: 3 months (90 days) between completed donations
    $cooldownStmt = db()->prepare("SELECT id, donation_date, created_at, reference_code,
        DATEDIFF(CURDATE(), LEAST(donation_date, CURDATE())) AS days_passed,
        TIMESTAMPDIFF(SECOND, created_at, NOW()) AS sec_passed
        FROM donations WHERE donor_id = ? AND status = 'Completed' ORDER BY id DESC LIMIT 1");
    $cooldownStmt->execute([$donor['id']]);
    $lastDonation = $cooldownStmt->fetch();

    $COOLDOWN_DAYS = 90; // 3 months donation interval
    if ($lastDonation) {
        $daysPassed = max(0, (int) ($lastDonation['days_passed'] ?? 0));
        if ($daysPassed < $COOLDOWN_DAYS) {
            $daysRemaining = $COOLDOWN_DAYS - $daysPassed;
            $effectiveDate = min(strtotime($lastDonation['donation_date']), time());
            $nextDate = date('F j, Y', strtotime("+{$COOLDOWN_DAYS} days", $effectiveDate));
            $secRemaining = max(0, $daysRemaining * 86400);

            jsonResponse([
                'ok' => false,
                'in_cooldown' => true,
                'days_remaining' => $daysRemaining,
                'seconds_remaining' => $secRemaining,
                'next_eligible_date' => $nextDate,
                'message' => "Donation Cooldown Active: Para sa iyong kalusugan at pagbawi ng hemoglobin, kailangang maghintay ng {$daysRemaining} araw (hanggang {$nextDate}) bago muling mag-donate ng dugo."
            ], 429);
        }
    }
    
    // Generate Appointment Reference Code & Group ID
    $refCode = 'BD-' . date('Y') . '-' . strtoupper(substr(md5((string)random_int(1, 10000)), 0, 4));
    $groupId = 'DON-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
    $isBroadcast = !empty($body['broadcast_all']) || ($location === 'ALL_HOSPITALS');
    $selectedHospIds = !empty($body['hospital_ids']) && is_array($body['hospital_ids']) ? $body['hospital_ids'] : [];

    $targetHospitals = [];
    if ($isBroadcast) {
        $targetHospitals = db()->query("SELECT id, location_name FROM blood_locations WHERE available_status = 'Active' ORDER BY location_name")->fetchAll();
    } elseif (!empty($selectedHospIds)) {
        $inMarks = implode(',', array_fill(0, count($selectedHospIds), '?'));
        $hStmt = db()->prepare("SELECT id, location_name FROM blood_locations WHERE id IN ($inMarks) AND available_status = 'Active'");
        $hStmt->execute($selectedHospIds);
        $targetHospitals = $hStmt->fetchAll();
    } elseif (ctype_digit((string)($body['location_id'] ?? ''))) {
        $hStmt = db()->prepare("SELECT id, location_name FROM blood_locations WHERE id = ? AND available_status = 'Active'");
        $hStmt->execute([(int) $body['location_id']]);
        $row = $hStmt->fetch();
        if ($row) $targetHospitals = [$row];
    }

    if (empty($targetHospitals)) {
        $insert = db()->prepare(
            'INSERT INTO donations (donor_id, blood_type, donation_date, donation_time, blood_units, donation_location, health_status, weight_check, sleep_check, alcohol_check, reference_code, status, group_id, target_hospital_id)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, "Pending", ?, NULL)'
        );
        $insert->execute([$donor['id'], $donor['blood_type'], $date, $time, $location, $healthStatus, $weight, $sleep, $alcohol, $refCode, $groupId]);
    } else {
        $insert = db()->prepare(
            'INSERT INTO donations (donor_id, blood_type, donation_date, donation_time, blood_units, donation_location, health_status, weight_check, sleep_check, alcohol_check, reference_code, status, group_id, target_hospital_id)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, "Pending", ?, ?)'
        );
        foreach ($targetHospitals as $th) {
            $insert->execute([$donor['id'], $donor['blood_type'], $date, $time, $th['location_name'], $healthStatus, $weight, $sleep, $alcohol, $refCode, $groupId, (int) $th['id']]);
        }
    }
    
    logAction((int)$user['id'], 'Donation Booked', 'Donor ' . $donor['first_name'] . ' booked a donation at ' . $location . ' (Ref: ' . $refCode . ')');
    
    notifyStaff('New Donation Booking', "Donor {$donor['first_name']} booked a donation of {$donor['blood_type']} at {$location} on {$date} at {$time}. (Ref: {$refCode})");
    
    $messageHTML = "
        <div style='text-align:center; padding:6px 0;'>
            <div style='font-size:36px; margin-bottom:8px;'>❤️🩸🎉</div>
            <h3 style='color:#b00020; margin:0 0 8px 0; font-size:20px;'>MARAMING SALAMAT SA IYONG KABAYANIHAN!</h3>
            <p style='font-size:14px; color:#444; line-height:1.5; margin:0 0 16px 0;'>
                Taos-pusong pasasalamat mula sa BloodLink at sa pamilya ng mga pasyente. Ang iyong donasyon ay may kakayahang magligtas ng hanggang tatlong (3) buhay!
            </p>
            <div style='background:#fdeff1; border:2px dashed #b00020; padding:14px; margin-bottom:14px; border-radius:8px;'>
                <div style='font-size:12px; color:#777; text-transform:uppercase;'>Appointment Reference Code</div>
                <b style='font-size:26px; color:#b00020; display:block; margin:6px 0;'>{$refCode}</b>
                <div style='font-size:13px; color:#444;'>
                    <b>Ospital:</b> {$location}<br>
                    <b>Araw at Oras:</b> {$date} ({$time})
                </div>
            </div>
            <div style='font-size:13px; background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:10px; margin-bottom:12px; border-radius:6px; text-align:left;'>
                ⏳ <b>Status: Pending</b> — Naisumite ang appointment. Hihintayin ang beripikasyon at completion ng ospital bago magsimula ang 3-month (90 days) recovery countdown.
            </div>
            <div style='font-size:13px; color:#555; text-align:left;'>
                <b>Paalala:</b> Dalhin ang Reference Code at isang valid physical ID sa araw ng iyong appointment.
            </div>
        </div>
    ";
    jsonResponse(['ok' => true, 'message' => $messageHTML, 'reference_code' => $refCode, 'next_eligible_date' => null, 'cooldown_days' => 0]);
}


/* ----------------------------------------------------- my stats */
if ($action === 'stats' && $method === 'GET') {
    $stats = db()->prepare(
        "SELECT d.id, d.blood_type, d.first_name,
                (SELECT COUNT(*) FROM donations dn WHERE dn.donor_id = d.id) AS total_donations,
                (SELECT COALESCE(SUM(dn.blood_units),0) FROM donations dn
                  WHERE dn.donor_id = d.id AND dn.status='Approved') AS verified_units
           FROM donors d WHERE d.id = ?"
    );
    $stats->execute([$donor['id']]);
    $row = $stats->fetch() ?: [];
    if ($row) {
        $row['total_donations'] = (int) $row['total_donations'];
        $row['verified_units'] = (int) $row['verified_units'];

        // Check active cooldown (strictly for Completed donations)
        $cooldownStmt = db()->prepare("SELECT id, donation_date, created_at, reference_code,
            DATEDIFF(CURDATE(), LEAST(donation_date, CURDATE())) AS days_passed,
            TIMESTAMPDIFF(SECOND, created_at, NOW()) AS sec_passed
            FROM donations WHERE donor_id = ? AND status = 'Completed' ORDER BY id DESC LIMIT 1");
        $cooldownStmt->execute([$donor['id']]);
        $lastDonation = $cooldownStmt->fetch();

        $COOLDOWN_DAYS = 90; // 3 months donation interval
        $row['in_cooldown'] = false;
        $row['cooldown_seconds'] = 0;
        $row['days_remaining'] = 0;
        $row['next_eligible_date'] = '';
        if ($lastDonation) {
            $daysPassed = max(0, (int) ($lastDonation['days_passed'] ?? 0));
            if ($daysPassed < $COOLDOWN_DAYS) {
                $row['in_cooldown'] = true;
                $row['days_remaining'] = $COOLDOWN_DAYS - $daysPassed;
                $effectiveDate = min(strtotime($lastDonation['donation_date']), time());
                $row['next_eligible_date'] = date('F j, Y', strtotime("+{$COOLDOWN_DAYS} days", $effectiveDate));
                $secRem = ($COOLDOWN_DAYS * 86400) - max(0, (int)$lastDonation['sec_passed']);
                $row['cooldown_seconds'] = max(0, $secRem);
                $row['last_donation_date'] = $lastDonation['donation_date'];
            }
        }
    }
    jsonResponse(['ok' => true, 'stats' => $row]);
}

/* -------------------------------------------- donation locations */
if ($action === 'nearby' && $method === 'GET') {
    $locations = [];
    foreach (groupAvailability(getAvailability()) as $location) {
        unset($location['units'], $location['blood']);
        $locations[] = $location;
    }
    jsonResponse(['ok' => true, 'locations' => $locations]);
}
