<?php
session_start();
header('Content-Type: application/json');

// Admin-only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// A rider is considered "online" if we've heard from them in the last 60s
// (the rider app broadcasts every 12s), otherwise flag as stale so the
// admin UI can grey them out instead of pretending they're live forever.
$sql = "
    SELECT
        u.id                                     AS rider_id,
        u.name                                    AS name,
        rl.lat,
        rl.lng,
        rl.updated_at,
        TIMESTAMPDIFF(SECOND, rl.updated_at, NOW()) AS seconds_ago
    FROM rider_locations rl
    JOIN users u ON u.id = rl.rider_id
    WHERE u.role = 'rider'
    ORDER BY rl.updated_at DESC
";

$res = $conn->query($sql);
$riders = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $riders[] = [
            'rider_id'    => (int)$row['rider_id'],
            'name'        => $row['name'],
            'lat'         => (float)$row['lat'],
            'lng'         => (float)$row['lng'],
            'updated_at'  => $row['updated_at'],
            'seconds_ago' => (int)$row['seconds_ago'],
            'online'      => (int)$row['seconds_ago'] <= 60,
        ];
    }
}

echo json_encode(['success' => true, 'riders' => $riders]);
