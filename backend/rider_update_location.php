<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'rider') {
    echo json_encode(['success' => false]);
    exit;
}

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) { echo json_encode(['success' => false]); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$rider_id = $_SESSION['user']['id'];
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;

if (!$lat || !$lng) {
    echo json_encode(['success' => false]);
    exit;
}

// Upsert rider location into rider_locations table
$stmt = $conn->prepare("
    INSERT INTO rider_locations (rider_id, lat, lng, updated_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE lat=VALUES(lat), lng=VALUES(lng), updated_at=NOW()
");
$stmt->bind_param("idd", $rider_id, $lat, $lng);
echo json_encode(['success' => $stmt->execute()]);
?>
