<?php
/**
 * php/routes/feedback.php
 * -----------------------
 * Handles Donor and Patient feedback submissions and Superadmin review.
 */
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

ensureFeedbacksTableExists();

$user = requireLogin();
$role = (string) $user['role'];
$db = db();

/* ---------------------------------------------------- POST feedback */
if ($method === 'POST') {
    // Only patient and donor can submit feedback
    if (!in_array($role, ['donor', 'patient'], true)) {
        jsonResponse(['ok' => false, 'message' => 'Only Donors and Patients may submit feedback.'], 403);
    }

    $rating = max(1, min(5, (int) ($body['rating'] ?? 5)));
    $category = trim((string) ($body['category'] ?? 'General Experience'));
    $message = trim((string) ($body['message'] ?? ($body['suggestion'] ?? '')));
    $suggestion = trim((string) ($body['suggestion'] ?? $message));

    if ($message === '' && $suggestion === '') {
        jsonResponse(['ok' => false, 'message' => 'Please provide your comments or suggestions.'], 400);
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO feedbacks (user_id, role, rating, category, message, suggestion, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'New', NOW())"
        );
        $stmt->execute([$user['id'], $role, $rating, $category, $message, $suggestion]);
        logAction((int) $user['id'], 'Feedback Submitted', "Submitted {$rating}-star feedback for {$category}");

        jsonResponse([
            'ok' => true,
            'message' => 'Thank you for your valuable feedback! It has been received by the Superadmin dashboard.'
        ]);
    } catch (Throwable $e) {
        appLog('feedback_submit', $e);
        jsonResponse(['ok' => false, 'message' => 'Failed to submit feedback. Please try again.'], 500);
    }
}

/* ----------------------------------------------------- PUT feedback (Mark Reviewed) */
if ($method === 'PUT') {
    if (!in_array($role, ['superadmin', 'admin'], true)) {
        jsonResponse(['ok' => false, 'message' => 'Access denied.'], 403);
    }
    $fbId = (int) ($segments[1] ?? 0);
    if ($fbId <= 0) {
        $fbId = (int) ($body['id'] ?? 0);
    }
    if ($fbId <= 0) {
        jsonResponse(['ok' => false, 'message' => 'Invalid feedback ID.'], 400);
    }
    $newStatus = trim((string) ($body['status'] ?? 'Reviewed'));
    try {
        $db->prepare("UPDATE feedbacks SET status = ? WHERE id = ?")->execute([$newStatus, $fbId]);
        jsonResponse(['ok' => true, 'message' => "Feedback marked as {$newStatus}."]);
    } catch (Throwable $e) {
        appLog('feedback_update', $e);
        jsonResponse(['ok' => false, 'message' => 'Failed to update feedback status.'], 500);
    }
}

/* ----------------------------------------------------- GET feedback (Superadmin / Admin) */
if ($method === 'GET') {
    if (!in_array($role, ['superadmin', 'admin'], true)) {
        jsonResponse(['ok' => false, 'message' => 'Access denied.'], 403);
    }

    try {
        $stmt = $db->query(
            "SELECT f.id, f.user_id, f.role, f.rating, f.category, f.message, f.suggestion, f.status, f.created_at,
                    u.username,
                    COALESCE(CONCAT(d.first_name, ' ', d.last_name), CONCAT(p.first_name, ' ', p.last_name), u.username) AS full_name
               FROM feedbacks f
               JOIN users u ON u.id = f.user_id
               LEFT JOIN donors d ON d.user_id = u.id AND f.role = 'donor'
               LEFT JOIN patients p ON p.user_id = u.id AND f.role = 'patient'
              ORDER BY f.id DESC LIMIT 100"
        );
        $feedbacks = $stmt->fetchAll();

        // Calculate summary metrics
        $avgRating = 5.0;
        $totalCount = count($feedbacks);
        if ($totalCount > 0) {
            $sum = array_sum(array_column($feedbacks, 'rating'));
            $avgRating = round($sum / $totalCount, 1);
        }

        jsonResponse([
            'ok' => true,
            'feedbacks' => $feedbacks,
            'total' => $totalCount,
            'average_rating' => $avgRating
        ]);
    } catch (Throwable $e) {
        appLog('feedback_get', $e);
        jsonResponse(['ok' => false, 'message' => 'Failed to fetch feedbacks.'], 500);
    }
}
