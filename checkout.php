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
    <title>VEXO | Checkout</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- ── Navbar ── -->
    <header class="navbar scrolled">
        <div class="logo-text" onclick="window.location.href='index.php'" style="cursor:pointer;">VEXO</div>
        <div style="flex:1;"></div>
        <div class="icons">
            <div class="logout-icon" onclick="window.location.href='logout.php'" title="Logout">🚪</div>
        </div>
    </header>

    <!-- ── Breadcrumb ── -->
    <div class="detail-breadcrumb">
        <span onclick="window.location.href='index.php'">Home</span>
        <span class="bc-sep">›</span>
        <span>Checkout</span>
    </div>

    <!-- ── Empty Cart Guard ── -->
    <div class="co-empty" id="coEmpty" style="display:none;">
        <div style="font-size:64px;">🛒</div>
        <h2>Your cart is empty</h2>
        <p>Add some products before checking out.</p>
        <button onclick="window.location.href='index.php'">Back to Shop</button>
    </div>

    <!-- ── Checkout Layout ── -->
    <main class="co-main" id="coMain" style="display:none;">

        <!-- Left: Order Summary -->
        <section class="co-summary">
            <h2 class="co-section-title">Order Summary</h2>
            <div class="co-items" id="coItems"></div>
            <div class="co-totals">
                <div class="co-row"><span>Subtotal</span><span id="coSubtotal">$0</span></div>
                <div class="co-row"><span>Shipping</span><span class="co-free">FREE</span></div>
                <div class="co-divider"></div>
                <div class="co-row co-total-row"><span>Total</span><span id="coTotal">$0</span></div>
            </div>
        </section>

        <!-- Right: Payment + Data Diri -->
        <section class="co-form-side">

            <!-- Payment Method -->
            <div class="co-card">
                <h2 class="co-section-title">Payment Method</h2>
                <div class="co-pay-tabs">
                    <button class="co-pay-tab active" id="tabMastercard" onclick="switchPayment('mastercard')">
                        <!-- Mastercard SVG -->
                        <svg class="pay-logo" viewBox="0 0 38 24" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="15" cy="12" r="10" fill="#EB001B"/>
                            <circle cx="23" cy="12" r="10" fill="#F79E1B"/>
                            <path d="M19 4.8a10 10 0 0 1 0 14.4A10 10 0 0 1 19 4.8z" fill="#FF5F00"/>
                        </svg>
                        Mastercard
                    </button>
                    <button class="co-pay-tab" id="tabPaypal" onclick="switchPayment('paypal')">
                        <!-- PayPal SVG -->
                        <svg class="pay-logo" viewBox="0 0 60 24" xmlns="http://www.w3.org/2000/svg">
                            <text y="18" font-size="13" font-family="Arial,sans-serif" font-weight="800">
                                <tspan fill="#003087">Pay</tspan><tspan fill="#009cde">Pal</tspan>
                            </text>
                        </svg>
                        PayPal
                    </button>
                </div>

                <!-- Mastercard Fields -->
                <div id="panelMastercard" class="co-pay-panel">
                    <div class="co-field-group">
                        <label>Card Number</label>
                        <input type="text" id="cardNumber" placeholder="0000 0000 0000 0000" maxlength="19" oninput="fmtCard(this)">
                    </div>
                    <div class="co-field-group">
                        <label>Name on Card</label>
                        <input type="text" id="cardName" placeholder="JOHN DOE">
                    </div>
                    <div class="co-field-row">
                        <div class="co-field-group">
                            <label>Expiry Date</label>
                            <input type="text" id="cardExpiry" placeholder="MM / YY" maxlength="7" oninput="fmtExpiry(this)">
                        </div>
                        <div class="co-field-group">
                            <label>CVV</label>
                            <input type="password" id="cardCvv" placeholder="•••" maxlength="4">
                        </div>
                    </div>
                </div>

                <!-- PayPal Fields -->
                <div id="panelPaypal" class="co-pay-panel" style="display:none;">
                    <div class="co-paypal-info">
                        <svg viewBox="0 0 90 30" xmlns="http://www.w3.org/2000/svg" style="height:36px;">
                            <text y="22" font-size="22" font-family="Arial,sans-serif" font-weight="800">
                                <tspan fill="#003087">Pay</tspan><tspan fill="#009cde">Pal</tspan>
                            </text>
                        </svg>
                        <p>You will be redirected to PayPal to complete your payment securely.</p>
                    </div>
                    <div class="co-field-group">
                        <label>PayPal Email</label>
                        <input type="email" id="paypalEmail" placeholder="yourname@email.com">
                    </div>
                </div>
            </div>

            <!-- Personal Info -->
            <div class="co-card">
                <h2 class="co-section-title">Shipping Information</h2>
                <div class="co-field-group">
                    <label>Full Name</label>
                    <input type="text" id="buyerName" placeholder="Enter your full name">
                </div>
                <div class="co-field-group">
                    <label>Phone Number</label>
                    <input type="text" id="buyerPhone" placeholder="+62 812-XXXX-XXXX">
                </div>
                <div class="co-field-group">
                    <label>Shipping Address</label>
                    <textarea id="buyerAddress" placeholder="Street, City, Province, Postal Code" rows="3"></textarea>
                </div>
            </div>

            <!-- Place Order -->
            <button class="co-place-btn" onclick="placeOrder()">
                Place Order →
            </button>
        </section>
    </main>


<script>const SESSION_USERNAME = '<?php echo htmlspecialchars($_SESSION['username'] ?? 'guest', ENT_QUOTES); ?>';</script>
    <script src="assets/js/script.js"></script>
    <script>
        let activePayment = 'mastercard';

        function switchPayment(method) {
            activePayment = method;
            document.getElementById('tabMastercard').classList.toggle('active', method === 'mastercard');
            document.getElementById('tabPaypal').classList.toggle('active', method === 'paypal');
            document.getElementById('panelMastercard').style.display = method === 'mastercard' ? '' : 'none';
            document.getElementById('panelPaypal').style.display     = method === 'paypal'     ? '' : 'none';
        }

        function fmtCard(el) {
            let v = el.value.replace(/\D/g, '').substring(0, 16);
            el.value = v.match(/.{1,4}/g)?.join(' ') || v;
        }

        function fmtExpiry(el) {
            let v = el.value.replace(/\D/g, '').substring(0, 4);
            if (v.length >= 3) v = v.substring(0,2) + ' / ' + v.substring(2);
            el.value = v;
        }

        function loadCart() {
            try { return JSON.parse(localStorage.getItem(STORAGE_CART_KEY) || '[]'); }
            catch (e) { return []; }
        }

        function renderSummary() {
            const cart = loadCart();
            if (cart.length === 0) {
                document.getElementById('coEmpty').style.display = '';
                document.getElementById('coMain').style.display  = 'none';
                return;
            }
            document.getElementById('coEmpty').style.display = 'none';
            document.getElementById('coMain').style.display  = '';

            let subtotal = 0;
            document.getElementById('coItems').innerHTML = cart.map(item => {
                subtotal += item.price * item.quantity;
                return `<div class="co-item">
                    <img src="${item.image}" onerror="this.src='https://placehold.co/80x80/333/fff?text=VEXO'">
                    <div class="co-item-info">
                        <span class="co-item-name">${escapeHtml(item.name)}</span>
                        <span class="co-item-qty">Qty: ${item.quantity}</span>
                    </div>
                    <span class="co-item-price">$${(item.price * item.quantity).toFixed(2)}</span>
                </div>`;
            }).join('');

            document.getElementById('coSubtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('coTotal').textContent    = '$' + subtotal.toFixed(2);
        }

        function validatePayment() {
            if (activePayment === 'mastercard') {
                const num  = document.getElementById('cardNumber').value.replace(/\s/g, '');
                const name = document.getElementById('cardName').value.trim();
                const exp  = document.getElementById('cardExpiry').value.trim();
                const cvv  = document.getElementById('cardCvv').value.trim();
                if (num.length < 16)  { showNotification('Enter a valid card number!', 'error'); return false; }
                if (!name)            { showNotification('Enter name on card!', 'error'); return false; }
                if (exp.length < 7)   { showNotification('Enter a valid expiry date!', 'error'); return false; }
                if (cvv.length < 3)   { showNotification('Enter a valid CVV!', 'error'); return false; }
            } else {
                const email = document.getElementById('paypalEmail').value.trim();
                if (!email || !email.includes('@')) { showNotification('Enter a valid PayPal email!', 'error'); return false; }
            }
            return true;
        }

        function placeOrder() {
            const name    = document.getElementById('buyerName').value.trim();
            const phone   = document.getElementById('buyerPhone').value.trim();
            const address = document.getElementById('buyerAddress').value.trim();

            if (!name)    { showNotification('Enter your full name!', 'error'); return; }
            if (!phone)   { showNotification('Enter your phone number!', 'error'); return; }
            if (!address) { showNotification('Enter your shipping address!', 'error'); return; }
            if (!validatePayment()) return;

            const cart = loadCart();
            if (cart.length === 0) { showNotification('Cart is empty!', 'error'); return; }

            let total = 0;
            const items = cart.map(c => {
                total += c.price * c.quantity;
                return { id: c.id, name: c.name, price: c.price, quantity: c.quantity };
            });

            const orders = (() => { try { return JSON.parse(localStorage.getItem(STORAGE_ORDERS_KEY) || '[]'); } catch(e){ return []; } })();
            orders.unshift({
                id: Date.now(),
                user: (typeof SESSION_USERNAME !== 'undefined' ? SESSION_USERNAME : null) || 'guest',
                items, total,
                shippingAddress: address,
                phone,
                paymentMethod: activePayment,
                status: 'pending',
                createdAt: new Date().toISOString()
            });
            localStorage.setItem(STORAGE_ORDERS_KEY, JSON.stringify(orders));
            localStorage.removeItem(STORAGE_CART_KEY);

            window.location.href = 'my-orders.php';
        }

        document.addEventListener('DOMContentLoaded', renderSummary);
    </script>
</body>
</html>
