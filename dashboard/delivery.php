<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'rider') {
    header("Location: /login.php");
    exit;
}
$user     = $_SESSION['user'];
$rider_id = $user['id'];

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) die("Connection failed");

// Canonical statuses: pending → assigned → in_transit → delivered
$deliveries = [];
$result = $conn->query("
    SELECT o.*,
           GROUP_CONCAT(CONCAT(p.name, ' × ', oi.quantity) SEPARATOR ', ') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE (
        (o.rider_id = $rider_id AND o.status IN ('assigned', 'in_transit'))
        OR
                (o.rider_id IS NULL AND o.status = 'confirmed' AND o.order_type = 'delivery')
    )
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
while ($row = $result->fetch_assoc()) $deliveries[] = $row;

$completed  = $conn->query("SELECT COUNT(*) as c FROM orders WHERE rider_id=$rider_id AND status='delivered'")->fetch_assoc()['c'];
$active     = count($deliveries);
$today_done = $conn->query("SELECT COUNT(*) as c FROM orders WHERE rider_id=$rider_id AND status='delivered' AND DATE(delivered_at)=CURDATE()")->fetch_assoc()['c'];

// Fetch all completed orders (current + previous)
$past_orders = [];
$past_result = $conn->query("
    SELECT o.id, o.customer_name, o.customer_address, o.total, o.delivered_at, o.created_at,
           o.delivery_photo, o.delivery_notes,
           GROUP_CONCAT(CONCAT(p.name, ' × ', oi.quantity) SEPARATOR ', ') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.rider_id = $rider_id AND o.status = 'delivered'
    GROUP BY o.id
    ORDER BY o.delivered_at DESC
    LIMIT 50
");
while ($row = $past_result->fetch_assoc()) $past_orders[] = $row;

$rider_name    = $user['name'] ?? $user['email'];
$rider_initial = strtoupper(substr($rider_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Rider — DeiTrack</title>

<!-- PWA -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#00a8c6">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="DeiTrack">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="/assets/css/rider.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/rider.css'); ?>">
<style>
/* ── DELIVERED SUCCESS MODAL ── */
.success-modal .modal-sheet {
    text-align: center;
    padding: 32px 24px;
}
.success-icon-wrap {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; color: white;
    margin: 0 auto 16px;
    box-shadow: 0 8px 32px rgba(34,197,94,0.35);
    animation: pop-in 0.4s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes pop-in {
    from { transform: scale(0); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}
.success-title {
    font-family: 'Syne', sans-serif;
    font-weight: 800; font-size: 1.3rem;
    color: var(--dark); margin-bottom: 4px;
}
.success-sub { color: var(--muted); font-size: 0.85rem; margin-bottom: 24px; }
.success-order-box {
    background: var(--bg);
    border-radius: 14px;
    padding: 14px 18px;
    margin-bottom: 20px;
    text-align: left;
}
.success-order-box .lbl { font-size: 0.72rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.success-order-box .val { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.95rem; color: var(--dark); margin-top: 2px; }
.notify-title {
    font-family: 'Syne', sans-serif;
    font-weight: 700; font-size: 0.78rem;
    text-transform: uppercase; letter-spacing: 0.6px;
    color: var(--muted); margin-bottom: 12px;
}
.notify-btns { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.btn-notify-sms {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 14px;
    background: var(--dark);
    color: white;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 600; font-size: 0.9rem;
    transition: opacity 0.15s;
}
.btn-notify-sms:hover { opacity: 0.85; }
.btn-notify-wa {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 14px;
    background: #25D366;
    color: white;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 600; font-size: 0.9rem;
    transition: opacity 0.15s;
}
.btn-notify-wa:hover { opacity: 0.85; }
.btn-done-success {
    width: 100%;
    padding: 14px;
    background: none;
    border: 1.5px solid var(--border);
    border-radius: 14px;
    font-weight: 600; font-size: 0.9rem;
    color: var(--muted);
    cursor: pointer;
    transition: all 0.15s;
}
.btn-done-success:hover { border-color: var(--primary); color: var(--primary); }

/* ── DELIVER MODAL IMPROVEMENTS ── */
.modal-step-indicator {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 20px;
    padding: 10px 14px;
    background: rgba(0,168,198,0.08);
    border-radius: 12px;
    border: 1px solid rgba(0,168,198,0.2);
    font-size: 0.8rem; color: var(--primary); font-weight: 600;
}
.customer-confirm-box {
    background: var(--bg);
    border-radius: 14px;
    padding: 14px;
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 12px;
}
.customer-confirm-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #0064ff);
    color: white; font-family: 'Syne', sans-serif; font-weight: 800;
    font-size: 1rem; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.customer-confirm-name { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.9rem; }
.customer-confirm-addr { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }
</style>
</head>
<body>

<!-- SIDEBAR (unified DeiTrack sidebar system) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="/src/logo.png" alt="Logo" onerror="this.style.display='none'">
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-name">DeiTrack</div>
            <div class="sidebar-brand-sub">Delivery Rider</div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?php echo $rider_initial; ?></div>
        <div class="sidebar-user-name"><?php echo htmlspecialchars($rider_name); ?></div>
        <div class="sidebar-user-email"><i class="bi bi-bicycle"></i> Delivery Rider · DeiTrack</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Menu</div>
        <a href="#" class="nav-item active" onclick="showPage('deliveries')">
            <i class="bi bi-box-seam"></i> My Deliveries
            <?php if ($active > 0): ?><span class="nav-badge"><?php echo $active; ?></span><?php endif; ?>
        </a>
        <a href="#" class="nav-item" onclick="showPage('history')">
            <i class="bi bi-clock-history"></i> All Orders
        </a>
        <a href="#" class="nav-item" onclick="showPage('map')">
            <i class="bi bi-broadcast"></i> Live Map
        </a>

        <div class="nav-label">Account</div>
        <a href="#" class="nav-item" onclick="showPage('account')">
            <i class="bi bi-person-circle"></i> Account
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/login.php" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<!-- TOPBAR -->
<header class="topbar">
    <button class="hamburger" onclick="toggleSidebar()" aria-label="Open menu">
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-brand" id="topbarPageTitle">My Deliveries</div>
    <div class="topbar-right">
        <div class="topbar-gps" id="gpsIndicator" title="GPS Active">
            <i class="bi bi-broadcast"></i>
        </div>
    </div>
</header>

<!-- PAGES -->
<main class="app-main">

    <!-- ── DELIVERIES PAGE ── -->
    <div id="page-deliveries" class="page active">
        <div class="stat-strip">
            <div class="stat-item">
                <div class="stat-val" id="statActive"><?php echo $active; ?></div>
                <div class="stat-lbl">Active</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-val"><?php echo $today_done; ?></div>
                <div class="stat-lbl">Today</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-val"><?php echo $completed; ?></div>
                <div class="stat-lbl">All-time</div>
            </div>
        </div>

        <div class="section-label">Active Deliveries</div>

        <div id="deliveriesList">
        <?php if (empty($deliveries)): ?>
        <div class="empty-state" id="emptyDeliveries">
            <i class="bi bi-bicycle"></i>
            <p>No active deliveries</p>
            <small>New orders will appear here automatically</small>
        </div>
        <?php else: ?>
        <?php foreach ($deliveries as $d):
            $isNew      = ($d['rider_id'] === null || $d['rider_id'] == 0);
            $isAssigned = ($d['status'] === 'assigned');
            $isTransit  = ($d['status'] === 'in_transit');
            $badgeCls   = $isNew ? 'new' : ($isTransit ? 'in_transit' : 'assigned');
            $badgeTxt   = $isNew ? 'New Order' : ($isTransit ? 'In Transit 🚴' : 'Assigned');
            $addr       = htmlspecialchars($d['customer_address'] ?? '');
            $landmark   = htmlspecialchars($d['customer_landmark'] ?? '');
            $phone      = htmlspecialchars($d['customer_phone'] ?? '');
            $name       = htmlspecialchars($d['customer_name'] ?? '');
            $nameJs     = htmlspecialchars(addslashes($d['customer_name'] ?? ''));
            $phoneJs    = htmlspecialchars($d['customer_phone'] ?? '');
        ?>
        <div class="delivery-card <?php echo $isNew ? 'card-new' : ''; ?>" id="card-<?php echo $d['id']; ?>">
            <div class="card-header-row">
                <div>
                    <div class="card-order-id">ORD-<?php echo str_pad($d['id'], 4, '0', STR_PAD_LEFT); ?></div>
                    <div class="card-customer"><?php echo $name; ?></div>
                </div>
                <span class="status-pill <?php echo $badgeCls; ?>"><?php echo $badgeTxt; ?></span>
            </div>

            <div class="card-items">
                <i class="bi bi-box-seam"></i>
                <span><?php echo htmlspecialchars($d['items_summary'] ?? 'No items'); ?></span>
            </div>

            <div class="card-info">
                <div class="info-row"><i class="bi bi-geo-alt-fill"></i><span><?php echo $addr; ?></span></div>
                <?php if (!empty($landmark)): ?>
                <div class="info-row"><i class="bi bi-signpost-2-fill"></i><span>🏷️ <?php echo $landmark; ?></span></div>
                <?php endif; ?>
                <div class="info-row"><i class="bi bi-telephone-fill"></i><span><?php echo $phone; ?></span></div>
                <div class="info-row"><i class="bi bi-cash-stack"></i><span>&#8369;<?php echo number_format($d['total'], 2); ?></span></div>
            </div>

            <?php if ($isNew): ?>
            <div class="accept-reject-row">
                <button class="btn-reject" onclick="rejectOrder(<?php echo $d['id']; ?>, this)">
                    <i class="bi bi-x-lg"></i> Decline
                </button>
                <button class="btn-accept" onclick="acceptOrder(<?php echo $d['id']; ?>, this)">
                    <i class="bi bi-check-lg"></i> Accept
                </button>
            </div>
            <?php endif; ?>

            <?php if ($isAssigned): ?>
            <button class="btn-transit" onclick="openPickupModal(<?php echo $d['id']; ?>, '<?php echo $nameJs; ?>', '<?php echo $phoneJs; ?>', '<?php echo htmlspecialchars(addslashes($d['customer_address'] ?? '')); ?>')">
                <i class="bi bi-box-seam"></i> Confirm Pickup
            </button>
            <?php endif; ?>

            <?php if ($isTransit): ?>
            <button class="btn-deliver" onclick="openDeliverModal(<?php echo $d['id']; ?>, '<?php echo $nameJs; ?>', '<?php echo $phoneJs; ?>', '<?php echo htmlspecialchars(addslashes($d['customer_address'] ?? '')); ?>')">
                <i class="bi bi-check-circle-fill"></i> Mark as Delivered
            </button>
            <?php endif; ?>

            <div class="card-actions">
                <button class="action-btn" onclick="openNavigation(<?php echo $d['id']; ?>, <?php echo $d['delivery_lat'] !== null ? $d['delivery_lat'] : 'null'; ?>, <?php echo $d['delivery_lng'] !== null ? $d['delivery_lng'] : 'null'; ?>, '<?php echo $nameJs; ?>', '<?php echo htmlspecialchars(addslashes($d['customer_address'] ?? '')); ?>')">
                    <i class="bi bi-map"></i> Navigate
                </button>
                <a href="tel:<?php echo $phone; ?>" class="action-btn">
                    <i class="bi bi-telephone"></i> Call
                </a>
                <button class="action-btn" onclick="openSmsModal('<?php echo $nameJs; ?>', '<?php echo $phoneJs; ?>', false)">
                    <i class="bi bi-chat-dots"></i> SMS
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>

    <!-- ── HISTORY PAGE ── -->
    <div id="page-history" class="page">
        <div class="section-label">All Orders</div>

        <?php if (!empty($deliveries)): ?>
        <div class="section-sublabel" style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--primary);padding:0 4px 8px;">Active Now</div>
        <?php foreach ($deliveries as $d):
            $isNew      = ($d['rider_id'] === null || $d['rider_id'] == 0);
            $isAssigned = ($d['status'] === 'assigned');
            $isTransit  = ($d['status'] === 'in_transit');
            $badgeCls   = $isNew ? 'new' : ($isTransit ? 'in_transit' : 'assigned');
            $badgeTxt   = $isNew ? 'New Order' : ($isTransit ? 'In Transit 🚴' : 'Assigned');
        ?>
        <div class="history-item" style="border-left:3px solid var(--primary);">
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <div class="history-id">ORD-<?php echo str_pad($d['id'], 4, '0', STR_PAD_LEFT); ?></div>
                    <span class="status-pill <?php echo $badgeCls; ?>" style="font-size:0.65rem;padding:2px 8px;"><?php echo $badgeTxt; ?></span>
                </div>
                <div class="history-customer"><?php echo htmlspecialchars($d['customer_name']); ?></div>
                <div class="history-items"><?php echo htmlspecialchars($d['items_summary'] ?? ''); ?></div>
                <div class="history-items" style="margin-top:2px;"><i class="bi bi-geo-alt" style="color:var(--primary);font-size:0.7rem;"></i> <?php echo htmlspecialchars($d['customer_address'] ?? ''); ?></div>
                <?php if (!empty($d['customer_landmark'])): ?>
                <div class="history-items" style="margin-top:2px;">🏷️ <?php echo htmlspecialchars($d['customer_landmark']); ?></div>
                <?php endif; ?>
            </div>
            <div style="text-align:right;flex-shrink:0;margin-left:12px;">
                <div class="history-amount">&#8369;<?php echo number_format($d['total'], 2); ?></div>
                <div class="history-date" style="margin-top:4px;"><?php echo date('M d, h:i A', strtotime($d['created_at'])); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <div style="margin-bottom:16px;"></div>
        <?php endif; ?>

        <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--muted);padding:0 4px 8px;">Completed (<?php echo $completed; ?>)</div>
        <div id="historyList">
        <?php if (empty($past_orders)): ?>
            <div class="empty-state" style="padding:30px 0;">
                <i class="bi bi-clock-history"></i>
                <p>No completed deliveries yet</p>
            </div>
        <?php else: ?>
        <?php foreach ($past_orders as $d): ?>
        <div class="history-item">
            <div style="flex:1;">
                <div class="history-id">ORD-<?php echo str_pad($d['id'], 4, '0', STR_PAD_LEFT); ?></div>
                <div class="history-customer"><?php echo htmlspecialchars($d['customer_name']); ?></div>
                <div class="history-items"><?php echo htmlspecialchars($d['items_summary'] ?? ''); ?></div>
                <?php if (!empty($d['delivery_notes'])): ?>
                <div class="history-items" style="margin-top:2px;font-style:italic;">"<?php echo htmlspecialchars($d['delivery_notes']); ?>"</div>
                <?php endif; ?>
            </div>
            <div style="text-align:right;flex-shrink:0;margin-left:12px;">
                <div class="history-amount">&#8369;<?php echo number_format($d['total'], 2); ?></div>
                <div class="history-date"><?php echo $d['delivered_at'] ? date('M d, h:i A', strtotime($d['delivered_at'])) : '—'; ?></div>
                <?php if (!empty($d['delivery_photo'])): ?>
                <img src="/assets/images/deliveries/<?php echo htmlspecialchars($d['delivery_photo']); ?>"
                     class="history-thumb" onclick="viewPhoto(this.src)" alt="Proof">
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>

    <!-- ── LIVE MAP PAGE ── -->
    <div id="page-map" class="page">
        <div class="section-label" id="mapSectionLabel">Your Live Location</div>

        <div class="nav-target-banner" id="navTargetBanner" style="display:none;">
            <div class="nav-target-info">
                <i class="bi bi-flag-fill"></i>
                <div>
                    <div class="nav-target-name" id="navTargetName"></div>
                    <div class="nav-target-addr" id="navTargetAddr"></div>
                </div>
            </div>
            <button class="nav-exit-btn" onclick="exitNavigation()"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="map-wrap">
            <div id="liveMap"></div>
            <div class="map-info-row">
                <div class="map-coords" id="mapCoords">Waiting for GPS...</div>
                <div class="gps-live-badge"><span class="live-dot"></span> Broadcasting</div>
            </div>
        </div>

        <div class="route-info-card" id="routeInfoCard" style="display:none;">
            <div class="route-stat">
                <div class="route-stat-val" id="routeDistance">-</div>
                <div class="route-stat-lbl">Distance</div>
            </div>
            <div class="route-stat">
                <div class="route-stat-val" id="routeEta">-</div>
                <div class="route-stat-lbl">Est. Time</div>
            </div>
        </div>
    </div>

    <!-- ── ACCOUNT PAGE ── -->
    <div id="page-account" class="page">
        <div class="account-hero">
            <div class="account-avatar"><?php echo $rider_initial; ?></div>
            <div class="account-name"><?php echo htmlspecialchars($rider_name); ?></div>
            <div class="account-role"><i class="bi bi-bicycle"></i> Rider · DeiTrack</div>
        </div>
        <div class="account-stats">
            <div class="acc-stat">
                <div class="acc-stat-val"><?php echo $completed; ?></div>
                <div class="acc-stat-lbl">Total Delivered</div>
            </div>
            <div class="acc-stat">
                <div class="acc-stat-val"><?php echo $today_done; ?></div>
                <div class="acc-stat-lbl">Today</div>
            </div>
        </div>
        <form class="account-form" onsubmit="saveAccount(event)">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="accName" value="<?php echo htmlspecialchars($rider_name); ?>" class="form-input">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="accEmail" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-input">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" id="accPhone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-input" placeholder="+63 9XX XXX XXXX">
            </div>
            <div class="form-group">
                <label>New Password <span style="color:#94a3b8;font-weight:400;">(leave blank to keep)</span></label>
                <input type="password" id="accPassword" class="form-input" placeholder="••••••••">
            </div>
            <button type="submit" class="btn-save">
                <i class="bi bi-check-lg"></i> Save Changes
            </button>
        </form>
    </div>

</main>

<!-- ═══ CONFIRM PICKUP MODAL ═══ -->
<div class="modal-overlay" id="pickupModal">
    <div class="modal-sheet">
        <div class="modal-handle"></div>
        <div class="modal-title">Confirm Pickup</div>

        <div class="modal-step-indicator">
            <i class="bi bi-camera-fill"></i>
            Take a photo proof that you picked up the item(s)
        </div>

        <div class="customer-confirm-box" id="pickupCustomerBox">
            <div class="customer-confirm-avatar"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="customer-confirm-name" id="pickupCustomerName"></div>
                <div class="customer-confirm-addr" id="pickupCustomerAddr"></div>
            </div>
        </div>

        <input type="hidden" id="pickupOrderId">

        <div class="photo-upload-area" id="pickupPhotoArea" onclick="document.getElementById('pickupPhoto').click()">
            <div id="pickupPhotoPreview" style="display:none;position:absolute;inset:0;">
                <img id="pickupPhotoPreviewImg" style="width:100%;height:100%;object-fit:cover;border-radius:14px;">
                <div style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,0.55);color:white;font-size:0.7rem;padding:4px 8px;border-radius:8px;">Tap to change</div>
            </div>
            <div id="pickupPhotoPlaceholder">
                <i class="bi bi-camera-fill"></i>
                <div>Tap to take pickup photo</div>
                <small>Required — proof of item pickup</small>
            </div>
        </div>
        <input type="file" id="pickupPhoto" accept="image/*" capture="environment" style="display:none;" onchange="previewPickupPhoto()">

        <button class="btn-confirm" id="pickupConfirmBtn" onclick="submitPickup()">
            <i class="bi bi-box-seam"></i> Confirm Pickup
        </button>
        <button class="btn-cancel" onclick="closeModal('pickupModal')">Cancel</button>
    </div>
</div>

<!-- ═══ DELIVER MODAL ═══ -->
<div class="modal-overlay" id="deliverModal">
    <div class="modal-sheet">
        <div class="modal-handle"></div>
        <div class="modal-title">Confirm Delivery</div>

        <div class="modal-step-indicator">
            <i class="bi bi-camera-fill"></i>
            Take a photo proof + add notes, then submit
        </div>

        <!-- Customer info strip -->
        <div class="customer-confirm-box" id="deliverCustomerBox">
            <div class="customer-confirm-avatar" id="deliverCustomerInitial">?</div>
            <div>
                <div class="customer-confirm-name" id="deliverCustomerName"></div>
                <div class="customer-confirm-addr" id="deliverCustomerAddr"></div>
            </div>
        </div>

        <input type="hidden" id="deliverOrderId">
        <input type="hidden" id="deliverCustomerPhone">

        <div class="photo-upload-area" id="photoArea" onclick="document.getElementById('deliverPhoto').click()">
            <div id="photoPreview" style="display:none;position:absolute;inset:0;">
                <img id="photoPreviewImg" style="width:100%;height:100%;object-fit:cover;border-radius:14px;">
                <div style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,0.55);color:white;font-size:0.7rem;padding:4px 8px;border-radius:8px;">Tap to change</div>
            </div>
            <div id="photoPlaceholder">
                <i class="bi bi-camera-fill"></i>
                <div>Tap to take delivery photo</div>
                <small>Required — proof of delivery</small>
            </div>
        </div>
        <input type="file" id="deliverPhoto" accept="image/*" capture="environment" style="display:none;" onchange="previewDeliveryPhoto()">

        <div class="form-group" style="margin-top:16px;">
            <label>Notes <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <textarea id="deliverNotes" class="form-input" rows="2" placeholder="e.g. Left with guard, handed to customer directly..."></textarea>
        </div>

        <button class="btn-confirm" id="confirmBtn" onclick="submitDelivery()">
            <i class="bi bi-check-circle-fill"></i> Confirm Delivered
        </button>
        <button class="btn-cancel" onclick="closeModal('deliverModal')">Cancel</button>
    </div>
</div>

<!-- ═══ DELIVERED SUCCESS MODAL ═══ -->
<div class="modal-overlay success-modal" id="successModal">
    <div class="modal-sheet">
        <div class="modal-handle"></div>
        <div class="success-icon-wrap"><i class="bi bi-check-lg"></i></div>
        <div class="success-title">Delivered! 🎉</div>
        <div class="success-sub">Order marked as delivered successfully.</div>

        <div class="success-order-box">
            <div class="lbl">Customer</div>
            <div class="val" id="successCustomerName"></div>
            <div style="margin-top:10px;" class="lbl">Order</div>
            <div class="val" id="successOrderId"></div>
        </div>

        <div class="notify-title">Notify your customer</div>
        <div class="notify-btns">
            <a id="successSmsLink" href="#" class="btn-notify-sms">
                <i class="bi bi-chat-dots-fill"></i> Send SMS Notification
            </a>
            <a id="successWaLink" href="#" class="btn-notify-wa" target="_blank">
                <i class="bi bi-whatsapp"></i> Send WhatsApp Message
            </a>
        </div>

        <button class="btn-done-success" onclick="closeSuccessAndGoHistory()">
            Done — View History
        </button>
    </div>
</div>

<!-- ═══ SMS MODAL ═══ -->
<div class="modal-overlay" id="smsModal">
    <div class="modal-sheet">
        <div class="modal-handle"></div>
        <div class="modal-title">Send SMS Update</div>
        <div class="sms-templates">
            <button class="sms-tpl" onclick="setSmsText('Hi {name}, your order is on its way! ETA: 15–30 mins. — DeiTrack')">🚴 On my way</button>
            <button class="sms-tpl" onclick="setSmsText('Hi {name}, I am near your location. Please be ready to receive your order. — DeiTrack')">📍 Almost there</button>
            <button class="sms-tpl" onclick="setSmsText('Hi {name}, your order has been delivered! Thank you. — DeiTrack')">✅ Delivered</button>
            <button class="sms-tpl" onclick="setSmsText('Hi {name}, I tried to deliver your order but no one was available. Please contact us.')">❌ Failed attempt</button>
        </div>
        <textarea id="smsText" class="form-input" rows="3" placeholder="Type message..."></textarea>
        <a id="smsLink" href="#" class="btn-confirm" style="display:block;text-align:center;text-decoration:none;margin-top:12px;">
            <i class="bi bi-chat-dots-fill"></i> Open SMS App
        </a>
        <button class="btn-cancel" onclick="closeModal('smsModal')">Cancel</button>
    </div>
</div>

<!-- TOAST -->
<div class="toast-msg" id="toastMsg"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="/assets/js/rider.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/rider.js'); ?>"></script>
<script src="/assets/js/pwa-install.js"></script>
</body>
</html>