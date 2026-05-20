// ============================================
// VEXO STREETWEAR - GLOBAL JAVASCRIPT
// ============================================

const STORAGE_CART_KEY   = 'vexo_cart';
const STORAGE_ORDERS_KEY = 'vexo_orders';

// ── Utilities ──────────────────────────────

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m =>
        m === '&' ? '&amp;' : m === '<' ? '&lt;' : '&gt;'
    );
}

function showNotification(message, type) {
    const colors = { success: '#10b981', error: '#ef4444', info: '#f59e0b' };
    const notif  = document.createElement('div');
    notif.className = 'notification';
    notif.style.cssText = `position:fixed;bottom:20px;right:20px;background:${colors[type] || colors.info};color:white;padding:12px 24px;border-radius:50px;z-index:10000;animation:slideIn 0.3s ease;`;
    notif.innerText = message;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 2500);
}

function normalizeProduct(p) {
    return {
        ...p,
        id:       parseInt(p.id),
        price:    parseFloat(p.price),
        oldPrice: p.old_price ? parseFloat(p.old_price) : null,
        stock:    parseInt(p.stock)
    };
}

async function loadProductsAsync() {
    try {
        const res  = await fetch('api/get_products.php');
        const data = await res.json();
        return (data.products || []).map(normalizeProduct);
    } catch (e) {
        console.error('Failed to load products:', e);
        return [];
    }
}

// ── Auth ───────────────────────────────────

function handleLogout() { window.location.href = 'logout.php'; }
function openProfile()  { window.location.href = 'login.php';  }

function goToDetail(e, id) {
    if (e.target.tagName === 'BUTTON') return;
    window.location.href = `product.php?id=${id}`;
}

function scrollToProducts()   { document.getElementById('productsSection')?.scrollIntoView({ behavior: 'smooth' }); }
function scrollToPromo()      { document.getElementById('promoSection')?.scrollIntoView({ behavior: 'smooth' }); }
function scrollToCategories() { document.getElementById('categoriesSection')?.scrollIntoView({ behavior: 'smooth' }); }

function setActiveTab(el) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

// ── User: Cart ─────────────────────────────

let userCart      = [];
let currentFilter = 'all';
let currentSearch = '';
let cachedProducts = [];

function loadUserCart() {
    const stored = localStorage.getItem(STORAGE_CART_KEY);
    userCart = stored ? JSON.parse(stored) : [];
    updateCartCount();
}

function saveUserCart() {
    localStorage.setItem(STORAGE_CART_KEY, JSON.stringify(userCart));
}

function updateCartCount() {
    const total = userCart.reduce((s, i) => s + i.quantity, 0);
    const span  = document.getElementById('cartCount');
    if (span) span.innerText = total;
}

function toggleCart()  { document.getElementById('cartPanel')?.classList.toggle('open'); }
function openCheckout() {
    if (userCart.length === 0) { showNotification('Cart empty!', 'error'); return; }
    window.location.href = 'checkout.php';
}
function closeCheckout() {
    document.getElementById('checkoutModal')?.classList.remove('active');
}

function addToCart(id) {
    const product = cachedProducts.find(p => p.id === id);
    if (!product) return;
    if (product.status === 'Out of Stock' || (product.status === 'Available' && product.stock <= 0)) {
        showNotification('Out of stock!', 'error');
        return;
    }
    const existing = userCart.find(i => i.id === id);
    if (existing) {
        if (product.status === 'Available' && existing.quantity >= product.stock) {
            showNotification('Not enough stock!', 'error');
            return;
        }
        existing.quantity++;
    } else {
        userCart.push({ id: product.id, name: product.name, price: product.price, image: product.image, quantity: 1 });
    }
    saveUserCart();
    updateCartCount();
    renderUserCart();
    showNotification(product.name + ' added!', 'success');
}

function updateQty(id, change) {
    const item = userCart.find(i => i.id === id);
    if (!item) return;
    if (change === 1) {
        item.quantity++;
    } else if (change === -1 && item.quantity > 1) {
        item.quantity--;
    } else if (change === -1 && item.quantity === 1) {
        removeFromCart(id);
        return;
    }
    saveUserCart();
    renderUserCart();
    updateCartCount();
}

function removeFromCart(id) {
    userCart = userCart.filter(i => i.id !== id);
    saveUserCart();
    renderUserCart();
    updateCartCount();
    showNotification('Item removed', 'info');
}

function renderUserCart() {
    const container = document.getElementById('cartItemsContainer');
    const totalSpan = document.getElementById('totalPrice');
    if (!container) return;
    if (userCart.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:40px;">Cart empty</div>';
        if (totalSpan) totalSpan.innerText = '$0';
        return;
    }
    let total = 0;
    container.innerHTML = userCart.map(i => {
        total += i.price * i.quantity;
        return `<div class="cart-item-card">
            <img src="${i.image}" class="cart-item-img">
            <div>
                <h4>${escapeHtml(i.name)}</h4>
                <p>$${i.price}</p>
                <div class="qty-control">
                    <button class="qty-btn" onclick="updateQty(${i.id},-1)">-</button>
                    <span>${i.quantity}</span>
                    <button class="qty-btn" onclick="updateQty(${i.id},1)">+</button>
                </div>
            </div>
            <button class="remove-small-btn" onclick="removeFromCart(${i.id})">✖</button>
        </div>`;
    }).join('');
    if (totalSpan) totalSpan.innerText = '$' + total;
}

// ── User: Products ─────────────────────────

function filterProducts(cat) {
    currentFilter = cat;
    renderUserProducts();
}

function handleUserSearch() {
    currentSearch = document.getElementById('searchInput')?.value || '';
    renderUserProducts();
}

function renderPromoSection(products) {
    const section   = document.getElementById('promoSection');
    const container = document.getElementById('promoContainer');
    if (!section || !container) return;
    const promos = products.filter(p => p.promo && p.status !== 'Out of Stock');
    if (promos.length === 0) { section.style.display = 'none'; return; }
    section.style.display = 'block';
    container.innerHTML = promos.map(p => {
        const discount = p.oldPrice ? Math.round((1 - p.price / p.oldPrice) * 100) : 0;
        return `<div class="promo-card" onclick="addToCart(${p.id})">
            <div class="promo-card-img-wrap">
                <img src="${p.image}" onerror="this.src='https://placehold.co/300x300/333/fff?text=VEXO'">
                ${discount > 0 ? `<span class="promo-discount">-${discount}%</span>` : ''}
            </div>
            <div class="promo-card-info">
                <h4>${escapeHtml(p.name)}</h4>
                <div class="promo-price">
                    <span class="promo-new-price">$${p.price}</span>
                    ${p.oldPrice ? `<span class="promo-old-price">$${p.oldPrice}</span>` : ''}
                </div>
            </div>
        </div>`;
    }).join('');
}

function updateCategoryImages(products) {
    const map = { men: 'catImgMen', women: 'catImgWomen', accessories: 'catImgAccessories' };
    for (const [cat, id] of Object.entries(map)) {
        const p = products.find(x => x.category === cat && x.image);
        if (p) {
            const el = document.getElementById(id);
            if (el) el.src = p.image;
        }
    }
}

async function renderUserProducts() {
    const products = await loadProductsAsync();
    cachedProducts = products;

    updateCategoryImages(products);
    renderPromoSection(products);

    const filtered = products.filter(p =>
        p.name.toLowerCase().includes(currentSearch.toLowerCase()) &&
        (currentFilter === 'all' || p.category === currentFilter)
    );
    const container = document.getElementById('productContainer');
    if (!container) return;
    if (filtered.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:50px;color:#999;">No products found.</div>';
        return;
    }
    container.innerHTML = filtered.map(p => {
        let stockStatus, stockClass, btnDisabled = '', btnText = 'Add to Cart';
        if (p.status === 'Available' && p.stock > 0) {
            stockStatus = `In Stock (${p.stock})`; stockClass = 'stock-available';
        } else if (p.status === 'Pre Order') {
            stockStatus = 'Pre Order'; stockClass = 'stock-preorder'; btnText = 'Pre Order';
        } else {
            stockStatus = 'Out of Stock'; stockClass = 'stock-out';
            btnText = 'Out of Stock'; btnDisabled = 'disabled';
        }
        return `<div class="product" onclick="goToDetail(event, ${p.id})">
            <img src="${p.image}" class="product-img" onerror="this.src='https://placehold.co/300x300/333/fff?text=VEXO'">
            <div class="product-body">
                <h3>${escapeHtml(p.name)}</h3>
                <div class="product-price">$${p.price}${p.oldPrice ? `<span class="product-old-price">$${p.oldPrice}</span>` : ''}</div>
                <span class="stock-badge ${stockClass}">${stockStatus}</span>
            </div>
            <button onclick="event.stopPropagation(); addToCart(${p.id})" ${btnDisabled}>${btnText}</button>
        </div>`;
    }).join('');
}

// ── User: Checkout ─────────────────────────

function loadOrders() {
    try { return JSON.parse(localStorage.getItem(STORAGE_ORDERS_KEY) || '[]'); }
    catch (e) { return []; }
}

function confirmCheckout() {
    const name    = document.getElementById('checkoutName')?.value;
    const address = document.getElementById('checkoutAddress')?.value;
    const phone   = document.getElementById('checkoutPhone')?.value;
    if (!name || !address || !phone) { showNotification('Fill all fields!', 'error'); return; }

    let totalAmount = 0;
    const orderItems = userCart.map(c => {
        totalAmount += c.price * c.quantity;
        return { id: c.id, name: c.name, price: c.price, quantity: c.quantity };
    });

    const orders = loadOrders();
    orders.unshift({
        id: Date.now(),
        user: (typeof SESSION_USERNAME !== 'undefined' ? SESSION_USERNAME : null) || 'guest',
        items: orderItems,
        total: totalAmount,
        shippingAddress: address,
        phone,
        status: 'pending',
        createdAt: new Date().toISOString()
    });
    localStorage.setItem(STORAGE_ORDERS_KEY, JSON.stringify(orders));

    userCart = [];
    saveUserCart();
    renderUserCart();
    updateCartCount();
    renderUserProducts();
    closeCheckout();
    showNotification('Thank you ' + name + '! Order placed!', 'success');
    ['checkoutName', 'checkoutAddress', 'checkoutPhone'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
}

// ── Admin: State ───────────────────────────

let adminProducts       = [];
let adminSearchTerm     = '';
let adminFilterCategory = 'all';
let selectedDeleteProduct = null;

async function loadAdminProducts() {
    adminProducts = await loadProductsAsync();
}

// ── Admin: Render ──────────────────────────

function renderAdminStats() {
    const total      = adminProducts.length;
    const available  = adminProducts.filter(p => p.status === 'Available').length;
    const preOrder   = adminProducts.filter(p => p.status === 'Pre Order').length;
    const totalStock = adminProducts.reduce((s, p) => s + (p.stock || 0), 0);
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerHTML = val; };
    set('statTotal', total);
    set('statAvailable', available);
    set('statPreOrder', preOrder);
    set('statTotalStock', totalStock);
}

function renderAdminProducts() {
    const filtered = adminProducts.filter(p =>
        p.name.toLowerCase().includes(adminSearchTerm.toLowerCase()) &&
        (adminFilterCategory === 'all' || p.category === adminFilterCategory)
    );
    const container = document.getElementById('productsGrid');
    if (!container) return;
    if (filtered.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">👕</div><h3>No Products</h3></div>';
        return;
    }
    const catLabel    = { men: '👔 Men', women: '👗 Women', accessories: '🧢 Accessories' };
    const statusClass = { 'Available': 'stock-available', 'Pre Order': 'stock-preorder', 'Out of Stock': 'stock-out' };
    container.innerHTML = filtered.map(p => `
        <div class="product-card-admin">
            <div class="product-img-wrapper">
                <img src="${p.image}" onerror="this.src='https://placehold.co/300x200/333/fff?text=VEXO'">
                ${p.promo ? '<span class="promo-badge">🔥 PROMO</span>' : ''}
                <span class="stock-badge-admin ${statusClass[p.status] || 'stock-out'}">${p.status}</span>
            </div>
            <div class="product-info-admin">
                <h3>${escapeHtml(p.name)}</h3>
                <div class="product-category">${catLabel[p.category] || p.category}</div>
                <div class="price-wrapper">
                    <span class="current-price">$${p.price}</span>
                    ${p.oldPrice ? `<span class="old-price">$${p.oldPrice}</span>` : ''}
                </div>
                <div class="stock-control">
                    <button class="stock-btn" onclick="adjustStock(${p.id},-1)">-</button>
                    <span>Stock: ${p.stock}</span>
                    <button class="stock-btn" onclick="adjustStock(${p.id},1)">+</button>
                </div>
                <p class="product-desc">${escapeHtml((p.description || '').substring(0, 80))}${(p.description || '').length > 80 ? '...' : ''}</p>
                <div class="card-actions">
                    <button class="btn-edit"   onclick="openEditModal(${p.id})">✏️ Edit</button>
                    <button class="btn-delete" onclick="openDeleteModal(${p.id})">🗑️ Delete</button>
                </div>
            </div>
        </div>
    `).join('');
}

const ADMIN_STATUS_LABELS = {
    pending:   { label: 'Diproses', cls: 'as-pending'   },
    packing:   { label: 'Dikemas',  cls: 'as-packing'   },
    shipped:   { label: 'Dikirim',  cls: 'as-shipped'   },
    delivered: { label: 'Selesai',  cls: 'as-delivered' }
};

function adminSetOrderStatus(orderId, newStatus) {
    const orders = loadOrders();
    const order  = orders.find(o => o.id === orderId);
    if (!order) return;
    order.status = newStatus;
    localStorage.setItem(STORAGE_ORDERS_KEY, JSON.stringify(orders));
    renderAdminOrders();
    showNotification(`Order #${orderId} → ${ADMIN_STATUS_LABELS[newStatus]?.label || newStatus}`, 'success');
}

function renderAdminOrders() {
    const orders    = loadOrders();
    const container = document.getElementById('ordersList');
    if (!container) return;
    if (!orders.length) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📋</div><h3>No Orders Yet</h3><p>When customers place orders, they will appear here.</p></div>';
        return;
    }
    container.innerHTML = orders.map(order => {
        const st       = ADMIN_STATUS_LABELS[order.status] || { label: order.status, cls: 'as-pending' };
        const isDone   = order.status === 'delivered';
        const payLabel = order.paymentMethod === 'paypal' ? '💳 PayPal' : '💳 Mastercard';
        const nextBtns = isDone ? '' : `
            <div class="admin-order-actions">
                ${order.status === 'pending'  ? `<button class="aoa-btn aoa-pack"  onclick="adminSetOrderStatus(${order.id},'packing')">📦 Kemas</button>` : ''}
                ${order.status === 'packing'  ? `<button class="aoa-btn aoa-ship"  onclick="adminSetOrderStatus(${order.id},'shipped')">🚚 Kirim</button>` : ''}
                ${order.status === 'shipped'  ? `<button class="aoa-btn aoa-done"  onclick="adminSetOrderStatus(${order.id},'delivered')">✅ Selesai</button>` : ''}
            </div>`;
        return `
        <div class="order-card ${isDone ? 'order-card-done' : ''}">
            <div class="order-header">
                <div>
                    <strong>Order #${order.id}</strong>
                    <span style="font-size:12px;color:#888;margin-left:10px;">${new Date(order.createdAt).toLocaleString('id-ID')}</span>
                </div>
                <span class="admin-status-chip ${st.cls}">${st.label}</span>
            </div>
            <div class="order-meta">
                <span>👤 ${escapeHtml(order.user)}</span>
                <span>${payLabel}</span>
                <span>📍 ${escapeHtml(order.shippingAddress)}</span>
                <span>📞 ${escapeHtml(order.phone)}</span>
            </div>
            <div class="order-details">
                ${order.items.map(item => `
                    <div class="order-item">
                        <span>${escapeHtml(item.name)}</span>
                        <span>${item.quantity}x</span>
                        <span>$${item.price}</span>
                    </div>
                `).join('')}
            </div>
            <div class="order-info">
                <span><strong>Total: $${order.total.toFixed(2)}</strong></span>
            </div>
            ${nextBtns}
        </div>`;
    }).join('');
}

// ── Admin: CRUD ────────────────────────────

async function addProduct() {
    const name     = document.getElementById('addName')?.value;
    const price    = parseFloat(document.getElementById('addPrice')?.value);
    const oldPrice = document.getElementById('addOldPrice')?.value ? parseFloat(document.getElementById('addOldPrice').value) : null;
    const category = document.getElementById('addCategory')?.value;
    const status   = document.getElementById('addStatus')?.value;
    const stock    = parseInt(document.getElementById('addStock')?.value);
    const image    = document.getElementById('addImageUrl')?.value;
    const desc     = document.getElementById('addDescription')?.value;
    if (!name || !price || !image) { showNotification('Fill all fields!', 'error'); return; }
    try {
        const res  = await fetch('api/api.php?action=product_add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, price, oldPrice, category, image, description: desc, status, stock })
        });
        const data = await res.json();
        if (!res.ok || data.error) { showNotification('Failed: ' + (data.error || 'Unknown error'), 'error'); return; }
        adminProducts.unshift(normalizeProduct(data.product));
        renderAdminStats();
        renderAdminProducts();
        closeAddModal();
        showNotification(`Product "${name}" added!`, 'success');
        document.getElementById('addForm')?.reset();
        document.getElementById('addImagePreview').innerHTML = '';
    } catch (e) {
        console.error('Add product error:', e);
        showNotification('Failed to save product', 'error');
    }
}

async function updateProduct() {
    const id       = parseInt(document.getElementById('editProductId')?.value);
    const name     = document.getElementById('editName')?.value;
    const price    = parseFloat(document.getElementById('editPrice')?.value);
    const oldPrice = document.getElementById('editOldPrice')?.value ? parseFloat(document.getElementById('editOldPrice').value) : null;
    const category = document.getElementById('editCategory')?.value;
    const status   = document.getElementById('editStatus')?.value;
    const stock    = parseInt(document.getElementById('editStock')?.value);
    const image    = document.getElementById('editImageUrl')?.value;
    const desc     = document.getElementById('editDescription')?.value;
    const index    = adminProducts.findIndex(p => p.id === id);
    if (index === -1) return;
    try {
        const res  = await fetch('api/api.php?action=product_update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, name, price, oldPrice, category, image, description: desc, status, stock })
        });
        const data = await res.json();
        if (!res.ok || data.error) { showNotification('Failed: ' + (data.error || 'Unknown error'), 'error'); return; }
        adminProducts[index] = { ...normalizeProduct(data.product), promo: !!oldPrice };
        renderAdminStats();
        renderAdminProducts();
        closeEditModal();
        showNotification('Product updated!', 'success');
    } catch (e) {
        console.error('Update product error:', e);
        showNotification('Failed to update product', 'error');
    }
}

async function adjustStock(id, change) {
    const p = adminProducts.find(p => p.id === id);
    if (!p) return;
    const ns        = Math.max(0, p.stock + change);
    const newStatus = ns === 0 && p.status !== 'Pre Order' ? 'Out of Stock'
                    : ns > 0  && p.status === 'Out of Stock' ? 'Available'
                    : p.status;
    try {
        const res  = await fetch('api/api.php?action=product_update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: p.id, name: p.name, price: p.price, oldPrice: p.oldPrice || null, category: p.category, image: p.image, description: p.description, status: newStatus, stock: ns })
        });
        const data = await res.json();
        if (!res.ok || data.error) { showNotification('Failed: ' + (data.error || 'Unknown error'), 'error'); return; }
        p.stock  = ns;
        p.status = newStatus;
        renderAdminStats();
        renderAdminProducts();
        showNotification(`Stock updated: ${p.name} now has ${ns}`, 'success');
    } catch (e) {
        console.error('Adjust stock error:', e);
        showNotification('Failed to update stock', 'error');
    }
}

function deleteProduct() {
    if (!selectedDeleteProduct) return;
    const { id, name } = selectedDeleteProduct;
    fetch('api/api_delete.php?action=product&id=' + id, { method: 'DELETE' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                adminProducts = adminProducts.filter(p => p.id !== id);
                renderAdminStats();
                renderAdminProducts();
                closeDeleteModal();
                showNotification(name + ' deleted!', 'warning');
            } else {
                showNotification('Delete failed: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(() => showNotification('Delete failed', 'error'));
}

function confirmDelete() { deleteProduct(); }

// ── Admin: Modals ──────────────────────────

function openAddModal()    { document.getElementById('addModal')?.classList.add('active'); }
function closeAddModal()   { document.getElementById('addModal')?.classList.remove('active'); }
function closeEditModal()  { document.getElementById('editModal')?.classList.remove('active'); }
function closeDeleteModal(){ document.getElementById('deleteModal')?.classList.remove('active'); selectedDeleteProduct = null; }

function openEditModal(id) {
    const p = adminProducts.find(p => p.id === id);
    if (!p) return;
    document.getElementById('editProductId').value    = p.id;
    document.getElementById('editName').value         = p.name;
    document.getElementById('editPrice').value        = p.price;
    document.getElementById('editOldPrice').value     = p.oldPrice || '';
    document.getElementById('editCategory').value     = p.category;
    document.getElementById('editStatus').value       = p.status;
    document.getElementById('editStock').value        = p.stock;
    document.getElementById('editImageUrl').value     = p.image;
    document.getElementById('editDescription').value  = p.description;
    document.getElementById('editImagePreview').innerHTML = `<img src="${p.image}" onerror="this.src='https://placehold.co/80x80/333/fff?text=VEXO'">`;
    document.getElementById('editModal')?.classList.add('active');
}

function openDeleteModal(id) {
    const p = adminProducts.find(p => p.id === id);
    if (!p) return;
    selectedDeleteProduct = p;
    document.getElementById('deleteProductName').innerText = p.name;
    document.getElementById('deleteModal')?.classList.add('active');
}

// ── Admin: Toolbar ─────────────────────────

function handleAdminSearch() {
    adminSearchTerm = document.getElementById('adminSearchInput')?.value || '';
    renderAdminProducts();
}

function handleAdminFilter() {
    adminFilterCategory = document.getElementById('filterCategoryAdmin')?.value || 'all';
    renderAdminProducts();
}

function showProductsTab() {
    const grid   = document.getElementById('productsGrid');
    const orders = document.getElementById('ordersSection');
    if (grid)   grid.style.display   = 'grid';
    if (orders) orders.style.display = 'none';
    document.querySelectorAll('.admin-nav-item').forEach((el, i) => el.classList.toggle('active', i === 0));
}

function showOrdersTab() {
    const grid   = document.getElementById('productsGrid');
    const orders = document.getElementById('ordersSection');
    if (grid)   grid.style.display   = 'none';
    if (orders) orders.style.display = 'block';
    document.querySelectorAll('.admin-nav-item').forEach((el, i) => el.classList.toggle('active', i === 1));
    renderAdminOrders();
}

// ── Image Preview ──────────────────────────

function previewAddImage() {
    const url  = document.getElementById('addImageUrl')?.value;
    const prev = document.getElementById('addImagePreview');
    if (prev) prev.innerHTML = url ? `<img src="${url}" onerror="this.src='https://placehold.co/80x80/333/fff?text=VEXO'">` : '';
}

function previewEditImage() {
    const url  = document.getElementById('editImageUrl')?.value;
    const prev = document.getElementById('editImagePreview');
    if (prev) prev.innerHTML = url ? `<img src="${url}" onerror="this.src='https://placehold.co/80x80/333/fff?text=VEXO'">` : '';
}

function handleAddImageUpload(input) {
    if (!input.files?.[0]) return;
    const r = new FileReader();
    r.onload = e => { document.getElementById('addImageUrl').value = e.target.result; previewAddImage(); };
    r.readAsDataURL(input.files[0]);
}

function handleEditImageUpload(input) {
    if (!input.files?.[0]) return;
    const r = new FileReader();
    r.onload = e => { document.getElementById('editImageUrl').value = e.target.result; previewEditImage(); };
    r.readAsDataURL(input.files[0]);
}

// ── Init ───────────────────────────────────

document.addEventListener('DOMContentLoaded', async () => {
    const page = window.location.pathname.split('/').pop();

    if (page === 'index.php' || page === '') {
        await renderUserProducts();
        loadUserCart();
        renderUserCart();
        document.addEventListener('click', e => {
            const panel = document.getElementById('cartPanel');
            const icon  = document.querySelector('.cart-icon');
            if (panel?.classList.contains('open') && !panel.contains(e.target) && !icon?.contains(e.target)) {
                panel.classList.remove('open');
            }
        });
        window.addEventListener('scroll', () => {
            document.getElementById('navbar')?.classList.toggle('scrolled', window.scrollY > 60);
        });
    }

    if (page === 'admin.php') {
        await loadAdminProducts();
        renderAdminStats();
        renderAdminProducts();
        renderAdminOrders();
        document.addEventListener('click', e => {
            if (e.target === document.getElementById('addModal'))    closeAddModal();
            if (e.target === document.getElementById('editModal'))   closeEditModal();
            if (e.target === document.getElementById('deleteModal')) closeDeleteModal();
        });
    }
});
