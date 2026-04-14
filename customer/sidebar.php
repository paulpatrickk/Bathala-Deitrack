<?php
// Get current page for active nav
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">

    <!-- BRAND -->
    <div class="sidebar-brand">
        <img src="/Bathala/src/logo.png" alt="Logo">
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-name">DeiTrack</div>
            <div class="sidebar-brand-sub">Bathala Tissue</div>
        </div>
    </div>

    <!-- USER INFO -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?php if (!empty($customer['photo'])): ?>
                <img src="/Bathala/assets/images/customers/<?php echo htmlspecialchars($customer['photo']); ?>" alt="">
            <?php else: ?>
                <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-name"><?php echo htmlspecialchars($customer['name']); ?></div>
        <div class="sidebar-user-email"><?php echo htmlspecialchars($customer['email']); ?></div>
    </div>

    <!-- NAV -->
    <nav class="sidebar-nav">
        <div class="nav-label">Menu</div>

        <a href="/Bathala/customer/dashboard.php"
           class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-shop"></i> Shop
        </a>

        <a href="/Bathala/customer/orders.php"
           class="nav-item <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
            <i class="bi bi-receipt"></i> My Orders
            <?php if (isset($pending_count) && $pending_count > 0): ?>
                <span class="nav-badge"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-label">Account</div>

        <a href="/Bathala/customer/profile.php"
           class="nav-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle"></i> My Profile
        </a>
    </nav>

    <!-- FOOTER -->
    <div class="sidebar-footer">
        <a href="/Bathala/backend/customer_logout.php" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

</div>
