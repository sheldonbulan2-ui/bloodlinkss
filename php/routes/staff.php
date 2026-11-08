<?php
/**
 * php/routes/staff.php
 * --------------------
 * Operational modules for staff / admin / super admin:
 *   dropdowns, donors, patients, donations, inventory, requests, locations.
 *
 * Business rules enforced here:
 *  - unverified donations NEVER reach inventory
 *  - approving a request deducts blood FIFO and notifies the patient
 *  - inventory can never go negative
 *  - "delete" for donors/patients/locations is a status toggle (soft delete)
 *  - every change is written to activity_logs
 */
declare(strict_types=1);

$user = requireRoles(['staff', 'admin', 'superadmin']);
$userId = (int) $user['id'];
$action = strtolower($segments[1] ?? '');
$idPart = (string) ($segments[2] ?? '');
$id = ctype_digit($idPart) ? (int) $idPart : null;
$sub = strtolower($segments[3] ?? '');
$database = db();

$userHospId = (int) ($user['hospital_id'] ?? 0);
$userHospName = '';
if ($userHospId > 0) {
    $hnStmt = $database->prepare("SELECT location_name FROM blood_locations WHERE id = ?");
    $hnStmt->execute([$userHospId]);
    $userHospName = (string) ($hnStmt->fetchColumn() ?: '');
}

/* ============================================== shared dropdowns */
if ($action === 'dropdowns' && $method === 'GET') {
    $locs = [];
    if ($user['role'] === 'staff' && !empty($user['hospital_id'])) {
        $locStmt = $database->prepare(
            "SELECT id, location_name FROM blood_locations WHERE id = ? AND available_status='Active'"
        );
        $locStmt->execute([(int) $user['hospital_id']]);
        $locs = $locStmt->fetchAll();
    } else {
        $locs = $database->query(
            "SELECT id, location_name FROM blood_locations
              WHERE available_status='Active' ORDER BY location_name"
        )->fetchAll();
    }

    jsonResponse([
        'ok' => true,
        'donors' => $database->query(
            "SELECT id, donor_code, first_name, last_name, blood_type FROM donors
              WHERE status='active' ORDER BY last_name, first_name"
        )->fetchAll(),
        'patients' => $database->query(
            "SELECT id, patient_code, first_name, last_name, blood_type_needed FROM patients
              WHERE status='active' ORDER BY last_name, first_name"
        )->fetchAll(),
        'locations' => $locs,
    ]);
}

/* ========================================================= DONORS */
if ($action === 'donors') {
    requireModule($user, 'donors');

    if ($method === 'GET' && $id === null) {
        $search = trim((string) ($_GET['search'] ?? ''));
        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where = '(d.first_name LIKE ? OR d.last_name LIKE ? OR d.donor_code LIKE ? OR d.blood_type LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like, $like];
        }
        $rows = $database->prepare(
            "SELECT d.*, u.status AS user_status, u.username,
                    (SELECT COUNT(*) FROM donations dn WHERE dn.donor_id = d.id) AS donation_count,
                    (SELECT COALESCE(SUM(dn.blood_units),0) FROM donations dn
                      WHERE dn.donor_id = d.id AND dn.status='Approved') AS verified_units
               FROM donors d JOIN users u ON u.id = d.user_id
              WHERE $where
              ORDER BY d.last_name, d.first_name"
        );
        $rows->execute($params);
        jsonResponse(['ok' => true, 'donors' => $rows->fetchAll()]);
    }

    if ($method === 'POST' || ($method === 'PUT' && $id !== null && $sub === '')) {
        $missing = requireFields($body, ['first_name', 'last_name', 'age', 'sex',
            'contact_number', 'address', 'blood_type', 'email']);
        if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);
        if (!validBloodType((string) $body['blood_type'])) jsonResponse(['ok' => false, 'message' => 'Please select a valid blood type.'], 400);
        if (!validEmail(trim((string) $body['email']))) jsonResponse(['ok' => false, 'message' => 'Please enter a valid email address.'], 400);
        if (!validAge($body['age'])) jsonResponse(['ok' => false, 'message' => 'Please enter a valid age (1-119).'], 400);
        if (!validPhone((string) $body['contact_number'])) jsonResponse(['ok' => false, 'message' => 'Please enter a valid contact number.'], 400);
        if (!in_array((string) ($body['sex'] ?? ''), ['Male', 'Female'], true)) jsonResponse(['ok' => false, 'message' => 'Please select a valid sex.'], 400);

        $middle = trim((string) ($body['middle_name'] ?? ''));
        $values = [trim((string) $body['first_name']), $middle !== '' ? $middle : null,
            trim((string) $body['last_name']), (int) $body['age'], (string) $body['sex'],
            trim((string) $body['contact_number']), trim((string) $body['address']),
            (string) $body['blood_type'], trim((string) $body['email'])];
        // PLACEHOLDER-DONOR-WRITE
        if ($method === 'PUT') {
            $existing = $database->prepare('SELECT * FROM donors WHERE id = ?');
            $existing->execute([$id]);
            $donor = $existing->fetch();
            if (!$donor) jsonResponse(['ok' => false, 'message' => 'Donor not found.'], 404);

            $database->prepare(
                'UPDATE donors SET first_name=?, middle_name=?, last_name=?, age=?, sex=?,
                        contact_number=?, address=?, blood_type=?, email=? WHERE id=?'
            )->execute([...$values, $donor['id']]);
            logAction($userId, 'Donor Updated', 'Updated ' . $donor['donor_code']);
            jsonResponse(['ok' => true, 'message' => 'Donor updated.']);
        }

        // Create: a login account is generated so the donor can sign in.
        try {
            $database->beginTransaction();
            $code = nextCode('donors', 'donor_code', 'DON');
            $username = strtolower(str_replace('-', '', $code));
            $password = 'Blood@' . $code;
            $database->prepare("INSERT INTO users(username, password_hash, role, status) VALUES (?, ?, 'donor', 'active')")
                ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $newUserId = (int) $database->lastInsertId();
            $database->prepare(
                'INSERT INTO donors(user_id, donor_code, first_name, middle_name, last_name, age, sex,
                        contact_number, address, blood_type, email)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$newUserId, $code, ...$values]);
            $database->commit();
        } catch (Throwable $error) {
            if ($database->inTransaction()) $database->rollBack();
            appLog('staff/donors create', $error);
            jsonResponse(['ok' => false, 'message' => 'Could not save the donor. Please check the details and try again.'], 500);
        }
        logAction($userId, 'Donor Added', $code . ' (default login ' . $username . ')');
        jsonResponse(['ok' => true, 'message' => "Donor saved. Login: $username / $password",
            'donor' => ['code' => $code, 'username' => $username, 'password' => $password]]);
    }

    if ($method === 'PUT' && $id !== null && $sub === 'status') {
        $existing = $database->prepare('SELECT * FROM donors WHERE id = ?');
        $existing->execute([$id]);
        $donor = $existing->fetch();
        if (!$donor) jsonResponse(['ok' => false, 'message' => 'Donor not found.'], 404);

        $newStatus = $donor['status'] === 'active' ? 'inactive' : 'active';
        $database->beginTransaction();
        $database->prepare('UPDATE donors SET status = ? WHERE id = ?')->execute([$newStatus, $donor['id']]);
        $database->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $donor['user_id']]);
        $database->commit();
        logAction($userId, 'Donor Status', $donor['donor_code'] . ' set to ' . $newStatus);
        jsonResponse(['ok' => true, 'message' => 'Donor ' . ($newStatus === 'active' ? 'activated' : 'deactivated') . '.']);
    }
}

/* ======================================================= PATIENTS */
if ($action === 'patients') {
    requireModule($user, 'patients');

    if ($method === 'GET' && $id === null) {
        $search = trim((string) ($_GET['search'] ?? ''));
        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where = '(p.first_name LIKE ? OR p.last_name LIKE ? OR p.patient_code LIKE ? OR p.blood_type_needed LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like, $like];
        }
        $rows = $database->prepare(
            "SELECT p.*, u.status AS user_status, u.username,
                    (SELECT COUNT(*) FROM blood_requests r WHERE r.patient_id = p.id) AS request_count
               FROM patients p JOIN users u ON u.id = p.user_id
              WHERE $where
              ORDER BY p.last_name, p.first_name"
        );
        $rows->execute($params);
        jsonResponse(['ok' => true, 'patients' => $rows->fetchAll()]);
    }

    if ($method === 'POST' || ($method === 'PUT' && $id !== null && $sub === '')) {
        $missing = requireFields($body, ['first_name', 'last_name', 'age', 'sex',
            'contact_number', 'address', 'blood_type_needed', 'hospital']);
        if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);
        if (!validBloodType((string) $body['blood_type_needed'])) jsonResponse(['ok' => false, 'message' => 'Please select a valid blood type.'], 400);
        if (!validAge($body['age'])) jsonResponse(['ok' => false, 'message' => 'Please enter a valid age (1-119).'], 400);
        if (!validPhone((string) $body['contact_number'])) jsonResponse(['ok' => false, 'message' => 'Please enter a valid contact number.'], 400);
        if (!in_array((string) ($body['sex'] ?? ''), ['Male', 'Female'], true)) jsonResponse(['ok' => false, 'message' => 'Please select a valid sex.'], 400);

        $middle = trim((string) ($body['middle_name'] ?? ''));
        $notes = trim((string) ($body['medical_notes'] ?? ''));
        $values = [trim((string) $body['first_name']), $middle !== '' ? $middle : null,
            trim((string) $body['last_name']), (int) $body['age'], (string) $body['sex'],
            trim((string) $body['contact_number']), trim((string) $body['address']),
            (string) $body['blood_type_needed'], trim((string) $body['hospital']),
            $notes !== '' ? $notes : null];
        // PLACEHOLDER-PATIENT-WRITE
        if ($method === 'PUT') {
            $existing = $database->prepare('SELECT * FROM patients WHERE id = ?');
            $existing->execute([$id]);
            $patient = $existing->fetch();
            if (!$patient) jsonResponse(['ok' => false, 'message' => 'Patient not found.'], 404);

            $database->prepare(
                'UPDATE patients SET first_name=?, middle_name=?, last_name=?, age=?, sex=?,
                        contact_number=?, address=?, blood_type_needed=?, hospital=?, medical_notes=?
                  WHERE id=?'
            )->execute([...$values, $patient['id']]);
            logAction($userId, 'Patient Updated', 'Updated ' . $patient['patient_code']);
            jsonResponse(['ok' => true, 'message' => 'Patient updated.']);
        }

        try {
            $database->beginTransaction();
            $code = nextCode('patients', 'patient_code', 'PAT');
            $username = strtolower(str_replace('-', '', $code));
            $password = 'Blood@' . $code;
            $database->prepare("INSERT INTO users(username, password_hash, role, status) VALUES (?, ?, 'patient', 'active')")
                ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $newUserId = (int) $database->lastInsertId();
            $database->prepare(
                'INSERT INTO patients(user_id, patient_code, first_name, middle_name, last_name, age, sex,
                        contact_number, address, blood_type_needed, hospital, medical_notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$newUserId, $code, ...$values]);
            $database->commit();
        } catch (Throwable $error) {
            if ($database->inTransaction()) $database->rollBack();
            appLog('staff/patients create', $error);
            jsonResponse(['ok' => false, 'message' => 'Could not save the patient. Please check the details and try again.'], 500);
        }
        logAction($userId, 'Patient Added', $code . ' (default login ' . $username . ')');
        jsonResponse(['ok' => true, 'message' => "Patient saved. Login: $username / $password",
            'patient' => ['code' => $code, 'username' => $username, 'password' => $password]]);
    }

    if ($method === 'PUT' && $id !== null && $sub === 'status') {
        $existing = $database->prepare('SELECT * FROM patients WHERE id = ?');
        $existing->execute([$id]);
        $patient = $existing->fetch();
        if (!$patient) jsonResponse(['ok' => false, 'message' => 'Patient not found.'], 404);

        $newStatus = $patient['status'] === 'active' ? 'inactive' : 'active';
        $database->beginTransaction();
        $database->prepare('UPDATE patients SET status = ? WHERE id = ?')->execute([$newStatus, $patient['id']]);
        $database->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $patient['user_id']]);
        $database->commit();
        logAction($userId, 'Patient Status', $patient['patient_code'] . ' set to ' . $newStatus);
        jsonResponse(['ok' => true, 'message' => 'Patient ' . ($newStatus === 'active' ? 'activated' : 'deactivated') . '.']);
    }
}

/* ====================================================== DONATIONS */
if ($action === 'donations') {
    requireModule($user, 'donations');

    if ($method === 'GET' && $id === null) {
        $clauses = [];
        $params = [];
        $status = trim((string) ($_GET['status'] ?? ''));
        if ($status !== '' && $status !== 'ALL') {
            if ($status === 'Verified') {
                $clauses[] = "dn.status IN ('Approved', 'Verified')";
            } else if (!in_array($status, ['Pending', 'Approved', 'Rejected', 'Verified', 'Completed', 'Cancelled'], true)) {
                jsonResponse(['ok' => false, 'message' => 'Invalid donation status filter.'], 400);
            } else {
                $clauses[] = 'dn.status = ?';
                $params[] = $status;
            }
        }
        $donorFilter = trim((string) ($_GET['donor_id'] ?? ''));
        if ($donorFilter !== '') {
            if (!ctype_digit($donorFilter)) jsonResponse(['ok' => false, 'message' => 'Invalid donor filter.'], 400);
            $clauses[] = 'dn.donor_id = ?';
            $params[] = (int) $donorFilter;
        }

        if ($user['role'] !== 'superadmin' && $userHospId > 0) {
            $clauses[] = "(dn.target_hospital_id = ? OR (dn.target_hospital_id IS NULL AND (dn.donation_location = ? OR dn.donation_location = 'ALL_HOSPITALS' OR dn.donation_location IS NULL OR dn.donation_location = '')))";
            $params[] = $userHospId;
            $params[] = $userHospName;
        }
        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        $rows = $database->prepare(
            "SELECT dn.*, d.donor_code, d.first_name, d.last_name,
                    d.blood_type AS donor_blood_type, u.username AS recorded_by_user
               FROM donations dn
               JOIN donors d ON d.id = dn.donor_id
               LEFT JOIN users u ON u.id = dn.recorded_by
             $where
              ORDER BY dn.donation_date DESC, dn.id DESC"
        );
        $rows->execute($params);
        jsonResponse(['ok' => true, 'donations' => $rows->fetchAll()]);
    }
    // PLACEHOLDER-DONATIONS
    /* Record a donation. It starts as Pending and is NOT in inventory yet. */
    if ($method === 'POST') {
        $missing = requireFields($body, ['donor_id', 'blood_type', 'donation_date', 'blood_units', 'donation_location']);
        if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);
        if (!validBloodType((string) $body['blood_type'])) jsonResponse(['ok' => false, 'message' => 'Please select a valid blood type.'], 400);
        if (!validPositiveInt($body['blood_units'])) jsonResponse(['ok' => false, 'message' => 'Blood units must be a positive whole number.'], 400);
        if (!validDate($body['donation_date'])) jsonResponse(['ok' => false, 'message' => 'Please enter a valid donation date.'], 400);
        if (substr((string) $body['donation_date'], 0, 10) > date('Y-m-d')) {
            jsonResponse(['ok' => false, 'message' => 'The donation date cannot be in the future.'], 400);
        }

        $donorRow = $database->prepare("SELECT * FROM donors WHERE id = ? AND status='active'");
        $donorRow->execute([(int) $body['donor_id']]);
        $donor = $donorRow->fetch();
        if (!$donor) jsonResponse(['ok' => false, 'message' => 'That donor was not found or is inactive.'], 404);

        $database->prepare(
            "INSERT INTO donations(donor_id, blood_type, donation_date, blood_units, donation_location, status, recorded_by)
             VALUES (?, ?, ?, ?, ?, 'Pending', ?)"
        )->execute([$donor['id'], (string) $body['blood_type'], substr((string) $body['donation_date'], 0, 10),
            (int) $body['blood_units'], mb_substr(trim((string) $body['donation_location']), 0, 255), $userId]);

        logAction($userId, 'Donation Recorded',
            (int) $body['blood_units'] . ' unit(s) of ' . $body['blood_type'] . ' from ' . $donor['donor_code']);
        jsonResponse(['ok' => true, 'message' => 'Donation recorded. Approve it to add the blood to inventory.']);
    }

    /* Verify or Approve -> the blood becomes available inventory at a chosen location. */
    if ($method === 'PUT' && $id !== null && ($sub === 'verify' || $sub === 'approve')) {
        $donationRow = $database->prepare('SELECT * FROM donations WHERE id = ?');
        $donationRow->execute([$id]);
        $donation = $donationRow->fetch();
        if (!$donation) jsonResponse(['ok' => false, 'message' => 'Donation not found.'], 404);
        if ($donation['status'] !== 'Pending') {
            jsonResponse(['ok' => false, 'message' => 'Only pending donations can be verified.'], 400);
        }

        $locationId = (int) ($body['location_id'] ?? 0);
        if ($locationId <= 0 && $userHospId > 0) {
            $locationId = $userHospId;
        }
        $locationRow = $database->prepare("SELECT * FROM blood_locations WHERE id = ? AND available_status='Active'");
        $locationRow->execute([$locationId]);
        $location = $locationRow->fetch();
        if (!$location) {
            $location = $database->query("SELECT * FROM blood_locations WHERE available_status='Active' ORDER BY id ASC LIMIT 1")->fetch();
        }
        if (!$location) jsonResponse(['ok' => false, 'message' => 'Please choose the blood location receiving these units.'], 400);

        $expiration = trim((string) ($body['expiration_date'] ?? ''));
        if ($expiration === '') $expiration = date('Y-m-d', strtotime('+35 days'));
        if (!validDate($expiration)) jsonResponse(['ok' => false, 'message' => 'Please enter a valid expiration date.'], 400);
        $expiration = substr($expiration, 0, 10);
        if ($expiration < date('Y-m-d')) {
            jsonResponse(['ok' => false, 'message' => 'The expiration date must be today or later.'], 400);
        }
        // Ensure schema allows Verified/Approved gracefully
        try {
            $database->exec("ALTER TABLE donations MODIFY COLUMN status ENUM('Pending','Verified','Rejected','Approved','Completed','Scheduled','Cancelled') NOT NULL DEFAULT 'Pending'");
        } catch (Throwable $ignored) {}

        $hospName = $location['location_name'];
        try {
            $database->beginTransaction();
            $database->prepare("UPDATE donations SET status='Verified', approved_by_hospital = ? WHERE id = ? AND status='Pending'")
                ->execute([$hospName, $donation['id']]);

            // Auto-cancel sibling pending donations across other hospitals
            $siblingNote = 'Fulfilled & Verified by ' . $hospName;
            if (!empty($donation['group_id'])) {
                $database->prepare(
                    "UPDATE donations 
                     SET status = 'Cancelled', approved_by_hospital = ?, notes = CONCAT(IFNULL(notes,''), ' [', ?, ']') 
                     WHERE group_id = ? AND id != ? AND status IN ('Pending', 'Scheduled')"
                )->execute([$hospName, $siblingNote, $donation['group_id'], $donation['id']]);
            } else {
                $database->prepare(
                    "UPDATE donations 
                     SET status = 'Cancelled', approved_by_hospital = ?, notes = CONCAT(IFNULL(notes,''), ' [Fulfilled & Verified by ', ?, ']') 
                     WHERE donor_id = ? AND donation_date = ? AND id != ? AND status IN ('Pending', 'Scheduled')"
                )->execute([$hospName, $hospName, $donation['donor_id'], $donation['donation_date'], $donation['id']]);
            }

            // Merge into an existing open batch with the same expiry when possible.
            $batchRow = $database->prepare(
                "SELECT id FROM blood_inventory
                  WHERE blood_type = ? AND location_id = ? AND status = 'Available' AND expiration_date = ?
                  ORDER BY id DESC LIMIT 1"
            );
            $batchRow->execute([$donation['blood_type'], $location['id'], $expiration]);
            $batchId = $batchRow->fetchColumn();
            if ($batchId) {
                $database->prepare('UPDATE blood_inventory SET blood_units = blood_units + ? WHERE id = ?')
                    ->execute([(int) $donation['blood_units'], $batchId]);
            } else {
                $database->prepare(
                    "INSERT INTO blood_inventory(blood_type, blood_units, location_id, expiration_date, status)
                     VALUES (?, ?, ?, ?, 'Available')"
                )->execute([$donation['blood_type'], (int) $donation['blood_units'], $location['id'], $expiration]);
            }
            $database->commit();
        } catch (Throwable $error) {
            if ($database->inTransaction()) $database->rollBack();
            appLog('staff/donations verify', $error);
            jsonResponse(['ok' => false, 'message' => 'Could not approve the donation: ' . $error->getMessage()], 500);
        }

        $donorRow = $database->prepare('SELECT user_id FROM donors WHERE id = ?');
        $donorRow->execute([$donation['donor_id']]);
        notify((int) $donorRow->fetchColumn(), 'Donation Verified',
            'Your donation of ' . $donation['blood_units'] . ' unit(s) of ' . $donation['blood_type']
            . ' was verified. Thank you for saving lives!');
        logAction($userId, 'Donation Verified', $donation['blood_units'] . ' unit(s) of '
            . $donation['blood_type'] . ' added to ' . $location['location_name']);
        jsonResponse(['ok' => true, 'message' => 'Donation accepted and verified. Blood added to inventory.']);
    }

    /* Reject a pending donation (nothing enters inventory). */
    if ($method === 'PUT' && $id !== null && $sub === 'reject') {
        $donationRow = $database->prepare('SELECT * FROM donations WHERE id = ?');
        $donationRow->execute([$id]);
        $donation = $donationRow->fetch();
        if (!$donation) jsonResponse(['ok' => false, 'message' => 'Donation not found.'], 404);
        if ($donation['status'] !== 'Pending') {
            jsonResponse(['ok' => false, 'message' => 'Only pending donations can be rejected.'], 400);
        }

        $database->prepare("UPDATE donations SET status='Rejected' WHERE id = ?")->execute([$donation['id']]);
        $donorRow = $database->prepare('SELECT user_id FROM donors WHERE id = ?');
        $donorRow->execute([$donation['donor_id']]);
        notify((int) $donorRow->fetchColumn(), 'Donation Rejected',
            'Your donation of ' . $donation['blood_units'] . ' unit(s) of ' . $donation['blood_type']
            . ' on ' . $donation['donation_date'] . ' was not approved.');
        logAction($userId, 'Donation Rejected', 'Rejected donation #' . $donation['id']);
        jsonResponse(['ok' => true, 'message' => 'Donation rejected.']);
    }

    /* Complete a verified donation -> mark status as 'Completed' and start cooldown countdown */
    if ($method === 'PUT' && $id !== null && $sub === 'complete') {
        $donationRow = $database->prepare('SELECT * FROM donations WHERE id = ?');
        $donationRow->execute([$id]);
        $donation = $donationRow->fetch();
        if (!$donation) jsonResponse(['ok' => false, 'message' => 'Donation not found.'], 404);
        if ($donation['status'] !== 'Verified' && $donation['status'] !== 'Approved') {
            jsonResponse(['ok' => false, 'message' => 'Only verified donations can be marked as completed.'], 400);
        }

        try {
            $database->exec("ALTER TABLE donations MODIFY COLUMN status ENUM('Pending','Verified','Rejected','Approved','Completed','Scheduled','Cancelled') NOT NULL DEFAULT 'Pending'");
        } catch (Throwable $ignored) {}

        $database->prepare("UPDATE donations SET status='Completed' WHERE id = ?")->execute([$donation['id']]);

        $donorRow = $database->prepare('SELECT user_id, first_name FROM donors WHERE id = ?');
        $donorRow->execute([$donation['donor_id']]);
        $donorInfo = $donorRow->fetch();
        if ($donorInfo) {
            notify((int) $donorInfo['user_id'], 'Donation Completed 🎉',
                'Your blood donation of ' . $donation['blood_units'] . ' unit(s) of ' . $donation['blood_type']
                . ' has been successfully completed! Thank you for saving lives. Your 56-day recovery cooldown is now active.');
        }

        logAction($userId, 'Donation Completed', 'Completed donation #' . $donation['id'] . ' for donor #' . $donation['donor_id']);
        jsonResponse(['ok' => true, 'message' => 'Donation completed successfully! Donor recovery period has started.']);
    }
}

/* ====================================================== INVENTORY */
if ($action === 'inventory') {
    requireModule($user, 'inventory');
    $statuses = ['Available', 'Reserved', 'Used', 'Expired', 'Unavailable'];

    if ($method === 'GET' && $id === null) {
        sweepExpired();
        $clauses = [];
        $params = [];
        $type = trim((string) ($_GET['blood_type'] ?? ''));
        if ($type !== '' && $type !== 'ALL') {
            if (!validBloodType($type)) jsonResponse(['ok' => false, 'message' => 'Invalid blood type filter.'], 400);
            $clauses[] = 'i.blood_type = ?';
            $params[] = $type;
        }

        // Staff is strictly locked to their assigned hospital location
        if ($user['role'] === 'staff') {
            $clauses[] = 'i.location_id = ?';
            $params[] = (int) ($user['hospital_id'] ?? 0);
        } elseif ($user['role'] === 'admin' && !empty($user['hospital_id']) && empty($_GET['location_id'])) {
            $clauses[] = 'i.location_id = ?';
            $params[] = (int) $user['hospital_id'];
        } else {
            $locationFilter = trim((string) ($_GET['location_id'] ?? ''));
            if ($locationFilter !== '') {
                if (!ctype_digit($locationFilter)) jsonResponse(['ok' => false, 'message' => 'Invalid location filter.'], 400);
                $clauses[] = 'i.location_id = ?';
                $params[] = (int) $locationFilter;
            }
        }

        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        if ($statusFilter !== '' && $statusFilter !== 'ALL') {
            if (!in_array($statusFilter, $statuses, true)) jsonResponse(['ok' => false, 'message' => 'Invalid status filter.'], 400);
            $clauses[] = 'i.status = ?';
            $params[] = $statusFilter;
        }
        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        $rows = $database->prepare(
            "SELECT i.*, l.location_name FROM blood_inventory i
               JOIN blood_locations l ON l.id = i.location_id
             $where ORDER BY i.expiration_date ASC, i.id ASC"
        );
        $rows->execute($params);
        jsonResponse(['ok' => true, 'inventory' => $rows->fetchAll()]);
    }
    /* ------------------------------------------- add a batch by hand */
    if ($method === 'POST' && $id === null) {
        $required = ['blood_type', 'blood_units', 'expiration_date'];
        if ($user['role'] === 'superadmin') $required[] = 'location_id';
        $missing = requireFields($body, $required);
        if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);
        if (!validBloodType((string) $body['blood_type'])) {
            jsonResponse(['ok' => false, 'message' => 'Please select a valid blood type.'], 400);
        }
        if (!validPositiveInt($body['blood_units'])) {
            jsonResponse(['ok' => false, 'message' => 'Blood units must be a positive whole number.'], 400);
        }
        $expiration = mb_substr(trim((string) $body['expiration_date']), 0, 10);
        if (!validDate($expiration)) {
            jsonResponse(['ok' => false, 'message' => 'Please enter a valid expiration date.'], 400);
        }
        if ($expiration < date('Y-m-d')) {
            jsonResponse(['ok' => false, 'message' => 'Expiration date cannot be in the past.'], 400);
        }
        $status = in_array((string) ($body['status'] ?? 'Available'), $statuses, true)
            ? (string) ($body['status'] ?? 'Available') : 'Available';

        // Lock to assigned hospital for staff
        $targetLocationId = ($user['role'] === 'staff' || ($user['role'] === 'admin' && !empty($user['hospital_id'])))
            ? (int) ($user['hospital_id'] ?? 0)
            : (int) ($body['location_id'] ?? 0);

        $locationStatement = $database->prepare(
            "SELECT * FROM blood_locations WHERE id = ? AND available_status = 'Active'"
        );
        $locationStatement->execute([$targetLocationId]);
        $location = $locationStatement->fetch();
        if (!$location) {
            jsonResponse(['ok' => false, 'message' => 'Your hospital facility is inactive or not found.'], 400);
        }

        $comp = in_array((string)($body['component_type'] ?? ''), ['Whole Blood', 'Packed Red Blood Cells (PRBC)', 'Platelets', 'Fresh Frozen Plasma (FFP)', 'Cryoprecipitate'], true)
            ? (string)$body['component_type'] : 'Whole Blood';

        $database->prepare(
            'INSERT INTO blood_inventory(blood_type, component_type, blood_units, location_id, expiration_date, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([(string) $body['blood_type'], $comp, (int) $body['blood_units'],
            (int) $location['id'], $expiration, $status]);

        logAction($userId, 'Inventory Added',
            (int) $body['blood_units'] . ' unit(s) of ' . $body['blood_type'] . ' at ' . $location['location_name']);
        jsonResponse(['ok' => true, 'message' => 'Blood batch added to inventory.']);
    }
    /* ------------------------------- update units / status / expiry */
    if ($method === 'PUT' && $id !== null && $sub === '') {
        $batchStatement = $database->prepare(
            'SELECT i.*, l.location_name FROM blood_inventory i
               JOIN blood_locations l ON l.id = i.location_id WHERE i.id = ?'
        );
        $batchStatement->execute([$id]);
        $batch = $batchStatement->fetch();
        if (!$batch) jsonResponse(['ok' => false, 'message' => 'Inventory batch not found.'], 404);

        if ($user['role'] === 'staff' && (int) $batch['location_id'] !== (int) ($user['hospital_id'] ?? 0)) {
            jsonResponse(['ok' => false, 'message' => 'Access denied. You can only manage inventory for your assigned hospital.'], 403);
        }

        $units = (int) $batch['blood_units'];
        if (array_key_exists('blood_units', $body)) {
            // 0 is allowed here (an emptied batch), negatives never are.
            if (!is_numeric($body['blood_units']) || (string) (int) $body['blood_units'] !== (string) $body['blood_units']
                || (int) $body['blood_units'] < 0) {
                jsonResponse(['ok' => false, 'message' => 'Blood units must be 0 or a positive whole number.'], 400);
            }
            $units = (int) $body['blood_units'];
        }
        $status = (string) $batch['status'];
        if (array_key_exists('status', $body)) {
            if (!in_array((string) $body['status'], $statuses, true)) {
                jsonResponse(['ok' => false, 'message' => 'Invalid inventory status.'], 400);
            }
            $status = (string) $body['status'];
        }
        $expiration = (string) $batch['expiration_date'];
        if (array_key_exists('expiration_date', $body)) {
            $expiration = mb_substr(trim((string) $body['expiration_date']), 0, 10);
            if (!validDate($expiration)) {
                jsonResponse(['ok' => false, 'message' => 'Please enter a valid expiration date.'], 400);
            }
        }

        $database->prepare(
            'UPDATE blood_inventory SET blood_units = ?, status = ?, expiration_date = ? WHERE id = ?'
        )->execute([$units, $status, $expiration, $batch['id']]);

        logAction($userId, 'Inventory Updated',
            $batch['blood_type'] . ' @ ' . $batch['location_name'] . ' -> ' . $units . ' unit(s), ' . $status);
        jsonResponse(['ok' => true, 'message' => 'Inventory updated.']);
    }
    /* ------- remove a batch (only after it is marked non-available) */
    if ($method === 'DELETE' && $id !== null) {
        $batchStatement = $database->prepare(
            'SELECT i.*, l.location_name FROM blood_inventory i
               JOIN blood_locations l ON l.id = i.location_id WHERE i.id = ?'
        );
        $batchStatement->execute([$id]);
        $batch = $batchStatement->fetch();
        if (!$batch) jsonResponse(['ok' => false, 'message' => 'Inventory batch not found.'], 404);

        if ($user['role'] === 'staff' && (int) $batch['location_id'] !== (int) ($user['hospital_id'] ?? 0)) {
            jsonResponse(['ok' => false, 'message' => 'Access denied. You can only manage inventory for your assigned hospital.'], 403);
        }

        if (!in_array((string) $batch['status'], ['Expired', 'Used', 'Unavailable'], true)) {
            jsonResponse(['ok' => false,
                'message' => 'Only Expired, Used or Unavailable batches can be removed. Mark the batch status first.'], 400);
        }
        $database->prepare('DELETE FROM blood_inventory WHERE id = ?')->execute([$batch['id']]);
        logAction($userId, 'Inventory Removed',
            'Removed ' . $batch['blood_type'] . ' batch @ ' . $batch['location_name']);
        jsonResponse(['ok' => true, 'message' => 'Batch removed from inventory.']);
    }
}

/* ================================================ BLOOD REQUESTS */
if ($action === 'requests') {
    requireModule($user, 'requests');

    if ($method === 'GET' && $id === null) {
        $params = [];
        $clauses = [];
        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        $requestStatuses = ['Pending', 'Approved', 'Rejected', 'Processing', 'Completed', 'Cancelled'];
        if ($statusFilter !== '' && $statusFilter !== 'ALL') {
            if (!in_array($statusFilter, $requestStatuses, true)) {
                jsonResponse(['ok' => false, 'message' => 'Invalid status filter.'], 400);
            }
            $clauses[] = 'r.status = ?';
            $params[] = $statusFilter;
        }

        if ($user['role'] !== 'superadmin' && $userHospId > 0) {
            $clauses[] = "(r.target_hospital_id = ? OR (r.target_hospital_id IS NULL AND (r.location = ? OR r.location = 'ALL_HOSPITALS' OR r.location IS NULL OR r.location = '')))";
            $params[] = $userHospId;
            $params[] = $userHospName;
        }

        $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
        $rows = $database->prepare(
            "SELECT r.*, p.patient_code, p.first_name, p.last_name, p.hospital,
                    p.age AS patient_age, p.sex AS patient_sex, p.medical_notes, p.address AS patient_address,
                    p.contact_number AS patient_contact, u.username AS processed_by_user
               FROM blood_requests r
               JOIN patients p ON p.id = r.patient_id
               LEFT JOIN users u ON u.id = r.processed_by
             $where
              ORDER BY CASE r.priority WHEN 'Critical' THEN 0 WHEN 'Urgent' THEN 1 ELSE 2 END,
                       r.created_at DESC, r.id DESC"
        );
        $rows->execute($params);
        jsonResponse(['ok' => true, 'requests' => $rows->fetchAll()]);
    }
    /* --------------------------------- Section F Review & Verification */
    if ($method === 'PUT' && $id !== null && $sub === 'verify') {
        $reqStmt = $database->prepare('SELECT r.*, p.user_id AS patient_uid FROM blood_requests r LEFT JOIN patients p ON p.id = r.patient_id WHERE r.id = ?');
        $reqStmt->execute([$id]);
        $request = $reqStmt->fetch();
        if (!$request) jsonResponse(['ok' => false, 'message' => 'Request not found.'], 404);

        $docStatus = (string)($body['document_status'] ?? 'Verified');
        if (!in_array($docStatus, ['Pending', 'Needs Correction', 'Verified', 'Rejected'], true)) {
            jsonResponse(['ok' => false, 'message' => 'Invalid document status.'], 400);
        }

        $unitsConfirmed = !empty($body['units_confirmed']) && is_numeric($body['units_confirmed']) ? (int)$body['units_confirmed'] : (int)$request['units_needed'];
        $compConfirmed = trim((string)($body['component_confirmed'] ?? $request['component_type']));
        $compatNotes = trim((string)($body['compatibility_notes'] ?? $request['compatibility_notes']));
        $corrNotes = trim((string)($body['correction_notes'] ?? ''));
        $staffSig = trim((string)($body['staff_signature_id'] ?? ($user['username'] . ' (ID #' . $userId . ')')));

        // Map request overall status based on document review
        $newOverallStatus = $request['status'];
        if ($docStatus === 'Verified') {
            $newOverallStatus = 'Verified';
        } elseif ($docStatus === 'Needs Correction') {
            $newOverallStatus = 'Needs Correction';
        } elseif ($docStatus === 'Rejected') {
            $newOverallStatus = 'Rejected';
        }

        $database->prepare(
            "UPDATE blood_requests SET
                document_status = ?,
                status = ?,
                reviewed_by = ?,
                reviewed_at = NOW(),
                units_confirmed = ?,
                component_confirmed = ?,
                compatibility_notes = ?,
                correction_notes = ?,
                staff_signature_id = ?,
                rejection_reason = CASE WHEN ? = 'Rejected' THEN ? ELSE rejection_reason END
             WHERE id = ?"
        )->execute([
            $docStatus,
            $newOverallStatus,
            $userId,
            $unitsConfirmed,
            $compConfirmed,
            $compatNotes,
            $corrNotes,
            $staffSig,
            $docStatus,
            $corrNotes,
            $request['id']
        ]);

        $patientUserId = (int)($request['patient_uid'] ?? 0);
        if ($patientUserId > 0) {
            if ($docStatus === 'Verified') {
                notify($patientUserId, 'Blood Request Form Verified',
                    'Your blood request (Ref #' . ($request['request_ref_no'] ?: $request['id']) . ') has been verified by the hospital blood bank.');
            } elseif ($docStatus === 'Needs Correction') {
                notify($patientUserId, 'Action Required: Request Needs Correction',
                    'The blood bank staff requested corrections on your request: ' . ($corrNotes ?: 'Please verify details with your attending physician.'));
            } elseif ($docStatus === 'Rejected') {
                notify($patientUserId, 'Blood Request Not Approved',
                    'Your blood request form was rejected: ' . ($corrNotes ?: 'Document criteria not met.'));
            }
        }

        logAction($userId, 'Request Verification', 'Updated document verification status for Request #' . $request['id'] . ' to ' . $docStatus);

        jsonResponse([
            'ok' => true,
            'message' => 'Document verification updated successfully: ' . $docStatus,
            'document_status' => $docStatus,
            'status' => $newOverallStatus
        ]);
    }

    /* ------------------------------------------- process a request */
    if ($method === 'PUT' && $id !== null && $sub === 'status') {
        $requestStatement = $database->prepare('SELECT * FROM blood_requests WHERE id = ?');
        $requestStatement->execute([$id]);
        $request = $requestStatement->fetch();
        if (!$request) jsonResponse(['ok' => false, 'message' => 'Blood request not found.'], 404);

        $newStatus = (string) ($body['status'] ?? '');
        $patientStatement = $database->prepare(
            'SELECT p.*, u.id AS uid FROM patients p JOIN users u ON u.id = p.user_id WHERE p.id = ?'
        );
        $patientStatement->execute([$request['patient_id']]);
        $patient = $patientStatement->fetch();
        $patientUserId = $patient ? (int) $patient['uid'] : null;
        $patientName = $patient ? $patient['first_name'] . ' ' . $patient['last_name'] : 'the patient';
        $units = (int) $request['units_needed'];
        $type = (string) $request['blood_type'];

        $setStatus = function (string $status) use ($database, $userId, $request): void {
            $database->prepare('UPDATE blood_requests SET status = ?, processed_by = ? WHERE id = ?')
                ->execute([$status, $userId, $request['id']]);
        };

        /* ---- Partially Fulfilled ---- */
        if ($newStatus === 'Partially Fulfilled') {
            $partialUnits = !empty($body['units_confirmed']) && is_numeric($body['units_confirmed']) ? (int)$body['units_confirmed'] : 1;
            if ($partialUnits <= 0 || $partialUnits >= $units) {
                jsonResponse(['ok' => false, 'message' => 'Partial units must be between 1 and ' . max(1, $units - 1) . '.'], 400);
            }
            $locationId = ctype_digit((string) ($body['location_id'] ?? '')) ? (int) $body['location_id'] : (int)($request['target_hospital_id'] ?? 0);
            if ($locationId <= 0) {
                $locationId = (int) $database->query("SELECT id FROM blood_locations WHERE available_status = 'Active' ORDER BY id ASC LIMIT 1")->fetchColumn();
            }
            $database->beginTransaction();
            try {
                [$consumed, $consumeMessage, $taken] = consumeUnits($type, $locationId, $partialUnits, $request['component_type'] ?? null);
                if (!$consumed) {
                    $database->rollBack();
                    jsonResponse(['ok' => false, 'message' => $consumeMessage], 409);
                }
                $database->prepare(
                    "UPDATE blood_requests SET
                        status = 'Partially Fulfilled',
                        final_status = 'Partially Fulfilled',
                        units_confirmed = ?,
                        inventory_deducted = 1,
                        processed_by = ?
                     WHERE id = ?"
                )->execute([$partialUnits, $userId, $request['id']]);
                $database->commit();
            } catch (Throwable $e) {
                if ($database->inTransaction()) $database->rollBack();
                throw $e;
            }
            notify($patientUserId, 'Blood Request Partially Fulfilled',
                "Your blood request has been partially fulfilled with $partialUnits unit(s) of {$request['component_type']} ($type).");
            logAction($userId, 'Request Partially Fulfilled', "Request #{$request['id']} partially fulfilled ($partialUnits/$units units)");
            jsonResponse(['ok' => true, 'message' => "Request marked as Partially Fulfilled ($partialUnits of $units units released)."]);
        }

        /* ---- Approved / Processing ---- */
        if ($newStatus === 'Approved' || $newStatus === 'Processing') {
            if (!in_array((string) $request['status'], ['Pending', 'Processing'], true)) {
                jsonResponse(['ok' => false,
                    'message' => 'Cannot move a request from "' . $request['status'] . '" to "' . $newStatus . '".'], 400);
            }
            if ($newStatus === 'Processing') {
                $setStatus('Processing');
                logAction($userId, 'Request Processing', 'Request #' . $request['id'] . ' is being processed');
                jsonResponse(['ok' => true, 'message' => 'Request marked as processing.']);
            }

            $locationId = ctype_digit((string) ($body['location_id'] ?? '')) ? (int) $body['location_id'] : 0;
            if ($locationId <= 0) {
                $locName = trim((string)($request['location'] ?? $patient['hospital'] ?? ''));
                if ($locName !== '') {
                    $lStmt = $database->prepare("SELECT id FROM blood_locations WHERE location_name = ? OR address LIKE ? LIMIT 1");
                    $lStmt->execute([$locName, '%' . $locName . '%']);
                    $locationId = (int) $lStmt->fetchColumn();
                }
            }
            if ($locationId <= 0) {
                $locationId = (int) $database->query("SELECT id FROM blood_locations WHERE available_status = 'Active' ORDER BY id ASC LIMIT 1")->fetchColumn();
            }

            $locationStatement = $database->prepare(
                "SELECT * FROM blood_locations WHERE id = ? AND available_status = 'Active'"
            );
            $locationStatement->execute([$locationId]);
            $location = $locationStatement->fetch();
            if (!$location) {
                jsonResponse(['ok' => false,
                    'message' => 'Please choose the blood location to release these units from.'], 400);
            }

            $database->beginTransaction();
            try {
                if (empty($request['inventory_deducted'])) {
                    [$consumed, $consumeMessage] = consumeUnits($type, (int) $location['id'], $units, $request['component_type'] ?? null);
                    if (!$consumed) {
                        $database->rollBack();
                        jsonResponse(['ok' => false, 'message' => $consumeMessage], 409);
                    }
                    $database->prepare('UPDATE blood_requests SET inventory_deducted = 1 WHERE id = ?')->execute([$request['id']]);
                }
                
                $approvingHospName = $location['location_name'];
                $database->prepare('UPDATE blood_requests SET status = "Approved", approved_by_hospital = ?, processed_by = ? WHERE id = ?')
                    ->execute([$approvingHospName, $userId, $request['id']]);

                // RACE RESOLUTION: Auto-cancel sibling requests in other hospitals
                $siblingReason = 'Fulfilled & Approved by ' . $approvingHospName;
                if (!empty($request['group_id'])) {
                    $database->prepare(
                        "UPDATE blood_requests 
                         SET status = 'Cancelled', approved_by_hospital = ?, rejection_reason = ? 
                         WHERE group_id = ? AND id != ? AND status IN ('Pending', 'Processing')"
                    )->execute([$approvingHospName, $siblingReason, $request['group_id'], $request['id']]);
                } else {
                    $database->prepare(
                        "UPDATE blood_requests 
                         SET status = 'Cancelled', approved_by_hospital = ?, rejection_reason = ? 
                         WHERE patient_id = ? AND blood_type = ? AND units_needed = ? AND id != ? AND status IN ('Pending', 'Processing') AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
                    )->execute([$approvingHospName, $siblingReason, $request['patient_id'], $type, $units, $request['id']]);
                }

                $database->commit();
            } catch (Throwable $error) {
                if ($database->inTransaction()) $database->rollBack();
                throw $error;
            }

            notify($patientUserId, 'Blood Request Approved',
                'Your request for ' . $units . ' unit(s) of ' . $type . ' was approved by '
                . $location['location_name'] . '. Blood will be released from ' . $location['location_name'] . '. Other hospitals in your broadcast have been automatically closed.');
            logAction($userId, 'Request Approved',
                'Request #' . $request['id'] . ': ' . $units . ' unit(s) of ' . $type . ' from ' . $location['location_name']);
            jsonResponse(['ok' => true, 'message' => 'Request approved! Blood reserved from ' . $location['location_name'] . ' and sibling hospital requests were auto-closed.']);
        }
        /* ---- Completed ---- */
        if ($newStatus === 'Completed') {
            if (!in_array((string) $request['status'], ['Approved', 'Processing', 'Pending'], true)) {
                jsonResponse(['ok' => false,
                    'message' => 'Only approved, processing or pending requests can be completed.'], 400);
            }

            $locationName = 'hospital inventory';
            $database->beginTransaction();
            try {
                if (empty($request['inventory_deducted'])) {
                    $locationId = ctype_digit((string) ($body['location_id'] ?? '')) ? (int) $body['location_id'] : 0;
                    if ($locationId <= 0) {
                        $locName = trim((string)($request['location'] ?? $patient['hospital'] ?? ''));
                        if ($locName !== '') {
                            $lStmt = $database->prepare("SELECT id, location_name FROM blood_locations WHERE location_name = ? OR address LIKE ? LIMIT 1");
                            $lStmt->execute([$locName, '%' . $locName . '%']);
                            $locRow = $lStmt->fetch();
                            if ($locRow) {
                                $locationId = (int) $locRow['id'];
                                $locationName = $locRow['location_name'];
                            }
                        }
                    }
                    if ($locationId <= 0) {
                        $locRow = $database->query("SELECT id, location_name FROM blood_locations WHERE available_status = 'Active' ORDER BY id ASC LIMIT 1")->fetch();
                        if ($locRow) {
                            $locationId = (int) $locRow['id'];
                            $locationName = $locRow['location_name'];
                        }
                    }

                    if ($locationId > 0) {
                        [$consumed, $consumeMessage] = consumeUnits($type, $locationId, $units);
                        if (!$consumed) {
                            $database->rollBack();
                            jsonResponse(['ok' => false, 'message' => "Kulang ang blood units sa inventory: $consumeMessage"], 409);
                        }
                        $database->prepare('UPDATE blood_requests SET inventory_deducted = 1 WHERE id = ?')->execute([$request['id']]);
                    }
                }
                $setStatus('Completed');
                if (empty($request['approved_by_hospital'])) {
                    $database->prepare('UPDATE blood_requests SET approved_by_hospital = ? WHERE id = ?')
                        ->execute([$locationName, $request['id']]);
                }
                if (!empty($request['group_id'])) {
                    $database->prepare(
                        "UPDATE blood_requests 
                         SET status = 'Cancelled', approved_by_hospital = ?, rejection_reason = ? 
                         WHERE group_id = ? AND id != ? AND status IN ('Pending', 'Processing')"
                    )->execute([$locationName, 'Fulfilled & Completed by ' . $locationName, $request['group_id'], $request['id']]);
                }
                $database->commit();
            } catch (Throwable $error) {
                if ($database->inTransaction()) $database->rollBack();
                throw $error;
            }

            notify($patientUserId, 'Blood Request Completed',
                'Your request for ' . $units . ' unit(s) of ' . $type . ' has been completed. Get well soon!');
            logAction($userId, 'Request Completed',
                'Request #' . $request['id'] . ' completed for ' . $patientName . ' (' . $units . ' unit(s) of ' . $type . ' deducted from ' . $locationName . ')');
            jsonResponse(['ok' => true, 'message' => 'Request marked as completed and blood units deducted from hospital inventory.']);
        }

        /* ---- Rejected ---- */
        if ($newStatus === 'Rejected') {
            if (!in_array((string) $request['status'], ['Pending', 'Processing', 'Approved'], true)) {
                jsonResponse(['ok' => false,
                    'message' => 'Only pending, processing or approved requests can be rejected.'], 400);
            }

            $database->beginTransaction();
            try {
                if (!empty($request['inventory_deducted'])) {
                    // Restore units back to hospital inventory
                    $locName = trim((string)($request['location'] ?? $patient['hospital'] ?? ''));
                    $locId = 0;
                    if ($locName !== '') {
                        $lStmt = $database->prepare("SELECT id FROM blood_locations WHERE location_name = ? OR address LIKE ? LIMIT 1");
                        $lStmt->execute([$locName, '%' . $locName . '%']);
                        $locId = (int) $lStmt->fetchColumn();
                    }
                    if ($locId <= 0) {
                        $locId = (int) $database->query("SELECT id FROM blood_locations WHERE available_status = 'Active' ORDER BY id ASC LIMIT 1")->fetchColumn();
                    }
                    if ($locId > 0) {
                        $batchStmt = $database->prepare("SELECT id FROM blood_inventory WHERE blood_type = ? AND location_id = ? AND status = 'Available' ORDER BY expiration_date DESC LIMIT 1");
                        $batchStmt->execute([$type, $locId]);
                        $batchId = $batchStmt->fetchColumn();
                        if ($batchId) {
                            $database->prepare("UPDATE blood_inventory SET blood_units = blood_units + ? WHERE id = ?")->execute([$units, $batchId]);
                        } else {
                            $database->prepare("INSERT INTO blood_inventory (blood_type, blood_units, location_id, expiration_date, status) VALUES (?, ?, ?, DATE_ADD(CURRENT_DATE, INTERVAL 35 DAY), 'Available')")->execute([$type, $units, $locId]);
                        }
                    }
                    $database->prepare('UPDATE blood_requests SET inventory_deducted = 0 WHERE id = ?')->execute([$request['id']]);
                }
                $setStatus('Rejected');
                $database->commit();
            } catch (Throwable $error) {
                if ($database->inTransaction()) $database->rollBack();
                throw $error;
            }

            notify($patientUserId, 'Blood Request Rejected',
                'Your request for ' . $units . ' unit(s) of ' . $type
                . ' was not approved. Please contact the blood center for details.');
            logAction($userId, 'Request Rejected', 'Request #' . $request['id'] . ' rejected');
            jsonResponse(['ok' => true, 'message' => 'Request rejected.']);
        }

        /* ---- Cancelled ---- */
        if ($newStatus === 'Cancelled') {
            if (!in_array((string) $request['status'], ['Pending', 'Processing', 'Approved'], true)) {
                jsonResponse(['ok' => false, 'message' => 'Only pending, processing or approved requests can be cancelled.'], 400);
            }

            $database->beginTransaction();
            try {
                if (!empty($request['inventory_deducted'])) {
                    // Restore units back to hospital inventory
                    $locName = trim((string)($request['location'] ?? $patient['hospital'] ?? ''));
                    $locId = 0;
                    if ($locName !== '') {
                        $lStmt = $database->prepare("SELECT id FROM blood_locations WHERE location_name = ? OR address LIKE ? LIMIT 1");
                        $lStmt->execute([$locName, '%' . $locName . '%']);
                        $locId = (int) $lStmt->fetchColumn();
                    }
                    if ($locId <= 0) {
                        $locId = (int) $database->query("SELECT id FROM blood_locations WHERE available_status = 'Active' ORDER BY id ASC LIMIT 1")->fetchColumn();
                    }
                    if ($locId > 0) {
                        $batchStmt = $database->prepare("SELECT id FROM blood_inventory WHERE blood_type = ? AND location_id = ? AND status = 'Available' ORDER BY expiration_date DESC LIMIT 1");
                        $batchStmt->execute([$type, $locId]);
                        $batchId = $batchStmt->fetchColumn();
                        if ($batchId) {
                            $database->prepare("UPDATE blood_inventory SET blood_units = blood_units + ? WHERE id = ?")->execute([$units, $batchId]);
                        } else {
                            $database->prepare("INSERT INTO blood_inventory (blood_type, blood_units, location_id, expiration_date, status) VALUES (?, ?, ?, DATE_ADD(CURRENT_DATE, INTERVAL 35 DAY), 'Available')")->execute([$type, $units, $locId]);
                        }
                    }
                    $database->prepare('UPDATE blood_requests SET inventory_deducted = 0 WHERE id = ?')->execute([$request['id']]);
                }
                $setStatus('Cancelled');
                $database->commit();
            } catch (Throwable $error) {
                if ($database->inTransaction()) $database->rollBack();
                throw $error;
            }

            notify($patientUserId, 'Blood Request Cancelled',
                'Your request for ' . $units . ' unit(s) of ' . $type . ' was cancelled.');
            logAction($userId, 'Request Cancelled', 'Request #' . $request['id'] . ' cancelled');
            jsonResponse(['ok' => true, 'message' => 'Request cancelled.']);
        }

        jsonResponse(['ok' => false, 'message' => 'Invalid request status.'], 400);
    }
}

/* =============================================== BLOOD LOCATIONS */
if ($action === 'locations') {
    if ($user['role'] !== 'superadmin') {
        jsonResponse(['ok' => false, 'message' => 'Access denied. Hospital and facility management is strictly reserved for Superadmin.'], 403);
    }
    requireModule($user, 'locations');

    if ($method === 'GET' && $id === null) {
        sweepExpired();
        ensureHospitalSchema();
        $rows = $database->query(
            "SELECT l.*, u.username AS admin_username, u.email AS admin_email,
                    (SELECT COALESCE(SUM(i.blood_units), 0) FROM blood_inventory i
                      WHERE i.location_id = l.id AND i.status = 'Available'
                        AND i.expiration_date >= CURRENT_DATE) AS available_units,
                    (SELECT GROUP_CONCAT(username SEPARATOR ', ') FROM users WHERE hospital_id = l.id AND role = 'staff' AND status = 'active') AS staff_usernames,
                    (SELECT COUNT(*) FROM users WHERE hospital_id = l.id AND role = 'staff' AND status = 'active') AS staff_count,
                    (SELECT GROUP_CONCAT(username SEPARATOR ', ') FROM users WHERE hospital_id = l.id AND role = 'admin' AND status = 'active') AS all_admins
               FROM blood_locations l
          LEFT JOIN users u ON u.id = l.admin_id
              ORDER BY l.location_name"
        )->fetchAll();
        foreach ($rows as $index => $row) {
            // DECIMAL comes back as a string from PDO; the UI does maths on these.
            $rows[$index]['latitude'] = (float) $row['latitude'];
            $rows[$index]['longitude'] = (float) $row['longitude'];
            $rows[$index]['available_units'] = (int) $row['available_units'];
        }
        jsonResponse(['ok' => true, 'locations' => $rows]);
    }

    /* ------------------------------------ create / update a location */
    if (($method === 'POST' && $id === null) || ($method === 'PUT' && $id !== null && $sub === '')) {
        $existing = null;
        if ($method === 'PUT') {
            $found = $database->prepare('SELECT * FROM blood_locations WHERE id = ?');
            $found->execute([$id]);
            $existing = $found->fetch();
            if (!$existing) jsonResponse(['ok' => false, 'message' => 'Blood location not found.'], 404);
        }

        $required = ['location_name', 'address', 'latitude', 'longitude', 'contact_number'];
        if (!$existing) {
            $required[] = 'admin_username';
            $required[] = 'admin_password';
        }
        $missing = requireFields($body, $required);
        if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);

        if (!validLatitude($body['latitude'])) {
            jsonResponse(['ok' => false, 'message' => 'Please enter a valid latitude (-90 to 90).'], 400);
        }
        if (!validLongitude($body['longitude'])) {
            jsonResponse(['ok' => false, 'message' => 'Please enter a valid longitude (-180 to 180).'], 400);
        }
        if (!validPhone((string) $body['contact_number'])) {
            jsonResponse(['ok' => false, 'message' => 'Please enter a valid contact number.'], 400);
        }

        $name = mb_substr(trim((string) $body['location_name']), 0, 160);
        $address = mb_substr(trim((string) $body['address']), 0, 255);
        $contact = mb_substr(trim((string) $body['contact_number']), 0, 30);
        $lat = (float) $body['latitude'];
        $lng = (float) $body['longitude'];

        if ($existing) {
            $database->prepare(
                'UPDATE blood_locations
                    SET location_name = ?, address = ?, latitude = ?, longitude = ?, contact_number = ?
                  WHERE id = ?'
            )->execute([$name, $address, $lat, $lng, $contact, $existing['id']]);
            logAction($userId, 'Location Updated', 'Superadmin updated facility ' . $name);
            jsonResponse(['ok' => true, 'message' => 'Blood location updated with 100% precision.']);
        }

        // Validate Administrator Account details
        $adminUsername = trim((string) $body['admin_username']);
        if (mb_strlen($adminUsername) < 4 || mb_strlen($adminUsername) > 80) {
            jsonResponse(['ok' => false, 'message' => 'Administrator username must be 4 to 80 characters.'], 400);
        }
        $checkUser = $database->prepare('SELECT id FROM users WHERE username = ?');
        $checkUser->execute([$adminUsername]);
        if ($checkUser->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'That administrator username is already taken. Please choose another.'], 409);
        }

        $adminPassword = (string) $body['admin_password'];
        if (!strongEnoughPassword($adminPassword)) {
            jsonResponse(['ok' => false, 'message' => 'Admin password must be 8+ characters and contain uppercase, lowercase, and a number.'], 400);
        }

        $adminEmail = trim((string) ($body['admin_email'] ?? ''));
        if ($adminEmail !== '' && !validEmail($adminEmail)) {
            jsonResponse(['ok' => false, 'message' => 'Please enter a valid admin email address.'], 400);
        }

        // Atomically create both Hospital Location and Official Administrator
        ensureHospitalSchema();
        $database->beginTransaction();
        try {
            $database->prepare(
                "INSERT INTO blood_locations(location_name, address, latitude, longitude, contact_number, available_status, created_by)
                 VALUES (?, ?, ?, ?, ?, 'Active', ?)"
            )->execute([$name, $address, $lat, $lng, $contact, $userId]);
            $locationId = (int) $database->lastInsertId();

            $database->prepare(
                "INSERT INTO users(username, email, password_hash, role, status, hospital_id)
                 VALUES (?, ?, ?, 'admin', 'active', ?)"
            )->execute([
                $adminUsername,
                $adminEmail !== '' ? $adminEmail : null,
                password_hash($adminPassword, PASSWORD_DEFAULT),
                $locationId
            ]);
            $adminId = (int) $database->lastInsertId();

            $database->prepare('UPDATE blood_locations SET admin_id = ? WHERE id = ?')
                ->execute([$adminId, $locationId]);

            $database->commit();
        } catch (Throwable $e) {
            if ($database->inTransaction()) $database->rollBack();
            throw $e;
        }

        logAction($userId, 'Hospital Registered', 'Superadmin registered ' . $name . ' and created admin "' . $adminUsername . '"');
        jsonResponse([
            'ok' => true,
            'message' => "Hospital \"$name\" and Administrator \"$adminUsername\" successfully registered and live on the map!"
        ]);
    }

    /* ------------------- archive / unarchive (safe "delete") */
    if ($method === 'PUT' && $id !== null && $sub === 'status') {
        $found = $database->prepare('SELECT * FROM blood_locations WHERE id = ?');
        $found->execute([$id]);
        $location = $found->fetch();
        if (!$location) jsonResponse(['ok' => false, 'message' => 'Blood location not found.'], 404);
        $newStatus = (string) $location['available_status'] === 'Active' ? 'Inactive' : 'Active';
        $database->prepare('UPDATE blood_locations SET available_status = ? WHERE id = ?')
            ->execute([$newStatus, $location['id']]);
        logAction($userId, 'Location Status', $location['location_name'] . ' set to ' . $newStatus);
        jsonResponse(['ok' => true,
            'message' => 'Location ' . ($newStatus === 'Active' ? 'activated' : 'deactivated') . '.']);
    }

    /* ------------------- permanent delete of a location */
    if ($method === 'DELETE' && $id !== null) {
        $found = $database->prepare('SELECT * FROM blood_locations WHERE id = ?');
        $found->execute([$id]);
        $location = $found->fetch();
        if (!$location) jsonResponse(['ok' => false, 'message' => 'Blood location not found.'], 404);

        $database->beginTransaction();
        try {
            $database->prepare('DELETE FROM blood_inventory WHERE location_id = ?')->execute([$id]);
            $database->prepare('DELETE FROM blood_locations WHERE id = ?')->execute([$id]);
            $database->commit();
        } catch (Throwable $e) {
            if ($database->inTransaction()) $database->rollBack();
            throw $e;
        }

        logAction($userId, 'Location Deleted', 'Permanently deleted location ' . $location['location_name']);
        jsonResponse(['ok' => true, 'message' => 'Location and associated inventory have been permanently deleted.']);
    }
}

