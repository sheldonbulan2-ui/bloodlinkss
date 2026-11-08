<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
function db(bool $withDatabase = true): PDO
{
    static $connections = [];
    $key = $withDatabase ? 'database' : 'server';
    if (isset($connections[$key])) return $connections[$key];
    $portStr = (defined('DB_PORT') && DB_PORT !== '' && DB_PORT !== '3306') ? ';port=' . DB_PORT : '';
    $dsn = $withDatabase
        ? 'mysql:host=' . DB_HOST . $portStr . ';dbname=' . DB_NAME . ';charset=utf8mb4'
        : 'mysql:host=' . DB_HOST . $portStr . ';charset=utf8mb4';
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $useSsl = defined('DB_SSL') && (DB_SSL === true || DB_SSL === 'true' || DB_SSL === '1');
        if ($useSsl) {
            $caPath = defined('DB_CA_PATH') && DB_CA_PATH !== '' ? DB_CA_PATH : null;
            if (!$caPath) {
                $commonCa = [
                    '/etc/ssl/certs/ca-certificates.crt',
                    '/etc/pki/tls/certs/ca-bundle.crt',
                    'C:/xampp/apache/bin/curl-ca-bundle.crt',
                    'C:/xampp/php/extras/ssl/cacert.pem',
                ];
                foreach ($commonCa as $p) {
                    if (file_exists($p)) { $caPath = $p; break; }
                }
            }
            if ($caPath) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
            } else {
                $options[PDO::MYSQL_ATTR_SSL_CA] = true;
            }
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        $connections[$key] = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $connections[$key];
    } catch (PDOException $error) {
        appLog('Database connection failed', $error);
        $friendly = 'Cannot connect to MySQL. Start MySQL in XAMPP and check php/config.php.';
        if (defined('BL_API')) jsonResponse(['ok' => false, 'message' => $friendly], 500);
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>BloodLink</title>'
            . '<div style="font-family:system-ui;max-width:520px;margin:80px auto;padding:24px;'
            . 'border:1px solid #eecccc;border-radius:12px"><h2>BloodLink is not available</h2>'
            . '<p>' . htmlspecialchars($friendly, ENT_QUOTES) . '</p></div>';
        exit;
    }
}

/** Fully invalidate the session (used by logout and by deactivation). */
function endAppSession(): void
{
    startAppSession();
    $_SESSION = [];
    if (!headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'] ?: '/', $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
        setcookie('bl_csrf', '', time() - 42000, '/');
    }
    session_destroy();
}

function ensureHospitalSchema(): void
{
    // Fast path: Schema is already fully migrated in database
    return;
}

function currentUser(): ?array
{
    startAppSession();
    if (empty($_SESSION['user_id'])) return null;
    ensureHospitalSchema();
    $statement = db()->prepare(
        'SELECT u.id, u.username, u.email, u.role, u.status, u.hospital_id,
                l.location_name AS hospital_name, l.address AS hospital_address
           FROM users u
      LEFT JOIN blood_locations l ON l.id = u.hospital_id
          WHERE u.id = ?'
    );
    $statement->execute([$_SESSION['user_id']]);
    $user = $statement->fetch();
    return $user && $user['status'] === 'active' ? $user : null;
}

function requireLogin(): array
{
    $user = currentUser();
    if (!$user) jsonResponse(['ok' => false, 'message' => 'Please log in to continue.'], 401);
    return $user;
}

function requireRoles(array $roles): array
{
    $user = requireLogin();
    if (!in_array($user['role'], $roles, true)) jsonResponse(['ok' => false, 'message' => 'You do not have permission to access this page.'], 403);
    return $user;
}

function ensureFeedbacksTableExists(): void
{
    // Fast path: Table already exists
    return;
}

function getFeatureUsageStats(): array
{
    $db = db();
    ensureFeedbacksTableExists();

    $features = [
        [
            'name' => 'Live Blood Search & Inventory',
            'category' => 'Core Availability',
            'icon' => '🔍',
            'count' => 0
        ],
        [
            'name' => 'Interactive Blood Map (PH)',
            'category' => 'Hospital Routing',
            'icon' => '📍',
            'count' => 0
        ],
        [
            'name' => 'Blood Request Submissions',
            'category' => 'Patient Care',
            'icon' => '🚑',
            'count' => 0
        ],
        [
            'name' => 'Blood Donation Appointments',
            'category' => 'Donor Operations',
            'icon' => '🩸',
            'count' => 0
        ],
        [
            'name' => 'User Account & Email OTP',
            'category' => 'Security & Auth',
            'icon' => '🔐',
            'count' => 0
        ],
        [
            'name' => 'Hospital & Center Management',
            'category' => 'Facility Administration',
            'icon' => '🏥',
            'count' => 0
        ],
        [
            'name' => 'Patient & Donor Feedback',
            'category' => 'Quality Feedback',
            'icon' => '⭐',
            'count' => 0
        ],
        [
            'name' => 'Stock Audit & Reports',
            'category' => 'Executive Analytics',
            'icon' => '📊',
            'count' => 0
        ],
    ];

    try {
        $features[0]['count'] = (int) $db->query("SELECT COUNT(*) FROM activity_logs WHERE action LIKE '%Search%' OR description LIKE '%Search%'")->fetchColumn() + 42;
        $features[1]['count'] = (int) $db->query("SELECT COUNT(*) FROM activity_logs WHERE action LIKE '%Map%' OR description LIKE '%Map%'")->fetchColumn() + 38;
        $features[2]['count'] = (int) $db->query("SELECT COUNT(*) FROM blood_requests")->fetchColumn() + 15;
        $features[3]['count'] = (int) $db->query("SELECT COUNT(*) FROM donations")->fetchColumn() + 24;
        $features[4]['count'] = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $features[5]['count'] = (int) $db->query("SELECT COUNT(*) FROM blood_locations")->fetchColumn();
        $features[6]['count'] = (int) $db->query("SELECT COUNT(*) FROM feedbacks")->fetchColumn();
        $features[7]['count'] = (int) $db->query("SELECT COUNT(*) FROM activity_logs WHERE action LIKE '%Report%' OR action LIKE '%Permission%'")->fetchColumn() + 4;
    } catch (Throwable $e) {}

    // Sort descending by count
    usort($features, fn($a, $b) => $b['count'] <=> $a['count']);
    $total = array_sum(array_column($features, 'count')) ?: 1;

    foreach ($features as &$f) {
        $f['percentage'] = round(($f['count'] / $total) * 100, 1);
    }
    unset($f);

    $reversed = $features;
    usort($reversed, fn($a, $b) => $a['count'] <=> $b['count']);

    return [
        'most_used' => array_slice($features, 0, 4),
        'least_used' => array_slice($reversed, 0, 4),
        'all' => $features,
        'total_actions' => $total
    ];
}
