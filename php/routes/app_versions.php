<?php
/**
 * php/routes/app_versions.php
 * ---------------------------
 * In-App Update & Version Management API.
 * - Public / App endpoint: GET /api/app-versions/latest (checks newest published build)
 * - Superadmin Only: Full management, APK upload, release notes publishing
 */
declare(strict_types=1);

$first = strtolower($segments[1] ?? '');

/* =========================================================================
 * 1. GET LATEST VERSION (Public / Mobile App Check)
 *    GET /api/app-versions/latest
 * ========================================================================= */
if ($first === 'latest' && $method === 'GET') {
    $db = db();
    $stmt = $db->query(
        "SELECT id, version_name, version_code, release_notes, apk_file_url, file_size_bytes, is_mandatory, created_at
         FROM app_versions
         WHERE is_active = 1
         ORDER BY version_code DESC, id DESC
         LIMIT 1"
    );
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$latest) {
        jsonResponse([
            'ok' => true,
            'has_update' => false,
            'message' => 'No published versions available.'
        ]);
    }

    // Build absolute URL for download if relative
    $apkUrl = $latest['apk_file_url'];
    if (!preg_match('#^https?://#i', $apkUrl)) {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                  ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'bloodlinkss.onrender.com';
        $apkUrl = $scheme . '://' . $host . '/' . ltrim($apkUrl, '/');
    }

    jsonResponse([
        'ok' => true,
        'has_update' => true,
        'version_name' => $latest['version_name'],
        'version_code' => (int) $latest['version_code'],
        'release_notes' => $latest['release_notes'],
        'apk_file_url' => $apkUrl,
        'file_size_bytes' => (int) $latest['file_size_bytes'],
        'is_mandatory' => (bool) $latest['is_mandatory'],
        'created_at' => $latest['created_at']
    ]);
}

/* =========================================================================
 * ALL OTHER ENDPOINTS ARE STRICTLY RESTRICTED TO SUPERADMIN ONLY
 * ========================================================================= */
$user = requireLogin();
requireRoles(['superadmin']);

/* -------------------------------------------------- 2. LIST ALL VERSIONS */
if ($first === '' && $method === 'GET') {
    $db = db();
    $stmt = $db->query(
        "SELECT a.*, DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p') AS formatted_date
         FROM app_versions a
         ORDER BY a.version_code DESC, a.id DESC"
    );
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        'ok' => true,
        'versions' => $versions
    ]);
}

/* ------------------------------------------- 3. PUBLISH NEW APP VERSION */
if ($first === '' && $method === 'POST') {
    $versionName = trim((string) ($body['version_name'] ?? ''));
    $versionCode = (int) ($body['version_code'] ?? 0);
    $releaseNotes = trim((string) ($body['release_notes'] ?? ''));
    $isMandatory = !empty($body['is_mandatory']) ? 1 : 0;

    if ($versionName === '') {
        jsonResponse(['ok' => false, 'message' => 'Version name (e.g. 1.1.0) is required.'], 422);
    }
    if ($versionCode <= 0) {
        jsonResponse(['ok' => false, 'message' => 'Target Version Code must be a valid positive integer (e.g. 2).'], 422);
    }
    if ($releaseNotes === '') {
        jsonResponse(['ok' => false, 'message' => 'Please provide Update Info / Release Notes explaining this release.'], 422);
    }

    $db = db();

    // Check if version_code already exists
    $chk = $db->prepare("SELECT id FROM app_versions WHERE version_code = ? LIMIT 1");
    $chk->execute([$versionCode]);
    if ($chk->fetch()) {
        jsonResponse(['ok' => false, 'message' => "Version Code $versionCode is already published. Use a higher integer code."], 422);
    }

    $targetDir = __DIR__ . '/../../public/download';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $apkUrl = '/public/download/Bloodlink_apk v1.0.apk';
    $fileSize = 0;

    // Handle uploaded file if present
    if (!empty($_FILES['apk_file']['name']) && is_uploaded_file($_FILES['apk_file']['tmp_name'])) {
        $file = $_FILES['apk_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['ok' => false, 'message' => 'File upload error code: ' . $file['error']], 400);
        }

        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext !== 'apk') {
            jsonResponse(['ok' => false, 'message' => 'Invalid file format. Please upload an Android .apk package.'], 422);
        }

        // Save versioned APK copy
        $versionedFilename = 'Bloodlink_apk_v' . $versionCode . '.apk';
        $destPath = $targetDir . '/' . $versionedFilename;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            jsonResponse(['ok' => false, 'message' => 'Failed to save uploaded APK to disk.'], 500);
        }

        // Also update standard download files
        $standard1 = $targetDir . '/Bloodlink_apk v1.0.apk';
        $standard2 = $targetDir . '/Bloodlink_apk_v1.0.apk';
        @copy($destPath, $standard1);
        @copy($destPath, $standard2);

        $fileSize = (int) filesize($destPath);
        $apkUrl = '/public/download/' . $versionedFilename;
    } else {
        // Use existing active APK file size if no new file uploaded
        $existingApk = $targetDir . '/Bloodlink_apk v1.0.apk';
        if (file_exists($existingApk)) {
            $fileSize = (int) filesize($existingApk);
        }
    }

    // Set previous versions as inactive
    $db->exec("UPDATE app_versions SET is_active = 0");

    // Insert new version
    $ins = $db->prepare(
        "INSERT INTO app_versions (version_name, version_code, release_notes, apk_file_url, file_size_bytes, is_mandatory, is_active, uploaded_by, uploaded_by_name, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, NOW())"
    );

    $uploaderName = $user['username'] ?? 'Superadmin';
    $ins->execute([
        $versionName,
        $versionCode,
        $releaseNotes,
        $apkUrl,
        $fileSize,
        $isMandatory,
        (int) $user['id'],
        $uploaderName
    ]);
    $newId = (int) $db->lastInsertId();

    // Log in audit trail
    try {
        $db->prepare("INSERT INTO activity_logs (user_id, action, description, created_at)
            VALUES (?, 'APP_UPDATE_PUBLISHED', ?, NOW())")
           ->execute([(int) $user['id'], "Published new mobile app version $versionName (Build $versionCode)."]);
    } catch (Throwable $e) {}

    jsonResponse([
        'ok' => true,
        'message' => "BloodLink version $versionName (Build $versionCode) published successfully! Mobile devices will now receive the update prompt.",
        'version_id' => $newId
    ]);
}

/* --------------------------------------------- 4. ACTIVATE A VERSION */
if (ctype_digit($first) && strtolower($segments[2] ?? '') === 'activate' && $method === 'POST') {
    $verId = (int) $first;
    $db = db();
    $db->exec("UPDATE app_versions SET is_active = 0");
    $db->prepare("UPDATE app_versions SET is_active = 1 WHERE id = ?")->execute([$verId]);

    jsonResponse(['ok' => true, 'message' => 'Version marked as active release.']);
}

/* ----------------------------------------------- 5. DELETE A VERSION */
if (ctype_digit($first) && $method === 'DELETE') {
    $verId = (int) $first;
    $db = db();

    $stmt = $db->prepare("SELECT is_active, apk_file_url FROM app_versions WHERE id = ?");
    $stmt->execute([$verId]);
    $ver = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ver) {
        jsonResponse(['ok' => false, 'message' => 'Version record not found.'], 404);
    }
    if ((int) $ver['is_active'] === 1) {
        jsonResponse(['ok' => false, 'message' => 'Cannot delete the currently active version. Activate another version first.'], 422);
    }

    $db->prepare("DELETE FROM app_versions WHERE id = ?")->execute([$verId]);
    jsonResponse(['ok' => true, 'message' => 'Version record deleted successfully.']);
}

jsonResponse(['ok' => false, 'message' => 'Endpoint not supported.'], 405);
