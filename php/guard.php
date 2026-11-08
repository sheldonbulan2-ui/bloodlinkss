<?php
/**
 * php/guard.php
 * -------------
 * Server-side gate for the HTML pages in /public.
 * Web platform supports Hospital Staff, Hospital Administrators, Superadmins,
 * Blood Donors, and Patients.
 */
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/** Path prefix the app is installed under, e.g. /Bloodlinks. */
function appBase(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $position = strpos($script, '/public/');
    if ($position !== false) return substr($script, 0, $position);
    return rtrim(dirname($script), '/');
}

/**
 * Require a logged-in user, optionally with one of $roles.
 * Returns the user row so the page can use it if needed.
 */
function requirePage(array $roles = []): array
{
    assertProductionConfig();
    securityHeaders();
    startAppSession();
    $user = currentUser();
    $base = appBase();

    if (!$user) {
        $next = (string) ($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . $base . '/login.php?next=' . urlencode($next));
        exit;
    }

    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
        header('Location: ' . $base . '/dashboard.php?denied=1');
        exit;
    }
    csrfToken(); // make sure the page can send state-changing requests
    return $user;
}

/** For public pages (landing/login): headers + CSRF cookie only. */
function publicPage(): void
{
    assertProductionConfig();
    securityHeaders();
    startAppSession();
    csrfToken();
}
