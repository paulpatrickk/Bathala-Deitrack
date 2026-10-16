<?php
// geocode_search.php
// Proxies address search to OpenStreetMap's Nominatim service.
//
// Why this exists: the POS location picker used to call Nominatim directly
// from the browser (fetch straight to nominatim.openstreetmap.org). Nominatim's
// usage policy requires requests to carry a real identifying User-Agent, which
// browser fetch() is not allowed to set -- the browser silently overrides it.
// Depending on the visitor's browser/extensions/network, that gets requests
// answered inconsistently (sometimes blocked, sometimes fine), which looked
// like "search just doesn't work" with no visible error, since the old code
// also swallowed fetch failures.
//
// Calling Nominatim from here instead means: one identifying User-Agent we
// control, one server making the request instead of every visitor's browser,
// and a real JSON error message the frontend can actually show.

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/geocode_lib.php';

$allowed_roles = ['admin', 'staff', 'warehouse'];
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed_roles)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');
if ($query === '' || mb_strlen($query) < 3) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$url = 'https://nominatim.openstreetmap.org/search?format=json'
     . '&q=' . urlencode($query)
     . '&limit=5&addressdetails=0&countrycodes=ph';

$result = deitrack_geocode_fetch($url);

if ($result === null) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Address search is temporarily unavailable. You can still drag the pin on the map.']);
    exit;
}

$decoded = json_decode($result, true);
if (!is_array($decoded)) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Address search returned an unexpected response.']);
    exit;
}

$results = array_map(function ($r) {
    return [
        'lat'          => $r['lat'] ?? null,
        'lon'          => $r['lon'] ?? null,
        'display_name' => $r['display_name'] ?? '',
    ];
}, $decoded);

echo json_encode(['success' => true, 'results' => $results]);