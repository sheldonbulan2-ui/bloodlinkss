<?php
/**
 * php/routes/settings.php
 * -----------------------
 * GET /api/settings - the few non-sensitive display settings the
 * frontend needs (map centre + zoom + system name).
 *
 * Login is required, and only whitelisted keys are returned so a
 * future secret in the settings table can never leak here.
 */
declare(strict_types=1);

if ($method !== 'GET' || isset($segments[1])) {
    jsonResponse(['ok' => false, 'message' => 'Unknown API endpoint.'], 404);
}

// Non-sensitive public display settings (system name, map center, zoom)
$all = allSettings();
$public = [];
foreach (['system_name', 'map_center_lat', 'map_center_lng', 'default_zoom'] as $key) {
    if (isset($all[$key])) $public[$key] = $all[$key];
}
jsonResponse(['ok' => true, 'settings' => $public]);
