let cart = [];
let orderType = 'walkin';

// ─── CATEGORY FILTER ───────────────────────────────────
function filterCategory(category) {
    // Update active tab
    document.querySelectorAll('.cat-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.closest('.cat-tab').classList.add('active');

    let visibleCount = 0;
    document.querySelectorAll('.product-card').forEach(card => {
        const match = category === 'all' || card.dataset.category.toLowerCase() === category.toLowerCase();
        card.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    // Clear any active search so filter + search don't fight each other
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';

    toggleNoResults(visibleCount === 0);
}

function toggleNoResults(show) {
    const msg = document.getElementById('noResultsMsg');
    const grid = document.getElementById('productsGrid');
    if (msg) msg.style.display = show ? 'block' : 'none';
    if (grid) grid.style.display = show ? 'none' : 'grid';
}

// ─── PRODUCT MODAL ─────────────────────────────────────
// ─── PRODUCT CARD CLICK → ADD TO CART DIRECTLY ────────
function openPosProductModal(card) {
    if (card.classList.contains('out-of-stock')) return;

    const id    = card.dataset.id;
    const name  = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    const stock = parseInt(card.dataset.stock);

    const existing = cart.find(i => i.id === id);
    
    if (existing) {
        if (existing.qty >= stock) { showToast('Max stock reached!', true); return; }
        existing.qty++;
    } else {
        cart.push({ id, name, price, qty: 1, stock });
    }

    renderCart();
    showToast(`${name} added to cart`);
}

// ─── IMAGE MANAGER ─────────────────────────────────────
let imgMgrProducts  = [];
let imgMgrTargetId  = null;

function openImageManager() {
    // close dropdown
    document.getElementById('dropdownMenu')?.classList.remove('show');

    // fetch all products (including zero stock ones)
    fetch('/backend/get_all_products.php')
        .then(r => r.json())
        .then(data => {
            imgMgrProducts = data;
            renderImgMgrGrid(data);
            openModal('imageManagerModal');
        })
        .catch(() => showToast('Failed to load products', true));
}

function renderImgMgrGrid(products) {
    const grid = document.getElementById('imgMgrGrid');

    if (!products || products.length === 0) {
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--muted);">
            <i class="bi bi-box-seam" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No products found.</div>`;
        return;
    }

    grid.innerHTML = '';

    products.forEach(function(p) {
        const hasImg = p.image && p.image !== '' && p.image !== null;
        const imgSrc = hasImg ? '/assets/images/products/' + p.image : '';

        const card = document.createElement('div');
        card.className = 'img-mgr-card';
        card.setAttribute('data-id', p.id);
        card.setAttribute('data-name', p.name.toLowerCase());

        card.innerHTML = `
            <div class="img-mgr-thumb" onclick="triggerImgUpload(${p.id})">
                ${hasImg
                    ? `<img src="${imgSrc}" alt="${p.name}">`
                    : `<i class="bi bi-image" style="font-size:2rem;color:#94a3b8;"></i>`}
                <div class="img-mgr-overlay"><i class="bi bi-upload"></i> Upload</div>
            </div>
            <div style="padding:10px 10px 4px;">
                <div style="font-size:0.8rem;font-weight:600;color:var(--dark);line-height:1.3;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${p.name}">${p.name}</div>
                <div style="font-size:0.72rem;color:var(--muted);">${p.category}</div>
            </div>
            <div style="padding:0 10px 10px;display:flex;gap:6px;">
                <button onclick="triggerImgUpload(${p.id})" class="img-mgr-btn upload-btn" style="flex:1;">
                    <i class="bi bi-upload"></i> ${hasImg ? 'Change' : 'Upload'}
                </button>
                ${hasImg ? `<button onclick="removeProductImage(${p.id})" class="img-mgr-btn remove-btn">
                    <i class="bi bi-trash"></i>
                </button>` : ''}
            </div>
        `;

        grid.appendChild(card);
    });
}

function filterImgManager(query) {
    const q = query.toLowerCase();
    const filtered = q ? imgMgrProducts.filter(p => p.name.toLowerCase().includes(q) || p.category.toLowerCase().includes(q)) : imgMgrProducts;
    renderImgMgrGrid(filtered);
}

function triggerImgUpload(productId) {
    imgMgrTargetId = productId;
    const input = document.getElementById('imgMgrFileInput');
    input.value = '';
    input.click();
}

function handleImgUpload(input) {
    if (!input.files.length || !imgMgrTargetId) return;
    const file = input.files[0];

    // 5MB limit
    if (file.size > 5 * 1024 * 1024) {
        showToast('File too large! Max 5MB.', true);
        return;
    }

    const formData = new FormData();
    formData.append('product_id', imgMgrTargetId);
    formData.append('image', file);

    // Show loading state on the card
    const card = document.querySelector(`.img-mgr-card[data-id="${imgMgrTargetId}"]`);
    const thumb = card?.querySelector('.img-mgr-thumb');
    if (thumb) thumb.style.opacity = '0.5';

    fetch('/backend/upload_product_image.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Update local data
            const p = imgMgrProducts.find(x => x.id == imgMgrTargetId);
            if (p) p.image = res.filename;

            renderImgMgrGrid(imgMgrProducts);
            showToast('Image updated!');

            // Also update the product card in POS view (live update)
            const posCard = document.querySelector(`.product-card[data-id="${imgMgrTargetId}"] .product-img`);
            if (posCard) {
                posCard.innerHTML = `<img src="/assets/images/products/${res.filename}" alt="">`;
            }
        } else {
            showToast('Error: ' + res.message, true);
            renderImgMgrGrid(imgMgrProducts);
        }
    })
    .catch(() => {
        showToast('Upload failed. Try again.', true);
        renderImgMgrGrid(imgMgrProducts);
    });
}

function removeProductImage(productId) {
    if (!confirm('Remove photo for this product?')) return;

    fetch('/backend/remove_product_image.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const p = imgMgrProducts.find(x => x.id == productId);
            if (p) p.image = '';
            renderImgMgrGrid(imgMgrProducts);
            showToast('Image removed.');

            // Live update POS card
            const posCard = document.querySelector(`.product-card[data-id="${productId}"] .product-img`);
            if (posCard) {
                posCard.innerHTML = `<div class="product-img-placeholder"><i class="bi bi-image"></i></div>`;
            }
        } else {
            showToast('Error: ' + res.message, true);
        }
    })
    .catch(() => showToast('Server error.', true));
}

// ─── RENDER CART ───────────────────────────────────────
function renderCart() {
    const container   = document.getElementById('cartItems');
    const empty       = document.getElementById('cartEmpty');
    const cartCount   = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn');

    // Always clear ALL cart items first
    container.querySelectorAll('.cart-item').forEach(i => i.remove());

    if (cart.length === 0) {
        empty.style.display = 'flex';  // ← was 'block', match your CSS
        cartCount.textContent = '0';
        checkoutBtn.disabled = true;
        updateTotals();
        return;
    }

    empty.style.display = 'none';
    cartCount.textContent = cart.reduce((sum, i) => sum + i.qty, 0);
    
    checkoutBtn.disabled = false;



    cart.forEach(item => {
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">₱${item.price.toFixed(2)} each</div>
            </div>
            <div class="cart-item-controls">
                <button class="qty-btn" onclick="changeQty('${item.id}', -1)">−</button>
                <span class="qty-display">${item.qty}</span>
                <button class="qty-btn" onclick="changeQty('${item.id}', 1)">+</button>
            </div>
            <div class="item-total">₱${(item.price * item.qty).toFixed(2)}</div>
            <button class="remove-item" onclick="removeItem('${item.id}')">
                <i class="bi bi-x"></i>
            </button>
        `;
        container.appendChild(div);
    });

    updateTotals();
}

// ─── CHANGE QTY ────────────────────────────────────────
function changeQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    
    item.qty += delta;
    if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
    else if (item.qty > item.stock) {
        item.qty = item.stock;
        showToast('Max stock reached!', true);
    }
    
    renderCart();
}

// ─── REMOVE ITEM ───────────────────────────────────────
function removeItem(id) {
    cart = cart.filter(i => i.id !== id);
    renderCart();
}

// ─── CLEAR CART ────────────────────────────────────────
function clearCart() {
    cart = [];
    renderCart();
}

// ─── TOTALS ────────────────────────────────────────────
function updateTotals() {
    const total = getTotal();
    document.getElementById('subtotal').textContent   = `₱${total.toFixed(2)}`;
    document.getElementById('grandTotal').textContent = `₱${total.toFixed(2)}`;
}

function getTotal() {
    return cart.reduce((sum, i) => sum + i.price * i.qty, 0);
}

// ─── ORDER TYPE ────────────────────────────────────────
function setOrderType(type) {
    orderType = type;
    document.getElementById('btnWalkin').classList.toggle('active', type === 'walkin');
    document.getElementById('btnDelivery').classList.toggle('active', type === 'delivery');
    document.getElementById('deliveryFields').classList.toggle('show', type === 'delivery');
    document.getElementById('checkoutLabel').textContent =
        type === 'walkin' ? 'Settle Payment' : 'Place Delivery Order';
}

// ─── SEARCH ────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function () {
    const query = this.value.toLowerCase();

    let visibleCount = 0;
    document.querySelectorAll('.product-card').forEach(card => {
        const match = card.dataset.name.toLowerCase().includes(query);
        card.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    toggleNoResults(visibleCount === 0);

    if (query === '') {
        document.querySelectorAll('.cat-tab').forEach((t, i) => t.classList.toggle('active', i === 0));
    } else {
        document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    }
});

// ─── CHECKOUT ──────────────────────────────────────────
function openCheckout() {
    if (cart.length === 0) return;
    const total = getTotal();

    if (orderType === 'walkin') {
        document.getElementById('modalTotal').textContent = `₱${total.toFixed(2)}`;
        document.getElementById('cashInput').value = '';
        document.getElementById('changeDisplay').classList.remove('show');
        openModal('walkinModal');
    } else {
        const name     = document.getElementById('customerName').value.trim();
        const phone    = document.getElementById('customerPhone').value.trim();
        const address  = document.getElementById('customerAddress').value.trim();
        const landmark = document.getElementById('customerLandmark').value.trim();

        if (!name || !phone) {
            showToast('Please fill in customer name and contact number!', true);
            return;
        }
        if (!address) {
            showToast('Please choose a delivery location on the map!', true);
            return;
        }

        document.getElementById('deliveryModalTotal').textContent = `₱${total.toFixed(2)}`;
        document.getElementById('deliverySummary').innerHTML = `
            <strong>${name}</strong><br>
            📞 ${phone}<br>
            📍 ${address}
            ${landmark ? `<br>🏷️ Landmark: ${landmark}` : ''}
        `;
        openModal('deliveryModal');
    }
}

// ─── LOCATION PICKER ───────────────────────────────────
let _locMap = null, _locMarker = null, _locSearchTimeout = null;
let _pendingLat = null, _pendingLng = null, _pendingAddress = '';

function openLocationPicker() {
    openModal('locationModal');
    document.getElementById('locSearchInput').value = '';
    document.getElementById('locSearchResults').classList.remove('show');
    setTimeout(initLocationMap, 50); // wait for modal to be visible before sizing the map
}

function initLocationMap() {
    const existingLat = parseFloat(document.getElementById('deliveryLat').value);
    const existingLng = parseFloat(document.getElementById('deliveryLng').value);
    const startLat = !isNaN(existingLat) ? existingLat : 15.4870; // default: Cabanatuan City
    const startLng = !isNaN(existingLng) ? existingLng : 120.9683;

    const pinIcon = L.divIcon({
        className: '',
        html: `<div style="width:34px;height:34px;background:linear-gradient(135deg,#00a8c6,#0064ff);border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 3px 12px rgba(0,168,198,0.5);"></div>`,
        iconSize: [34, 34], iconAnchor: [17, 34]
    });

    if (!_locMap) {
        _locMap = L.map('locationMap').setView([startLat, startLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(_locMap);

        _locMarker = L.marker([startLat, startLng], { icon: pinIcon, draggable: true }).addTo(_locMap);
        _locMarker.on('dragend', () => {
            const pos = _locMarker.getLatLng();
            reverseGeocode(pos.lat, pos.lng);
        });
        _locMap.on('click', (e) => {
            _locMarker.setLatLng(e.latlng);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });
    } else {
        _locMap.invalidateSize();
        _locMap.setView([startLat, startLng], 15);
        _locMarker.setLatLng([startLat, startLng]);
    }

    const savedAddr = document.getElementById('customerAddress').value;
    if (savedAddr && !isNaN(existingLat)) {
        _pendingLat = startLat; _pendingLng = startLng; _pendingAddress = savedAddr;
        document.getElementById('locAddressText').textContent = savedAddr;
        document.getElementById('confirmLocBtn').disabled = false;
    } else {
        reverseGeocode(startLat, startLng);
    }
}

function reverseGeocode(lat, lng) {
    document.getElementById('locAddressText').textContent = 'Looking up address…';
    document.getElementById('confirmLocBtn').disabled = true;

    // Goes through our own backend instead of calling Nominatim straight from
    // the browser -- see backend/geocode_lib.php for why.
    fetch(`/backend/geocode_reverse.php?lat=${lat}&lng=${lng}`)
        .then(r => r.json())
        .then(data => {
            const label = (data.success && data.display_name) ? data.display_name : `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            _pendingLat = lat; _pendingLng = lng; _pendingAddress = label;
            document.getElementById('locAddressText').textContent = label;
            document.getElementById('confirmLocBtn').disabled = false;
        })
        .catch(() => {
            // Still let the pin be used even if the label lookup failed --
            // raw coordinates work fine for the rider, they're just less readable.
            const label = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            _pendingLat = lat; _pendingLng = lng; _pendingAddress = label;
            document.getElementById('locAddressText').textContent = label;
            document.getElementById('confirmLocBtn').disabled = false;
        });
}

function debouncedLocSearch(query) {
    clearTimeout(_locSearchTimeout);
    if (query.trim().length < 3) {
        document.getElementById('locSearchResults').classList.remove('show');
        return;
    }
    _locSearchTimeout = setTimeout(() => searchLocationAddress(query), 400);
}

function searchLocationAddress(query) {
    const box = document.getElementById('locSearchResults');
    box.innerHTML = '<div class="loc-search-result-item" style="color:var(--muted);">Searching…</div>';
    box.classList.add('show');

    // Goes through our own backend instead of calling Nominatim straight from
    // the browser -- see backend/geocode_lib.php for why. That old direct
    // call could fail silently (caught and ignored), which is why the search
    // box would just sit there doing nothing with no clue why.
    fetch(`/backend/geocode_search.php?q=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                box.innerHTML = `<div class="loc-search-result-item" style="color:var(--danger);">${escHtmlPos(data.message || 'Search failed. Try again.')}</div>`;
                box.classList.add('show');
                return;
            }
            const results = data.results || [];
            if (!results.length) {
                box.innerHTML = '<div class="loc-search-result-item" style="color:var(--muted);">No results found</div>';
                box.classList.add('show');
                return;
            }
            box.innerHTML = results.map((r, i) =>
                `<div class="loc-search-result-item" onclick="selectSearchResult(${i})">${escHtmlPos(r.display_name)}</div>`
            ).join('');
            box.classList.add('show');
            _lastLocSearchResults = results;
        })
        .catch(() => {
            box.innerHTML = '<div class="loc-search-result-item" style="color:var(--danger);">Couldn\'t reach the address search. Check your connection or drag the pin on the map instead.</div>';
            box.classList.add('show');
        });
}

let _lastLocSearchResults = [];

function selectSearchResult(index) {
    const r = _lastLocSearchResults[index];
    if (!r) return;
    const lat = parseFloat(r.lat), lng = parseFloat(r.lon), label = r.display_name;

    document.getElementById('locSearchResults').classList.remove('show');
    document.getElementById('locSearchInput').value = label;
    _locMap.setView([lat, lng], 16);
    _locMarker.setLatLng([lat, lng]);
    _pendingLat = lat; _pendingLng = lng; _pendingAddress = label;
    document.getElementById('locAddressText').textContent = label;
    document.getElementById('confirmLocBtn').disabled = false;
}

function useThisLocation() {
    if (_pendingLat == null || _pendingLng == null) return;

    document.getElementById('customerAddress').value = _pendingAddress;
    document.getElementById('deliveryLat').value      = _pendingLat;
    document.getElementById('deliveryLng').value      = _pendingLng;

    document.getElementById('pickedLocationText').textContent = _pendingAddress;
    document.getElementById('pickedLocationCard').classList.add('show');
    document.getElementById('chooseLocBtn').style.display = 'none';

    closeModal('locationModal');
}

function escHtmlPos(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─── CALC CHANGE ───────────────────────────────────────
function calcChange() {
    const cash  = parseFloat(document.getElementById('cashInput').value) || 0;
    const total = getTotal();
    
    const display = document.getElementById('changeDisplay');

    if (cash >= total) {
        display.classList.add('show');
        document.getElementById('changeAmount').textContent = `₱${(cash - total).toFixed(2)}`;
    } else {
        display.classList.remove('show');
    }
}

// ─── CONFIRM WALKIN ────────────────────────────────────
function confirmWalkin() {
    const cash = parseFloat(document.getElementById('cashInput').value) || 0;
    if (cash < getTotal()) {
        showToast('Insufficient cash amount!', true);
        return;
    }
    submitOrder('walkin', {});
}

// ─── CONFIRM DELIVERY ──────────────────────────────────
function confirmDelivery() {
    const name     = document.getElementById('customerName').value.trim();
    const phone    = document.getElementById('customerPhone').value.trim();
    const address  = document.getElementById('customerAddress').value.trim();
    const landmark = document.getElementById('customerLandmark').value.trim();
    const lat      = document.getElementById('deliveryLat').value;
    const lng      = document.getElementById('deliveryLng').value;
    submitOrder('delivery', { name, phone, address, landmark, lat, lng });
}

// ─── SUBMIT ORDER ──────────────────────────────────────
function submitOrder(type, customer) {
    const data = {
        order_type:        type,
        total:             getTotal(),
        items:             cart,
        customer_name:     customer.name     || '',
        customer_phone:    customer.phone    || '',
        customer_address:  customer.address  || '',
        customer_landmark: customer.landmark || '',
        delivery_lat:      customer.lat      || null,
        delivery_lng:      customer.lng      || null
    };

    fetch('/backend/save_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeModal('walkinModal');
            closeModal('deliveryModal');
            openReceiptTab(res.order_id, type, customer);
            cart = [];
            renderCart();
            document.getElementById('customerName').value      = '';
            document.getElementById('customerPhone').value     = '';
            document.getElementById('customerAddress').value   = '';
            document.getElementById('customerLandmark').value  = '';
            document.getElementById('deliveryLat').value       = '';
            document.getElementById('deliveryLng').value       = '';
            document.getElementById('pickedLocationCard').classList.remove('show');
            document.getElementById('chooseLocBtn').style.display = 'flex';
            showToast(type === 'walkin' ? 'Payment confirmed! ✓' : 'Delivery order placed! 🚴');

            if (type === 'delivery' && res.tracking_token) {
                showTrackingLinkModal(res.order_id, res.tracking_token);
            }
        } else {
            showToast('Error: ' + res.message, true);
        }
    })
    .catch(() => showToast('Server error. Try again.', true));
}

// ─── TRACKING LINK (delivery orders) ───────────────────
let _currentTrackingLink = '';

function showTrackingLinkModal(orderId, token) {
    _currentTrackingLink = `${window.location.origin}/customer/track_order.php?id=${orderId}&token=${token}`;

    document.getElementById('trackingOrderLabel').textContent = 'Order #' + String(orderId).padStart(4, '0');
    document.getElementById('trackingLinkInput').value = _currentTrackingLink;

    const shareBtn = document.getElementById('shareTrackingLinkBtn');
    shareBtn.style.display = (navigator.share) ? 'flex' : 'none';

    document.getElementById('trackingLinkModal').classList.add('show');
}

function copyTrackingLink() {
    const input = document.getElementById('trackingLinkInput');
    input.select();
    input.setSelectionRange(0, 99999);

    const done = () => {
        const btn = document.getElementById('copyTrackingLinkBtn');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
        showToast('Tracking link copied — paste it in Messenger to send to the customer');
        setTimeout(() => { btn.innerHTML = original; }, 1800);
    };

    if (navigator.clipboard) {
        navigator.clipboard.writeText(_currentTrackingLink).then(done).catch(() => {
            document.execCommand('copy');
            done();
        });
    } else {
        document.execCommand('copy');
        done();
    }
}

function shareTrackingLink() {
    if (navigator.share) {
        navigator.share({
            title: 'Track your DeiTrack delivery',
            text: 'Track your order and rider live:',
            url: _currentTrackingLink
        }).catch(() => {});
    }
}

// ─── OPEN RECEIPT IN NEW TAB ───────────────────────────
function openReceiptTab(orderId, type, customer) {
    const total  = getTotal();
    const cash   = parseFloat(document.getElementById('cashInput')?.value) || 0;
    const change = cash - total;
    const now    = new Date();
    const logoSrc = '/src/logo.png';

    const dateStr = now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) +
                    ' ' + now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });

    const orderNo = '#' + String(orderId).padStart(4, '0');
    const typeStr = type === 'walkin' ? '🏪 Walk-in' : '🚴 Delivery';
    const cashier = document.querySelector('.user-badge')?.innerText.replace(/\s+/g, ' ').trim() || 'Staff';

    const itemsHTML = cart.map(item => `
        <div class="receipt-item">
            <div class="receipt-item-left">
                <div class="receipt-item-name">${item.name}</div>
                <div class="receipt-item-sub">₱${item.price.toFixed(2)} × ${item.qty}</div>
            </div>
            <div class="receipt-item-total">₱${(item.price * item.qty).toFixed(2)}</div>
        </div>
    `).join('');

    let extraHTML = '';
    if (type === 'walkin') {
        extraHTML = `
            <div class="cash-info">
                <div class="cash-row"><span>Cash</span><span>₱${cash.toFixed(2)}</span></div>
                <div class="cash-row change"><span>Change</span><span>₱${change.toFixed(2)}</span></div>
            </div>
        `;
    } else if (customer.name) {
        extraHTML = `
            <div class="delivery-info">
                <div class="delivery-label">📍 Delivery Details</div>
                <div><strong>${customer.name}</strong></div>
                <div>📞 ${customer.phone}</div>
                <div>📍 ${customer.address}</div>
            </div>
        `;
    }

    const win = window.open('', '_blank', 'width=480,height=750');
    win.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt ${orderNo} - DeiTrack</title>
            <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
            <style>
                * { margin:0; padding:0; box-sizing:border-box; }
                body { font-family:'DM Sans',sans-serif; background:#f0f4f8; display:flex; flex-direction:column; align-items:center; padding:20px; min-height:100vh; }
                .receipt-wrap { background:white; width:320px; border-radius:16px; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.15); }
                .receipt-header { background:linear-gradient(135deg,#0d1b2a,#1a3a5c); padding:24px; text-align:center; color:white; }
                .receipt-header img { width:40px; display:block; margin:0 auto 10px; }
                .receipt-header h2 { font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:800; }
                .receipt-header p { font-size:0.75rem; color:rgba(255,255,255,0.6); margin-top:2px; }
                .receipt-body { padding:20px; }
                .meta-row { display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.82rem; }
                .meta-label { font-size:0.65rem; color:#94a3b8; font-weight:600; text-transform:uppercase; }
                .meta-value { font-weight:700; color:#0d1b2a; }
                .divider { border-top:1.5px dashed #e2e8f0; margin:14px 0; }
                .receipt-item { display:flex; justify-content:space-between; margin-bottom:10px; gap:8px; }
                .receipt-item-left { flex:1; }
                .receipt-item-name { font-weight:600; font-size:0.85rem; color:#0d1b2a; line-height:1.3; }
                .receipt-item-sub { font-size:0.72rem; color:#94a3b8; margin-top:2px; }
                .receipt-item-total { font-weight:700; font-size:0.85rem; color:#0d1b2a; white-space:nowrap; }
                .total-row { display:flex; justify-content:space-between; font-size:0.85rem; color:#64748b; margin-bottom:4px; }
                .grand-total { font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:800; color:#0d1b2a; margin-top:8px; padding-top:8px; border-top:2px solid #e2e8f0; display:flex; justify-content:space-between; }
                .cash-info { margin-top:12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:12px; }
                .cash-row { display:flex; justify-content:space-between; font-size:0.85rem; color:#64748b; margin-bottom:4px; }
                .cash-row.change { color:#16a34a; font-weight:700; font-size:0.95rem; }
                .delivery-info { margin-top:12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px; font-size:0.82rem; color:#1e293b; line-height:1.8; }
                .delivery-label { font-weight:700; color:#2563eb; font-size:0.7rem; text-transform:uppercase; margin-bottom:6px; }
                .receipt-footer { text-align:center; margin-top:20px; padding-top:14px; border-top:1.5px dashed #e2e8f0; font-size:0.75rem; color:#94a3b8; }
                .save-btn { margin-top:20px; width:320px; padding:14px; background:#E85D26; color:white; border:none; border-radius:12px; font-family:'DM Sans',sans-serif; font-size:0.95rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:0.2s; }
                .save-btn:hover { background:#cc4f1f; transform:translateY(-1px); box-shadow:0 6px 15px rgba(232,93,38,0.35); }
                @media print { body { background:white; padding:0; } .save-btn { display:none; } .receipt-wrap { box-shadow:none; border-radius:0; } }
            </style>
        </head>
        <body>
            <div class="receipt-wrap">
                <div class="receipt-header">
                    <img src="${logoSrc}" alt="Logo">
                    <h2>DeiTrack</h2>
                    <p>Bathala Tissue Supplier</p>
                    <p>Quezon City & Bulacan</p>
                </div>
                <div class="receipt-body">
                    <div class="meta-row">
                        <div><div class="meta-label">Order #</div><div class="meta-value">${orderNo}</div></div>
                        <div style="text-align:right;"><div class="meta-label">Date</div><div class="meta-value">${dateStr}</div></div>
                    </div>
                    <div class="meta-row" style="margin-top:8px;">
                        <div><div class="meta-label">Type</div><div class="meta-value">${typeStr}</div></div>
                        <div style="text-align:right;"><div class="meta-label">Cashier</div><div class="meta-value">${cashier}</div></div>
                    </div>
                    <div class="divider"></div>
                    ${itemsHTML}
                    <div class="divider"></div>
                    <div class="total-row"><span>Subtotal</span><span>₱${total.toFixed(2)}</span></div>
                    <div class="grand-total"><span>TOTAL</span><span>₱${total.toFixed(2)}</span></div>
                    ${extraHTML}
                    <div class="receipt-footer">
                        <div>Thank you for your purchase!</div>
                        <div style="margin-top:4px;opacity:0.6;">© 2026 Bathala Tissue Supplier</div>
                    </div>
                </div>
            </div>
            <button class="save-btn" onclick="window.print()">💾 Save as PDF / Print</button>
        </body>
        </html>
    `);
    win.document.close();
}

// ─── MODAL HELPERS ─────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

// ─── USER DROPDOWN ─────────────────────────────────────
function toggleDropdown() {
    document.getElementById('dropdownMenu')?.classList.toggle('show');
}

document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('userDropdown');
    const menu     = document.getElementById('dropdownMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.remove('show');
    }
});

// ─── CUSTOMER ORDER NOTIFICATIONS ──────────────────────
// Polls for delivery orders placed by customers that are still 'pending'
// (i.e. not yet reviewed by staff). Staff confirms or declines them here;
// confirming makes the order visible to riders on the delivery dashboard.
let _knownPendingIds = new Set();
let _notifPollTimer  = null;

function startOrderNotifPolling() {
    fetchPendingCustomerOrders(true); // silent first load, no "new order" ding
    _notifPollTimer = setInterval(() => fetchPendingCustomerOrders(false), 7000);
}

function fetchPendingCustomerOrders(isFirstLoad) {
    fetch('/backend/get_pending_customer_orders.php')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            renderOrderNotifList(res.orders, isFirstLoad);
        })
        .catch(() => {});
}

function renderOrderNotifList(orders, isFirstLoad) {
    const badge     = document.getElementById('notifBadge');
    const bell      = document.getElementById('notifBell');
    const listEl    = document.getElementById('notifOrdersList');
    const emptyMsg  = document.getElementById('notifEmptyMsg');

    // Detect brand-new orders since last poll (skip on first load)
    const currentIds = new Set(orders.map(o => o.id));
    if (!isFirstLoad) {
        const newOnes = orders.filter(o => !_knownPendingIds.has(o.id));
        if (newOnes.length > 0) {
            bell.classList.remove('has-new');
            void bell.offsetWidth; // restart animation
            bell.classList.add('has-new');
            showToast(newOnes.length === 1
                ? `New customer order #${String(newOnes[0].id).padStart(4,'0')} needs confirmation`
                : `${newOnes.length} new customer orders need confirmation`);
        }
    }
    _knownPendingIds = currentIds;

    if (orders.length > 0) {
        badge.style.display = 'flex';
        badge.textContent = orders.length > 99 ? '99+' : orders.length;
    } else {
        badge.style.display = 'none';
    }

    if (orders.length === 0) {
        emptyMsg.style.display = 'block';
        listEl.innerHTML = '';
        return;
    }
    emptyMsg.style.display = 'none';

    listEl.innerHTML = orders.map(o => `
        <div class="order-notif-card" id="onc-${o.id}">
            <div class="onc-top">
                <span class="onc-order-id">Order #${String(o.id).padStart(4, '0')}</span>
                <span class="onc-total">₱${parseFloat(o.total).toFixed(2)}</span>
            </div>
            <div class="onc-row"><i class="bi bi-person"></i> ${escapeHtml(o.customer_name || 'Customer')}</div>
            ${o.customer_phone ? `<div class="onc-row"><i class="bi bi-telephone"></i> ${escapeHtml(o.customer_phone)}</div>` : ''}
            <div class="onc-row"><i class="bi bi-geo-alt"></i> ${escapeHtml(o.customer_address || 'No address given')}</div>
            <div class="onc-items">${escapeHtml(o.items_summary || 'No items')}</div>
            <div class="onc-btns">
                <button class="onc-btn onc-decline" onclick="handleOrderReview(${o.id}, 'decline', this)">Decline</button>
                <button class="onc-btn onc-confirm" onclick="handleOrderReview(${o.id}, 'confirm', this)">Confirm</button>
            </div>
        </div>
    `).join('');
}

function handleOrderReview(orderId, action, btnEl) {
    const card = document.getElementById(`onc-${orderId}`);
    card.querySelectorAll('button').forEach(b => b.disabled = true);

    fetch('/backend/confirm_customer_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, action })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(action === 'confirm'
                ? `Order #${String(orderId).padStart(4,'0')} confirmed — sent to riders 🚴`
                : `Order #${String(orderId).padStart(4,'0')} declined`);
            card.remove();
            fetchPendingCustomerOrders(false);
        } else {
            showToast('Error: ' + (res.message || 'Could not update order'), true);
            card.querySelectorAll('button').forEach(b => b.disabled = false);
        }
    })
    .catch(() => {
        showToast('Server error. Try again.', true);
        card.querySelectorAll('button').forEach(b => b.disabled = false);
    });
}

function toggleOrderNotifPanel(force) {
    const panel   = document.getElementById('orderNotifPanel');
    const overlay = document.getElementById('notifPanelOverlay');
    const show = force !== undefined ? force : !panel.classList.contains('show');
    panel.classList.toggle('show', show);
    overlay.classList.toggle('show', show);
    if (show) fetchPendingCustomerOrders(true);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', startOrderNotifPolling);

// ─── TOAST ─────────────────────────────────────────────
function showToast(msg, isError = false) {
    const toast    = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMsg');
    const icon     = toast.querySelector('i');

    toastMsg.textContent = msg;
    icon.className  = isError ? 'bi bi-exclamation-circle-fill' : 'bi bi-check-circle-fill';
    icon.style.color = isError ? '#ef4444' : '#22c55e';

    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// ─── MOBILE CART SHEET ─────────────────────────────────
// On narrow screens the cart panel becomes a bottom sheet (see the
// max-width:768px rules in pos.css) that's collapsed to just its header
// + totals by default. This just toggles the class that expands it;
// on wider screens the CSS media query doesn't apply, so this is a
// harmless no-op there.
function toggleMobileCart() {
    document.querySelector('.cart-panel').classList.toggle('expanded');
}