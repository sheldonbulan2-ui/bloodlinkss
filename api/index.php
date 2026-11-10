<?php
/**
 * api/index.php
 * -------------
 * Single entry point for every /api/... request (see .htaccess).
 *
 * Responsibilities kept here on purpose:
 *   1. parse the URL into module / action / id segments
 *   2. apply the site-wide security basics (headers, session, CSRF)
 *   3. hand the request to one small module file in php/routes/
 *
 * Every module file returns JSON through jsonResponse() and exits.
 */
declare(strict_types=1);
define('BL_API', true);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header("Content-Type: application/json; charset=UTF-8");
        }
        if (ob_get_length()) ob_clean();
        echo json_encode(["ok" => false, "message" => "Server error: " . $err['message']]);
    }
});
ob_start();

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/helpers.php';
require_once __DIR__ . '/../php/ratelimit.php';

assertProductionConfig();
securityHeaders();
header('Cache-Control: no-store');
startAppSession();

// -------------------------------------------------------------------------
// Application-layer flood protection (basic DDoS/DoS mitigation).
// Every API caller is capped at 150 requests per 60 seconds per IP.
// Legitimate use stays well under this; scripted floods get 429 + Retry-After.
// (For true volumetric DDoS you still need protection at the web-server /
//  network / CDN layer — this guards the application tier.)
// -------------------------------------------------------------------------
rateLimitGc();
[$allowed, $retryAfter] = rateLimitHit('api:' . clientIp(), 150, 60);
if (!$allowed) {
    header('Retry-After: ' . $retryAfter);
    jsonResponse(['ok' => false, 'message' => 'Too many requests. Please slow down and try again shortly.'], 429);
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
$parts = array_values(array_filter(explode('/', $path), static fn($piece) => $piece !== ''));
$apiIndex = array_search('api', $parts, true);
$segments = $apiIndex === false ? [] : array_values(array_slice($parts, $apiIndex + 1));

$module = strtolower($segments[0] ?? '');
$body = requestBody();

// State-changing requests must carry the session's CSRF token.
// Exception: register and forgot-password are anonymous endpoints and
// do NOT require a prior session/CSRF token.
$action = strtolower($segments[1] ?? '');
$anonymousActions = ['register', 'login', 'forgot-password', 'resend-otp', 'verify-otp', 'verify-reset-otp', 'resend-reset-otp', 'reset-password'];
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
    && !($action !== '' && in_array($action, $anonymousActions, true))
) {
    requireCsrf();
}

$modules = [
    'auth' => 'auth.php',
    'blood' => 'blood.php',
    'dashboard' => 'dashboard.php',
    'donor' => 'donor.php',
    'patient' => 'patient.php',
    'notifications' => 'notifications.php',
    'announcements' => 'announcements.php',
    'app-versions' => 'app_versions.php',
    'app-version' => 'app_versions.php',
    'settings' => 'settings.php',
    'staff' => 'staff.php',
    'admin' => 'admin.php',
    'feedback' => 'feedback.php',
];

try {
    if (!isset($modules[$module])) {
        jsonResponse(['ok' => false, 'message' => 'Unknown API endpoint.'], 404);
    }
    require __DIR__ . '/../php/routes/' . $modules[$module];
    jsonResponse(['ok' => false, 'message' => 'Unknown API endpoint.'], 404);
} catch (Throwable $error) {
    appLog('API ' . $method . ' /' . $path, $error);
    // In development, surface the real reason so bugs are diagnosable.
    // In production this stays a generic message (no internals leaked).
    $detail = (env('APP_ENV', 'production') === 'development')
        ? (': ' . $error->getMessage() . ' @ ' . basename($error->getFile()) . ':' . $error->getLine())
        : '';
    jsonResponse(['ok' => false, 'message' => 'Something went wrong on the server. Please try again.' . $detail], 500);
}
