<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /Bathala/LandingPage/customer_login.php");
    exit;
}
$customer = $_SESSION['customer'];

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

// Fetch products grouped by category
$products_result = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY category ASC, name ASC");
$products = [];
$categories = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
    if (!in_array($row['category'], $categories)) {
        $categories[] = $row['category'];
    }
}

// Pending orders count
$pending = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE customer_name = '{$customer['name']}' AND status = 'pending'");
$pending_count = $pending->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop - DeiTrack Customer Portal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="/Bathala/assets/css/customer.css">
</head>
<body>

<div class="customer-layout">

    <!-- ═══ SIDEBAR ═══ -->
    <?php include 'sidebar.php'; ?>

    <!-- ═══ MAIN CONTENT ═══ -->
    <div class="main-content">

        <!-- TOP NAVBAR -->
        <div class="top-navbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="hamburger" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            <div class="top-navbar-right">
                <div style="display:flex;align-items:center;gap:8px;background:#f0f4f8;border:1.5px solid #e2e8f0;border-radius:12px;padding:8px 14px;">
                    <i class="bi bi-search" style="color:#94a3b8;"></i>
                    <input type="text" id="shopSearch" placeholder="Search products..." style="border:none;background:transparent;outline:none;font-family:'DM Sans',sans-serif;font-size:0.88rem;width:180px;">
                </div>
                <button class="cart-btn" onclick="openCart()">
                    <i class="bi bi-cart3"></i>
                    Cart
                    <span class="cart-badge" id="cartBadge">0</span>
                </button>
            </div>
        </div>

        <!-- PAGE CONTENT -->
        <div class="page-content">
            <div class="page-hero" style="padding:0;margin-bottom:24px;">
                <h2>🛍️ Online Shop</h2>
                <p>Browse and order our products — delivered straight to your door.</p>
            </div>
            <!-- CATEGORY FILTERS -->
            <div class="shop-filters">
                <button class="filter-btn active" onclick="filterShop('all', this)">
                    <i class="bi bi-grid"></i> All Products
                </button>
                <?php foreach ($categories as $cat): ?>
                <button class="filter-btn" onclick="filterShop('<?php echo htmlspecialchars($cat); ?>', this)">
                    <?php echo htmlspecialchars($cat); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- PRODUCTS BY CATEGORY -->
            <?php if (!empty($products)): ?>
                <?php foreach ($categories as $cat): ?>
                <div class="shop-category-section" data-cat="<?php echo htmlspecialchars($cat); ?>">
                    <div class="shop-category-title">
                        <i class="bi bi-tag-fill"></i>
                        <?php echo htmlspecialchars($cat); ?>
                    </div>
                    <div class="shop-grid">
                        <?php foreach ($products as $p): ?>
                            <?php if ($p['category'] === $cat): ?>
                            <div class="shop-product-card"
                                data-id="<?php echo $p['id']; ?>"
                                data-name="<?php echo htmlspecialchars($p['name']); ?>"
                                data-price="<?php echo $p['price']; ?>"
                                data-stock="<?php echo $p['stock']; ?>"
                                data-cat="<?php echo htmlspecialchars($p['category']); ?>"
                                data-image="<?php echo htmlspecialchars($p['image'] ?? ''); ?>"
                                data-desc="<?php echo htmlspecialchars($p['description'] ?? ''); ?>"
                                onclick="openProductModal(this)">
                                <div class="shop-product-img">
                                    <?php if (!empty($p['image'])): ?>
                                        <img src="/Bathala/assets/images/products/<?php echo htmlspecialchars($p['image']); ?>" alt="">
                                    <?php else: ?>
                                        <i class="bi bi-image placeholder-icon"></i>
                                    <?php endif; ?>
                                    <span class="category-badge"><?php echo htmlspecialchars($p['category']); ?></span>
                                </div>
                                <div class="shop-product-info">
                                    <div class="shop-product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <div class="shop-product-bottom">
                                        <div class="shop-product-price">₱<?php echo number_format($p['price'], 2); ?></div>
                                        <?php if ($p['stock'] > 0): ?>
                                        <button class="add-to-cart-btn" onclick="addToCart(this.closest('.shop-product-card'))">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                        <?php else: ?>
                                        <span class="out-of-stock-badge">Out of stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-box-seam"></i>
                    <h4>No Products Available</h4>
                    <p>Check back soon for new products.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ═══ CART OVERLAY ═══ -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>

<!-- ═══ CART SIDEBAR ═══ -->
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-sidebar-header">
        <h3>🛒 Your Cart <span id="cartCount" style="font-size:0.85rem;color:#94a3b8;font-weight:400;"></span></h3>
        <button class="close-cart" onclick="closeCart()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cart-sidebar-items" id="cartItems">
        <div class="cart-empty-state" id="cartEmpty">
            <i class="bi bi-cart3"></i>
            <p>Your cart is empty.<br>Add some products!</p>
        </div>
    </div>
    <div class="cart-sidebar-footer">
        <div class="cart-total-row">
            <span>Subtotal</span>
            <span id="cartSubtotal">₱0.00</span>
        </div>
        <div class="cart-total-row grand">
            <span>Total</span>
            <span id="cartTotal">₱0.00</span>
        </div>
        <button class="checkout-btn" id="checkoutBtn" onclick="openCheckout()" disabled>
            <i class="bi bi-bag-check"></i> Proceed to Checkout
        </button>
    </div>
</div>

<!-- ═══ CHECKOUT MODAL ═══ -->
<div class="modal-overlay" id="checkoutModal">
    <div class="modal-box">
        <div class="modal-title">🛍️ Place Order</div>
        <div class="modal-subtitle">Review your order before confirming</div>

        <!-- ORDER SUMMARY -->
        <div class="order-summary">
            <div class="order-summary-title">Order Summary</div>
            <div id="orderSummaryItems"></div>
            <div class="order-summary-total">
                <span>Total</span>
                <span id="orderTotal">₱0.00</span>
            </div>
        </div>

        <!-- DELIVERY INFO -->
        <div class="delivery-info">
            <div class="delivery-info-title">📍 Delivery Address</div>
            <p>
                <?php echo htmlspecialchars($customer['name']); ?><br>
                <?php echo htmlspecialchars($customer['home_address'] ?? ''); ?>,
                <?php echo htmlspecialchars($customer['barangay'] ?? ''); ?>,
                <?php echo htmlspecialchars($customer['municipality'] ?? ''); ?>
                <?php echo !empty($customer['zip_code']) ? ', ' . htmlspecialchars($customer['zip_code']) : ''; ?><br>
                📞 <?php echo htmlspecialchars($customer['phone'] ?? ''); ?>
            </p>
        </div>

        <!-- PAYMENT METHOD -->
        <div class="payment-method">
            <div class="payment-label">Payment Method</div>
            <div class="payment-option selected" onclick="selectPayment(this, 'cod')">
                <i class="bi bi-cash-coin"></i>
                <span>Cash on Delivery</span>
            </div>
        </div>

        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeModal('checkoutModal')">Cancel</button>
            <button class="btn-confirm" onclick="confirmOrder()">Place Order</button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="toastMsg"></span>
</div>
<!-- PRODUCT INFO MODAL -->
<div class="modal-overlay" id="productModal">
    <div class="modal-box" style="width:500px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div class="modal-title" id="modalProductName">Product Name</div>
            <button onclick="closeModal('productModal')" style="background:#f0f4f8;border:none;border-radius:10px;width:34px;height:34px;cursor:pointer;font-size:1rem;color:#94a3b8;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- IMAGE -->
        <div id="modalProductImg" style="width:100%;height:200px;background:linear-gradient(135deg,#f0f4f8,#e2e8f0);border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:20px;overflow:hidden;">
            <i class="bi bi-image" style="font-size:3rem;color:#94a3b8;"></i>
        </div>

        <!-- INFO -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
            <div style="background:#f0f4f8;border-radius:12px;padding:14px;">
                <div style="font-size:0.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Price</div>
                <div id="modalProductPrice" style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:#00a8c6;"></div>
            </div>
            <div style="background:#f0f4f8;border-radius:12px;padding:14px;">
                <div style="font-size:0.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Stock</div>
                <div id="modalProductStock" style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:#0d1b2a;"></div>
            </div>
        </div>

        <div style="background:#f0f4f8;border-radius:12px;padding:14px;margin-bottom:20px;">
            <div style="font-size:0.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Category</div>
            <div id="modalProductCategory" style="font-size:0.9rem;font-weight:600;color:#1e293b;"></div>
        </div>

        <div id="modalProductDescWrap" style="background:#f0f4f8;border-radius:12px;padding:14px;margin-bottom:20px;display:none;">
            <div style="font-size:0.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Description</div>
            <div id="modalProductDesc" style="font-size:0.88rem;color:#1e293b;line-height:1.6;"></div>
        </div>

        <button id="modalAddBtn" class="checkout-btn" onclick="addToCartFromModal()">
            <i class="bi bi-cart-plus"></i> Add to Cart
        </button>
    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<script src="/Bathala/assets/js/customer.js"></script>



</body>
</html>
