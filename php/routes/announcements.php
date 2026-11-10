<?php
/**
 * php/routes/announcements.php
 * ----------------------------
 * Hospital and Network Announcements management & broadcast API.
 * Accessible to Staff, Admin, Superadmin (create/delete/list),
 * and Donors/Patients (view announcements targeted to them).
 */
declare(strict_types=1);

$user = requireLogin();
$role = $user['role'];
$first = strtolower($segments[1] ?? '');

/* =============================================================
 * 1. LIST ANNOUNCEMENTS (GET /api/announcements)
 * ============================================================= */
if ($first === '' && $method === 'GET') {
    $db = db();
    $isPg = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');
    $dateExpr = $isPg ? "TO_CHAR(a.created_at, 'Mon DD, YYYY HH12:MI AM')" : "DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p')";

    if ($role === 'superadmin') {
        $stmt = $db->query(
            "SELECT a.*, $dateExpr AS formatted_date
             FROM announcements a
             ORDER BY a.is_urgent DESC, a.id DESC
             LIMIT 100"
        );
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (in_array($role, ['staff', 'admin'], true)) {
        $hospId = (int) ($user['hospital_id'] ?? 0);
        $stmt = $db->prepare(
            "SELECT a.*, $dateExpr AS formatted_date
             FROM announcements a
             WHERE a.hospital_id = ? OR a.hospital_id IS NULL OR a.hospital_id = 0
             ORDER BY a.is_urgent DESC, a.id DESC
             LIMIT 100"
        );
        $stmt->execute([$hospId]);
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Donor or Patient
        $targetFilter = ($role === 'donor') ? ['all', 'donor'] : ['all', 'patient'];
        $placeholders = implode(',', array_fill(0, count($targetFilter), '?'));
        
        $sql = "SELECT a.id, a.title, a.message, a.category, a.target_audience, a.is_urgent,
                       a.hospital_name, a.created_by_name,
                       $dateExpr AS formatted_date
                FROM announcements a
                WHERE a.status = 'active'
                  AND a.target_audience IN ($placeholders)
                ORDER BY a.is_urgent DESC, a.id DESC
                LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute($targetFilter);
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ensure formatted_date is never empty
    foreach ($announcements as &$ann) {
        if (empty($ann['formatted_date']) && !empty($ann['created_at'])) {
            $ann['formatted_date'] = date('M d, Y h:i A', strtotime((string)$ann['created_at']));
        }
    }
    unset($ann);

    jsonResponse(['ok' => true, 'announcements' => $announcements]);
}

/* =============================================================
 * 2. CREATE / BROADCAST ANNOUNCEMENT (POST /api/announcements)
 * ============================================================= */
if ($first === '' && $method === 'POST') {
    requireRoles(['staff', 'admin', 'superadmin']);

    $title = trim((string) ($body['title'] ?? ''));
    $message = trim((string) ($body['message'] ?? ''));
    $category = trim((string) ($body['category'] ?? 'General'));
    $target = strtolower(trim((string) ($body['target_audience'] ?? 'all')));
    $isUrgent = !empty($body['is_urgent']) ? 1 : 0;

    if ($title === '') {
        jsonResponse(['ok' => false, 'message' => 'Announcement title is required.'], 422);
    }
    if ($message === '') {
        jsonResponse(['ok' => false, 'message' => 'Announcement message content is required.'], 422);
    }
    if (!in_array($target, ['all', 'donor', 'patient'], true)) {
        $target = 'all';
    }

    $db = db();
    $hospId = !empty($user['hospital_id']) ? (int) $user['hospital_id'] : (!empty($body['hospital_id']) ? (int) $body['hospital_id'] : null);
    $hospName = 'Network Central';
    if ($hospId) {
        $hStmt = $db->prepare("SELECT location_name FROM blood_locations WHERE id = ? LIMIT 1");
        $hStmt->execute([$hospId]);
        $fetchedName = $hStmt->fetchColumn();
        if ($fetchedName) {
            $hospName = (string) $fetchedName;
        }
    }

    // Get author display name safely
    $authorName = $user['username'] ?? 'Staff';
    try {
        $pStmt = $db->prepare("SELECT first_name, last_name FROM profiles WHERE user_id = ? LIMIT 1");
        $pStmt->execute([(int) $user['id']]);
        $prof = $pStmt->fetch(PDO::FETCH_ASSOC);
        if ($prof && (!empty($prof['first_name']) || !empty($prof['last_name']))) {
            $authorName = trim(($prof['first_name'] ?? '') . ' ' . ($prof['last_name'] ?? ''));
        }
    } catch (Throwable $pe) {
        // Fallback for setups without separate profiles table
        try {
            $uStmt = $db->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
            $uStmt->execute([(int) $user['id']]);
            $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
            if ($uRow && !empty($uRow['username'])) {
                $authorName = (string)$uRow['username'];
            }
        } catch (Throwable $ue) {}
    }

    $nowStr = date('Y-m-d H:i:s');
    // Insert into announcements
    $ins = $db->prepare(
        "INSERT INTO announcements (title, message, category, target_audience, is_urgent, hospital_id, hospital_name, created_by, created_by_name, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)"
    );
    $ins->execute([
        $title,
        $message,
        $category,
        $target,
        $isUrgent,
        $hospId,
        $hospName,
        (int) $user['id'],
        $authorName,
        $nowStr
    ]);
    $announcementId = (int) $db->lastInsertId();

    // Fan-out to target users in notifications table for immediate alert on mobile/web
    try {
        $targetRoles = [];
        if ($target === 'all') {
            $targetRoles = ['donor', 'patient'];
        } elseif ($target === 'donor') {
            $targetRoles = ['donor'];
        } elseif ($target === 'patient') {
            $targetRoles = ['patient'];
        }

        if (!empty($targetRoles)) {
            $rolePlaceholders = implode(',', array_fill(0, count($targetRoles), '?'));
            $userStmt = $db->prepare("SELECT id FROM users WHERE role IN ($rolePlaceholders) AND status = 'active'");
            $userStmt->execute($targetRoles);
            $targetUserIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($targetUserIds)) {
                $notifTitle = ($isUrgent ? '🚨 URGENT: ' : '📢 ') . $title;
                $notifMsg = ($hospName ? "[$hospName] " : '') . $message;
                
                $chunkSize = 100;
                foreach (array_chunk($targetUserIds, $chunkSize) as $chunk) {
                    $values = [];
                    $params = [];
                    foreach ($chunk as $uid) {
                        $values[] = "(?, ?, ?, 'unread', NOW())";
                        $params[] = (int) $uid;
                        $params[] = $notifTitle;
                        $params[] = $notifMsg;
                    }
                    $sql = "INSERT INTO notifications (user_id, title, message, status, created_at) VALUES " . implode(',', $values);
                    $db->prepare($sql)->execute($params);
                }
            }
        }
    } catch (Throwable $notifErr) {
        // Non-blocking notification fan-out
    }

    jsonResponse([
        'ok' => true,
        'message' => 'Announcement posted and broadcasted successfully.',
        'id' => $announcementId
    ]);
}

/* =============================================================
 * 3. DELETE ANNOUNCEMENT (DELETE /api/announcements/:id)
 * ============================================================= */
if (ctype_digit($first) && $method === 'DELETE') {
    requireRoles(['staff', 'admin', 'superadmin']);
    $annId = (int) $first;
    $db = db();

    // Check ownership
    $chk = $db->prepare("SELECT id, hospital_id, created_by FROM announcements WHERE id = ?");
    $chk->execute([$annId]);
    $item = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        jsonResponse(['ok' => false, 'message' => 'Announcement not found.'], 404);
    }

    if ($role !== 'superadmin') {
        $userHosp = (int) ($user['hospital_id'] ?? 0);
        $itemHosp = (int) ($item['hospital_id'] ?? 0);
        $creator = (int) ($item['created_by'] ?? 0);

        if ($creator !== (int) $user['id'] && ($userHosp === 0 || $userHosp !== $itemHosp)) {
            jsonResponse(['ok' => false, 'message' => 'Unauthorized to delete this announcement.'], 403);
        }
    }

    $db->prepare("DELETE FROM announcements WHERE id = ?")->execute([$annId]);
    jsonResponse(['ok' => true, 'message' => 'Announcement deleted successfully.']);
}

jsonResponse(['ok' => false, 'message' => 'Endpoint not supported.'], 405);
