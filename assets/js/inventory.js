// ─── SECTION SWITCHER ──────────────────────────────────
function showSection(name, el) {
    document.querySelectorAll('[id^="section-"]').forEach(s => s.style.display = 'none');
    const target = document.getElementById('section-' + name);
    if (target) target.style.display = 'block';

    document.querySelectorAll('.inv-nav-item').forEach(item => item.classList.remove('active'));
    if (el) el.closest('.inv-nav-item').classList.add('active');
}

// ─── SEARCH ───────────────────────────────────────────
document.getElementById('invSearch').addEventListener('input', function () {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#invTable tbody tr').forEach(row => {
        row.style.display = (row.dataset.name || '').includes(query) ? '' : 'none';
    });
});

// ─── CATEGORY FILTER ───────────────────────────────────
function filterByCategory(cat, btn) {
    document.querySelectorAll('.inv-cat-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('#invTable tbody tr').forEach(row => {
        const rowCat = row.querySelector('.inv-category-badge')?.textContent.trim();
        row.style.display = (cat === 'all' || rowCat === cat) ? '' : 'none';
    });
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
    document.getElementById('editDescription').value = product.description || '';
    document.getElementById('editPhotoName').textContent = '';
    document.getElementById('editImgPreview').style.display = 'none';
    document.getElementById('editUploadIcon').style.display = 'block';

    // Set category
    const catSelect = document.getElementById('editCategory');
    for (let i = 0; i < catSelect.options.length; i++) {
        if (catSelect.options[i].value === product.category) {
            catSelect.selectedIndex = i;
            break;
        }
    }

    // Show current image
    const imgWrap = document.getElementById('editCurrentImg');
    if (product.image) {
        imgWrap.innerHTML = `
            <div style="display:flex;align-items:center;gap:10px;padding:10px;background:#f0f4f8;border-radius:10px;margin-bottom:8px;">
                <img src="/Bathala/assets/images/products/${product.image}" style="width:48px;height:48px;border-radius:8px;object-fit:cover;">
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
