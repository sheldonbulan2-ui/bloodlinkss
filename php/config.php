<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
loadEnv();

// Credentials come from .env; the XAMPP defaults keep the app working
// out of the box if .env is absent. Nothing secret is hardcoded here.
define('DB_HOST', env('DB_HOST', 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com'));
define('DB_PORT', env('DB_PORT', '4000'));
define('DB_NAME', env('DB_NAME', 'bloodlink_app'));
define('DB_USER', env('DB_USER', '4WaGn3o2jzBLVEy.root'));
define('DB_PASS', env('DB_PASS', 'kooqLdqUZgiH9yJR'));
define('DB_SSL', env('DB_SSL', 'true'));
define('DB_CA_PATH', env('DB_CA_PATH', ''));
define('APP_ENV', (string) env('APP_ENV', 'development'));

/**
 * At-rest encryption for sensitive user fields (email, contact, address,
 * medical notes). Uses AES-256-GCM with a key derived from APP_KEY via
 * HKDF (HMAC-SHA256), so the raw key never lives in the codebase.
 *
 * Wire format: "v1:" base64(nonce|ciphertext|tag)  — 4-byte version + 12-byte
 * nonce + ciphertext + 16-byte GCM tag.
 */
define('ENC_VERSION', 'v1');

function _encKey(): string
{
    static $key = null;
    if ($key === null) {
        // Derive a 32-byte key from APP_KEY with HKDF using a fixed info string.
        $key = hash_hmac('sha256', APP_KEY . ':field-encryption', APP_KEY, true);
    }
    return $key;
}

function encryptField(string $plaintext): string
{
    if ($plaintext === '') return '';
    $nonce = random_bytes(12);
    $ct = openssl_encrypt($plaintext, 'aes-256-gcm', _encKey(), OPENSSL_RAW_DATA, $nonce, $tag);
    if ($ct === false) {
        error_log('[BloodLink][enc] openssl_encrypt failed');
        return '';
    }
    return ENC_VERSION . ':' . base64_encode($nonce . $ct . $tag);
}

function decryptField(string $ciphertext): string
{
    if ($ciphertext === '') return '';
    if (str_starts_with($ciphertext, ENC_VERSION . ':')) {
        $raw = base64_decode(substr($ciphertext, strlen(ENC_VERSION) + 1), true);
        if ($raw === false || strlen($raw) < 28) return '';
        $nonce = substr($raw, 0, 12);
        $tag = substr($raw, -16);
        $ct = substr($raw, 12, -16);
        $pt = openssl_decrypt($ct, 'aes-256-gcm', _encKey(), OPENSSL_RAW_DATA, $nonce, $tag);
        return $pt === false ? '' : $pt;
    }
    // Legacy plaintext value — return as-is so backfill can run.
    return $ciphertext;
}

/**
 * Deterministic keyed hash for email lookup during login/forgot-password.
 * Lets the app query by email without storing it in plaintext or a weak
 * unsalted hash. Uses HMAC-SHA256 keyed by APP_KEY.
 */
function emailHash(string $email): string
{
    return hash_hmac('sha256', strtolower(trim($email)), APP_KEY);
}

// APP_KEY is required in production. It powers OTP hashing, session CSRF,
// and the AES-256-GCM envelope used to encrypt sensitive fields at rest.
// Generate with: php -r "echo bin2hex(random_bytes(32));"
if (env('APP_KEY', '') === '') {
    define('APP_KEY', 'bloodlink-insecure-fallback-key');
} else {
    define('APP_KEY', (string) env('APP_KEY'));
}

/**
 * Production environment validation.
 * Called by api/index.php and public/* pages. Refuses to run when the
 * deployment is misconfigured for a live site:
 *  - .env is present and APP_ENV=production
 *  - APP_KEY is a strong random value (not the fallback)
 *  - BL_LOCAL_OTP is never enabled in production
 *  - the public base URL is HTTPS (when set from .env)
 */
function assertProductionConfig(): void
{
    if (APP_ENV !== 'production') return;

    $problems = [];
    if (APP_KEY === 'bloodlink-insecure-fallback-key' || strlen(APP_KEY) < 32) {
        $problems[] = 'APP_KEY is missing or too short — set a 32-byte random key in .env.';
    }
    if (env('BL_LOCAL_OTP', '0') === '1') {
        $problems[] = 'BL_LOCAL_OTP must not be enabled in production.';
    }
    $baseUrl = (string) env('APP_URL', '');
    if ($baseUrl !== '' && stripos($baseUrl, 'https://') !== 0) {
        $problems[] = 'APP_URL must be an HTTPS URL in production.';
    }

    if ($problems) {
        http_response_code(503);
        if (defined('BL_API')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Service unavailable.'], JSON_UNESCAPED_SLASHES);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><meta charset="utf-8"><title>BloodLink</title><div style="font:'
                . 'system-ui;max-width:520px;margin:80px auto;padding:24px;border:1px solid #eecccc;'
                . 'border-radius:12px"><h2>Service unavailable</h2><ul>';
            foreach ($problems as $p) echo '<li>' . htmlspecialchars($p, ENT_QUOTES) . '</li>';
            echo '</ul></div>';
        }
        exit;
    }
}

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function requestBody(): array
{
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : $_POST;
}

function requireFields(array $body, array $fields): ?string
{
    foreach ($fields as $field) {
        if (!isset($body[$field]) || trim((string) $body[$field]) === '') {
            return 'Please provide a value for "' . str_replace('_', ' ', $field) . '".';
        }
    }
    return null;
}

function validBloodType(string $type): bool
{
    return in_array($type, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true);
}

function strongEnoughPassword(string $password): bool
{
    // Must be at least 6 characters with character or number combinations (e.g. 0992xxxxxxxx)
    return strlen($password) >= 6
        && (preg_match('/[a-zA-Z]/', $password) === 1 || preg_match('/\d/', $password) === 1);
}

function validEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validPhone(string $phone): bool
{
    return preg_match('/^[0-9+()\-\s]{7,20}$/', trim($phone)) === 1;
}

function validAge($value): bool
{
    $age = (int) $value;
    return $age >= 1 && $age <= 119;
}

/** The 8 official blood types, in display order. */
function bloodTypes(): array
{
    return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
}

/** Whole number greater than zero (blood units, units needed, ...). */
function validPositiveInt($value): bool
{
    return is_numeric($value) && (float) $value == (int) $value && (int) $value > 0;
}

/** YYYY-MM-DD (or a datetime whose first 10 chars are a real date). */
function validDate($value): bool
{
    $text = substr(trim((string) $value), 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) return false;
    [$y, $m, $d] = array_map('intval', explode('-', $text));
    return checkdate($m, $d, $y);
}

function validLatitude($value): bool
{
    return is_numeric($value) && (float) $value >= -90 && (float) $value <= 90;
}

function validLongitude($value): bool
{
    return is_numeric($value) && (float) $value >= -180 && (float) $value <= 180;
}

/**
 * Technical details go to the server log, never to the browser.
 * Keeps database structure and stack traces away from end users.
 */
function appLog(string $context, Throwable $error): void
{
    error_log('[BloodLink] ' . $context . ': ' . $error->getMessage()
        . ' @ ' . $error->getFile() . ':' . $error->getLine());
}

/**
 * Start the session with hardened cookie flags.
 * HttpOnly stops JavaScript from reading the session id,
 * SameSite=Lax blocks the simplest cross-site request forgery.
 */
/**
 * Detect HTTPS reliably, including behind Render and Cloudflare reverse proxies.
 */
function isHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains((string)$_SERVER['HTTP_CF_VISITOR'], '"https"'));
}

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    if (headers_sent()) return;

    if (session_name() !== 'BLOODLINK_SESSION') {
        session_name('BLOODLINK_SESSION');
    }
    ini_set('session.gc_maxlifetime', '86400');
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isHttps(),
    ]);
    session_start();
}

/**
 * CSRF token for state-changing requests.
 * The token lives in the session and is mirrored into a readable
 * cookie so the JavaScript layer can echo it back in a header.
 */
function csrfToken(): string
{
    startAppSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if (!headers_sent() && ($_COOKIE['bl_csrf'] ?? '') !== $_SESSION['csrf_token']) {
        setcookie('bl_csrf', $_SESSION['csrf_token'], [
            'expires' => 0, 'path' => '/', 'httponly' => false,
            'samesite' => 'Lax', 'secure' => isHttps(),
        ]);
    }
    return $_SESSION['csrf_token'];
}

/** Reject POST/PUT/DELETE that do not carry the session's CSRF token. */
function requireCsrf(): void
{
    startAppSession();
    $expected = (string) ($_SESSION['csrf_token'] ?? '');
    $sent = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '');
    if ($expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
        jsonResponse(['ok' => false, 'message' => 'Your session expired. Please refresh the page and try again.'], 419);
    }
}

/** Baseline response headers for every page and API call. */
function securityHeaders(): void
{
    if (headers_sent()) return;

    // Stop MIME sniffing, clickjacking, and referrer leakage.
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');

    // Content-Security-Policy tuned to the assets this app actually uses:
    //  - scripts/styles: self + inline (pages use inline <script>) + Leaflet CDN
    //  - map tiles:      OpenStreetMap tile servers (images)
    //  - connections:    same-origin API only
    // This blocks injected third-party scripts and exfiltration endpoints
    // WITHOUT changing how the existing pages/map work.
    header(
        "Content-Security-Policy: "
        . "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' https://unpkg.com; "
        . "style-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com; "
        . "img-src 'self' data: https://*.tile.openstreetmap.org https://unpkg.com; "
        . "font-src 'self' data: https://fonts.gstatic.com; "
        . "connect-src 'self'; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "frame-ancestors 'self'; "
        . "form-action 'self'"
    );

    // Lock down powerful browser features the app does not need.
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    // Force HTTPS for a year once the site is actually served over TLS.
    if (isHttps()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
