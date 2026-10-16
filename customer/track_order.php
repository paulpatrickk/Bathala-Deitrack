<?php
$order_id = (int)($_GET['id'] ?? 0);
$token    = trim($_GET['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Track Your Order - DeiTrack</title>

<!-- PWA -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#00a8c6">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="DeiTrack">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
@font-face{font-family:'DM Sans';font-style:normal;font-weight:100 900;font-display:swap;unicode-range:U+20B1;src:url(data:font/woff2;base64,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) format('woff2');}

:root{
    --primary:#00a8c6; --primary-dark:#0090a8; --accent:#ff6b35;
    --success:#22c55e; --danger:#ef4444; --warning:#f59e0b;
    --dark:#0d1b2a; --text:#1e293b; --muted:#94a3b8; --border:#e2e8f0; --bg:#f0f4f8; --white:#fff;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
.wrap{max-width:480px;margin:0 auto;min-height:100vh;background:var(--white);box-shadow:0 0 30px rgba(0,0,0,0.06);}
.header{background:linear-gradient(135deg,var(--dark),#16324a);color:#fff;padding:22px 20px;}
.header .brand{font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;display:flex;align-items:center;gap:8px;}
.header .order-no{margin-top:6px;font-size:0.8rem;color:#9fb4c7;}
.status-banner{padding:16px 20px;font-weight:700;font-size:1.05rem;display:flex;align-items:center;gap:10px;}
.status-banner i{font-size:1.4rem;}
.status-pending{background:#fff7ed;color:var(--warning);}
.status-assigned{background:#eff6ff;color:#2563eb;}
.status-in_transit{background:#ecfeff;color:var(--primary-dark);}
.status-delivered{background:#f0fdf4;color:var(--success);}
.status-invalid{background:#fef2f2;color:var(--danger);}

#map{height:280px;width:100%;background:#e2e8f0;}

.steps{display:flex;justify-content:space-between;padding:18px 24px 8px;position:relative;}
.steps::before{content:'';position:absolute;top:32px;left:40px;right:40px;height:3px;background:var(--border);z-index:0;}
.step{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;}
.step .dot{width:26px;height:26px;border-radius:50%;background:var(--border);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;transition:.3s;}
.step.done .dot{background:var(--primary);}
.step.active .dot{background:var(--accent);box-shadow:0 0 0 5px rgba(255,107,53,0.18);}
.step span{font-size:0.68rem;color:var(--muted);text-align:center;font-weight:600;}
.step.done span, .step.active span{color:var(--text);}

.card{margin:14px 20px;padding:16px;border:1px solid var(--border);border-radius:14px;}
.card h4{font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--muted);margin-bottom:10px;}
.rider-row{display:flex;align-items:center;gap:12px;}
.rider-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0;}
.rider-name{font-weight:700;}
.rider-sub{font-size:0.78rem;color:var(--muted);}
.call-btn{margin-left:auto;background:var(--success);color:#fff;border:none;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:1.1rem;}

.info-line{display:flex;gap:8px;font-size:0.85rem;margin-bottom:6px;align-items:flex-start;}
.info-line i{color:var(--primary);margin-top:2px;}
.items-list{font-size:0.85rem;color:var(--text);line-height:1.6;}
.total-row{display:flex;justify-content:space-between;font-weight:700;margin-top:8px;padding-top:8px;border-top:1px dashed var(--border);}

.footer-note{text-align:center;color:var(--muted);font-size:0.72rem;padding:20px;}
.invalid-box{padding:60px 24px;text-align:center;}
.invalid-box i{font-size:2.4rem;color:var(--danger);}
.invalid-box p{margin-top:12px;color:var(--muted);}
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="brand">🚴 DeiTrack Delivery Tracking</div>
        <div class="order-no" id="orderNoLine">Order #<?php echo str_pad($order_id, 4, '0', STR_PAD_LEFT); ?></div>
    </div>

    <div id="statusBanner" class="status-banner status-pending">
        <i class="dot-icon">⏳</i><span id="statusText">Loading order status…</span>
    </div>

    <div id="mainContent" style="display:none;">
        <div class="steps" id="stepsRow">
            <div class="step" data-step="placed"><div class="dot">1</div><span>Order Placed</span></div>
            <div class="step" data-step="assigned"><div class="dot">2</div><span>Rider Assigned</span></div>
            <div class="step" data-step="in_transit"><div class="dot">3</div><span>On the Way</span></div>
            <div class="step" data-step="delivered"><div class="dot">4</div><span>Delivered</span></div>
        </div>

        <div id="map"></div>

        <div class="card" id="riderCard" style="display:none;">
            <h4>Your Rider</h4>
            <div class="rider-row">
                <div class="rider-avatar" id="riderInitial">?</div>
                <div>
                    <div class="rider-name" id="riderName">-</div>
                    <div class="rider-sub" id="riderUpdated">-</div>
                </div>
                <a href="#" class="call-btn" id="riderCallBtn">📞</a>
            </div>
        </div>

        <div class="card">
            <h4>Delivery Details</h4>
            <div class="info-line"><i>📍</i><span id="deliveryAddress">-</span></div>
            <div class="info-line" id="landmarkLine" style="display:none;"><i>🏷️</i><span id="deliveryLandmark">-</span></div>
        </div>

        <div class="card">
            <h4>Order Summary</h4>
            <div class="items-list" id="itemsList">-</div>
            <div class="total-row"><span>Total</span><span id="totalAmount">₱0.00</span></div>
        </div>
    </div>

    <div id="invalidBox" class="invalid-box" style="display:none;">
        <i>⚠️</i>
        <p>This tracking link is invalid or has expired.</p>
    </div>

    <div class="footer-note">Powered by DeiTrack POS System</div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const ORDER_ID = <?php echo (int)$order_id; ?>;
const TOKEN    = <?php echo json_encode($token); ?>;

let map = null, riderMarker = null, destMarker = null, polling = null;

function escHtml(str){ return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function setStep(status) {
    const order = ['placed','assigned','in_transit','delivered'];
    let doneUpTo = 0;
    if (status === 'pending')          doneUpTo = 0;
    else if (status === 'assigned')    doneUpTo = 1;
    else if (status === 'in_transit')  doneUpTo = 2;
    else if (status === 'delivered')   doneUpTo = 3;

    document.querySelectorAll('.step').forEach((el, i) => {
        el.classList.remove('done','active');
        if (i < doneUpTo) el.classList.add('done');
        else if (i === doneUpTo) el.classList.add('active');
    });
    // "placed" step is always at least done once we have data
    document.querySelector('.step[data-step="placed"]').classList.add('done');
}

function setBanner(status) {
    const banner = document.getElementById('statusBanner');
    const text   = document.getElementById('statusText');
    banner.className = 'status-banner';
    if (status === 'pending') {
        banner.classList.add('status-pending');
        text.textContent = '⏳ Waiting for a rider to accept your order';
    } else if (status === 'assigned') {
        banner.classList.add('status-assigned');
        text.textContent = '📦 Rider is preparing your order for pickup';
    } else if (status === 'in_transit') {
        banner.classList.add('status-in_transit');
        text.textContent = '🚴 Your rider is on the way!';
    } else if (status === 'delivered') {
        banner.classList.add('status-delivered');
        text.textContent = '✅ Delivered! Enjoy your order.';
    }
}

function ensureMap(destLat, destLng) {
    if (map) return;
    map = L.map('map', { attributionControl: false, zoomControl: true })
            .setView([destLat || 15.4870, destLng || 120.9683], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
}

function updateMap(d) {
    const destLat = parseFloat(d.delivery_lat), destLng = parseFloat(d.delivery_lng);
    const hasDest = !isNaN(destLat) && !isNaN(destLng);
    const hasRider = d.rider_lat && d.rider_lng;

    ensureMap(hasDest ? destLat : null, hasDest ? destLng : null);

    if (hasDest) {
        const destIcon = L.divIcon({ className:'', html:'<div style="width:34px;height:34px;background:#ef4444;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>', iconSize:[34,34], iconAnchor:[17,34] });
        if (!destMarker) destMarker = L.marker([destLat, destLng], {icon: destIcon}).addTo(map).bindPopup('Delivery Address');
        else destMarker.setLatLng([destLat, destLng]);
    }

    if (hasRider) {
        const rLat = parseFloat(d.rider_lat), rLng = parseFloat(d.rider_lng);
        const riderIcon = L.divIcon({ className:'', html:'<div style="width:42px;height:42px;background:linear-gradient(135deg,#00a8c6,#0064ff);border-radius:50%;border:3px solid white;box-shadow:0 3px 14px rgba(0,168,198,0.55);display:flex;align-items:center;justify-content:center;font-size:20px;">🚴</div>', iconSize:[42,42], iconAnchor:[21,21] });
        if (!riderMarker) riderMarker = L.marker([rLat, rLng], {icon: riderIcon}).addTo(map).bindPopup('Your Rider');
        else riderMarker.setLatLng([rLat, rLng]);

        const bounds = [[rLat, rLng]];
        if (hasDest) bounds.push([destLat, destLng]);
        if (bounds.length > 1) map.fitBounds(bounds, { padding: [40,40] });
        else map.setView([rLat, rLng], 15);
    } else if (hasDest) {
        map.setView([destLat, destLng], 15);
    }
}

function render(d) {
    document.getElementById('mainContent').style.display = 'block';
    document.getElementById('invalidBox').style.display = 'none';

    setBanner(d.status);
    setStep(d.status);
    updateMap(d);

    document.getElementById('deliveryAddress').textContent = d.customer_address || '-';
    if (d.customer_landmark) {
        document.getElementById('landmarkLine').style.display = 'flex';
        document.getElementById('deliveryLandmark').textContent = d.customer_landmark;
    }

    if (d.has_rider && d.rider_name) {
        document.getElementById('riderCard').style.display = 'block';
        document.getElementById('riderInitial').textContent = d.rider_name.charAt(0).toUpperCase();
        document.getElementById('riderName').textContent = d.rider_name;
        document.getElementById('riderCallBtn').href = d.rider_phone ? ('tel:' + d.rider_phone) : '#';
        if (d.rider_loc_updated) {
            const secsAgo = Math.floor((Date.now() - new Date(d.rider_loc_updated).getTime())/1000);
            document.getElementById('riderUpdated').textContent = secsAgo < 90 ? 'Location updated moments ago' : 'Location last updated a while ago';
        }
    }

    const items = d.items || [];
    document.getElementById('itemsList').innerHTML = items.map(it => `${escHtml(it.name)} × ${it.quantity}`).join('<br>') || 'No items';
    document.getElementById('totalAmount').textContent = '₱' + parseFloat(d.total).toLocaleString('en-PH', {minimumFractionDigits:2});

    if (d.status === 'delivered' && polling) {
        clearInterval(polling);
        polling = null;
    }
}

function fetchTracking() {
    fetch(`/backend/get_order_tracking.php?id=${ORDER_ID}&token=${encodeURIComponent(TOKEN)}`)
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                document.getElementById('mainContent').style.display = 'none';
                document.getElementById('invalidBox').style.display = 'block';
                document.getElementById('statusBanner').className = 'status-banner status-invalid';
                document.getElementById('statusText').textContent = d.message || 'Invalid link';
                if (polling) { clearInterval(polling); polling = null; }
                return;
            }
            render(d);
        })
        .catch(() => {});
}

fetchTracking();
polling = setInterval(fetchTracking, 8000);
</script>
<script src="/assets/js/pwa-install.js"></script>
</body>
</html>
