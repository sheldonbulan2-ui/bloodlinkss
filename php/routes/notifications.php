<?php
/**
 * php/routes/notifications.php
 * ----------------------------
 * The logged-in user's own notifications (any role).
 * Every query is scoped by user_id, so one user can never read
 * or modify another user's notifications.
 */
declare(strict_types=1);

$user = requireLogin();
$first = strtolower($segments[1] ?? '');

/* ------------------------------------------------------------- list */
if ($first === '' && $method === 'GET') {
    $rows = db()->prepare(
        'SELECT id, title, message, status, created_at FROM notifications
          WHERE user_id = ? ORDER BY id DESC'
    );
    $rows->execute([$user['id']]);
    $notifications = $rows->fetchAll();
    $unread = 0;
    foreach ($notifications as $notification) if ($notification['status'] === 'unread') $unread++;
    jsonResponse(['ok' => true, 'notifications' => $notifications, 'unread' => $unread]);
}

/* --------------------------------------------------- mark all read */
if ($first === 'read-all' && $method === 'PUT') {
    db()->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ?")->execute([$user['id']]);
    jsonResponse(['ok' => true, 'message' => 'All notifications marked as read.']);
}

/* --------------------------------------------------- mark one read */
if (ctype_digit($first) && strtolower($segments[2] ?? '') === 'read' && $method === 'PUT') {
    // Check ownership first: re-marking an already-read item must not 404.
    $owned = db()->prepare('SELECT id FROM notifications WHERE id = ? AND user_id = ?');
    $owned->execute([(int) $first, $user['id']]);
    if (!$owned->fetch()) {
        jsonResponse(['ok' => false, 'message' => 'Notification not found.'], 404);
    }
    db()->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?")
        ->execute([(int) $first, $user['id']]);
    jsonResponse(['ok' => true, 'message' => 'Marked as read.']);
}
