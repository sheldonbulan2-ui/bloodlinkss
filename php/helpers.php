<?php
/**
 * php/helpers.php
 * ---------------
 * Domain helpers shared by every API module.
 *
 * These are the single source of truth for the business rules:
 *  - only Verified donations reach inventory
 *  - expired blood never counts as available
 *  - only Active locations appear in search/map
 *  - inventory can never go negative
 *  - important actions are logged, users are notified
 */
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/** Sequential human-readable codes: DON-00001 / PAT-00001. */
function nextCode(string $table, string $column, string $prefix): string
{
    $allowed = ['donors' => 'donor_code', 'patients' => 'patient_code'];
    if (($allowed[$table] ?? null) !== $column) {
        throw new InvalidArgumentException('Unsupported code column.');
    }
    $row = db()->query("SELECT $column AS code FROM $table ORDER BY id DESC LIMIT 1")->fetch();
    $last = 0;
    if ($row && preg_match('/(\d+)$/', (string) $row['code'], $m)) $last = (int) $m[1];
    return $prefix . '-' . str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
}

/** Write an entry to activity_logs (never fatal). */
function logAction(?int $userId, string $action, string $description): void
{
    try {
        $statement = db()->prepare('INSERT INTO activity_logs(user_id, action, description) VALUES (?, ?, ?)');
        $statement->execute([$userId, $action, mb_substr($description, 0, 500)]);
    } catch (Throwable $error) {
        appLog('logAction', $error);
    }
}

/** Send an in-app notification (never fatal). */
function notify(?int $userId, string $title, string $message): void
{
    if (!$userId) return;
    try {
        $statement = db()->prepare('INSERT INTO notifications(user_id, title, message) VALUES (?, ?, ?)');
        $statement->execute([$userId, mb_substr($title, 0, 160), $message]);
    } catch (Throwable $error) {
        appLog('notify', $error);
    }
}

/** Broadcast a notification to all active staff, admin, and superadmin users. */
function notifyStaff(string $title, string $message): void
{
    try {
        $admins = db()->query("SELECT id FROM users WHERE role IN ('staff', 'admin', 'superadmin') AND status = 'active'")->fetchAll();
        foreach ($admins as $admin) {
            notify((int)$admin['id'], $title, $message);
        }
    } catch (Throwable $error) {
        appLog('notifyStaff', $error);
    }
}

/** Move past-expiry Available batches to Expired so they stop showing as stock. */
function sweepExpired(): void
{
    try {
        db()->exec("UPDATE blood_inventory SET status = 'Expired'
                     WHERE status = 'Available' AND expiration_date < CURRENT_DATE");
    } catch (Throwable $error) {
        appLog('sweepExpired', $error);
    }
}

/**
 * Availability rows used by BOTH search and map, so the two can never disagree.
 * Returns one row per (location, blood type) with the summed units.
 */
function getAvailability(?string $bloodType = null, ?int $locationId = null): array
{
    sweepExpired();
    // 1. Fetch all active locations
    $locWhere = "available_status = 'Active'";
    $locParams = [];
    if ($locationId !== null && $locationId > 0) {
        $locWhere .= " AND id = ?";
        $locParams[] = $locationId;
    }
    $locStmt = db()->prepare("SELECT id, location_name, address, latitude, longitude, contact_number FROM blood_locations WHERE $locWhere ORDER BY location_name ASC");
    $locStmt->execute($locParams);
    $activeLocs = $locStmt->fetchAll();

    $results = [];
    foreach ($activeLocs as $loc) {
        $invWhere = "status = 'Available' AND expiration_date >= CURRENT_DATE AND location_id = ?";
        $invParams = [$loc['id']];
        if ($bloodType !== null && $bloodType !== 'ALL') {
            $invWhere .= " AND blood_type = ?";
            $invParams[] = $bloodType;
        }
        $invStmt = db()->prepare(
            "SELECT blood_type, SUM(blood_units) AS units, MAX(updated_at) AS last_updated
               FROM blood_inventory
              WHERE $invWhere
              GROUP BY blood_type"
        );
        $invStmt->execute($invParams);
        $stocks = $invStmt->fetchAll();

        if (!empty($stocks)) {
            foreach ($stocks as $s) {
                $u = (int) $s['units'];
                if ($u > 0) {
                    $results[] = [
                        'location_id' => $loc['id'],
                        'location_name' => $loc['location_name'],
                        'address' => $loc['address'],
                        'latitude' => (float) $loc['latitude'],
                        'longitude' => (float) $loc['longitude'],
                        'contact_number' => $loc['contact_number'],
                        'blood_type' => $s['blood_type'],
                        'units' => $u,
                        'last_updated' => $s['last_updated'],
                    ];
                }
            }
        }
    }
    return $results;
}

/**
 * Group availability rows by location.
 * Each location carries BOTH shapes on purpose:
 *   units[] -> used by the search results list
 *   blood{} -> used by the Leaflet map helper
 * so search results can be dropped straight onto the map.
 */
function groupAvailability(array $rows): array
{
    $locations = [];
    foreach ($rows as $row) {
        $id = (int) $row['location_id'];
        if (!isset($locations[$id])) {
            $locations[$id] = [
                'location_id' => $id,
                'location_name' => $row['location_name'],
                'address' => $row['address'],
                'latitude' => (float) $row['latitude'],
                'longitude' => (float) $row['longitude'],
                'contact_number' => $row['contact_number'],
                'available_status' => 'Active',
                'units' => [],
                'blood' => [],
            ];
        }
        $units = (int) $row['units'];
        $locations[$id]['units'][] = [
            'blood_type' => $row['blood_type'],
            'units' => $units,
            'last_updated' => $row['last_updated'],
        ];
        $locations[$id]['blood'][$row['blood_type']] = $units;
    }
    return array_values($locations);
}

/** Total available units per blood type, all 8 types always present. */
function getTotalsByType(): array
{
    sweepExpired();
    $totals = [];
    foreach (bloodTypes() as $type) $totals[$type] = 0;
    $rows = db()->query(
        "SELECT i.blood_type, SUM(i.blood_units) AS units
           FROM blood_inventory i JOIN blood_locations l ON l.id = i.location_id
          WHERE i.status = 'Available' AND l.available_status = 'Active'
            AND i.expiration_date >= CURRENT_DATE
          GROUP BY i.blood_type"
    )->fetchAll();
    foreach ($rows as $row) $totals[$row['blood_type']] = (int) $row['units'];
    return $totals;
}

/**
 * Deduct units from a location, oldest expiring batch first (FIFO).
 * Must run inside the caller's transaction. Never lets units go negative.
 * Returns [ok, message].
 */
/**
 * FIFO consumption of blood inventory batches with strict zero-negative stock guard.
 * Supports blood component matching and atomicity under concurrent requests.
 * Returns [ok, message, unitsDeducted].
 */
function consumeUnits(string $bloodType, int $locationId, int $unitsNeeded, ?string $componentType = null): array
{
    $database = db();
    $bloodType = trim($bloodType);

    // Normalize component type string
    $cleanComp = null;
    if ($componentType !== null && $componentType !== '' && $componentType !== 'Other') {
        $cLower = strtolower($componentType);
        if (str_contains($cLower, 'pack') || str_contains($cLower, 'prbc') || str_contains($cLower, 'red blood')) {
            $cleanComp = 'Packed Red Blood Cells (PRBC)';
        } elseif (str_contains($cLower, 'platelet') || str_contains($cLower, 'pc')) {
            $cleanComp = 'Platelet Concentrate (PC)';
        } elseif (str_contains($cLower, 'plasma') || str_contains($cLower, 'ffp')) {
            $cleanComp = 'Fresh Frozen Plasma (FFP)';
        } elseif (str_contains($cLower, 'cryo')) {
            $cleanComp = 'Cryoprecipitate';
        } else {
            $cleanComp = 'Whole Blood';
        }
    }

    // Step 1: Query exact or compatible component first
    $sql = "SELECT id, blood_units, component_type FROM blood_inventory
             WHERE blood_type = ? AND location_id = ? AND status = 'Available'
               AND expiration_date >= CURRENT_DATE";
    $params = [$bloodType, $locationId];

    if ($cleanComp !== null) {
        $sql .= " AND (component_type = ? OR component_type LIKE ? OR component_type IS NULL OR component_type = 'Whole Blood')";
        $params[] = $cleanComp;
        $params[] = '%' . $cleanComp . '%';
    }

    $sql .= " ORDER BY expiration_date ASC, id ASC FOR UPDATE";
    $batches = $database->prepare($sql);
    $batches->execute($params);
    $rows = $batches->fetchAll();

    $total = 0;
    foreach ($rows as $row) $total += (int) $row['blood_units'];

    // Step 2: Fallback to ANY available unit of this blood type if component query alone is insufficient
    if ($total < $unitsNeeded) {
        $fbSql = "SELECT id, blood_units, component_type FROM blood_inventory
                  WHERE blood_type = ? AND location_id = ? AND status = 'Available'
                    AND expiration_date >= CURRENT_DATE
                  ORDER BY expiration_date ASC, id ASC FOR UPDATE";
        $fbBatches = $database->prepare($fbSql);
        $fbBatches->execute([$bloodType, $locationId]);
        $fbRows = $fbBatches->fetchAll();
        $fbTotal = 0;
        foreach ($fbRows as $row) $fbTotal += (int) $row['blood_units'];
        if ($fbTotal >= $unitsNeeded || $fbTotal > $total) {
            $rows = $fbRows;
            $total = $fbTotal;
        }
    }

    $nameStatement = $database->prepare('SELECT location_name FROM blood_locations WHERE id = ?');
    $nameStatement->execute([$locationId]);
    $name = (string) ($nameStatement->fetchColumn() ?: 'San Mariano District Hospital');

    $compLabel = ($cleanComp ? " of $cleanComp" : ($componentType ? " of $componentType" : ""));
    if ($total < $unitsNeeded) {
        return [false, "Insufficient inventory: only $total unit(s)$compLabel ($bloodType) available at $name.", 0];
    }

    // Atomic decrement guard: guaranteed non-negative stock (blood_units >= ?)
    $update = $database->prepare(
        "UPDATE blood_inventory
            SET blood_units = blood_units - ?,
                status = CASE WHEN (blood_units - ?) <= 0 THEN 'Used' ELSE status END,
                updated_at = NOW()
          WHERE id = ? AND blood_units >= ?"
    );

    $remaining = $unitsNeeded;
    $totalTaken = 0;
    foreach ($rows as $row) {
        if ($remaining <= 0) break;
        $have = (int) $row['blood_units'];
        $take = min($have, $remaining);
        if ($take <= 0) continue;

        $update->execute([$take, $take, $row['id'], $take]);
        if ($update->rowCount() === 0) {
            // Concurrent race condition: another transaction took stock simultaneously
            return [false, "Inventory conflict detected during deduction at $name. Please try again.", $totalTaken];
        }
        $remaining -= $take;
        $totalTaken += $take;
    }
    return [true, "Successfully allocated $totalTaken unit(s)$compLabel ($bloodType) from $name.", $totalTaken];
}

/** Modules that can be granted per role in the Roles & Permissions page. */
function allModules(): array
{
    return ['donors', 'patients', 'donations', 'inventory', 'requests',
        'locations', 'reports', 'users', 'logs', 'settings'];
}

/** Sensible fallback when the permissions table has no row yet. */
function defaultModuleAccess(string $role, string $module): bool
{
    if ($module === 'locations' || $module === 'roles') {
        return $role === 'superadmin';
    }
    $staff = ['donors', 'patients', 'donations', 'inventory', 'requests', 'reports'];
    if ($role === 'staff') return in_array($module, $staff, true);
    $admin = ['donors', 'patients', 'donations', 'inventory', 'requests', 'reports', 'users', 'logs', 'settings'];
    if ($role === 'admin') return in_array($module, $admin, true);
    return false;
}

/** True when the role may use the module. Super admin always passes. */
function moduleAllowed(string $role, string $module): bool
{
    if ($role === 'superadmin') return true;
    if ($module === 'locations' || $module === 'roles') return false;
    try {
        $statement = db()->prepare('SELECT can_access FROM permissions WHERE role = ? AND module = ?');
        $statement->execute([$role, $module]);
        $row = $statement->fetch();
        if ($row) return (int) $row['can_access'] === 1;
    } catch (Throwable $error) {
        // permissions table missing -> fall back to the built-in defaults
        appLog('moduleAllowed', $error);
    }
    return defaultModuleAccess($role, $module);
}

/** 403 unless the current user's role may use the module. */
function requireModule(array $user, string $module): void
{
    if (!moduleAllowed((string) $user['role'], $module)) {
        jsonResponse(['ok' => false, 'message' => "You do not have permission to access the $module module."], 403);
    }
}

/** All rows of the settings table as key => value. */
function allSettings(): array
{
    $settings = [
        'system_name' => 'BloodLink',
        'map_center_lat' => '17.03',
        'map_center_lng' => '121.9',
        'default_zoom' => '10',
        'allow_registration' => '1',
    ];
    try {
        foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $error) {
        appLog('allSettings', $error);
    }
    return $settings;
}

/**
 * Returns list of donor blood types compatible with a patient's blood type.
 * e.g. for O+, compatible donors are ['O+', 'O-']
 * for AB+, compatible donors are all 8 types.
 */
function getCompatibleDonorTypes(string $patientBloodType, string $componentType = 'Whole Blood'): array
{
    $bt = strtoupper(trim($patientBloodType));
    $redCellMatrix = [
        'O-'  => ['O-'],
        'O+'  => ['O+', 'O-'],
        'A-'  => ['A-', 'O-'],
        'A+'  => ['A+', 'A-', 'O+', 'O-'],
        'B-'  => ['B-', 'O-'],
        'B+'  => ['B+', 'B-', 'O+', 'O-'],
        'AB-' => ['AB-', 'A-', 'B-', 'O-'],
        'AB+' => ['AB+', 'AB-', 'A+', 'A-', 'B+', 'B-', 'O+', 'O-'],
    ];

    $plasmaMatrix = [
        'O-'  => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'],
        'O+'  => ['O+', 'A+', 'B+', 'AB+'],
        'A-'  => ['A-', 'A+', 'AB-', 'AB+'],
        'A+'  => ['A+', 'AB+'],
        'B-'  => ['B-', 'B+', 'AB-', 'AB+'],
        'B+'  => ['B+', 'AB+'],
        'AB-' => ['AB-', 'AB+'],
        'AB+' => ['AB+'],
    ];

    if ($componentType === 'Fresh Frozen Plasma (FFP)' || $componentType === 'Plasma') {
        return $plasmaMatrix[$bt] ?? [$bt];
    }
    return $redCellMatrix[$bt] ?? [$bt];
}




