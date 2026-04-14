<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /Bathala/LandingPage/customer_login.php");
    exit;
}
$customer = $_SESSION['customer'];

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

// Fetch customer orders
$stmt = $conn->prepare("SELECT o.*, GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.customer_name = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC");
$stmt->bind_param("s", $customer['name']);
$stmt->execute();
$orders = $stmt->get_result();

$pending_count = 0;
$orders_data = [];
while ($row = $orders->fetch_assoc()) {
    $orders_data[] = $row;
    if ($row['status'] === 'pending') $pending_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - DeiTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="/Bathala/assets/css/customer.css">
</head>
<body>

<div class="customer-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <div class="page-title">My Orders</div>
                <div class="page-subtitle">Track and view your order history</div>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_data as $order): ?>
                        <tr>
                            <td><strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                            <td style="max-width:200px;font-size:0.82rem;color:#64748b;">
                                <?php echo htmlspecialchars($order['product_names'] ?? '-'); ?>
                            </td>
                            <td>
                                <strong style="font-family:'Syne',sans-serif;color:#0d1b2a;">
                                    ₱<?php echo number_format($order['total'], 2); ?>
                                </strong>
                            </td>
                            <td>
                                <span style="font-size:0.82rem;">
                                    <?php echo $order['order_type'] === 'walkin' ? '🏪 Walk-in' : '🚴 Delivery'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php
                                    $icons = [
                                        'pending'    => 'bi-clock',
                                        'paid'       => 'bi-check-circle-fill',
                                        'delivering' => 'bi-truck',
                                        'delivered'  => 'bi-check2-all',
                                        'cancelled'  => 'bi-x-circle'
                                    ];
                                    $icon = $icons[$order['status']] ?? 'bi-circle';
                                    ?>
                                    <i class="bi <?php echo $icon; ?>"></i>
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td style="font-size:0.82rem;color:#94a3b8;">
                                <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
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
                <a href="/Bathala/customer/dashboard.php" style="color:#00a8c6;font-weight:600;">Start shopping →</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="toast" id="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="toastMsg"></span>
</div>

</body>
</html>
