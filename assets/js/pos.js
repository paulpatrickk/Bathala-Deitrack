let cart = [];
let orderType = 'walkin';

// ─── CATEGORY FILTER ───────────────────────────────────
function filterCategory(category) {
    // Update active tab
    document.querySelectorAll('.cat-tab').forEach(tab => tab.classList.remove('active'));
    event.target.closest('.cat-tab').classList.add('active');

    // Show/hide sections
    if (category === 'all') {
        document.querySelectorAll('.category-section').forEach(s => s.style.display = 'block');
        document.querySelectorAll('.product-card').forEach(c => c.style.display = 'block');
    } else {
        document.querySelectorAll('.category-section').forEach(section => {
            section.style.display = section.dataset.category === category ? 'block' : 'none';
        });
    }
}

// ─── ADD TO CART ───────────────────────────────────────
function addToCart(card) {
    if (card.classList.contains('out-of-stock')) return;

    const id    = card.dataset.id;
    const name  = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    const stock = parseInt(card.dataset.stock);

    const existing = cart.find(i => i.id === id);

    if (existing) {
        if (existing.qty >= stock) {
            showToast('Not enough stock!', true);
            return;
        }
        existing.qty++;
    } else {
        cart.push({ id, name, price, qty: 1, stock });
    }

    renderCart();
    showToast(`${name} added to cart`);
}

// POS Product Modal
let currentPosProduct = null;

function openPosProductModal(card) {
    if (card.classList.contains('out-of-stock')) return;

    currentPosProduct = {
        id:    card.dataset.id,
        name:  card.dataset.name,
        price: parseFloat(card.dataset.price),
        stock: parseInt(card.dataset.stock),
        image: card.dataset.image || '',
        desc:  card.dataset.desc || ''
    };

    document.getElementById('posModalProductName').textContent = currentPosProduct.name;
    document.getElementById('posModalProductPrice').textContent = `₱${currentPosProduct.price.toFixed(2)}`;
    document.getElementById('posModalProductStock').textContent = `${currentPosProduct.stock} available`;

    const imgEl = document.getElementById('posModalProductImg');
    if (currentPosProduct.image) {
        imgEl.innerHTML = `<img src="/Bathala/assets/images/products/${currentPosProduct.image}" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
        imgEl.innerHTML = `<i class="bi bi-image" style="font-size:3rem;color:#94a3b8;"></i>`;
    }

    const descWrap = document.getElementById('posModalDescWrap');
    if (currentPosProduct.desc) {
        document.getElementById('posModalProductDesc').textContent = currentPosProduct.desc;
        descWrap.style.display = 'block';
    } else {
        descWrap.style.display = 'none';
    }

    openModal('productModal');
}

function closePosProductModal() {
    closeModal('productModal');
    currentPosProduct = null;
}

function addToCartFromPosModal() {
    if (!currentPosProduct) return;
    const card = document.querySelector(`.product-card[data-id="${currentPosProduct.id}"]`);
    if (card) addToCart(card);
    closePosProductModal();
}

// ─── RENDER CART ───────────────────────────────────────
function renderCart() {
    const container  = document.getElementById('cartItems');
    const empty      = document.getElementById('cartEmpty');
    const cartCount  = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (cart.length === 0) {
        empty.style.display = 'block';
        cartCount.textContent = '0';
        checkoutBtn.disabled = true;
        updateTotals();
        return;
    }

    empty.style.display = 'none';
    cartCount.textContent = cart.reduce((sum, i) => sum + i.qty, 0);
    checkoutBtn.disabled = false;

    const oldItems = container.querySelectorAll('.cart-item');
    oldItems.forEach(i => i.remove());

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

    if (item.qty <= 0) {
        cart = cart.filter(i => i.id !== id);
    } else if (item.qty > item.stock) {
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
    const total = cart.reduce((sum, i) => sum + i.price * i.qty, 0);
    document.getElementById('subtotal').textContent = `₱${total.toFixed(2)}`;
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

    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.dataset.name.toLowerCase();
        card.style.display = name.includes(query) ? 'block' : 'none';
    });

    // Show all sections when searching
    document.querySelectorAll('.category-section').forEach(s => s.style.display = 'block');

    // Reset category tabs
    if (query === '') {
        document.querySelectorAll('.cat-tab').forEach((t, i) => {
            t.classList.toggle('active', i === 0);
        });
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
        const name    = document.getElementById('customerName').value.trim();
        const phone   = document.getElementById('customerPhone').value.trim();
        const address = document.getElementById('customerAddress').value.trim();

        if (!name || !phone || !address) {
            showToast('Please fill in delivery details!', true);
            return;
        }

        document.getElementById('deliveryModalTotal').textContent = `₱${total.toFixed(2)}`;
        document.getElementById('deliverySummary').innerHTML = `
            <strong>${name}</strong><br>
            📞 ${phone}<br>
            📍 ${address}
        `;
        openModal('deliveryModal');
    }
}

// ─── CALC CHANGE ───────────────────────────────────────
function calcChange() {
    const cash  = parseFloat(document.getElementById('cashInput').value) || 0;
    const total = getTotal();
    const change = cash - total;
    const display = document.getElementById('changeDisplay');

    if (cash >= total) {
        display.classList.add('show');
        document.getElementById('changeAmount').textContent = `₱${change.toFixed(2)}`;
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
    const name    = document.getElementById('customerName').value.trim();
    const phone   = document.getElementById('customerPhone').value.trim();
    const address = document.getElementById('customerAddress').value.trim();
    submitOrder('delivery', { name, phone, address });
}

// ─── SUBMIT ORDER ──────────────────────────────────────
function submitOrder(type, customer) {
    const data = {
        order_type:       type,
        total:            getTotal(),
        items:            cart,
        customer_name:    customer.name    || '',
        customer_phone:   customer.phone   || '',
        customer_address: customer.address || ''
    };

    fetch('/Bathala/backend/save_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeModal('walkinModal');
            closeModal('deliveryModal');
            clearCart();
            document.getElementById('customerName').value    = '';
            document.getElementById('customerPhone').value   = '';
            document.getElementById('customerAddress').value = '';
            showToast(type === 'walkin' ? 'Payment settled! ✓' : 'Delivery order placed! 🚴');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Error: ' + res.message, true);
        }
    })
    .catch(() => showToast('Server error. Try again.', true));
}

// ─── MODAL HELPERS ─────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

// ─── USER DROPDOWN ─────────────────────────────────────
function toggleDropdown() {
    const menu = document.getElementById('dropdownMenu');
    if (menu) menu.classList.toggle('show');
}

document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('userDropdown');
    const menu     = document.getElementById('dropdownMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.remove('show');
    }
});

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
