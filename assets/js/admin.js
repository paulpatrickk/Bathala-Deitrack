// ─── NAV TAB SWITCHER ───────────────────────────────────────────────────────
function showTab(id, el) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById(id).classList.add('active');

  const headings = {
    dashboard : 'Dashboard',
    inventory : 'Inventory',
    orders    : 'Orders',
    delivery  : 'Delivery',
    reports   : 'Reports',
    users     : 'Users'
  };
  document.getElementById('topbar-heading').textContent = headings[id] || id;

  if (el) {
    el.classList.add('active');
  } else {
    const nav = document.getElementById('nav-' + id);
    if (nav) nav.classList.add('active');
  }

  // Live rider tracking only needs to run while the Delivery tab is visible
  if (id === 'delivery') {
    startRiderTracking();
  } else {
    stopRiderTracking();
  }

  closeSidebar();
}

// ─── MOBILE SIDEBAR TOGGLE ──────────────────────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
}

function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
}

// ─── CLOCK ──────────────────────────────────────────────────────────────────
function updateClock() {
  const el = document.getElementById('topbar-time-el');
  if (el) {
    el.textContent = new Date().toLocaleString('en-PH', {
      weekday : 'short',
      month   : 'short',
      day     : 'numeric',
      hour    : '2-digit',
      minute  : '2-digit'
    });
  }
}
updateClock();
setInterval(updateClock, 30000);

// ─── ORDER FILTER ────────────────────────────────────────────────────────────
function filterOrders(val) {
  const url = new URL(window.location.href);
  if (val) url.searchParams.set('status', val);
  else url.searchParams.delete('status');
  window.location.href = url.toString() + '#orders';
}

// ─── EXPORT PLACEHOLDER ──────────────────────────────────────────────────────
function exportReport() {
  alert('Export to Excel/PDF — connect to your backend export endpoint here.');
}

// ─── EDIT USER MODAL ─────────────────────────────────────────────────────────

/**
 * Opens the Edit User modal and pre-fills all fields.
 * Called by the Edit button in each user row.
 *
 * @param {number} id    - user id
 * @param {string} email - current email
 * @param {string} role  - current role
 */
function openEditModal(id, email, role) {
  document.getElementById('edit_user_id').value  = id;
  document.getElementById('edit_email').value    = email;
  document.getElementById('edit_role').value     = role;
  document.getElementById('edit_password').value = '';   // always clear password field

  document.getElementById('editModal').classList.add('open');
  // Focus the email field for quick editing
  setTimeout(() => document.getElementById('edit_email').focus(), 80);
}

/**
 * Closes the Edit User modal.
 * If called from the overlay click handler, only close when clicking the
 * backdrop itself (not the modal card inside it).
 *
 * @param {Event|undefined} event - the click event, or undefined for direct close
 */
function closeEditModal(event) {
  if (event && event.target !== document.getElementById('editModal')) return;
  document.getElementById('editModal').classList.remove('open');
}

// Also allow ESC key to close the modal
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeEditModal();
});

// ─── LIVE RIDER TRACKING (Delivery tab) ──────────────────────────────────────
let _riderMap = null;
let _riderMarkers = {};   // rider_id -> L.marker
let _riderPollTimer = null;
const RIDER_POLL_MS = 8000; // matches ~ rider app's 12s broadcast, with headroom

function ensureRiderMap() {
  if (_riderMap) return;
  const el = document.getElementById('riderMap');
  if (!el || typeof L === 'undefined') return;

  _riderMap = L.map('riderMap', { attributionControl: false }).setView([14.85, 120.98], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(_riderMap);
}

function riderIcon(online) {
  const bg = online ? 'linear-gradient(135deg,#00a8c6,#0064ff)' : '#94a3b8';
  return L.divIcon({
    className: '',
    html: `<div style="width:40px;height:40px;background:${bg};border-radius:50%;border:3px solid white;box-shadow:0 3px 14px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;font-size:18px;">🚴</div>`,
    iconSize: [40, 40], iconAnchor: [20, 20]
  });
}

function renderRiderList(riders) {
  const list = document.getElementById('rider-list');
  if (!list) return;

  if (!riders.length) {
    list.innerHTML = `
      <div style="padding:32px;text-align:center;color:var(--muted);" id="noRidersMsg">
        <i class="bi bi-bicycle" style="font-size:32px;opacity:0.3;"></i>
        <p style="margin-top:10px;font-size:13px;">No active riders</p>
      </div>`;
    return;
  }

  list.innerHTML = riders.map(r => {
    const initial = (r.name || '?').charAt(0).toUpperCase();
    const pulseStyle = r.online ? '' : 'background:var(--muted);box-shadow:none;';
    const agoText = r.seconds_ago < 60
      ? `${r.seconds_ago}s ago`
      : `${Math.floor(r.seconds_ago / 60)}m ago`;
    return `
      <div class="rider-card">
        <div class="rider-avatar">${initial}</div>
        <div class="rider-info">
          <div class="rider-name">${r.name}</div>
          <div class="rider-coords">${r.lat.toFixed(4)}, ${r.lng.toFixed(4)} · ${agoText}</div>
        </div>
        <div class="rider-pulse" style="${pulseStyle}"></div>
      </div>`;
  }).join('');
}

function updateRiderMap(riders) {
  ensureRiderMap();
  if (!_riderMap) return;

  const seenIds = new Set();

  riders.forEach(r => {
    seenIds.add(r.rider_id);
    const latlng = [r.lat, r.lng];
    if (_riderMarkers[r.rider_id]) {
      _riderMarkers[r.rider_id].setLatLng(latlng).setIcon(riderIcon(r.online));
    } else {
      _riderMarkers[r.rider_id] = L.marker(latlng, { icon: riderIcon(r.online) })
        .addTo(_riderMap)
        .bindPopup(`<strong>${r.name}</strong>`);
    }
  });

  // Remove markers for riders no longer reporting a location at all
  Object.keys(_riderMarkers).forEach(id => {
    if (!seenIds.has(Number(id))) {
      _riderMap.removeLayer(_riderMarkers[id]);
      delete _riderMarkers[id];
    }
  });

  if (riders.length) {
    const bounds = L.latLngBounds(riders.map(r => [r.lat, r.lng]));
    _riderMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
  }
}

function refreshRiderLocations() {
  fetch('../backend/get_rider_locations.php')
    .then(r => r.json())
    .then(data => {
      const badge = document.getElementById('riderLiveBadge');
      if (!data.success) {
        if (badge) badge.textContent = '● Error';
        return;
      }
      if (badge) badge.textContent = '● Live';
      renderRiderList(data.riders);
      updateRiderMap(data.riders);
    })
    .catch(() => {
      const badge = document.getElementById('riderLiveBadge');
      if (badge) badge.textContent = '● Offline';
    });
}

function startRiderTracking() {
  ensureRiderMap();
  refreshRiderLocations();
  // invalidateSize handles the case where the map div was hidden (display:none)
  // during initialization, which otherwise leaves Leaflet with a broken layout
  setTimeout(() => { if (_riderMap) _riderMap.invalidateSize(); }, 150);

  if (_riderPollTimer) clearInterval(_riderPollTimer);
  _riderPollTimer = setInterval(refreshRiderLocations, RIDER_POLL_MS);
}

function stopRiderTracking() {
  if (_riderPollTimer) {
    clearInterval(_riderPollTimer);
    _riderPollTimer = null;
  }
}

// ─── CHARTS (runs after DOM is ready) ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

  // ── Open the correct tab on load (e.g. after PHP redirect) ──────────────
  if (typeof startTab !== 'undefined' && startTab) {
    const navBtn = document.getElementById('nav-' + startTab);
    showTab(startTab, navBtn || null);
  } else {
    // Default: dashboard
    showTab('dashboard', document.getElementById('nav-dashboard'));
  }

  // ── Sales line chart ─────────────────────────────────────────────────────
  const ctx1 = document.getElementById('salesChart');
  if (ctx1 && typeof dates !== 'undefined' && typeof totals !== 'undefined') {
    new Chart(ctx1, {
      type: 'line',
      data: {
        labels: dates,
        datasets: [{
          label           : 'Sales (₱)',
          data            : totals,
          borderColor     : '#00a8c6',
          backgroundColor : 'rgba(0,168,198,0.08)',
          borderWidth     : 3,
          pointBackgroundColor : '#00a8c6',
          pointRadius     : 5,
          pointHoverRadius: 7,
          fill            : true,
          tension         : 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend : { display: false },
          tooltip: {
            backgroundColor : 'rgba(13,27,42,0.95)',
            borderColor     : 'rgba(0,168,198,0.3)',
            borderWidth     : 1,
            titleColor      : '#e8edf5',
            bodyColor       : '#00a8c6',
            callbacks: {
              label: ctx => ' ₱' + Number(ctx.raw).toLocaleString()
            }
          }
        },
        scales: {
          x: {
            grid  : { color: 'rgba(0,0,0,0.05)' },
            ticks : { color: '#94a3b8', font: { size: 11 } }
          },
          y: {
            grid  : { color: 'rgba(0,0,0,0.05)' },
            ticks : {
              color   : '#94a3b8',
              font    : { size: 11 },
              callback: v => '₱' + v.toLocaleString()
            }
          }
        }
      }
    });
  }

  // ── Orders by status donut chart ─────────────────────────────────────────
  const ctx2 = document.getElementById('donutChart');
  if (ctx2 && typeof orderStatusData !== 'undefined' && Object.keys(orderStatusData).length) {
    new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: Object.keys(orderStatusData),
        datasets: [{
          data           : Object.values(orderStatusData),
          backgroundColor: ['#f5a623', '#00a8c6', '#954ce9', '#22d07a'],
          borderColor    : 'rgba(255,255,255,0.8)',
          borderWidth    : 3,
          hoverOffset    : 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout : '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels  : {
              color         : '#94a3b8',
              font          : { size: 11, family: 'Syne' },
              padding       : 16,
              boxWidth      : 12,
              usePointStyle : true
            }
          },
          tooltip: {
            backgroundColor : 'rgba(13,27,42,0.95)',
            borderColor     : 'rgba(0,168,198,0.3)',
            borderWidth     : 1,
            titleColor      : '#e8edf5',
            bodyColor       : '#aaa'
          }
        }
      }
    });
  }

});