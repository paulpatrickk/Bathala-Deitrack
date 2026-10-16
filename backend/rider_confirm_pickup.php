<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'rider') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$rider_id = $_SESSION['user']['id'];
$order_id = (int)($_POST['order_id'] ?? 0);
$lat      = $_POST['lat'] ?? null;
$lng      = $_POST['lng'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order']);
    exit;
}

// Require proof photo
if (empty($_FILES['photo']['name'])) {
    echo json_encode(['success' => false, 'message' => 'Pickup proof photo is required']);
    exit;
}

require_once __DIR__ . '/upload_lib.php';
[$ok, $result] = save_uploaded_image($_FILES['photo'], __DIR__ . '/../assets/images/deliveries', 'pickup_' . $order_id, 20 * 1024 * 1024);
if (!$ok) {
    echo json_encode(['success' => false, 'message' => $result]);
    exit;
}
$photo_filename = $result;

// Self-assign rider if not yet assigned, attach pickup proof, and move to in_transit
// (this also starts the "live tracking" phase — the rider's GPS is already being
// broadcast continuously via rider_update_location.php)
$stmt = $conn->prepare("
    UPDATE orders
    SET status = 'in_transit', rider_id = ?, pickup_photo = ?
    WHERE id = ?
    AND (rider_id = ? OR rider_id IS NULL)
    AND status IN ('confirmed', 'assigned')
");
$stmt->bind_param("isii", $rider_id, $photo_filename, $order_id, $rider_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Log the rider's location at pickup time too, so it's fresh for the tracking page
    if ($lat && $lng) {
        $loc = $conn->prepare("
            INSERT INTO rider_locations (rider_id, lat, lng, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE lat=VALUES(lat), lng=VALUES(lng), updated_at=NOW()
        ");
        $loc->bind_param("idd", $rider_id, $lat, $lng);
        $loc->execute();
    }
    echo json_encode(['success' => true, 'order_id' => $order_id, 'pickup_photo' => $photo_filename]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order already taken, not found, or already picked up']);
}
?>
