<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - DeiTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="/assets/css/pos.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/pos.css'); ?>">
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
                <img src="/src/logo.png" height="36" style="object-fit:contain;">
                <h1>DeiTrack: Bathala Tissue <span style="color:#00a8c6;">POS System</span></h1>
            </div>

            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search products...">
            </div>
            
            <div class="notif-bell" id="notifBell" onclick="toggleOrderNotifPanel()" title="Online orders awaiting confirmation">
                <i class="bi bi-bell"></i>
                <span class="notif-bell-label">Online Orders</span>
                <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
            </div>

            <div class="user-dropdown" id="userDropdown">
    <div class="user-badge" onclick="toggleDropdown()">
        <i class="bi bi-person-circle"></i>
        <?php echo htmlspecialchars($user['name'] ?? $user['email']); ?>
        <i class="bi bi-chevron-down" style="font-size:0.7rem;"></i>
    </div>

    <div class="pos-dropdown-menu" id="dropdownMenu">
        <a href="#" onclick="openImageManager(); return false;">
            <i class="bi bi-image"></i> Edit Product Images
        </a>
        <div class="dropdown-divider"></div>
        <a href="/login.php" class="logout-link">
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

                <div class="products-grid" id="productsGrid">
                    <?php foreach ($products as $p): ?>
                    <div class="product-card <?php echo $p['stock'] == 0 ? 'out-of-stock' : ''; ?>"
                         data-id="<?php echo $p['id']; ?>"
                         data-name="<?php echo htmlspecialchars($p['name']); ?>"
                         data-price="<?php echo $p['price']; ?>"
                         data-stock="<?php echo $p['stock']; ?>"
                         data-category="<?php echo htmlspecialchars($p['category']); ?>"
                         data-image="<?php echo htmlspecialchars($p['image'] ?? ''); ?>"
                         data-desc="<?php echo htmlspecialchars($p['description'] ?? ''); ?>"
                         onclick="openPosProductModal(this)">

                        <!-- Sits above/outside the white card, not inside it -->
                        <span class="category-tag"><?php echo htmlspecialchars($p['category']); ?></span>

                        <div class="product-box">
                            <!-- PRODUCT IMAGE -->
                            <div class="product-img">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="/assets/images/products/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                <?php else: ?>
                                    <div class="product-img-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="product-price">₱<?php echo number_format($p['price'], 2); ?></div>
                            <div class="product-stock <?php echo $p['stock'] <= $p['_low_threshold'] ? 'low' : ''; ?>">
                                <i class="bi bi-box"></i> Stock: <?php echo $p['stock']; ?>
                            </div>
                            <div class="add-btn"><i class="bi bi-plus"></i></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="empty-products" id="noResultsMsg" style="display:none;">
                    <i class="bi bi-search"></i>
                    <p>No products match your search or filter.</p>
                </div>

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

        <div class="cart-header" onclick="toggleMobileCart()">
            <div style="display:flex;align-items:center;gap:10px;">
                <h2>Order</h2>
                <span class="cart-count" id="cartCount">0</span>
                <i class="bi bi-chevron-down cart-expand-icon"></i>
            </div>
            <button class="clear-btn" onclick="event.stopPropagation(); clearCart()">Clear all</button>
        </div>

        <!-- Everything below the header + above the footer is the part
             that collapses behind the mobile bottom sheet; the header
             (tap to expand) and footer (totals + checkout) stay visible
             on small screens regardless of expanded state. -->
        <div class="cart-body-collapsible">

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

            <!-- Hidden fields populated by the map picker -->
            <input type="hidden" id="customerAddress">
            <input type="hidden" id="deliveryLat">
            <input type="hidden" id="deliveryLng">

            <!-- CHOOSE LOCATION BUTTON (shown until a location is picked) -->
            <button type="button" class="choose-location-btn" id="chooseLocBtn" onclick="openLocationPicker()">
                <i class="bi bi-geo-alt"></i> Choose Location on Map
            </button>

            <!-- PICKED LOCATION SUMMARY (shown after a location is picked) -->
            <div class="picked-location-card" id="pickedLocationCard">
                <i class="bi bi-geo-alt-fill"></i>
                <div class="ploc-text" id="pickedLocationText"></div>
                <span class="ploc-change" onclick="openLocationPicker()">Change</span>
            </div>

            <input type="text" id="customerLandmark" placeholder="Landmark (e.g. near barangay hall, beside sari-sari store)">
        </div>

        <!-- CART ITEMS -->
        <div class="cart-items" id="cartItems">
            <div class="cart-empty" id="cartEmpty">
                <i class="bi bi-cart3"></i>
                <p>Cart is empty.<br>Click a product to add.</p>
            </div>
        </div>

        </div><!-- end cart-body-collapsible -->

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
<!-- CUSTOMER ORDER NOTIFICATIONS   -->
<!-- ═══════════════════════════════ -->
<div class="notif-panel" id="orderNotifPanel">
    <div class="notif-panel-header">
        <span><i class="bi bi-bag-check-fill"></i> Online Orders Awaiting Confirmation</span>
        <button class="notif-panel-close" onclick="toggleOrderNotifPanel(false)"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="notif-panel-body" id="notifPanelBody">
        <div class="notif-empty" id="notifEmptyMsg">
            <i class="bi bi-check2-circle"></i>
            <p>No online orders waiting for confirmation.</p>
        </div>
        <div id="notifOrdersList"></div>
    </div>
</div>
<div class="notif-panel-overlay" id="notifPanelOverlay" onclick="toggleOrderNotifPanel(false)"></div>


<!-- ═══════════════════════════════ -->
<!-- IMAGE MANAGER MODAL            -->
<!-- ═══════════════════════════════ -->
<div class="modal-overlay" id="imageManagerModal">
  <div class="modal-box" style="width:680px;max-height:85vh;display:flex;flex-direction:column;padding:0;overflow:hidden;">

    <!-- Header -->
    <div style="padding:24px 28px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
      <div>
        <div class="modal-title" style="margin-bottom:4px;">🖼️ Product Images</div>
        <div class="modal-subtitle" style="margin-bottom:0;">Upload, change, or remove photos for each product</div>
      </div>
      <button onclick="closeModal('imageManagerModal')" style="background:#f0f4f8;border:none;border-radius:10px;width:36px;height:36px;cursor:pointer;font-size:1rem;color:#94a3b8;display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Search -->
    <div style="padding:14px 28px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div class="search-box" style="width:100%;">
        <i class="bi bi-search"></i>
        <input type="text" id="imgMgrSearch" placeholder="Search products..." oninput="filterImgManager(this.value)">
      </div>
    </div>

    <!-- Product Grid -->
    <div id="imgMgrGrid" style="overflow-y:auto;padding:20px 28px;display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:14px;flex:1;">
      <!-- JS will populate this -->
    </div>

    <!-- Hidden file input -->
    <input type="file" id="imgMgrFileInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleImgUpload(this)">

  </div>
</div>

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
<!-- LOCATION PICKER MODAL          -->
<!-- ═══════════════════════════════ -->
<div class="modal-overlay" id="locationModal">
    <div class="modal-box modal-box-lg">
        <div class="modal-title">📍 Choose Delivery Location</div>
        <div class="modal-subtitle">Search an address or tap/drag the pin on the map</div>

        <div class="loc-search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="locSearchInput" placeholder="Search address..." oninput="debouncedLocSearch(this.value)">
            <div class="loc-search-results" id="locSearchResults"></div>
        </div>

        <div id="locationMap"></div>

        <div class="loc-address-preview">
            <i class="bi bi-geo-alt-fill"></i>
            <span id="locAddressText">Looking up address…</span>
        </div>

        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeModal('locationModal')">Cancel</button>
            <button class="btn-confirm" id="confirmLocBtn" onclick="useThisLocation()" disabled>Use This Location</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════ -->
<!-- TRACKING LINK MODAL            -->
<!-- ═══════════════════════════════ -->
<div class="modal-overlay" id="trackingLinkModal">
    <div class="modal-box">
        <div class="modal-title">🚴 Delivery Order Placed!</div>
        <div class="modal-subtitle" id="trackingOrderLabel">Order #0000</div>

        <div style="background:var(--bg);border-radius:12px;padding:14px;margin-bottom:16px;font-size:0.85rem;color:var(--dark);">
            Send this link to the customer (e.g. on Facebook Messenger) so they can watch their order and rider live.
        </div>

        <div style="display:flex;gap:8px;margin-bottom:16px;">
            <input type="text" id="trackingLinkInput" readonly
                   style="flex:1;padding:10px 12px;border:1px solid var(--border);border-radius:10px;font-size:0.8rem;color:var(--muted);background:#f8fafc;">
            <button type="button" id="copyTrackingLinkBtn" onclick="copyTrackingLink()"
                    style="background:var(--primary);color:#fff;border:none;border-radius:10px;padding:0 16px;font-weight:600;cursor:pointer;white-space:nowrap;">
                <i class="bi bi-clipboard"></i> Copy
            </button>
        </div>

        <div class="modal-btns">
            <button class="btn-cancel" id="shareTrackingLinkBtn" onclick="shareTrackingLink()" style="display:none;">
                <i class="bi bi-share-fill"></i> Share…
            </button>
            <button class="btn-confirm" onclick="closeModal('trackingLinkModal')">Done</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="/assets/js/pos.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/pos.js'); ?>"></script>

</body>
</html>