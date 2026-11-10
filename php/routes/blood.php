<?php
/**
 * php/routes/blood.php
 * --------------------
 * Blood availability: type list, search, map data.
 * Tables: blood_inventory, blood_locations.
 *
 * These endpoints are PUBLIC on purpose: the landing page invites
 * anyone to search available blood and view the map without logging
 * in. No donor or patient personal data is returned here - only the
 * location, its contact number and the unit counts.
 */
declare(strict_types=1);

$action = strtolower($segments[1] ?? '');

/* --------------------------------------------------- the 8 types */
if ($action === 'types' && $method === 'GET') {
    jsonResponse(['ok' => true, 'types' => bloodTypes()]);
}

/* ------------------------------------------------------- search */
if ($action === 'search' && $method === 'GET') {
    $type = strtoupper(trim((string) ($_GET['blood_type'] ?? 'ALL')));
    if ($type === '') $type = 'ALL';
    if ($type !== 'ALL' && !validBloodType($type)) {
        jsonResponse(['ok' => false, 'message' => 'Invalid blood type. Please select one of the standard types.'], 400);
    }

    $rawLocation = trim((string) ($_GET['location_id'] ?? ''));
    if ($rawLocation !== '' && !ctype_digit($rawLocation)) {
        jsonResponse(['ok' => false, 'message' => 'Invalid location filter.'], 400);
    }
    $locationId = $rawLocation === '' ? null : (int) $rawLocation;

    $locations = groupAvailability(getAvailability($type === 'ALL' ? null : $type, $locationId));
    $totals = getTotalsByType();

    $selectedTotal = null;
    if ($type !== 'ALL') {
        $selectedTotal = 0;
        foreach ($locations as $location) $selectedTotal += $location['blood'][$type] ?? 0;
    }

    jsonResponse([
        'ok' => true,
        'blood_type' => $type,
        'locations' => $locations,
        'totals' => $totals,
        'selected_total' => $selectedTotal,
        'result_count' => count($locations),
    ]);
}

/* ---------------------------------------------------------- map */
if ($action === 'map' && $method === 'GET') {
    jsonResponse(['ok' => true, 'locations' => groupAvailability(getAvailability())]);
}

/* ---------------------------------------------------- locations */
if ($action === 'locations' && $method === 'GET') {
    $rows = db()->query("SELECT id, location_name, address, latitude, longitude, contact_number, available_status FROM blood_locations WHERE available_status = 'Active' ORDER BY location_name ASC")->fetchAll();
    jsonResponse(['ok' => true, 'locations' => $rows]);
}

/* ---------------------------------------------------- fee transparency */
if ($action === 'fees' && $method === 'GET') {
    $fees = db()->query("SELECT * FROM blood_fee_schedule WHERE is_active = 1 ORDER BY total_fee DESC")->fetchAll();
    jsonResponse(['ok' => true, 'fees' => $fees]);
}
