<?php
// geocode_reverse.php
// Proxies reverse geocoding (lat/lng -> address) to Nominatim.
// See geocode_search.php for why this runs server-side instead of the
// browser calling Nominatim directly.

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/geocode_lib.php';

$allowed_roles = ['admin', 'staff', 'warehouse'];
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed_roles)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;
if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}

$url = 'https://nominatim.openstreetmap.org/reverse?format=json'
     . '&lat=' . urlencode($lat)
     . '&lon=' . urlencode($lng)
     . '&zoom=18&addressdetails=1';

$result = deitrack_geocode_fetch($url);

if ($result === null) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Could not look up that address right now.']);
    exit;
}

$decoded = json_decode($result, true);
$label = is_array($decoded) ? ($decoded['display_name'] ?? null) : null;

if ($label === null) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Could not look up that address right now.']);
    exit;
}

echo json_encode(['success' => true, 'display_name' => $label]);