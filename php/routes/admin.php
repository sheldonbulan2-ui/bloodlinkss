<?php
/**
 * php/routes/admin.php
 * --------------------
 * Admin / super admin module: user accounts, role permissions,
 * system settings, activity logs, reports and a read-only SQL export.
 *
 * Business rules enforced here:
 *  - only the Super Admin may create or edit admin accounts
 *  - super admin accounts can never be created or edited by others
 *  - deactivating an account blocks its next login and notifies the user
 *  - every change is written to activity_logs
 */
declare(strict_types=1);

$user = requireRoles(['staff', 'admin', 'superadmin']);
$userId = (int) $user['id'];
$role = (string) $user['role'];
$action = strtolower($segments[1] ?? '');
$idPart = (string) ($segments[2] ?? '');
$id = ctype_digit($idPart) ? (int) $idPart : null;
$database = db();

/** Everything below except reports is admin/super-admin only. */
function adminOnly(string $role): void
{
    if (!in_array($role, ['admin', 'superadmin'], true)) {
        jsonResponse(['ok' => false, 'message' => 'Access denied.'], 403);
    }
}
/** Super-admin-only helper. */
function superAdminOnly(string $role): void
{
    if ($role !== 'superadmin') {
        jsonResponse(['ok' => false, 'message' => 'Only the Super Admin can do this.'], 403);
    }
}

/* ========================================= USERS (staff / admins) */
if ($action === 'users') {
    adminOnly($role);
    requireModule($user, 'users');

    /* ------------------------------------------------------- list */
    if ($method === 'GET' && $id === null) {
        ensureHospitalSchema();
        if ($role === 'superadmin') {
            // Requirement: Superadmin sees ONLY patient and donor users, organized by hospital
            $rows = $database->query(
                "SELECT u.id, u.username, u.email, u.role, u.status, u.created_at, u.updated_at, u.hospital_id,
                        COALESCE(l.location_name, p.hospital, 'San Mariano District Hospital') AS hospital_name,
                        COALESCE(p.first_name, d.first_name, u.username) AS first_name,
                        COALESCE(p.last_name, d.last_name, '') AS last_name,
                        COALESCE(p.contact_number, d.contact_number, '') AS contact_number,
                        COALESCE(p.address, d.address, '') AS address,
                        COALESCE(p.blood_type_needed, d.blood_type, '') AS blood_type
                   FROM users u
              LEFT JOIN patients p ON p.user_id = u.id
              LEFT JOIN donors d ON d.user_id = u.id
              LEFT JOIN blood_locations l ON l.id = u.hospital_id
                  WHERE u.role IN ('patient', 'donor')
                  ORDER BY hospital_name ASC, u.role ASC, u.username ASC"
            )->fetchAll();
        } else {
            // Requirement: Admin sees ONLY users belonging to their assigned hospital
            $hospitalId = (int) ($user['hospital_id'] ?? 1);
            $hnStmt = $database->prepare("SELECT location_name FROM blood_locations WHERE id = ?");
            $hnStmt->execute([$hospitalId]);
            $hospName = $hnStmt->fetchColumn() ?: 'San Mariano District Hospital';

            $stmt = $database->prepare(
                "SELECT u.id, u.username, u.email, u.role, u.status, u.created_at, u.updated_at, u.hospital_id,
                        COALESCE(l.location_name, p.hospital, ?) AS hospital_name,
                        COALESCE(p.first_name, d.first_name, u.username) AS first_name,
                        COALESCE(p.last_name, d.last_name, '') AS last_name,
                        COALESCE(p.contact_number, d.contact_number, '') AS contact_number,
                        COALESCE(p.address, d.address, '') AS address,
                        COALESCE(p.blood_type_needed, d.blood_type, '') AS blood_type
                   FROM users u
              LEFT JOIN patients p ON p.user_id = u.id
              LEFT JOIN donors d ON d.user_id = u.id
              LEFT JOIN blood_locations l ON l.id = u.hospital_id
                  WHERE (u.hospital_id = ? OR p.hospital = ? OR l.id = ?)
                    AND u.role != 'superadmin'
                  ORDER BY u.role ASC, u.username ASC"
            );
            $stmt->execute([$hospName, $hospitalId, $hospName, $hospitalId]);
            $rows = $stmt->fetchAll();
        }
        jsonResponse(['ok' => true, 'users' => $rows]);
    }

    /* ----------------------------------------------------- create */
    if ($method === 'POST' && $id === null) {
        ensureHospitalSchema();
        $missing = requireFields($body, ['username', 'password']);
        if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);

        if ($role === 'admin') {
            // Admin can ONLY create official staff for their assigned hospital
            $newRole = 'staff';
            $hospitalId = (int) ($user['hospital_id'] ?? 0);
            if ($hospitalId <= 0) {
                jsonResponse(['ok' => false, 'message' => 'No hospital is assigned to your admin account. Please contact Superadmin.'], 400);
            }
        } else {
            // Superadmin can create admin or staff and optionally assign hospital
            $newRole = (string) ($body['role'] ?? 'staff');
            if ($newRole === 'superadmin') {
                jsonResponse(['ok' => false, 'message' => 'New super admin accounts cannot be created.'], 403);
            }
            if (!in_array($newRole, ['staff', 'admin'], true)) {
                jsonResponse(['ok' => false, 'message' => 'Role must be staff or admin.'], 400);
            }
            $hospitalId = !empty($body['hospital_id']) ? (int) $body['hospital_id'] : null;
        }

        $username = trim((string) $body['username']);
        if (mb_strlen($username) < 4 || mb_strlen($username) > 80) {
            jsonResponse(['ok' => false, 'message' => 'Username must be 4 to 80 characters.'], 400);
        }
        $taken = $database->prepare('SELECT id FROM users WHERE username = ?');
        $taken->execute([$username]);
        if ($taken->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'That username is already taken.'], 409);
        }
        if (!strongEnoughPassword((string) $body['password'])) {
            jsonResponse(['ok' => false,
                'message' => 'Password must be 8+ characters with uppercase, lowercase, and a number.'], 400);
        }
        $email = trim((string) ($body['email'] ?? ''));
        if ($email !== '' && !validEmail($email)) {
            jsonResponse(['ok' => false, 'message' => 'Please enter a valid email address.'], 400);
        }

        $database->prepare(
            "INSERT INTO users(username, email, password_hash, role, status, hospital_id) VALUES (?, ?, ?, ?, 'active', ?)"
        )->execute([
            $username,
            $email !== '' ? $email : null,
            password_hash((string) $body['password'], PASSWORD_DEFAULT),
            $newRole,
            $hospitalId
        ]);

        $hospitalLabel = $user['hospital_name'] ?? 'hospital';
        logAction($userId, 'Staff Created', 'Created official ' . $newRole . ' account "' . $username . '" for ' . $hospitalLabel);
        jsonResponse(['ok' => true, 'message' => 'Official Staff "' . $username . '" created successfully.']);
    }

    /* ----------------------------------------------------- update */
    if ($method === 'PUT' && $id !== null) {
        $found = $database->prepare('SELECT * FROM users WHERE id = ?');
        $found->execute([$id]);
        $target = $found->fetch();
        if (!$target) jsonResponse(['ok' => false, 'message' => 'User not found.'], 404);

        if ($role === 'admin') {
            if ((string) $target['role'] !== 'staff' || (int) $target['hospital_id'] !== (int) ($user['hospital_id'] ?? 0)) {
                jsonResponse(['ok' => false, 'message' => 'Access denied. You can only manage staff of your assigned hospital.'], 403);
            }
        }
        if ($role !== 'superadmin' && in_array((string) $target['role'], ['admin', 'superadmin'], true)) {
            jsonResponse(['ok' => false, 'message' => 'Only the Super Admin can manage admin accounts.'], 403);
        }
        if ((string) $target['role'] === 'superadmin' && (int) $target['id'] !== $userId) {
            jsonResponse(['ok' => false, 'message' => 'Super admin accounts cannot be edited.'], 403);
        }

        $changed = [];
        if (isset($body['username']) && trim((string) $body['username']) !== '') {
            $username = trim((string) $body['username']);
            if (mb_strlen($username) < 4 || mb_strlen($username) > 80) {
                jsonResponse(['ok' => false, 'message' => 'Username must be 4 to 80 characters.'], 400);
            }
            $duplicate = $database->prepare('SELECT id FROM users WHERE username = ? AND id <> ?');
            $duplicate->execute([$username, $target['id']]);
            if ($duplicate->fetch()) {
                jsonResponse(['ok' => false, 'message' => 'That username is already taken.'], 409);
            }
            $database->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$username, $target['id']]);
            $changed[] = 'username';
        }
        if (isset($body['password']) && (string) $body['password'] !== '') {
            if (!strongEnoughPassword((string) $body['password'])) {
                jsonResponse(['ok' => false,
                    'message' => 'Password must be 8+ characters with uppercase, lowercase, and a number.'], 400);
            }
            $database->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash((string) $body['password'], PASSWORD_DEFAULT), $target['id']]);
            $changed[] = 'password';
        }
        if (isset($body['status']) && in_array((string) $body['status'], ['active', 'inactive'], true)) {
            $newStatus = (string) $body['status'];
            if ((int) $target['id'] === $userId && $newStatus === 'inactive') {
                jsonResponse(['ok' => false, 'message' => 'You cannot deactivate your own account.'], 400);
            }
            $database->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $target['id']]);
            // Keep the matching profile row in step so lists stay consistent.
            if ((string) $target['role'] === 'donor') {
                $database->prepare('UPDATE donors SET status = ? WHERE user_id = ?')
                    ->execute([$newStatus, $target['id']]);
            }
            if ((string) $target['role'] === 'patient') {
                $database->prepare('UPDATE patients SET status = ? WHERE user_id = ?')
                    ->execute([$newStatus, $target['id']]);
            }
            if ($newStatus === 'inactive') {
                notify((int) $target['id'], 'Account Deactivated',
                    'Your account has been deactivated by an administrator.');
            }
            $changed[] = 'status';
        }
        if (!$changed) jsonResponse(['ok' => false, 'message' => 'Nothing to update.'], 400);

        logAction($userId, 'User Updated',
            'Updated "' . $target['username'] . '" (' . implode(', ', $changed) . ')');
        jsonResponse(['ok' => true, 'message' => 'User updated.']);
    }
}
/* ============================================ ROLES & PERMISSIONS */
if ($action === 'permissions') {
    superAdminOnly($role);
    $modules = allModules();

    if ($method === 'GET') {
        $staffRows = [];
        $adminRows = [];
        try {
            $rows = $database->query(
                'SELECT role, module, can_access FROM permissions ORDER BY role, module'
            )->fetchAll();
        } catch (Throwable $error) {
            // Table not migrated yet: show the built-in defaults instead of failing.
            appLog('permissions read', $error);
            $rows = [];
        }
        $seen = ['staff' => [], 'admin' => []];
        foreach ($rows as $row) {
            $rowRole = (string) $row['role'];
            if (!isset($seen[$rowRole])) continue;
            $seen[$rowRole][(string) $row['module']] = (int) $row['can_access'];
        }
        foreach ($modules as $module) {
            foreach (['staff', 'admin'] as $rowRole) {
                $access = $seen[$rowRole][$module]
                    ?? (defaultModuleAccess($rowRole, $module) ? 1 : 0);
                $entry = ['role' => $rowRole, 'module' => $module, 'can_access' => $access];
                if ($rowRole === 'staff') $staffRows[] = $entry; else $adminRows[] = $entry;
            }
        }
        jsonResponse(['ok' => true, 'modules' => $modules,
            'staff_only' => $staffRows, 'admin' => $adminRows]);
    }

    if ($method === 'PUT') {
        $database->beginTransaction();
        try {
            $upsert = $database->prepare(
                'INSERT INTO permissions(role, module, can_access) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE can_access = VALUES(can_access)'
            );
            foreach (['staff', 'admin'] as $rowRole) {
                if (!isset($body[$rowRole]) || !is_array($body[$rowRole])) continue;
                foreach ($modules as $module) {
                    $upsert->execute([$rowRole, $module, !empty($body[$rowRole][$module]) ? 1 : 0]);
                }
            }
            $database->commit();
        } catch (Throwable $error) {
            if ($database->inTransaction()) $database->rollBack();
            appLog('permissions write', $error);
            jsonResponse(['ok' => false,
                'message' => 'Permissions could not be saved. If this is a new install, run the database migration in database/migration-2026.sql first.'], 500);
        }
        logAction($userId, 'Permissions Updated', 'Role permissions matrix was updated');
        jsonResponse(['ok' => true, 'message' => 'Permissions saved.']);
    }
}

/* ================================================ SYSTEM SETTINGS */
if ($action === 'settings') {
    adminOnly($role);

    if ($method === 'GET') {
        jsonResponse(['ok' => true, 'settings' => allSettings()]);
    }

    if ($method === 'PUT') {
        superAdminOnly($role);
        $allowed = ['system_name', 'map_center_lat', 'map_center_lng', 'default_zoom', 'allow_registration'];
        $updates = [];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $body)) continue;
            $value = trim((string) $body[$key]);
            if ($key === 'system_name') {
                if ($value === '' || mb_strlen($value) > 80) {
                    jsonResponse(['ok' => false, 'message' => 'System name must be 1 to 80 characters.'], 400);
                }
            }
            if ($key === 'map_center_lat' && !validLatitude($value)) {
                jsonResponse(['ok' => false, 'message' => 'Map centre latitude must be between -90 and 90.'], 400);
            }
            if ($key === 'map_center_lng' && !validLongitude($value)) {
                jsonResponse(['ok' => false, 'message' => 'Map centre longitude must be between -180 and 180.'], 400);
            }
            if ($key === 'default_zoom') {
                if (!ctype_digit($value) || (int) $value < 1 || (int) $value > 19) {
                    jsonResponse(['ok' => false, 'message' => 'Default zoom must be a whole number from 1 to 19.'], 400);
                }
            }
            if ($key === 'allow_registration') {
                $value = in_array($value, ['1', 'true', 'on', 'yes'], true) ? '1' : '0';
            }
            $updates[$key] = mb_substr($value, 0, 255);
        }
        if (!$updates) jsonResponse(['ok' => false, 'message' => 'No settings were provided.'], 400);

        $upsert = $database->prepare(
            'INSERT INTO settings(setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $database->beginTransaction();
        try {
            foreach ($updates as $key => $value) $upsert->execute([$key, $value]);
            $database->commit();
        } catch (Throwable $error) {
            if ($database->inTransaction()) $database->rollBack();
            throw $error;
        }
        logAction($userId, 'Settings Updated', 'System settings were changed: ' . implode(', ', array_keys($updates)));
        jsonResponse(['ok' => true, 'message' => 'Settings saved.']);
    }
}
/* ================================================== ACTIVITY LOGS */
if ($action === 'logs' && $method === 'GET') {
    adminOnly($role);
    requireModule($user, 'logs');
    $where = '1=1';
    $params = [];
    $username = trim((string) ($_GET['username'] ?? ''));
    if ($username !== '') {
        $where .= ' AND u.username LIKE ?';
        $params[] = '%' . $username . '%';
    }
    $actionFilter = trim((string) ($_GET['action'] ?? ''));
    if ($actionFilter !== '') {
        $where .= ' AND l.action LIKE ?';
        $params[] = '%' . $actionFilter . '%';
    }
    $rows = $database->prepare(
        "SELECT l.id, l.action, l.description, l.created_at, u.username
           FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
          WHERE $where ORDER BY l.id DESC LIMIT 300"
    );
    $rows->execute($params);
    jsonResponse(['ok' => true, 'logs' => $rows->fetchAll()]);
}

/* ========================================================= REPORTS */
if ($action === 'reports' && $method === 'GET') {
    requireModule($user, 'reports');
    $type = strtolower((string) ($segments[2] ?? ''));

    /* summary tiles */
    if ($type === '') {
        jsonResponse(['ok' => true, 'report' => 'summary',
            'totals' => getTotalsByType(),
            'by_status' => $database->query(
                'SELECT status, COUNT(*) AS n FROM blood_requests GROUP BY status'
            )->fetchAll()]);
    }

    $bloodType = trim((string) ($_GET['blood_type'] ?? ''));
    if ($bloodType !== '' && $bloodType !== 'ALL' && !validBloodType($bloodType)) {
        jsonResponse(['ok' => false, 'message' => 'Invalid blood type filter.'], 400);
    }
    $statusFilter = trim((string) ($_GET['status'] ?? ''));
    if ($statusFilter !== '' && !preg_match('/^[A-Za-z]{1,20}$/', $statusFilter)) {
        jsonResponse(['ok' => false, 'message' => 'Invalid status filter.'], 400);
    }
    $from = trim((string) ($_GET['from'] ?? ''));
    $to = trim((string) ($_GET['to'] ?? ''));
    if ($from !== '' && !validDate($from)) jsonResponse(['ok' => false, 'message' => 'Invalid "from" date.'], 400);
    if ($to !== '' && !validDate($to)) jsonResponse(['ok' => false, 'message' => 'Invalid "to" date.'], 400);

    /** Build "AND col = ?" pieces only for the filters that were sent. */
    $params = [];
    $filter = function (string $column, string $value) use (&$params): string {
        if ($value === '' || $value === 'ALL') return '';
        $params[] = $value;
        return " AND $column = ?";
    };
    $dateRange = function (string $column) use ($from, $to, &$params): string {
        $sql = '';
        if ($from !== '') { $sql .= " AND $column >= ?"; $params[] = $from; }
        if ($to !== '') { $sql .= " AND $column <= ?"; $params[] = $to; }
        return $sql;
    };

    $sql = '';
    if ($type === 'inventory') {
        sweepExpired();
        $extra = $filter('i.blood_type', $bloodType) . $filter('i.status', $statusFilter);
        $sql = "SELECT i.blood_type, i.blood_units, i.status, i.expiration_date, l.location_name
                  FROM blood_inventory i JOIN blood_locations l ON l.id = i.location_id
                 WHERE 1=1 $extra ORDER BY l.location_name, i.blood_type";
    } elseif ($type === 'donors') {
        $extra = $filter('d.blood_type', $bloodType) . $filter('d.status', $statusFilter);
        $sql = "SELECT d.donor_code, d.first_name, d.middle_name, d.last_name, d.age, d.sex,
                       d.contact_number, d.blood_type, d.email, d.status, d.date_registered,
                       (SELECT COUNT(*) FROM donations dn WHERE dn.donor_id = d.id) AS donations
                  FROM donors d WHERE 1=1 $extra ORDER BY d.last_name, d.first_name";
    } elseif ($type === 'patients') {
        $extra = $filter('p.blood_type_needed', $bloodType) . $filter('p.status', $statusFilter);
        $sql = "SELECT p.patient_code, p.first_name, p.middle_name, p.last_name, p.age, p.sex,
                       p.contact_number, p.blood_type_needed, p.hospital, p.status
                  FROM patients p WHERE 1=1 $extra ORDER BY p.last_name, p.first_name";
    } elseif ($type === 'donations') {
        $extra = $filter('dn.blood_type', $bloodType) . $filter('dn.status', $statusFilter)
            . $dateRange('dn.donation_date');
        $sql = "SELECT dn.donation_date, d.donor_code, d.first_name, d.last_name, dn.blood_type,
                       dn.blood_units, dn.donation_location, dn.status
                  FROM donations dn JOIN donors d ON d.id = dn.donor_id
                 WHERE 1=1 $extra ORDER BY dn.donation_date DESC, dn.id DESC";
    } elseif ($type === 'requests') {
        $extra = $filter('r.blood_type', $bloodType) . $filter('r.status', $statusFilter)
            . $dateRange('r.request_date');
        $sql = "SELECT r.request_date, COALESCE(r.patient_name, CONCAT(p.first_name, ' ', p.last_name)) AS patient_name,
                       r.blood_type, r.component_type, r.units_needed, r.needed_datetime, r.priority, r.status, r.location, r.contact_number
                  FROM blood_requests r JOIN patients p ON p.id = r.patient_id
                 WHERE 1=1 $extra ORDER BY r.request_date DESC, r.id DESC";
    } elseif ($type === 'locations') {
        sweepExpired();
        $sql = "SELECT l.location_name, l.address, l.contact_number, l.available_status,
                       (SELECT COALESCE(SUM(i.blood_units), 0) FROM blood_inventory i
                         WHERE i.location_id = l.id AND i.status = 'Available'
                           AND i.expiration_date >= CURRENT_DATE) AS available_units
                  FROM blood_locations l ORDER BY l.location_name";
    } elseif ($type === 'usage') {
        $extra = $filter('dn.blood_type', $bloodType) . $dateRange('dn.donation_date');
        $sql = "SELECT dn.blood_type, dn.donation_date, dn.blood_units, dn.status,
                       CONCAT(d.first_name, ' ', d.last_name) AS donor
                  FROM donations dn JOIN donors d ON d.id = dn.donor_id
                 WHERE dn.status = 'Verified' $extra ORDER BY dn.donation_date DESC, dn.id DESC";
    } else {
        jsonResponse(['ok' => false, 'message' => 'Unknown report type.'], 404);
    }

    $rows = $database->prepare($sql);
    $rows->execute($params);
    jsonResponse(['ok' => true, 'report' => $type, 'rows' => $rows->fetchAll()]);
}
/* ================================ DATABASE EXPORT (super admin) */
/**
 * GET /api/admin/backup
 * Streams a plain .sql file (schema-safe INSERTs only) that can be
 * imported back through phpMyAdmin. It only READS, so it can never
 * damage the live data.
 */
if ($action === 'backup' && $method === 'GET') {
    superAdminOnly($role);
    $tables = ['users', 'donors', 'patients', 'blood_locations', 'blood_inventory',
        'blood_requests', 'donations', 'notifications', 'activity_logs', 'settings'];

    $stamp = date('Y-m-d_His');
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="bloodlink-backup-' . $stamp . '.sql"');
    header('Cache-Control: no-store');

    echo "-- BloodLink data export\n-- Created: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Import with: phpMyAdmin > bloodlink_app > Import\n";
    echo "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n\n";
    foreach ($tables as $table) {
        echo "-- ---------- $table ----------\n";
        try {
            $rows = $database->query("SELECT * FROM `$table`");
        } catch (Throwable $error) {
            appLog('backup ' . $table, $error);
            echo "-- skipped (table unavailable)\n\n";
            continue;
        }
        foreach ($rows as $row) {
            $columns = array_map(static fn($name) => '`' . $name . '`', array_keys($row));
            $values = [];
            foreach ($row as $value) {
                $values[] = $value === null ? 'NULL' : $database->quote((string) $value);
            }
            echo 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES ('
                . implode(', ', $values) . ");\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    logAction($userId, 'Backup Created', 'Downloaded a database export');
    exit;
}

/**
 * POST /api/admin/restore
 * Restoring by uploading a file is intentionally NOT supported: the old
 * SQLite-file swap cannot work on MySQL, and overwriting the live
 * database from a browser upload is exactly the kind of one-click data
 * loss this system must avoid. Restores are done deliberately through
 * phpMyAdmin instead.
 */
if ($action === 'restore' && $method === 'POST') {
    superAdminOnly($role);
    jsonResponse(['ok' => false,
        'message' => 'Restoring from the browser is disabled to protect live data. '
            . 'Import your .sql backup through phpMyAdmin (select the bloodlink_app database, then Import).'], 400);
}
