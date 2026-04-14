let cart = [];

// ─── ADD TO CART ───────────────────────────────────────
function addToCart(card) {
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

// Store current modal product
let currentModalProduct = null;

// Open product modal
function openProductModal(card) {
    const id       = card.dataset.id;
    const name     = card.dataset.name;
    const price    = parseFloat(card.dataset.price);
    const stock    = parseInt(card.dataset.stock);
    const category = card.dataset.cat || '';
    const image    = card.dataset.image || '';
    const desc     = card.dataset.desc || '';

    currentModalProduct = { id, name, price, stock, category, image };

    document.getElementById('modalProductName').textContent = name;
    document.getElementById('modalProductPrice').textContent = `₱${price.toFixed(2)}`;
    document.getElementById('modalProductStock').textContent = `${stock} available`;
    document.getElementById('modalProductCategory').textContent = category;

    // Image
    const imgEl = document.getElementById('modalProductImg');
    if (image) {
        imgEl.innerHTML = `<img src="/Bathala/assets/images/products/${image}" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
        imgEl.innerHTML = `<i class="bi bi-image" style="font-size:3rem;color:#94a3b8;"></i>`;
    }

    // Description
    const descWrap = document.getElementById('modalProductDescWrap');
    if (desc) {
        document.getElementById('modalProductDesc').textContent = desc;
        descWrap.style.display = 'block';
    } else {
        descWrap.style.display = 'none';
    }

    // Disable add button if out of stock
    const addBtn = document.getElementById('modalAddBtn');
    addBtn.disabled = stock === 0;
    addBtn.textContent = stock === 0 ? 'Out of Stock' : '';
    if (stock > 0) {
        addBtn.innerHTML = '<i class="bi bi-cart-plus"></i> Add to Cart';
    }

    document.getElementById('productModal').classList.add('show');
}

function addToCartFromModal() {
    if (!currentModalProduct) return;
    const { id, name, price, stock } = currentModalProduct;

    const existing = cart.find(i => i.id === id);
    if (existing) {
        if (existing.qty >= stock) { showToast('Not enough stock!', true); return; }
        existing.qty++;
    } else {
        cart.push({ id, name, price, qty: 1, stock });
    }

    renderCart();
    showToast(`${name} added to cart`);
    closeModal('productModal');
}

// Close sidebar when clicking overlay
document.getElementById('sidebarOverlay').addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('open');
    this.classList.remove('show');
});

// Close sidebar when pressing Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    }
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main    = document.querySelector('.main-content');
    const isOpen  = sidebar.classList.contains('open');

    if (isOpen) {
        sidebar.classList.remove('open');
        main.classList.remove('shifted');
    } else {
        sidebar.classList.add('open');
        main.classList.add('shifted');
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('sidebar').classList.remove('open');
        document.querySelector('.main-content').classList.remove('shifted');
    }
});

// ─── RENDER CART ───────────────────────────────────────
function renderCart() {
    const container   = document.getElementById('cartItems');
    const empty       = document.getElementById('cartEmpty');
    const cartBadge   = document.getElementById('cartBadge');
    const cartCount   = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const subtotal    = document.getElementById('cartSubtotal');
    const total       = document.getElementById('cartTotal');

    const totalQty   = cart.reduce((s, i) => s + i.qty, 0);
    const totalPrice = cart.reduce((s, i) => s + i.price * i.qty, 0);

    // Update badge
    if (cartBadge) {
        cartBadge.textContent = totalQty;
        cartBadge.classList.toggle('show', totalQty > 0);
    }

    if (cartCount) cartCount.textContent = totalQty > 0 ? `(${totalQty} items)` : '';

    if (cart.length === 0) {
        if (empty) empty.style.display = 'block';
        if (checkoutBtn) checkoutBtn.disabled = true;
        if (subtotal) subtotal.textContent = '₱0.00';
        if (total) total.textContent = '₱0.00';

        // Remove old items
        container.querySelectorAll('.cart-sidebar-item').forEach(i => i.remove());
        return;
    }

    if (empty) empty.style.display = 'none';
    if (checkoutBtn) checkoutBtn.disabled = false;
    if (subtotal) subtotal.textContent = `₱${totalPrice.toFixed(2)}`;
    if (total) total.textContent = `₱${totalPrice.toFixed(2)}`;

    container.querySelectorAll('.cart-sidebar-item').forEach(i => i.remove());

    cart.forEach(item => {
        const div = document.createElement('div');
        div.className = 'cart-sidebar-item';
        div.innerHTML = `
            <div class="cart-sidebar-item-info">
                <div class="cart-sidebar-item-name">${item.name}</div>
                <div class="cart-sidebar-item-price">₱${item.price.toFixed(2)} each</div>
            </div>
            <div class="cart-qty-controls">
                <button class="cart-qty-btn" onclick="changeQty('${item.id}', -1)">−</button>
                <span class="cart-qty-num">${item.qty}</span>
                <button class="cart-qty-btn" onclick="changeQty('${item.id}', 1)">+</button>
            </div>
            <div class="cart-item-total">₱${(item.price * item.qty).toFixed(2)}</div>
            <button class="cart-remove-btn" onclick="removeItem('${item.id}')">
                <i class="bi bi-x"></i>
            </button>
        `;
        container.appendChild(div);
    });
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

// ─── CART OPEN/CLOSE ───────────────────────────────────
function openCart() {
    document.getElementById('cartSidebar').classList.add('open');
    document.getElementById('cartOverlay').classList.add('show');
}

function closeCart() {
    document.getElementById('cartSidebar').classList.remove('open');
    document.getElementById('cartOverlay').classList.remove('show');
}

// ─── CATEGORY FILTER ───────────────────────────────────
function filterShop(cat, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    if (cat === 'all') {
        document.querySelectorAll('.shop-category-section').forEach(s => s.style.display = 'block');
    } else {
        document.querySelectorAll('.shop-category-section').forEach(s => {
            s.style.display = s.dataset.cat === cat ? 'block' : 'none';
        });
    }
}

// ─── SEARCH ────────────────────────────────────────────
const shopSearch = document.getElementById('shopSearch');
if (shopSearch) {
    shopSearch.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.shop-product-card').forEach(card => {
            const name = card.dataset.name.toLowerCase();
            card.style.display = name.includes(query) ? 'block' : 'none';
        });
        document.querySelectorAll('.shop-category-section').forEach(s => s.style.display = 'block');
    });
}

// ─── CHECKOUT ──────────────────────────────────────────
function openCheckout() {
    if (cart.length === 0) return;

    const total = cart.reduce((s, i) => s + i.price * i.qty, 0);

    // Populate order summary
    const summaryEl = document.getElementById('orderSummaryItems');
    if (summaryEl) {
        summaryEl.innerHTML = cart.map(item => `
            <div class="order-summary-item">
                <span>${item.name} x${item.qty}</span>
                <span>₱${(item.price * item.qty).toFixed(2)}</span>
            </div>
        `).join('');
    }

    const orderTotalEl = document.getElementById('orderTotal');
    if (orderTotalEl) orderTotalEl.textContent = `₱${total.toFixed(2)}`;

    closeCart();
    document.getElementById('checkoutModal').classList.add('show');
}

// ─── SELECT PAYMENT ────────────────────────────────────
function selectPayment(el, method) {
    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.dataset.method = method;
}

// ─── CONFIRM ORDER ─────────────────────────────────────
function confirmOrder() {
    const total = cart.reduce((s, i) => s + i.price * i.qty, 0);

    const data = {
        order_type: 'delivery',
        total: total,
        items: cart,
        payment_method: 'cod',
        source: 'customer'
    };

    fetch('/Bathala/backend/save_customer_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            document.getElementById('checkoutModal').classList.remove('show');
            cart = [];
            renderCart();
            showToast('Order placed successfully! 🎉');
            setTimeout(() => {
                window.location.href = '/Bathala/customer/orders.php';
            }, 2000);
        } else {
            showToast('Error: ' + res.message, true);
        }
    })
    .catch(() => showToast('Server error. Try again.', true));
}

// ─── MODAL ─────────────────────────────────────────────
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// ─── TOAST ─────────────────────────────────────────────
function showToast(msg, isError = false) {
    const toast    = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMsg');
    const icon     = toast.querySelector('i');

    if (!toast) return;

    toastMsg.textContent = msg;
    icon.className  = isError ? 'bi bi-exclamation-circle-fill' : 'bi bi-check-circle-fill';
    icon.style.color = isError ? '#ef4444' : '#22c55e';

    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}
