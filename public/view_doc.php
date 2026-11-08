<?php
/**
 * public/view_doc.php
 * -------------------
 * Safe document viewer for Doctor Requisition Slips and NVBSP Forms.
 * Resolves missing uploads (e.g. Render container restarts) gracefully
 * by streaming the file if found, or rendering the filled official
 * requisition form template instead of returning an Apache 404.
 */
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/helpers.php';
require_once __DIR__ . '/../php/guard.php';

assertProductionConfig();
securityHeaders();
startAppSession();

$user = currentUser();
if (!$user) {
    $next = (string) ($_SERVER['REQUEST_URI'] ?? '');
    header('Location: login.php?next=' . urlencode($next));
    exit;
}

$id = (int) ($_GET['id'] ?? $_GET['req_id'] ?? 0);
$url = trim((string) ($_GET['url'] ?? ''));

$req = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM blood_requests WHERE id = ?');
    $stmt->execute([$id]);
    $req = $stmt->fetch();
}

$docUrl = $req['medical_document_url'] ?? $url;

// If we have a document URL or filename, check if the physical file exists on disk
if (!empty($docUrl)) {
    $parsedPath = parse_url($docUrl, PHP_URL_PATH) ?: $docUrl;
    $filename = basename($parsedPath);
    
    // Sanitize filename against directory traversal
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
    $diskPath = __DIR__ . '/uploads/' . $filename;

    if (!empty($filename) && file_exists($diskPath) && is_file($diskPath)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $diskPath) ?: 'application/octet-stream';
        finfo_close($finfo);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($diskPath));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=86400');
        readfile($diskPath);
        exit;
    }
}

// Fallback: If physical image file does not exist on disk (e.g. Render container restart),
// redirect to the official digital requisition document pre-filled with this request's details!
if ($id > 0) {
    header('Location: official_blood_request_form.php?id=' . $id);
    exit;
}

// No request id found: redirect to the blank official template
header('Location: official_blood_request_form.php');
exit;
