<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VEXO | My Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header class="navbar scrolled">
        <div class="logo-text" onclick="window.location.href='index.php'" style="cursor:pointer;">VEXO</div>
        <div style="flex:1;"></div>
        <div class="icons">
            <div class="cart-icon" onclick="window.location.href='index.php'" title="Shop">🛍️</div>
            <div class="logout-icon" onclick="window.location.href='logout.php'" title="Logout">🚪</div>
        </div>
    </header>

    <div class="detail-breadcrumb">
        <span onclick="window.location.href='index.php'">Home</span>
        <span class="bc-sep">›</span>
        <span>My Orders</span>
    </div>

    <main class="mo-main">
        <div class="mo-header">
            <h1>My Orders</h1>
            <p id="moSubtitle"></p>
        </div>

        <div id="moEmpty" style="display:none;" class="mo-empty">
            <div style="font-size:64px;">📦</div>
            <h2>No orders yet</h2>
            <p>Start shopping and your orders will appear here.</p>
            <button onclick="window.location.href='index.php'">Shop Now</button>
        </div>

        <div id="moList" class="mo-list"></div>
    </main>

    <script>const SESSION_USERNAME = '<?php echo htmlspecialchars($_SESSION['username'] ?? 'guest', ENT_QUOTES); ?>';</script>
    <script src="assets/js/script.js"></script>
    <script>
        const STATUS_STEPS = [
            { key: 'pending',   label: 'Diproses',  icon: '🔄', desc: 'Pesanan sedang diproses' },
            { key: 'packing',   label: 'Dikemas',   icon: '📦', desc: 'Pesanan sedang dikemas' },
            { key: 'shipped',   label: 'Dikirim',   icon: '🚚', desc: 'Pesanan dalam perjalanan' },
            { key: 'delivered', label: 'Selesai',   icon: '✅', desc: 'Pesanan telah diterima' }
        ];

        const STEP_HOURS = [0, 2, 24, 96];

        function getStepIndex(status) {
            const map = { pending: 0, packing: 1, shipped: 2, delivered: 3 };
            return map[status] ?? 0;
        }

        function fmtDate(iso) {
            return new Date(iso).toLocaleString('id-ID', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        }

        function addHours(iso, h) {
            return new Date(new Date(iso).getTime() + h * 3600000);
        }

        function fmtEst(date) {
            return date.toLocaleString('id-ID', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        }

        function renderSteps(order) {
            const active = getStepIndex(order.status);
            return STATUS_STEPS.map((s, i) => {
                const done    = i < active;
                const current = i === active;
                const estTime = fmtEst(addHours(order.createdAt, STEP_HOURS[i]));
                return `<div class="mo-step ${done ? 'done' : ''} ${current ? 'current' : ''}">
                    <div class="mo-step-circle">${done ? '✓' : s.icon}</div>
                    <div class="mo-step-info">
                        <span class="mo-step-label">${s.label}</span>
                        <span class="mo-step-time">${estTime}</span>
                    </div>
                    ${i < STATUS_STEPS.length - 1 ? '<div class="mo-step-line"></div>' : ''}
                </div>`;
            }).join('');
        }

        function renderOrders() {
            const raw    = localStorage.getItem(STORAGE_ORDERS_KEY);
            const orders = raw ? JSON.parse(raw) : [];
            const mine   = orders.filter(o =>
                o.user === SESSION_USERNAME || o.user === 'guest'
            );

            document.getElementById('moSubtitle').textContent =
                mine.length ? `${mine.length} pesanan ditemukan` : '';

            if (mine.length === 0) {
                document.getElementById('moEmpty').style.display = '';
                document.getElementById('moList').style.display  = 'none';
                return;
            }

            document.getElementById('moEmpty').style.display = 'none';
            document.getElementById('moList').style.display  = '';

            const estDelivery = o => fmtEst(addHours(o.createdAt, 96));
            const payLabel    = m => m === 'paypal' ? 'PayPal' : 'Mastercard';

            document.getElementById('moList').innerHTML = mine.map(order => `
                <div class="mo-card ${order.status === 'delivered' ? 'mo-card-done' : ''}">
                    <div class="mo-card-head">
                        <div>
                            <span class="mo-order-id">Order #${order.id}</span>
                            <span class="mo-order-date">${fmtDate(order.createdAt)}</span>
                        </div>
                        <span class="mo-status-chip mo-status-${order.status}">
                            ${STATUS_STEPS[getStepIndex(order.status)].icon}
                            ${STATUS_STEPS[getStepIndex(order.status)].label}
                        </span>
                    </div>

                    <div class="mo-stepper">${renderSteps(order)}</div>

                    <div class="mo-items">
                        ${order.items.map(item => `
                            <div class="mo-item">
                                <span class="mo-item-name">${escapeHtml(item.name)}</span>
                                <span class="mo-item-qty">${item.quantity}x</span>
                                <span class="mo-item-price">$${(item.price * item.quantity).toFixed(2)}</span>
                            </div>
                        `).join('')}
                    </div>

                    <div class="mo-footer-row">
                        <div class="mo-meta">
                            <span>📍 ${escapeHtml(order.shippingAddress)}</span>
                            <span>💳 ${payLabel(order.paymentMethod)}</span>
                            ${order.status !== 'delivered'
                                ? `<span>🕐 Estimasi tiba: <strong>${estDelivery(order)}</strong></span>`
                                : `<span>🎉 Pesanan telah diterima!</span>`
                            }
                        </div>
                        <div class="mo-total">Total: <strong>$${order.total.toFixed(2)}</strong></div>
                    </div>
                </div>
            `).join('');
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderOrders();
            window.addEventListener('storage', renderOrders);
        });
    </script>
</body>
</html>
