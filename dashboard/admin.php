<?php
session_start();

// AUTH
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// DB
require_once __DIR__ . '/../config/db.php';

// ================= ACTIONS =================

// Update order
if (isset($_POST['update_order'])) {
    $id     = (int)$_POST['order_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE orders SET status='$status' WHERE id=$id");
}

// Restock
if (isset($_POST['restock'])) {
    $pid = (int)$_POST['product_id'];
    $qty = (int)$_POST['quantity'];
    $conn->query("UPDATE products SET stock = stock + $qty WHERE id=$pid");
}

// Add user
if (isset($_POST['add_user'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role  = $conn->real_escape_string($_POST['role']);
    $conn->query("INSERT INTO users(email,password,role) VALUES('$email','$pass','$role')");
}

// ── EDIT USER ──────────────────────────────────────────────────────────────
if (isset($_POST['edit_user'])) {
    $uid   = (int)$_POST['user_id'];
    $email = $conn->real_escape_string($_POST['email']);
    $role  = $conn->real_escape_string($_POST['role']);

    if (!empty($_POST['password'])) {
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET email='$email', password='$pass', role='$role' WHERE id=$uid");
    } else {
        $conn->query("UPDATE users SET email='$email', role='$role' WHERE id=$uid");
    }
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?tab=users");
    exit;
}

// Delete user
if (isset($_GET['delete_user'])) {
    $conn->query("DELETE FROM users WHERE id=" . (int)$_GET['delete_user']);
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?tab=users");
    exit;
}

// ================= DATA =================

$salesToday     = $conn->query("SELECT SUM(total) t FROM orders WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['t'] ?? 0;
$salesYesterday = $conn->query("SELECT SUM(total) t FROM orders WHERE DATE(created_at)=CURDATE()-1")->fetch_assoc()['t'] ?? 0;
$totalOrders    = $conn->query("SELECT COUNT(*) t FROM orders")->fetch_assoc()['t'];
$lowStock       = $conn->query("SELECT COUNT(*) t FROM products WHERE stock <= IFNULL(reorder_threshold, 10)")->fetch_assoc()['t'];
$delivering     = $conn->query("SELECT COUNT(*) t FROM orders WHERE status IN ('assigned','in_transit')")->fetch_assoc()['t'];
$awaitingReview = $conn->query("SELECT COUNT(*) t FROM orders WHERE status='pending' AND order_type='delivery'")->fetch_assoc()['t'];

$recent     = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
$products   = $conn->query("SELECT * FROM products");

$where = "1";
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $where .= " AND status='$status'";
}
$orders = $conn->query("SELECT * FROM orders WHERE $where ORDER BY created_at DESC");

$riders = $conn->query("
    SELECT u.name, rl.lat, rl.lng,
           TIMESTAMPDIFF(SECOND, rl.updated_at, NOW()) AS seconds_ago
    FROM rider_locations rl
    JOIN users u ON u.id = rl.rider_id
    WHERE u.role = 'rider'
");

// ── REVENUE BY PERIOD ───────────────────────────────────────────────────────
$salesWeek  = $conn->query("SELECT SUM(total) t FROM orders WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)")->fetch_assoc()['t'] ?? 0;
$salesMonth = $conn->query("SELECT SUM(total) t FROM orders WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())")->fetch_assoc()['t'] ?? 0;
$salesYear  = $conn->query("SELECT SUM(total) t FROM orders WHERE YEAR(created_at)=YEAR(CURDATE())")->fetch_assoc()['t'] ?? 0;

// ── TOP SELLING PRODUCTS (all-time) ─────────────────────────────────────────
$topProducts = [];
$tp = $conn->query("
    SELECT p.name, SUM(oi.quantity) AS units_sold, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    GROUP BY oi.product_id
    ORDER BY units_sold DESC
    LIMIT 5
");
if ($tp) { while ($row = $tp->fetch_assoc()) $topProducts[] = $row; }

// ── TOP RIDERS (by completed deliveries) ────────────────────────────────────
$topRiders = [];
$tr = $conn->query("
    SELECT u.name, COUNT(*) AS deliveries, COALESCE(SUM(o.total),0) AS value_delivered
    FROM orders o
    JOIN users u ON u.id = o.rider_id
    WHERE o.status = 'delivered'
    GROUP BY o.rider_id
    ORDER BY deliveries DESC
    LIMIT 5
");
if ($tr) { while ($row = $tr->fetch_assoc()) $topRiders[] = $row; }

// Fetch all users into array (used in table + modal JS)
$usersArr    = [];
$usersResult = $conn->query("SELECT * FROM users ORDER BY id ASC");
while ($u = $usersResult->fetch_assoc()) $usersArr[] = $u;

// REPORT DATA
$salesData = $conn->query("SELECT DATE(created_at) as d, SUM(total) as t FROM orders GROUP BY DATE(created_at) ORDER BY d ASC");
$dates = []; $totals = [];
while ($row = $salesData->fetch_assoc()) { $dates[] = $row['d']; $totals[] = $row['t']; }

// Which tab to open on load (after redirect)
$activeTab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bathala Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="../src/logo.png" alt="Logo">
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-name">Bathala</div>
            <div class="sidebar-brand-sub">Admin Dashboard</div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">A</div>
        <div class="sidebar-user-name">Admin</div>
        <div class="sidebar-user-email">Super Admin</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Menu</div>
        <button class="nav-item" id="nav-dashboard" onclick="showTab('dashboard', this)">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </button>
        <button class="nav-item" id="nav-inventory" onclick="showTab('inventory', this)">
            <i class="bi bi-box-seam-fill"></i> Products
            <?php if ($lowStock > 0): ?>
            <span class="nav-badge"><?= $lowStock ?></span>
            <?php endif; ?>
        </button>
        <button class="nav-item" id="nav-orders" onclick="showTab('orders', this)">
            <i class="bi bi-cart-fill"></i> Orders
        </button>
        <button class="nav-item" id="nav-delivery" onclick="showTab('delivery', this)">
            <i class="bi bi-bicycle"></i> Delivery
        </button>
        <button class="nav-item" id="nav-reports" onclick="showTab('reports', this)">
            <i class="bi bi-bar-chart-fill"></i> Reports
        </button>
        <button class="nav-item" id="nav-users" onclick="showTab('users', this)">
            <i class="bi bi-people-fill"></i> Users
        </button>
    </nav>

    <div class="sidebar-footer">
        <a href="../login.php" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<!-- ═══════════════ MAIN ═══════════════ -->
<div class="main">
  <div class="topbar">
    <button class="hamburger" onclick="toggleSidebar()" aria-label="Open menu">
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-title" id="topbar-heading">Dashboard</div>
    <div class="topbar-right">
      <span class="topbar-time" id="topbar-time-el"></span>
      
    </div>
  </div>

  <div class="content">

    <!-- ═══ DASHBOARD ═══ -->
    <div id="dashboard" class="tab">
      <div class="page-header">
        <div><h2>Overview</h2><p>Welcome back, Admin. Here's what's happening today.</p></div>
      </div>
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-icon cyan"><i class="bi bi-cash-coin"></i></div>
          <?php $diff=$salesToday-$salesYesterday; $pct=$salesYesterday>0?round(($diff/$salesYesterday)*100,1):0; ?>
          <span class="stat-change <?= $diff>=0?'up':'down' ?>"><?= $diff>=0?'↑':'↓' ?> <?= abs($pct) ?>%</span>
          <div class="stat-value">₱<?= number_format($salesToday,0) ?></div>
          <div class="stat-label">Sales Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple"><i class="bi bi-calendar2-check"></i></div>
          <div class="stat-value">₱<?= number_format($salesYesterday,0) ?></div>
          <div class="stat-label">Yesterday</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-receipt"></i></div>
          <div class="stat-value"><?= $totalOrders ?></div>
          <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="bi bi-bicycle"></i></div>
          <div class="stat-value"><?= $delivering ?></div>
          <div class="stat-label">Delivering</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple"><i class="bi bi-bell-fill"></i></div>
          <div class="stat-value"><?= $awaitingReview ?></div>
          <div class="stat-label">Awaiting Staff Confirmation</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <div class="stat-value"><?= $lowStock ?></div>
          <div class="stat-label">Low Stock</div>
        </div>
      </div>

      <div class="dash-bottom">
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Recent Orders</span>
            <button class="btn-primary-sm" onclick="showTab('orders',document.getElementById('nav-orders'))">View all</button>
          </div>
          <div class="panel-body">
            <table class="data-table">
              <thead><tr><th>#</th><th>Status</th><th>Amount</th></tr></thead>
              <tbody>
                <?php $recent->data_seek(0); while ($o=$recent->fetch_assoc()): ?>
                <tr>
                  <td><b>#<?= $o['id'] ?></b></td>
                  <td><span class="badge-status <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                  <td><b>₱<?= number_format($o['total'],2) ?></b></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title">Orders by Status</span></div>
          <div class="panel-body"><div class="donut-wrap"><canvas id="donutChart"></canvas></div></div>
        </div>
      </div>
    </div>

    <!-- ═══ INVENTORY ═══ -->
    <div id="inventory" class="tab">
      <div class="page-header"><div><h2>Inventory</h2><p>Monitor stock levels and restock products.</p></div></div>
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">All Products</span>
          <span style="font-size:12px;color:var(--muted);"><?= $lowStock ?> items low on stock</span>
        </div>
        <?php $products->data_seek(0); ?>
        <table class="data-table">
          <thead><tr><th>Product</th><th>Stock Level</th><th>Restock</th></tr></thead>
          <tbody>
            <?php while ($p=$products->fetch_assoc()):
              $s=$p['stock']; $pct=min(100,round($s/100*100));
              $cls=$s<=10?'low':($s<=30?'mid':'');
            ?>
            <tr>
              <td><b><?= htmlspecialchars($p['name']) ?></b></td>
              <td>
                <div class="stock-bar">
                  <div class="stock-track"><div class="stock-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
                  <span class="stock-num" style="color:<?= $s<=10?'var(--danger)':($s<=30?'var(--warning)':'var(--dark)') ?>"><?= $s ?></span>
                </div>
              </td>
              <td>
                <form method="POST" class="restock-form">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="number" name="quantity" class="restock-input" placeholder="Qty" min="1" required>
                  <button name="restock" class="btn-success-sm">+ Restock</button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══ ORDERS ═══ -->
    <div id="orders" class="tab">
      <div class="page-header"><div><h2>Orders</h2><p>Manage and update all orders.</p></div></div>
      <div class="panel">
        <div class="filter-bar">
          <form method="GET" style="display:flex;align-items:center;gap:8px;" onsubmit="return false;">
            <select class="form-control-dark" name="status" onchange="filterOrders(this.value)">
              <option value="">All Status</option>
              <option value="pending"    <?= ($_GET['status']??'')==='pending'    ?'selected':'' ?>>Pending Confirmation</option>
              <option value="confirmed"  <?= ($_GET['status']??'')==='confirmed'  ?'selected':'' ?>>Confirmed</option>
              <option value="paid"       <?= ($_GET['status']??'')==='paid'       ?'selected':'' ?>>Paid (Walk-in)</option>
              <option value="assigned"   <?= ($_GET['status']??'')==='assigned'   ?'selected':'' ?>>Assigned to Rider</option>
              <option value="in_transit" <?= ($_GET['status']??'')==='in_transit' ?'selected':'' ?>>In Transit</option>
              <option value="delivered"  <?= ($_GET['status']??'')==='delivered'  ?'selected':'' ?>>Delivered</option>
              <option value="cancelled"  <?= ($_GET['status']??'')==='cancelled'  ?'selected':'' ?>>Cancelled</option>
            </select>
          </form>
        </div>
        <table class="data-table">
          <thead><tr><th>Order #</th><th>Status</th><th>Total</th><th>Date</th><th>Update</th></tr></thead>
          <tbody>
            <?php while ($o=$orders->fetch_assoc()): ?>
            <tr>
              <td><b>#<?= $o['id'] ?></b></td>
              <td><span class="badge-status <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
              <td>₱<?= number_format($o['total'],2) ?></td>
              <td style="color:var(--muted);font-size:12px;"><?= isset($o['created_at'])?date('M d, H:i',strtotime($o['created_at'])):'-' ?></td>
              <td>
                <form method="POST" style="display:flex;align-items:center;gap:6px;">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <select name="status" class="form-control-dark" style="padding:5px 8px;font-size:12px;">
                    <option value="pending"    <?= $o['status']==='pending'    ?'selected':'' ?>>Pending Confirmation</option>
                    <option value="confirmed"  <?= $o['status']==='confirmed'  ?'selected':'' ?>>Confirmed</option>
                    <option value="paid"       <?= $o['status']==='paid'       ?'selected':'' ?>>Paid (Walk-in)</option>
                    <option value="assigned"   <?= $o['status']==='assigned'   ?'selected':'' ?>>Assigned to Rider</option>
                    <option value="in_transit" <?= $o['status']==='in_transit' ?'selected':'' ?>>In Transit</option>
                    <option value="delivered"  <?= $o['status']==='delivered'  ?'selected':'' ?>>Delivered</option>
                    <option value="cancelled"  <?= $o['status']==='cancelled'  ?'selected':'' ?>>Cancelled</option>
                  </select>
                  <button name="update_order" class="btn-primary-sm">Save</button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══ DELIVERY ═══ -->
    <div id="delivery" class="tab">
      <div class="page-header"><div><h2>Delivery</h2><p>Live tracking of active riders.</p></div></div>
      <div style="display:grid;grid-template-columns:340px 1fr;gap:18px;align-items:start;">
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Active Riders</span>
            <span class="badge-status delivered" style="font-size:11px;" id="riderLiveBadge">● Live</span>
          </div>
          <div id="rider-list">
            <?php
            $ridersArr=[];
            if($riders){ while($r=$riders->fetch_assoc()) $ridersArr[]=$r; }
            foreach($ridersArr as $r):
              $isStale = $r['seconds_ago'] > 60;
            ?>
            <div class="rider-card">
              <div class="rider-avatar"><?= strtoupper(substr($r['name'],0,1)) ?></div>
              <div class="rider-info">
                <div class="rider-name"><?= htmlspecialchars($r['name']) ?></div>
                <div class="rider-coords"><?= round($r['lat'],4) ?>, <?= round($r['lng'],4) ?></div>
              </div>
              <div class="rider-pulse" style="<?= $isStale ? 'background:var(--muted);box-shadow:none;' : '' ?>"></div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($ridersArr)): ?>
            <div style="padding:32px;text-align:center;color:var(--muted);" id="noRidersMsg">
              <i class="bi bi-bicycle" style="font-size:32px;opacity:0.3;"></i>
              <p style="margin-top:10px;font-size:13px;">No active riders</p>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel" style="overflow:hidden;">
          <div class="panel-header">
            <span class="panel-title">GPS Map</span>
            <span style="font-size:12px;color:var(--muted);">Live positions</span>
          </div>
          <div id="riderMap" style="height:420px;border-radius:0 0 var(--radius) var(--radius);"></div>
        </div>
      </div>
    </div>

    <!-- ═══ REPORTS ═══ -->
    <div id="reports" class="tab">
      <div class="page-header">
        <div><h2>Analytics</h2><p>Sales trends and performance metrics.</p></div>
        <div style="display:flex;gap:8px;">
          <button class="btn-cyan" onclick="exportReport()"><i class="bi bi-download"></i> Export</button>
        </div>
      </div>

      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-icon cyan"><i class="bi bi-cash-coin"></i></div>
          <div class="stat-value">₱<?= number_format($salesToday,0) ?></div>
          <div class="stat-label">Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple"><i class="bi bi-calendar-week"></i></div>
          <div class="stat-value">₱<?= number_format($salesWeek,0) ?></div>
          <div class="stat-label">This Week</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-calendar-month"></i></div>
          <div class="stat-value">₱<?= number_format($salesMonth,0) ?></div>
          <div class="stat-label">This Month</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="bi bi-calendar-range"></i></div>
          <div class="stat-value">₱<?= number_format($salesYear,0) ?></div>
          <div class="stat-label">This Year</div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header"><span class="panel-title">Daily Sales</span></div>
        <div class="chart-container"><canvas id="salesChart"></canvas></div>
      </div>

      <div class="dash-bottom">
        <div class="panel">
          <div class="panel-header"><span class="panel-title">Top Selling Products</span></div>
          <div class="panel-body">
            <table class="data-table">
              <thead><tr><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
              <tbody>
                <?php if (empty($topProducts)): ?>
                <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:24px;">No sales data yet</td></tr>
                <?php else: foreach ($topProducts as $p): ?>
                <tr>
                  <td><b><?= htmlspecialchars($p['name']) ?></b></td>
                  <td><?= (int)$p['units_sold'] ?></td>
                  <td><b>₱<?= number_format($p['revenue'],2) ?></b></td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title">Top Riders</span></div>
          <div class="panel-body">
            <table class="data-table">
              <thead><tr><th>Rider</th><th>Deliveries</th><th>Value Delivered</th></tr></thead>
              <tbody>
                <?php if (empty($topRiders)): ?>
                <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:24px;">No completed deliveries yet</td></tr>
                <?php else: foreach ($topRiders as $r): ?>
                <tr>
                  <td><b><?= htmlspecialchars($r['name']) ?></b></td>
                  <td><?= (int)$r['deliveries'] ?></td>
                  <td><b>₱<?= number_format($r['value_delivered'],2) ?></b></td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ USERS ═══ -->
    <div id="users" class="tab">
      <div class="page-header">
        <div><h2>Users</h2><p>Manage staff, riders, and warehouse accounts.</p></div>
      </div>
      <div class="panel">

        <!-- ADD USER -->
        <div class="add-user-form">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" form="add-user-form" class="form-control-dark" style="width:100%;" placeholder="user@email.com" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" form="add-user-form" class="form-control-dark" style="width:100%;" placeholder="••••••••" required>
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role" form="add-user-form" class="form-control-dark" style="width:100%;">
              <option>admin</option><option>staff</option><option>warehouse</option><option>rider</option>
            </select>
          </div>
          <form method="POST" id="add-user-form">
            <button name="add_user" class="btn-cyan" style="height:36px;">
              <i class="bi bi-person-plus-fill"></i> Add User
            </button>
          </form>
        </div>

        <!-- USERS TABLE -->
        <table class="data-table">
          <thead>
            <tr><th>#</th><th>Email</th><th>Role</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($usersArr as $u): ?>
            <tr>
              <td style="color:var(--muted);"><?= $u['id'] ?></td>
              <td><b><?= htmlspecialchars($u['email']) ?></b></td>
              <td><span class="badge-status <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
              <td>
                <div style="display:flex;gap:8px;align-items:center;">
                  <button class="btn-primary-sm"
                    onclick="openEditModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>', '<?= $u['role'] ?>')">
                    <i class="bi bi-pencil-fill"></i> Edit
                  </button>
                  <a href="?delete_user=<?= $u['id'] ?>" class="btn-danger-sm"
                     onclick="return confirm('Delete this user?')">
                    <i class="bi bi-trash3"></i> Delete
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<!-- ═══════════════ EDIT USER MODAL ═══════════════ -->
<div id="editModal" class="modal-overlay" onclick="closeEditModal(event)">
  <div class="modal-box">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Edit User</h3>
        <p class="modal-sub">Update email, role, or reset password</p>
      </div>
      <button type="button" class="modal-close" onclick="closeEditModal()">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <form method="POST" id="edit-user-form" class="modal-body">
      <input type="hidden" name="user_id" id="edit_user_id">

      <div class="form-group" style="margin-bottom:16px;">
        <label>Email Address</label>
        <input type="email" name="email" id="edit_email"
               class="form-control-dark" style="width:100%;" required>
      </div>

      <div class="form-group" style="margin-bottom:16px;">
        <label>Role</label>
        <select name="role" id="edit_role" class="form-control-dark" style="width:100%;">
          <option value="admin">Admin</option>
          <option value="staff">Staff</option>
          <option value="warehouse">Warehouse</option>
          <option value="rider">Rider</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom:24px;">
        <label>
          New Password
          <span style="color:var(--muted);font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;">
            — leave blank to keep current
          </span>
        </label>
        <input type="password" name="password" id="edit_password"
               class="form-control-dark" style="width:100%;" placeholder="••••••••">
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">
          <i class="bi bi-x"></i> Cancel
        </button>
        <button type="submit" name="edit_user" class="btn-cyan">
          <i class="bi bi-check2-circle"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ─── PHP DATA FOR JS ─── -->
<script>
const dates           = <?= json_encode($dates) ?>;
const totals          = <?= json_encode($totals) ?>;
const orderStatusData = {};
<?php
$orderStats2 = $conn->query("SELECT status, COUNT(*) c FROM orders GROUP BY status");
if ($orderStats2) {
    while ($row = $orderStats2->fetch_assoc()) {
        echo "orderStatusData['{$row['status']}'] = {$row['c']};\n";
    }
}
?>
const startTab = '<?= htmlspecialchars($activeTab) ?>';
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../assets/js/admin.js"></script>
</body>
</html>