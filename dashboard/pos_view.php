<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - DeiTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/Bathala/assets/css/pos.css">
</head>
<body>

<div class="pos-wrapper">

    <!-- ═══════════════════════════════ -->
    <!-- LEFT: PRODUCTS PANEL           -->
    <!-- ═══════════════════════════════ -->
    <div class="products-panel">

        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="top-bar-left">
                <img src="/Bathala/src/logo.png" height="36" style="object-fit:contain;">
                <h1>DeiTrack: Bathala Tissue <span style="color:#00a8c6;">POS System</span></h1>
            </div>

            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search products...">
            </div>

            <div class="user-dropdown" id="userDropdown">
                <div class="user-badge" onclick="toggleDropdown()">
                    <i class="bi bi-person-circle"></i>
                    <?php echo htmlspecialchars($user['name'] ?? $user['email']); ?>
                    <i class="bi bi-chevron-down" style="font-size:0.7rem;"></i>
                </div>
                <div class="pos-dropdown-menu" id="dropdownMenu">
    <a href="#"><i class="bi bi-person"></i> Edit Account</a>
    <div class="dropdown-divider"></div>
    <a href="/Bathala/LandingPage/index.html" class="logout-link">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
                </div>
            </div>

        </div><!-- end top-bar -->

        <!-- CATEGORY FILTER TABS -->
        <div class="category-tabs">
            <button class="cat-tab active" onclick="filterCategory('all')">
                <i class="bi bi-grid"></i> All
            </button>
            <?php foreach ($categories as $cat): ?>
            <button class="cat-tab" onclick="filterCategory('<?php echo htmlspecialchars($cat); ?>')">
                <?php echo htmlspecialchars($cat); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- PRODUCTS BODY -->
        <div class="products-body">
            <?php if (!empty($products)): ?>

                <?php foreach ($categories as $cat): ?>
                <div class="category-section" data-category="<?php echo htmlspecialchars($cat); ?>">
                    <div class="category-title">
                        <i class="bi bi-tag-fill"></i>
                        <?php echo htmlspecialchars($cat); ?>
                    </div>
                    <div class="products-grid">
                        <?php foreach ($products as $p): ?>
                            <?php if ($p['category'] === $cat): ?>
                            <div class="product-card <?php echo $p['stock'] == 0 ? 'out-of-stock' : ''; ?>"
                                 data-id="<?php echo $p['id']; ?>"
                                 data-name="<?php echo htmlspecialchars($p['name']); ?>"
                                 data-price="<?php echo $p['price']; ?>"
                                 data-stock="<?php echo $p['stock']; ?>"
                                 data-category="<?php echo htmlspecialchars($p['category']); ?>"
                                 data-image="<?php echo htmlspecialchars($p['image'] ?? ''); ?>"
                                 data-desc="<?php echo htmlspecialchars($p['description'] ?? ''); ?>"
                                 onclick="openPosProductModal(this)">
                                 

                                <!-- PRODUCT IMAGE -->
                                <div class="product-img">
                                    <?php if (!empty($p['image'])): ?>
                                        <img src="/Bathala/assets/images/products/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-img-placeholder">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="product-price">₱<?php echo number_format($p['price'], 2); ?></div>
                                <div class="product-stock <?php echo $p['stock'] <= 10 ? 'low' : ''; ?>">
                                    <i class="bi bi-box"></i> Stock: <?php echo $p['stock']; ?>
                                </div>
                                <div class="add-btn"><i class="bi bi-plus"></i></div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-products">
                    <i class="bi bi-box-seam"></i>
                    <p>No products available.<br>Ask the warehouse to add products.</p>
                </div>
            <?php endif; ?>
        </div><!-- end products-body -->

    </div><!-- end products-panel -->

    <!-- ═══════════════════════════════ -->
    <!-- RIGHT: CART PANEL              -->
    <!-- ═══════════════════════════════ -->
    <div class="cart-panel">

        <div class="cart-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <h2>Order</h2>
                <span class="cart-count" id="cartCount">0</span>
            </div>
            <button class="clear-btn" onclick="clearCart()">Clear all</button>
        </div>

        <!-- ORDER TYPE -->
        <div class="order-type-section">
            <div class="order-type-label">Order Type</div>
            <div class="order-type-btns">
                <button class="type-btn active" id="btnWalkin" onclick="setOrderType('walkin')">
                    <i class="bi bi-person-walking"></i> Walk-in
                </button>
                <button class="type-btn" id="btnDelivery" onclick="setOrderType('delivery')">
                    <i class="bi bi-truck"></i> Delivery
                </button>
            </div>
        </div>

        <!-- DELIVERY FIELDS -->
        <div class="delivery-fields" id="deliveryFields">
            <input type="text" id="customerName" placeholder="Customer Name">
            <input type="text" id="customerPhone" placeholder="Phone Number">
            <input type="text" id="customerAddress" placeholder="Delivery Address">
        </div>

        <!-- CART ITEMS -->
        <div class="cart-items" id="cartItems">
            <div class="cart-empty" id="cartEmpty">
                <i class="bi bi-cart3"></i>
                <p>Cart is empty.<br>Click a product to add.</p>
            </div>
        </div>

        <!-- CART FOOTER -->
        <div class="cart-footer">
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span id="subtotal">₱0.00</span>
                </div>
                <div class="total-row grand">
                    <span>Total</span>
                    <span id="grandTotal">₱0.00</span>
                </div>
            </div>
            <button class="checkout-btn" id="checkoutBtn" onclick="openCheckout()" disabled>
                <i class="bi bi-bag-check"></i>
                <span id="checkoutLabel">Settle Payment</span>
            </button>
        </div>

    </div><!-- end cart-panel -->

</div><!-- end pos-wrapper -->

<!-- ═══════════════════════════════ -->
<!-- WALKIN PAYMENT MODAL           -->
<!-- ═══════════════════════════════ -->
<div class="modal-overlay" id="walkinModal">
    <div class="modal-box">
        <div class="modal-title">💳 Settle Payment</div>
        <div class="modal-subtitle">Walk-in customer</div>
        <div class="modal-total">
            <div class="modal-total-label">Amount Due</div>
            <div class="modal-total-amount" id="modalTotal">₱0.00</div>
        </div>
        <div class="payment-input-label">Cash Received</div>
        <input type="number" class="payment-input" id="cashInput" placeholder="0.00" oninput="calcChange()">
        <div class="change-display" id="changeDisplay">
            <div class="change-label">Change</div>
            <div class="change-amount" id="changeAmount">₱0.00</div>
        </div>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeModal('walkinModal')">Cancel</button>
            <button class="btn-confirm" onclick="confirmWalkin()">Confirm</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════ -->
<!-- DELIVERY CONFIRM MODAL         -->
<!-- ═══════════════════════════════ -->
<div class="modal-overlay" id="deliveryModal">
    <div class="modal-box">
        <div class="modal-title">🚴 Confirm Delivery</div>
        <div class="modal-subtitle">Order will be assigned for delivery</div>
        <div class="modal-total">
            <div class="modal-total-label">Order Total</div>
            <div class="modal-total-amount" id="deliveryModalTotal">₱0.00</div>
        </div>
        <div style="background:var(--bg);border-radius:12px;padding:14px;margin-bottom:20px;font-size:0.85rem;">
            <div style="color:var(--muted);margin-bottom:6px;">Delivery Details</div>
            <div id="deliverySummary" style="color:var(--dark);line-height:1.8;"></div>
        </div>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeModal('deliveryModal')">Cancel</button>
            <button class="btn-confirm" onclick="confirmDelivery()">Place Order</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════ -->
<!-- TOAST                          -->
<!-- ═══════════════════════════════ -->
<div class="toast" id="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="toastMsg">Order placed successfully!</span>
</div>

<!-- PRODUCT INFO MODAL -->
<div class="modal-overlay" id="productModal">
    <div class="modal-box" style="width:500px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div class="modal-title" id="posModalProductName"></div>
            <button onclick="closePosProductModal()" style="background:#f0f4f8;border:none;border-radius:10px;width:34px;height:34px;cursor:pointer;font-size:1rem;color:#94a3b8;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div id="posModalProductImg" style="width:100%;height:200px;background:linear-gradient(135deg,#f0f4f8,#e2e8f0);border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:20px;overflow:hidden;">
            <i class="bi bi-image" style="font-size:3rem;color:#94a3b8;"></i>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
            <div style="background:#f0f4f8;border-radius:12px;padding:14px;">
                <div style="font-size:0.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Price</div>
                <div id="posModalProductPrice" style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:#00a8c6;"></div>
            </div>
            <div style="background:#f0f4f8;border-radius:12px;padding:14px;">
                <div style="font-size:0.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Stock</div>
                <div id="posModalProductStock" style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:#0d1b2a;"></div>
            </div>
        </div>

        <div id="posModalDescWrap" style="background:#f0f4f8;border-radius:12px;padding:14px;margin-bottom:20px;display:none;">
            <div style="font-size:0.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Description</div>
            <div id="posModalProductDesc" style="font-size:0.88rem;color:#1e293b;line-height:1.6;"></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <button onclick="closePosProductModal()" class="btn-cancel">Close</button>
            <button id="posModalAddBtn" onclick="addToCartFromPosModal()" class="btn-confirm">
                <i class="bi bi-plus"></i> Add to Cart
            </button>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Bathala/assets/js/pos.js"></script>

</body>
</html>
