<?php
/**
 * php/routes/dashboard.php
 * ------------------------
 * GET /api/dashboard - role-specific summary numbers.
 * Donor and patient dashboards are assembled on the frontend from
 * /api/donor/* and /api/patient/*, so only a greeting is returned here.
 */
declare(strict_types=1);

if ($method !== 'GET') jsonResponse(['ok' => false, 'message' => 'Unknown API endpoint.'], 404);

$user = requireLogin();
$role = (string) $user['role'];
sweepExpired();

$database = db();
$data = [];

if (in_array($role, ['staff', 'admin', 'superadmin'], true)) {
    $count = static function (string $sql) use ($database): int {
        return (int) $database->query($sql)->fetchColumn();
    };

    $hospitalId = (int) ($user['hospital_id'] ?? 0);
    $hasHospital = $hospitalId > 0;

    if ($role === 'staff' && $hasHospital) {
        $totalUnits = $count("SELECT COALESCE(SUM(i.blood_units),0) FROM blood_inventory i
                                WHERE i.location_id = $hospitalId AND i.status='Available'
                                  AND i.expiration_date >= CURRENT_DATE");
    } elseif ($role === 'admin' && $hasHospital) {
        $totalUnits = $count("SELECT COALESCE(SUM(i.blood_units),0) FROM blood_inventory i
                                WHERE i.location_id = $hospitalId AND i.status='Available'
                                  AND i.expiration_date >= CURRENT_DATE");
    } else {
        $totalUnits = $count("SELECT COALESCE(SUM(i.blood_units),0) FROM blood_inventory i
                                JOIN blood_locations l ON l.id = i.location_id
                               WHERE i.status='Available' AND l.available_status='Active'
                                 AND i.expiration_date >= CURRENT_DATE");
    }

    $staffCount = ($role === 'admin' && $hasHospital)
        ? $count("SELECT COUNT(*) FROM users WHERE role='staff' AND hospital_id = $hospitalId AND status='active'")
        : $count("SELECT COUNT(*) FROM users WHERE role='staff' AND status='active'");

    if ($role === 'staff' && $hasHospital) {
        $pendingDonationsCount = $count("SELECT COUNT(*) FROM donations WHERE status='Pending' AND (target_hospital_id = $hospitalId OR (target_hospital_id IS NULL AND (donation_location = " . $database->quote($user['hospital_name'] ?? '') . " OR donation_location = 'ALL_HOSPITALS' OR donation_location IS NULL OR donation_location = '')))");
        $recentDonationsQuery = "SELECT dn.id, dn.blood_type, dn.blood_units, dn.donation_date, dn.status,
                                        d.first_name, d.last_name
                                   FROM donations dn JOIN donors d ON d.id = dn.donor_id
                                  WHERE dn.status = 'Pending'
                                    AND (dn.target_hospital_id = $hospitalId OR (dn.target_hospital_id IS NULL AND (dn.donation_location = " . $database->quote($user['hospital_name'] ?? '') . " OR dn.donation_location = 'ALL_HOSPITALS' OR dn.donation_location IS NULL OR dn.donation_location = '')))
                                  ORDER BY dn.created_at DESC, dn.id DESC LIMIT 10";
    } elseif ($role === 'admin' && $hasHospital) {
        $pendingDonationsCount = $count("SELECT COUNT(*) FROM donations WHERE status='Pending' AND (target_hospital_id = $hospitalId OR (target_hospital_id IS NULL AND (donation_location = " . $database->quote($user['hospital_name'] ?? '') . " OR donation_location = 'ALL_HOSPITALS' OR donation_location IS NULL OR donation_location = '')))");
        $recentDonationsQuery = "SELECT dn.id, dn.blood_type, dn.blood_units, dn.donation_date, dn.status,
                                        d.first_name, d.last_name
                                   FROM donations dn JOIN donors d ON d.id = dn.donor_id
                                  WHERE dn.status = 'Pending'
                                    AND (dn.target_hospital_id = $hospitalId OR (dn.target_hospital_id IS NULL AND (dn.donation_location = " . $database->quote($user['hospital_name'] ?? '') . " OR dn.donation_location = 'ALL_HOSPITALS' OR dn.donation_location IS NULL OR dn.donation_location = '')))
                                  ORDER BY dn.created_at DESC, dn.id DESC LIMIT 10";
    } else {
        $pendingDonationsCount = $count("SELECT COUNT(*) FROM donations WHERE status='Pending'");
        $recentDonationsQuery = "SELECT dn.id, dn.blood_type, dn.blood_units, dn.donation_date, dn.status,
                                        d.first_name, d.last_name
                                   FROM donations dn JOIN donors d ON d.id = dn.donor_id
                                  WHERE dn.status = 'Pending'
                                  ORDER BY dn.created_at DESC, dn.id DESC LIMIT 10";
    }

    $data['counts'] = [
        'donors' => $count("SELECT COUNT(*) FROM donors WHERE status='active'"),
        'patients' => $count("SELECT COUNT(*) FROM patients WHERE status='active'"),
        'staff' => $staffCount,
        'total_units' => $totalUnits,
        'pending_requests' => $count("SELECT COUNT(*) FROM blood_requests WHERE status='Pending'"),
        'approved_requests' => $count("SELECT COUNT(*) FROM blood_requests WHERE status IN ('Approved','Processing')"),
        'locations' => $count("SELECT COUNT(*) FROM blood_locations WHERE available_status='Active'"),
        'pending_donations' => $pendingDonationsCount,
    ];

    if ($hasHospital) {
        $data['hospital'] = [
            'id' => $hospitalId,
            'name' => $user['hospital_name'] ?? 'Assigned Hospital',
            'address' => $user['hospital_address'] ?? ''
        ];
    }

    $data['recent_donations'] = $database->query($recentDonationsQuery)->fetchAll();

    $data['recent_requests'] = $database->query(
        "SELECT r.id, r.blood_type, r.units_needed, r.priority, r.status, r.created_at,
                p.first_name, p.last_name
           FROM blood_requests r JOIN patients p ON p.id = r.patient_id
          ORDER BY r.created_at DESC, r.id DESC LIMIT 10"
    )->fetchAll();

    if (($role === 'staff' || $role === 'admin') && $hasHospital) {
        $byTypeRows = $database->query(
            "SELECT blood_type, COALESCE(SUM(blood_units),0) as units FROM blood_inventory
              WHERE location_id = $hospitalId AND status='Available' AND expiration_date >= CURRENT_DATE
              GROUP BY blood_type"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        $byType = [];
        foreach (bloodTypes() as $type) {
            $byType[] = ['blood_type' => $type, 'units' => (int) ($byTypeRows[$type] ?? 0)];
        }
        $data['by_type'] = $byType;
    } else {
        $byType = [];
        foreach (getTotalsByType() as $type => $units) $byType[] = ['blood_type' => $type, 'units' => $units];
        $data['by_type'] = $byType;
    }

    if ($role !== 'staff') {
        $data['recent_logs'] = $database->query(
            "SELECT l.action, l.description, l.created_at, u.username
               FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
              ORDER BY l.id DESC LIMIT 6"
        )->fetchAll();
    }

    if ($role === 'superadmin') {
        $data['users_total'] = $count('SELECT COUNT(*) FROM users');
        $data['users_inactive'] = $count("SELECT COUNT(*) FROM users WHERE status='inactive'");
        $data['role_counts'] = [
            'superadmin' => $count("SELECT COUNT(*) FROM users WHERE role='superadmin'"),
            'admin' => $count("SELECT COUNT(*) FROM users WHERE role='admin'"),
            'staff' => $count("SELECT COUNT(*) FROM users WHERE role='staff'"),
            'donor' => $count("SELECT COUNT(*) FROM users WHERE role='donor'"),
            'patient' => $count("SELECT COUNT(*) FROM users WHERE role='patient'"),
        ];

        // Real-time Feature Usage Tracking (Most Used vs. Least Used)
        $data['feature_stats'] = getFeatureUsageStats();

        // Feedbacks for Superadmin Review
        ensureFeedbacksTableExists();
        $data['recent_feedbacks'] = $database->query(
            "SELECT f.id, f.user_id, f.role, f.rating, f.category, f.message, f.suggestion, f.status, f.created_at,
                    u.username,
                    COALESCE(CONCAT(d.first_name, ' ', d.last_name), CONCAT(p.first_name, ' ', p.last_name), u.username) AS full_name
               FROM feedbacks f
               JOIN users u ON u.id = f.user_id
               LEFT JOIN donors d ON d.user_id = u.id AND f.role = 'donor'
               LEFT JOIN patients p ON p.user_id = u.id AND f.role = 'patient'
              ORDER BY f.id DESC LIMIT 12"
        )->fetchAll();
        $data['feedback_stats'] = [
            'total' => $count("SELECT COUNT(*) FROM feedbacks"),
            'avg_rating' => round((float) ($database->query("SELECT AVG(rating) FROM feedbacks")->fetchColumn() ?: 5.0), 1)
        ];
    }
} else {
    $data['greeting'] = 'Hello, ' . $user['username'] . '!';
}

jsonResponse(['ok' => true, 'dashboard' => $data, 'role' => $role]);
