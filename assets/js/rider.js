// ── PAGE SWITCHER ──────────────────────────────────────
let _mapRefreshTimer = null;

function showPage(name) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const target = document.getElementById('page-' + name);
    if (target) target.classList.add('active');

    document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));
    const activeLink = document.querySelector(`.nav-item[onclick*="'${name}'"]`);
    if (activeLink) activeLink.classList.add('active');

    const titles = { deliveries: 'My Deliveries', history: 'All Orders', map: 'Live Map', account: 'Account' };
    const titleEl = document.getElementById('topbarPageTitle');
    if (titleEl && titles[name]) titleEl.textContent = titles[name];

    if (name === 'history') loadHistory();

    if (name === 'map') {
        initMap();
        if (_mapRefreshTimer) clearInterval(_mapRefreshTimer);
        _mapRefreshTimer = setInterval(initMap, 10000);
    } else if (_mapRefreshTimer) {
        clearInterval(_mapRefreshTimer);
        _mapRefreshTimer = null;
    }

    closeSidebar();
}

// ─── MOBILE SIDEBAR TOGGLE ──────────────────────────────
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}

// ── ACCEPT / REJECT ────────────────────────────────────
function acceptOrder(orderId, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Accepting...';

    fetch('/backend/rider_accept_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, action: 'accept' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Order accepted!');
            // Update card state to "assigned" in-place
            updateCardToAssigned(orderId);
            // Track it so pollOrders knows it's already shown
            knownOrderIds.add(String(orderId));
            cardStatuses[String(orderId)] = 'assigned';
            updateStatBadge(1);
        } else {
            showToast(data.message || 'Already taken by another rider', true);
            const card = document.getElementById('card-' + orderId);
            if (card) {
                card.style.opacity = '0';
                setTimeout(() => { card.remove(); knownOrderIds.delete(String(orderId)); checkEmpty(); }, 350);
            }
        }
    })
    .catch(() => { showToast('Network error', true); btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Accept'; });
}

function updateCardToAssigned(orderId) {
    const card = document.getElementById('card-' + orderId);
    if (!card) return;
    card.classList.remove('card-new', 'new-order-flash');
    const pill = card.querySelector('.status-pill');
    if (pill) { pill.className = 'status-pill assigned'; pill.textContent = 'Assigned'; }
    const row = card.querySelector('.accept-reject-row');
    if (row) {
        const cName  = card.querySelector('.card-customer')?.textContent?.trim() || '';
        const cPhone = card.querySelector('a[href^="tel:"]')?.getAttribute('href')?.replace('tel:','') || '';
        const cAddr  = card.querySelector('.info-row span')?.textContent?.trim() || '';
        const pickupBtn = document.createElement('button');
        pickupBtn.className = 'btn-transit';
        pickupBtn.innerHTML = '<i class="bi bi-box-seam"></i> Confirm Pickup';
        pickupBtn.onclick = () => openPickupModal(orderId, cName, cPhone, cAddr);
        row.replaceWith(pickupBtn);
    }
}

function updateCardToTransit(orderId) {
    const card = document.getElementById('card-' + orderId);
    if (!card) return;
    const pill = card.querySelector('.status-pill');
    if (pill) { pill.className = 'status-pill in_transit'; pill.textContent = 'In Transit 🚴'; }
    const transitBtn = card.querySelector('.btn-transit');
    if (transitBtn) {
        const cName  = card.querySelector('.card-customer')?.textContent?.trim() || '';
        const cPhone = card.querySelector('a[href^="tel:"]')?.getAttribute('href')?.replace('tel:','') || '';
        const cAddr  = card.querySelector('.info-row span')?.textContent?.trim() || '';
        const deliverBtn = document.createElement('button');
        deliverBtn.className = 'btn-deliver';
        deliverBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Mark as Delivered';
        deliverBtn.onclick = () => openDeliverModal(orderId, cName, cPhone, cAddr);
        transitBtn.replaceWith(deliverBtn);
    }
}

function rejectOrder(orderId, btn) {
    fetch('/backend/rider_accept_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, action: 'reject' })
    })
    .then(() => {
        const card = document.getElementById('card-' + orderId);
        if (card) {
            card.style.transition = 'all 0.35s';
            card.style.opacity = '0';
            card.style.transform = 'translateX(30px)';
            setTimeout(() => {
                card.remove();
                knownOrderIds.delete(String(orderId));
                delete cardStatuses[String(orderId)];
                checkEmpty();
            }, 350);
        }
    })
    .catch(() => showToast('Network error', true));
}

// ── CONFIRM PICKUP MODAL (photo proof required) ────────
function openPickupModal(orderId, customerName, customerPhone, customerAddr) {
    document.getElementById('pickupOrderId').value = orderId;
    document.getElementById('pickupPhoto').value   = '';
    document.getElementById('pickupPhotoPreview').style.display    = 'none';
    document.getElementById('pickupPhotoPlaceholder').style.display = 'flex';

    document.getElementById('pickupCustomerName').textContent = customerName || '';
    document.getElementById('pickupCustomerAddr').textContent = customerAddr || '';

    const btn = document.getElementById('pickupConfirmBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-box-seam"></i> Confirm Pickup';
    document.getElementById('pickupModal').classList.add('show');
}

function previewPickupPhoto() {
    const input = document.getElementById('pickupPhoto');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('pickupPhotoPreviewImg').src = e.target.result;
            document.getElementById('pickupPhotoPreview').style.display = 'block';
            document.getElementById('pickupPhotoPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function submitPickup() {
    const orderId    = document.getElementById('pickupOrderId').value;
    const photoInput = document.getElementById('pickupPhoto');
    const btn        = document.getElementById('pickupConfirmBtn');

    if (!photoInput.files || !photoInput.files[0]) {
        showToast('📷 Pickup proof photo is required', true);
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';

    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('photo',    photoInput.files[0]);

    const send = (fd) => {
        fetch('/backend/rider_confirm_pickup.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal('pickupModal');
                showToast('🚴 Pickup confirmed — delivery started!');
                updateCardToTransit(orderId);
                cardStatuses[String(orderId)] = 'in_transit';
            } else {
                showToast(data.message || 'Failed', true);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-box-seam"></i> Confirm Pickup';
            }
        })
        .catch(() => {
            showToast('Network error', true);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-seam"></i> Confirm Pickup';
        });
    };

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => { formData.append('lat', pos.coords.latitude); formData.append('lng', pos.coords.longitude); send(formData); },
            ()  => send(formData),
            { timeout: 4000 }
        );
    } else {
        send(formData);
    }
}

// ── DELIVER MODAL ──────────────────────────────────────
function openDeliverModal(orderId, customerName, customerPhone, customerAddr) {
    document.getElementById('deliverOrderId').value       = orderId;
    document.getElementById('deliverCustomerPhone').value = customerPhone || '';
    document.getElementById('deliverPhoto').value         = '';
    document.getElementById('photoPreview').style.display    = 'none';
    document.getElementById('photoPlaceholder').style.display = 'flex';
    document.getElementById('deliverNotes').value         = '';

    const initial = (customerName || '?').charAt(0).toUpperCase();
    document.getElementById('deliverCustomerInitial').textContent = initial;
    document.getElementById('deliverCustomerName').textContent    = customerName || '';
    document.getElementById('deliverCustomerAddr').textContent    = customerAddr || '';

    const btn = document.getElementById('confirmBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Delivered';
    document.getElementById('deliverModal').classList.add('show');
}

function closeModal(id) { document.getElementById(id).classList.remove('show'); }

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});

// ── PHOTO PREVIEW ──────────────────────────────────────
function previewDeliveryPhoto() {
    const input = document.getElementById('deliverPhoto');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreviewImg').src = e.target.result;
            document.getElementById('photoPreview').style.display = 'block';
            document.getElementById('photoPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── CONFIRM DELIVERY ───────────────────────────────────
function submitDelivery() {
    const orderId       = document.getElementById('deliverOrderId').value;
    const customerPhone = document.getElementById('deliverCustomerPhone').value;
    const photoInput    = document.getElementById('deliverPhoto');
    const notes         = document.getElementById('deliverNotes').value;
    const btn           = document.getElementById('confirmBtn');

    if (!photoInput.files || !photoInput.files[0]) {
        showToast('📷 Proof photo is required', true);
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';

    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('photo',    photoInput.files[0]);
    formData.append('notes',    notes);

    const send = (fd) => {
        fetch('/backend/rider_confirm_delivery.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal('deliverModal');
                if (_navTarget && String(_navTarget.orderId) === String(orderId)) exitNavigation();
                // Remove card
                const card = document.getElementById('card-' + orderId);
                if (card) {
                    card.style.transition = 'all 0.4s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.remove();
                        knownOrderIds.delete(String(orderId));
                        delete cardStatuses[String(orderId)];
                        checkEmpty();
                    }, 400);
                }
                showDeliverySuccess(data);
            } else {
                showToast(data.message || 'Failed', true);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Delivered';
            }
        })
        .catch(() => {
            showToast('Network error', true);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Delivered';
        });
    };

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => { formData.append('lat', pos.coords.latitude); formData.append('lng', pos.coords.longitude); send(formData); },
            ()  => send(formData),
            { timeout: 4000 }
        );
    } else {
        send(formData);
    }
}

// ── DELIVERY SUCCESS MODAL ─────────────────────────────
function showDeliverySuccess(data) {
    const name    = data.customer_name  || 'Customer';
    const phone   = data.customer_phone || '';
    const orderId = data.order_id       || '';
    const ordFmt  = 'ORD-' + String(orderId).padStart(4, '0');

    document.getElementById('successCustomerName').textContent = name;
    document.getElementById('successOrderId').textContent      = ordFmt;

    const msg = `Hi ${name}, your order ${ordFmt} has been delivered! Thank you for ordering with DeiTrack. 🚴`;

    const smsLink = phone ? `sms:${phone}?body=${encodeURIComponent(msg)}` : '#';
    document.getElementById('successSmsLink').href = smsLink;
    document.getElementById('successSmsLink').style.opacity = phone ? '1' : '0.4';

    let waPhone = phone.replace(/\s/g, '');
    if (waPhone.startsWith('0')) waPhone = '63' + waPhone.slice(1);
    else if (waPhone.startsWith('+')) waPhone = waPhone.slice(1);
    const waLink = waPhone ? `https://wa.me/${waPhone}?text=${encodeURIComponent(msg)}` : '#';
    document.getElementById('successWaLink').href = waLink;
    document.getElementById('successWaLink').style.opacity = waPhone ? '1' : '0.4';

    document.getElementById('successModal').classList.add('show');
}

function closeSuccessAndGoHistory() {
    closeModal('successModal');
    showPage('history');
    loadHistory(); // Refresh completed list immediately
}

// ── HISTORY ────────────────────────────────────────────
function loadHistory() {
    // Reload the completed orders section live after a delivery is confirmed
    const container = document.getElementById('historyList');
    if (!container) return;
    container.innerHTML = '<div class="loading-spin"><i class="bi bi-arrow-repeat"></i> Loading...</div>';

    fetch('/backend/rider_history.php')
    .then(r => r.json())
    .then(data => {
        if (!data.length) {
            container.innerHTML = '<div class="empty-state" style="padding:30px 0;"><i class="bi bi-clock-history"></i><p>No completed deliveries yet</p></div>';
            return;
        }
        container.innerHTML = data.map(d => `
            <div class="history-item">
                <div style="flex:1;">
                    <div class="history-id">ORD-${String(d.id).padStart(4,'0')}</div>
                    <div class="history-customer">${escHtml(d.customer_name)}</div>
                    <div class="history-items">${escHtml(d.items_summary || '')}</div>
                    ${d.delivery_notes ? `<div class="history-items" style="margin-top:2px;font-style:italic;">"${escHtml(d.delivery_notes)}"</div>` : ''}
                </div>
                <div style="text-align:right;flex-shrink:0;margin-left:12px;">
                    <div class="history-amount">&#8369;${parseFloat(d.total).toLocaleString('en-PH',{minimumFractionDigits:2})}</div>
                    <div class="history-date">${formatDate(d.delivered_at || d.created_at)}</div>
                    ${d.delivery_photo ? `<img src="/assets/images/deliveries/${d.delivery_photo}" class="history-thumb" onclick="viewPhoto(this.src)">` : ''}
                </div>
            </div>
        `).join('');
    })
    .catch(() => {
        container.innerHTML = '<div class="empty-state"><i class="bi bi-wifi-off"></i><p>Failed to load</p></div>';
    });
}

function formatDate(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) + ' ' +
           d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
}

function viewPhoto(src) {
    const el = document.createElement('div');
    el.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.88);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;cursor:pointer;';
    el.innerHTML = `<img src="${src}" style="max-width:100%;max-height:90vh;border-radius:14px;object-fit:contain;">`;
    el.onclick = () => el.remove();
    document.body.appendChild(el);
}

// ── SMS MODAL ──────────────────────────────────────────
let currentSmsPhone = '', currentSmsName = '';

function openSmsModal(name, phone, autoDelivered) {
    currentSmsName  = name;
    currentSmsPhone = phone;
    document.getElementById('smsModal').classList.add('show');
    if (autoDelivered) {
        setSmsText('Hi {name}, your order has been delivered! Thank you for ordering with DeiTrack. 🚴');
    } else {
        document.getElementById('smsText').value = '';
        document.getElementById('smsLink').href  = '#';
    }
}
function setSmsText(template) {
    const text = template.replace('{name}', currentSmsName);
    document.getElementById('smsText').value = text;
    updateSmsLink();
}
function updateSmsLink() {
    const text = document.getElementById('smsText').value;
    document.getElementById('smsLink').href = `sms:${currentSmsPhone}?body=${encodeURIComponent(text)}`;
}
document.getElementById('smsText').addEventListener('input', updateSmsLink);

// ── ACCOUNT ────────────────────────────────────────────
function saveAccount(e) {
    e.preventDefault();
    const btn = e.target.querySelector('.btn-save');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
    fetch('/backend/rider_update_account.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            name:     document.getElementById('accName').value,
            email:    document.getElementById('accEmail').value,
            phone:    document.getElementById('accPhone').value,
            password: document.getElementById('accPassword').value,
        })
    })
    .then(r => r.json())
    .then(d => {
        showToast(d.success ? '✅ Account updated!' : (d.message || 'Failed'), !d.success);
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Changes';
    })
    .catch(() => { showToast('Network error', true); btn.disabled = false; });
}

// ── LEAFLET MAP + IN-APP NAVIGATION ─────────────────────
let _map = null, _selfMarker = null, _destMarker = null, _routeLine = null;
let _navTarget = null;        // { orderId, lat, lng, name, address }
let _lastRouteFetch = 0;
let _lastRouteFrom = null;    // avoid refetching when rider hasn't moved much

function initMap() {
    if (!navigator.geolocation) { document.getElementById('mapCoords').textContent = 'GPS not supported.'; return; }
    navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        document.getElementById('mapCoords').textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        if (!_map) {
            _map = L.map('liveMap', { attributionControl: false }).setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(_map);
        }
        const icon = L.divIcon({
            className: '',
            html: `<div style="width:46px;height:46px;background:linear-gradient(135deg,#00a8c6,#0064ff);border-radius:50%;border:3px solid white;box-shadow:0 3px 16px rgba(0,168,198,0.55);display:flex;align-items:center;justify-content:center;font-size:22px;">🚴</div>`,
            iconSize: [46,46], iconAnchor: [23,23]
        });
        if (!_selfMarker) {
            _selfMarker = L.marker([lat,lng], {icon}).addTo(_map).bindPopup('<strong>You are here</strong>');
        } else {
            _selfMarker.setLatLng([lat,lng]);
        }

        if (_navTarget) {
            updateNavigation(lat, lng);
        } else {
            _map.setView([lat,lng]);
        }
    }, () => { document.getElementById('mapCoords').textContent = 'Enable GPS to see location.'; });
}

// Called from a card's "Navigate" button. Falls back to Google Maps
// (new tab) if this order has no saved delivery pin (e.g. an older order).
function openNavigation(orderId, lat, lng, name, address) {
    if (lat === null || lng === null || lat === undefined || lng === undefined || lat === '' || lng === '') {
        window.open(`https://maps.google.com/?q=${encodeURIComponent(address || '')}`, '_blank');
        return;
    }
    _navTarget = { orderId, lat: parseFloat(lat), lng: parseFloat(lng), name, address };
    _lastRouteFetch = 0;
    _lastRouteFrom = null;

    document.getElementById('navTargetBanner').style.display = 'flex';
    document.getElementById('navTargetName').textContent = name || 'Customer';
    document.getElementById('navTargetAddr').textContent = address || '';
    document.getElementById('mapSectionLabel').textContent = 'Navigating to Customer';

    showPage('map');
}

function exitNavigation() {
    _navTarget = null;
    document.getElementById('navTargetBanner').style.display = 'none';
    document.getElementById('routeInfoCard').style.display = 'none';
    document.getElementById('mapSectionLabel').textContent = 'Your Live Location';
    if (_destMarker) { _map.removeLayer(_destMarker); _destMarker = null; }
    if (_routeLine)  { _map.removeLayer(_routeLine);  _routeLine  = null; }
}

function updateNavigation(riderLat, riderLng) {
    const destIcon = L.divIcon({
        className: '',
        html: `<div style="width:34px;height:34px;background:#ef4444;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>`,
        iconSize: [34,34], iconAnchor: [17,34]
    });
    if (!_destMarker) {
        _destMarker = L.marker([_navTarget.lat, _navTarget.lng], {icon: destIcon}).addTo(_map).bindPopup(_navTarget.address || 'Delivery Address');
    } else {
        _destMarker.setLatLng([_navTarget.lat, _navTarget.lng]);
    }

    _map.fitBounds([[riderLat, riderLng], [_navTarget.lat, _navTarget.lng]], { padding: [50, 80] });

    // Throttle route re-fetch (free public OSRM server) — every ~20s, and only
    // if the rider has actually moved a meaningful amount since the last fetch
    const now = Date.now();
    const moved = !_lastRouteFrom || haversine(riderLat, riderLng, _lastRouteFrom[0], _lastRouteFrom[1]) > 25;
    if (now - _lastRouteFetch > 20000 && moved) {
        _lastRouteFetch = now;
        _lastRouteFrom = [riderLat, riderLng];
        fetchRoute(riderLat, riderLng, _navTarget.lat, _navTarget.lng);
    }
}

function fetchRoute(lat1, lng1, lat2, lng2) {
    const url = `https://router.project-osrm.org/route/v1/driving/${lng1},${lat1};${lng2},${lat2}?overview=full&geometries=geojson`;
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.routes || !data.routes[0]) return;
            const route  = data.routes[0];
            const coords = route.geometry.coordinates.map(c => [c[1], c[0]]);

            if (_routeLine) _map.removeLayer(_routeLine);
            _routeLine = L.polyline(coords, { color: '#0064ff', weight: 5, opacity: 0.85 }).addTo(_map);

            const km   = (route.distance / 1000).toFixed(1);
            const mins = Math.max(1, Math.round(route.duration / 60));
            document.getElementById('routeDistance').textContent = km + ' km';
            document.getElementById('routeEta').textContent      = mins + ' min';
            document.getElementById('routeInfoCard').style.display = 'flex';
        })
        .catch(() => {});
}

// Straight-line distance in meters, used only to decide whether to refetch the route
function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const toRad = d => d * Math.PI / 180;
    const dLat = toRad(lat2 - lat1), dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

// ── GPS BROADCAST ──────────────────────────────────────
function startGPSBroadcast() {
    if (!navigator.geolocation) return;
    const broadcast = () => {
        navigator.geolocation.getCurrentPosition(pos => {
            fetch('/backend/rider_update_location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude })
            });
        });
    };
    broadcast();
    setInterval(broadcast, 12000);
}

// ── TOAST ──────────────────────────────────────────────
function showToast(msg, isError = false) {
    const toast = document.getElementById('toastMsg');
    toast.textContent = msg;
    toast.style.background = isError ? '#ef4444' : '#0d1b2a';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

// ── HELPERS ────────────────────────────────────────────
function escHtml(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function checkEmpty() {
    const list = document.getElementById('deliveriesList');
    if (!list) return;
    const cards = list.querySelectorAll('.delivery-card');
    if (cards.length === 0 && !list.querySelector('.empty-state')) {
        list.innerHTML = '<div class="empty-state" id="emptyDeliveries"><i class="bi bi-bicycle"></i><p>No active deliveries</p><small>New orders will appear here automatically</small></div>';
    }
    const stat = document.getElementById('statActive');
    if (stat) stat.textContent = list.querySelectorAll('.delivery-card').length;
}

function updateStatBadge(delta) {
    const stat = document.getElementById('statActive');
    if (stat) stat.textContent = Math.max(0, parseInt(stat.textContent || 0) + delta);
}

function playNotif() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator(), gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.setValueAtTime(520, ctx.currentTime);
        osc.frequency.setValueAtTime(680, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
        osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.45);
    } catch(e) {}
}

// ── POLL FOR NEW ORDERS ────────────────────────────────
// Track both the IDs we know about AND their last known status
let knownOrderIds = new Set(
    [...document.querySelectorAll('.delivery-card')].map(c => c.id.replace('card-',''))
);
let cardStatuses = {}; // id → status string
// Seed statuses from already-rendered cards
document.querySelectorAll('.delivery-card').forEach(card => {
    const id   = card.id.replace('card-','');
    const pill = card.querySelector('.status-pill');
    if (pill) {
        if (pill.classList.contains('in_transit')) cardStatuses[id] = 'in_transit';
        else if (pill.classList.contains('assigned')) cardStatuses[id] = 'assigned';
        else cardStatuses[id] = 'new';
    }
});

function pollOrders() {
    fetch('/backend/rider_active_orders.php')
    .then(r => r.json())
    .then(orders => {
        const newIds = new Set(orders.map(o => String(o.id)));

        orders.forEach(order => {
            const id = String(order.id);
            if (!knownOrderIds.has(id)) {
                // Brand new order — render it
                renderNewCard(order);
                knownOrderIds.add(id);
                cardStatuses[id] = order.status || (order.rider_id ? 'assigned' : 'new');
                showToast('🚴 New delivery available!');
                playNotif();
            } else {
                // Already shown — check if status changed, update in-place
                const prevStatus = cardStatuses[id];
                const currStatus = order.status;
                if (prevStatus !== currStatus) {
                    if (currStatus === 'assigned' && prevStatus !== 'assigned') {
                        updateCardToAssigned(order.id);
                    } else if (currStatus === 'in_transit') {
                        updateCardToTransit(order.id);
                    }
                    cardStatuses[id] = currStatus;
                }
            }
        });

        // Remove cards that are no longer in active list (delivered, cancelled, etc.)
        knownOrderIds.forEach(id => {
            if (!newIds.has(id)) {
                const card = document.getElementById('card-' + id);
                if (card) {
                    card.style.transition = 'all 0.35s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(-20px)';
                    setTimeout(() => { card.remove(); checkEmpty(); }, 350);
                }
                knownOrderIds.delete(id);
                delete cardStatuses[id];
            }
        });

        checkEmpty();
    })
    .catch(() => {});
}

function renderNewCard(d) {
    const list = document.getElementById('deliveriesList');
    const empty = document.getElementById('emptyDeliveries');
    if (empty) empty.remove();

    const isNew      = !d.rider_id;
    const isTransit  = d.status === 'in_transit';
    const isAssigned = d.status === 'assigned';
    const pill       = isNew ? 'new' : (isTransit ? 'in_transit' : 'assigned');
    const pillTxt    = isNew ? 'New Order' : (isTransit ? 'In Transit 🚴' : 'Assigned');
    const padId      = String(d.id).padStart(4,'0');
    const cNameJs    = escHtml(d.customer_name || '');
    const cPhone     = escHtml(d.customer_phone || '');
    const cAddr      = escHtml(d.customer_address || '');
    const cLandmark  = escHtml(d.customer_landmark || '');

    const card = document.createElement('div');
    card.className = 'delivery-card ' + (isNew ? 'card-new new-order-flash' : '');
    card.id = 'card-' + d.id;
    card.innerHTML = `
        <div class="card-header-row">
            <div>
                <div class="card-order-id">ORD-${padId}</div>
                <div class="card-customer">${cNameJs}</div>
            </div>
            <span class="status-pill ${pill}">${pillTxt}</span>
        </div>
        <div class="card-items"><i class="bi bi-box-seam"></i><span>${escHtml(d.items_summary||'No items')}</span></div>
        <div class="card-info">
            <div class="info-row"><i class="bi bi-geo-alt-fill"></i><span>${cAddr}</span></div>
            ${cLandmark ? `<div class="info-row"><i class="bi bi-signpost-2-fill"></i><span>🏷️ ${cLandmark}</span></div>` : ''}
            <div class="info-row"><i class="bi bi-telephone-fill"></i><span>${cPhone}</span></div>
            <div class="info-row"><i class="bi bi-cash-stack"></i><span>&#8369;${parseFloat(d.total).toLocaleString('en-PH',{minimumFractionDigits:2})}</span></div>
        </div>
        ${isNew ? `
        <div class="accept-reject-row">
            <button class="btn-reject" onclick="rejectOrder(${d.id},this)"><i class="bi bi-x-lg"></i> Decline</button>
            <button class="btn-accept" onclick="acceptOrder(${d.id},this)"><i class="bi bi-check-lg"></i> Accept</button>
        </div>` : ''}
        ${isAssigned ? `<button class="btn-transit" onclick="openPickupModal(${d.id},'${cNameJs}','${cPhone}','${cAddr}')"><i class="bi bi-box-seam"></i> Confirm Pickup</button>` : ''}
        ${isTransit  ? `<button class="btn-deliver" onclick="openDeliverModal(${d.id},'${cNameJs}','${cPhone}','${cAddr}')"><i class="bi bi-check-circle-fill"></i> Mark as Delivered</button>` : ''}
        <div class="card-actions">
            <button class="action-btn" onclick="openNavigation(${d.id}, ${d.delivery_lat != null ? d.delivery_lat : 'null'}, ${d.delivery_lng != null ? d.delivery_lng : 'null'}, '${cNameJs}', '${cAddr}')"><i class="bi bi-map"></i> Navigate</button>
            <a href="tel:${cPhone}" class="action-btn"><i class="bi bi-telephone"></i> Call</a>
            <button class="action-btn" onclick="openSmsModal('${cNameJs}','${cPhone}',false)"><i class="bi bi-chat-dots"></i> SMS</button>
        </div>
    `;

    list.insertBefore(card, list.firstChild);
    updateStatBadge(isNew ? 0 : 1);
}

// ── INIT ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    startGPSBroadcast();
    setInterval(pollOrders, 10000);
});