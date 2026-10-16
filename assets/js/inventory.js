// ─── SECTION SWITCHER ──────────────────────────────────
function showSection(name, el) {
    document.querySelectorAll('[id^="section-"]').forEach(s => s.style.display = 'none');
    const target = document.getElementById('section-' + name);
    if (target) target.style.display = 'block';

    document.querySelectorAll('.inv-nav-item').forEach(item => item.classList.remove('active'));
    if (el) el.closest('.inv-nav-item').classList.add('active');

    closeInvSidebar();
}

// ─── MOBILE SIDEBAR TOGGLE ──────────────────────────────
function toggleInvSidebar() {
    document.getElementById('invSidebar').classList.toggle('open');
    document.getElementById('invSidebarOverlay').classList.toggle('show');
}

function closeInvSidebar() {
    document.getElementById('invSidebar').classList.remove('open');
    document.getElementById('invSidebarOverlay').classList.remove('show');
}

// ─── COMBINED FILTER STATE ──────────────────────────────
// Search, category, and stock-status (Low/Out stat cards) used to be three
// separate pieces of code that each set a row's display style directly --
// whichever ran last won, so typing a search term while a category filter
// was active would silently undo the category filter. They now all read
// from one state object and re-apply together.
let invFilterState = { search: '', category: 'all', stock: null };

function applyInvFilters() {
    const { search, category, stock } = invFilterState;
    let visible = 0;

    document.querySelectorAll('#invTable tbody tr').forEach(row => {
        const matchesSearch   = !search || (row.dataset.name || '').includes(search);
        const matchesCategory = category === 'all' || row.dataset.category === category;
        const matchesStock    = !stock || row.dataset.status === stock;
        const show = matchesSearch && matchesCategory && matchesStock;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const noResults = document.getElementById('invNoResults');
    if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
}

// ─── SEARCH ───────────────────────────────────────────
document.getElementById('invSearch').addEventListener('input', function () {
    invFilterState.search = this.value.toLowerCase();
    applyInvFilters();
});

// ─── CATEGORY FILTER ───────────────────────────────────
function filterByCategory(cat, btn) {
    document.querySelectorAll('.inv-cat-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    invFilterState.category = cat;
    applyInvFilters();
}

// ─── STOCK STATUS FILTER (Low Stock / Out of Stock cards) ──
// Click a stat card to show only those rows; click it again to clear.
// Independent of the category filter -- both apply together.
function toggleStockFilter(status) {
    const cardId = status === 'low' ? 'statLow' : 'statOut';
    const card = document.getElementById(cardId);
    const turningOn = invFilterState.stock !== status;

    document.getElementById('statLow').classList.remove('active-filter');
    document.getElementById('statOut').classList.remove('active-filter');

    invFilterState.stock = turningOn ? status : null;
    if (turningOn) card.classList.add('active-filter');

    applyInvFilters();
}

// ─── SORT BY STOCK ──────────────────────────────────────
let stockSortDir = null; // null | 'asc' | 'desc'

function sortByStock() {
    const tbody = document.querySelector('#invTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    stockSortDir = stockSortDir === 'asc' ? 'desc' : 'asc';

    rows.sort((a, b) => {
        const diff = parseInt(a.dataset.stock, 10) - parseInt(b.dataset.stock, 10);
        return stockSortDir === 'asc' ? diff : -diff;
    });
    rows.forEach(row => tbody.appendChild(row));

    const icon = document.getElementById('stockSortIcon');
    icon.className = stockSortDir === 'asc' ? 'bi bi-sort-numeric-down' : 'bi bi-sort-numeric-up-alt';
    icon.style.color = 'var(--primary)';
}

// ─── OPEN ADD MODAL ────────────────────────────────────
function openAddModal() {
    document.getElementById('addModal').classList.add('show');
}

// ─── OPEN EDIT MODAL ───────────────────────────────────
function openEditModal(product) {
    document.getElementById('editId').value          = product.id;
    document.getElementById('editName').value        = product.name;
    document.getElementById('editPrice').value       = product.price;
    document.getElementById('editStock').value       = product.stock;
    document.getElementById('editThreshold').value   = (product.reorder_threshold !== undefined && product.reorder_threshold !== null && product.reorder_threshold !== '') ? product.reorder_threshold : 10;
    document.getElementById('editDescription').value = product.description || '';
    document.getElementById('editPhotoName').textContent = '';
    document.getElementById('editImgPreview').style.display = 'none';
    document.getElementById('editUploadIcon').style.display = 'block';
    document.getElementById('editCategory').value = product.category || '';

    // Show current image
    const imgWrap = document.getElementById('editCurrentImg');
    if (product.image) {
        imgWrap.innerHTML = `
            <div style="display:flex;align-items:center;gap:10px;padding:10px;background:#f0f4f8;border-radius:10px;margin-bottom:8px;">
                <img src="/assets/images/products/${product.image}" style="width:48px;height:48px;border-radius:8px;object-fit:cover;">
                <div>
                    <div style="font-size:0.82rem;font-weight:600;color:#0d1b2a;">Current Photo</div>
                    <div style="font-size:0.75rem;color:#94a3b8;">${product.image}</div>
                </div>
            </div>
        `;
    } else {
        imgWrap.innerHTML = '<div style="font-size:0.82rem;color:#94a3b8;margin-bottom:8px;">No photo uploaded yet</div>';
    }

    document.getElementById('editModal').classList.add('show');
}

// ─── OPEN RESTOCK MODAL ────────────────────────────────
function openRestockModal(id, name, currentStock) {
    document.getElementById('restockId').value             = id;
    document.getElementById('restockName').textContent     = name;
    document.getElementById('restockCurrent').textContent  = currentStock;
    document.getElementById('restockQty').value            = '';
    document.getElementById('restockModal').classList.add('show');
}

// ─── CONFIRM DELETE ────────────────────────────────────
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteModal').classList.add('show');
}

// ─── CLOSE MODAL ───────────────────────────────────────
function closeInvModal(id) {
    document.getElementById(id).classList.remove('show');
}

document.querySelectorAll('.inv-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.inv-modal-overlay.show').forEach(m => m.classList.remove('show'));
    }
});

// ─── IMAGE PREVIEW ─────────────────────────────────────
function previewImage(inputId, imgId, previewId, iconId, nameId) {
    const input   = document.getElementById(inputId);
    const img     = document.getElementById(imgId);
    const preview = document.getElementById(previewId);
    const icon    = document.getElementById(iconId);
    const nameEl  = document.getElementById(nameId);

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.style.display = 'block';
            icon.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
        nameEl.textContent = '✓ ' + input.files[0].name;
    }
}

// ─── EXPORT TO EXCEL ───────────────────────────────────
function exportToExcel() {
    if (typeof XLSX === 'undefined') {
        showInvToast('Excel library not loaded. Try again.', true);
        return;
    }

    const ws = XLSX.utils.json_to_sheet(exportData);

    ws['!cols'] = [
        { wch: 50 },
        { wch: 25 },
        { wch: 12 },
        { wch: 10 },
        { wch: 16 },
        { wch: 15 },
        { wch: 60 },
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Products');

    const date = new Date().toISOString().slice(0, 10);
    XLSX.writeFile(wb, `Bathala_Inventory_${date}.xlsx`);

    showInvToast('Inventory exported to Excel! ✓');
}

// ─── TOAST ─────────────────────────────────────────────
function showInvToast(msg, isError = false) {
    const toast  = document.getElementById('invToast');
    const msgEl  = document.getElementById('invToastMsg');
    const icon   = toast.querySelector('i');

    msgEl.textContent = msg;
    icon.className   = isError ? 'bi bi-exclamation-circle-fill' : 'bi bi-check-circle-fill';
    icon.style.color = isError ? '#ef4444' : '#22c55e';

    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}