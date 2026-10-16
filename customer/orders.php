<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /customer_login.php");
    exit;
}
$customer = $_SESSION['customer'];

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) die("Connection failed");

$stmt = $conn->prepare("SELECT o.*, GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC");
$stmt->bind_param("i", $customer['id']);
$stmt->execute();
$orders = $stmt->get_result();

$orders_data = [];
while ($row = $orders->fetch_assoc()) {
    $orders_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - DeiTrack</title>

<!-- PWA -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#00a8c6">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="DeiTrack">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/customer.css">
<style>
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }
.orders-table tbody tr { cursor:pointer; transition:background 0.15s; }
.orders-table tbody tr:hover td { background:#f0f9ff; }
.status-badge { display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600; }
.status-badge.pending    { background:#fef3c7; color:#d97706; }
.status-badge.assigned   { background:#ede9fe; color:#7c3aed; }
.status-badge.in_transit { background:#dbeafe; color:#2563eb; }
.status-badge.delivering { background:#dbeafe; color:#2563eb; }
.status-badge.delivered  { background:#dcfce7; color:#16a34a; }
.status-badge.cancelled  { background:#fee2e2; color:#dc2626; }
.status-badge.paid       { background:#dcfce7; color:#16a34a; }
</style>
</head>
<body>

<div class="customer-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <button class="hamburger" onclick="toggleSidebar()" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>
            <div style="display:flex;align-items:center;gap:12px;">
                <a href="/customer/dashboard.php" class="back-btn" title="Back to Shop">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <div class="page-title">My Orders</div>
                    <div class="page-subtitle">Track and view your order history</div>
                </div>
            </div>
        </div>

        <div class="page-content">
            <?php if (!empty($orders_data)): ?>
            <div class="orders-table-wrap">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Products</th>
                            <th>Total</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_data as $order):
                            $oid       = (int)$order['id'];
                            $status    = $order['status'] ?? '';
                            $typeRaw   = strtolower(trim($order['order_type'] ?? ''));
                            $isDelivery = ($typeRaw === 'delivery');
                            $icons  = ['pending'=>'bi-clock','paid'=>'bi-check-circle-fill','assigned'=>'bi-person-check','in_transit'=>'bi-bicycle','delivering'=>'bi-bicycle','delivered'=>'bi-check2-all','cancelled'=>'bi-x-circle'];
                            $labels = ['pending'=>'Pending','paid'=>'Paid','assigned'=>'Rider Assigned','in_transit'=>'On the Way','delivering'=>'On the Way','delivered'=>'Delivered','cancelled'=>'Cancelled'];
                            $icon      = $icons[$status]  ?? 'bi-clock';
                            $label     = $labels[$status] ?? ucfirst($status);
                            $badgeCls  = ($status === 'delivering') ? 'in_transit' : $status;
                            $inTransit = in_array($status, ['in_transit','delivering']);
                        ?>
                        <tr onclick="window.location.href='/customer/track.php?id=<?php echo $oid; ?>'">
                            <td><strong>#<?php echo str_pad($oid, 4, '0', STR_PAD_LEFT); ?></strong></td>
                            <td style="max-width:200px;font-size:0.82rem;color:#64748b;">
                                <?php echo htmlspecialchars($order['product_names'] ?? '-'); ?>
                            </td>
                            <td>
                                <strong style="font-family:'Syne',sans-serif;color:#0d1b2a;">
                                    &#8369;<?php echo number_format($order['total'], 2); ?>
                                </strong>
                            </td>
                            <td>
                                <span style="font-size:0.82rem;">
                                    <?php echo $isDelivery ? '🚴 Delivery' : '🏪 Walk-in'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $badgeCls; ?>">
                                    <i class="bi <?php echo $icon; ?>"></i>
                                    <?php echo $label; ?>
                                </span>
                            </td>
                            <td style="font-size:0.82rem;color:#94a3b8;">
                                <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <?php if ($isDelivery && $status !== 'cancelled'): ?>
                                <a href="/customer/track.php?id=<?php echo $oid; ?>"
                                   style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#f0fafd;border:1.5px solid #bae6fd;border-radius:8px;font-size:0.78rem;font-weight:600;color:#0284c7;text-decoration:none;">
                                    <i class="bi bi-geo-alt"></i> Track
                                    <?php if ($inTransit): ?>
                                    <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;animation:blink 1.2s infinite;display:inline-block;"></span>
                                    <?php endif; ?>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-receipt"></i>
                <h4>No Orders Yet</h4>
                <p>You haven't placed any orders yet.<br>
                <a href="/customer/dashboard.php" style="color:#00a8c6;font-weight:600;">Start shopping &rarr;</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="toast" id="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="toastMsg"></span>
</div>

<script src="/assets/js/pwa-install.js"></script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}
</script>
</body>
</html>