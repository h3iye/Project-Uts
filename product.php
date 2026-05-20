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

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VEXO | Product Detail</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- ── Navbar ── -->
    <header class="navbar scrolled" id="navbar">
        <div class="logo-text" onclick="window.location.href='index.php'" style="cursor:pointer;">VEXO</div>
        <div style="flex:1;"></div>
        <div class="icons">
            <div class="profile-icon" onclick="openProfile()" title="Profile">👤</div>
            <div class="cart-icon" onclick="toggleCart()" title="Cart">
                🛒 <span id="cartCount">0</span>
            </div>
            <div class="logout-icon" onclick="handleLogout()" title="Logout">🚪</div>
        </div>
    </header>

    <!-- ── Breadcrumb ── -->
    <div class="detail-breadcrumb">
        <span onclick="window.location.href='index.php'">Home</span>
        <span class="bc-sep">›</span>
        <span id="bcProductName">Product</span>
    </div>

    <!-- ── Product Detail ── -->
    <main class="detail-main" id="detailMain">
        <div class="detail-loading" id="detailLoading">
            <div class="detail-spinner"></div>
            <p>Loading product...</p>
        </div>

        <div class="detail-content" id="detailContent" style="display:none;">
            <!-- Left: Image -->
            <div class="detail-left">
                <div class="detail-img-wrap">
                    <img id="detailImg" src="" alt="" onerror="this.src='https://placehold.co/600x700/111/fff?text=VEXO'">
                    <span class="detail-discount-badge" id="detailDiscountBadge" style="display:none;"></span>
                </div>
            </div>

            <!-- Right: Info -->
            <div class="detail-right">
                <div class="detail-category" id="detailCategory"></div>
                <h1 class="detail-name" id="detailName"></h1>

                <div class="detail-price-row">
                    <span class="detail-price" id="detailPrice"></span>
                    <span class="detail-old-price" id="detailOldPrice" style="display:none;"></span>
                </div>

                <div class="detail-stock-row">
                    <span class="detail-stock-badge" id="detailStockBadge"></span>
                    <span class="detail-stock-count" id="detailStockCount"></span>
                </div>

                <div class="detail-desc-block">
                    <h4>Description</h4>
                    <p id="detailDesc"></p>
                </div>

                <div class="detail-size-block" id="detailSizeBlock">
                    <h4>Select Size</h4>
                    <div class="size-options" id="sizeOptions"></div>
                    <p class="size-guide-link">Size Guide →</p>
                </div>

                <div class="detail-actions">
                    <button class="detail-buy-btn" id="detailBuyBtn" onclick="detailBuyNow()">
                        Buy Now
                    </button>
                    <button class="detail-cart-btn" id="detailCartBtn" onclick="detailAddToCart()">
                        Add to Cart
                    </button>
                </div>
                <div class="detail-back-row">
                    <button class="detail-back-btn" onclick="window.location.href='index.php'">
                        ← Back to Shop
                    </button>
                </div>
            </div>
        </div>

        <div class="detail-error" id="detailError" style="display:none;">
            <div style="font-size:64px;">😕</div>
            <h2>Product not found</h2>
            <button onclick="window.location.href='index.php'">Back to Shop</button>
        </div>
    </main>

    <!-- ── Cart Panel ── -->
    <div id="cartPanel" class="cart-panel">
        <div class="cart-header">
            <h2>Shopping Cart</h2>
            <span class="close-cart" onclick="toggleCart()">✖</span>
        </div>
        <div id="cartItemsContainer" class="cart-items"></div>
        <div class="cart-footer">
            <div class="subtotal-row"><span>Subtotal</span><h3 id="totalPrice">$0</h3></div>
            <button class="checkout-btn" onclick="openCheckout()">Checkout</button>
        </div>
    </div>

    <!-- ── Checkout Modal ── -->
    <div id="checkoutModal" class="checkout-modal">
        <div class="checkout-box">
            <span class="close-btn" onclick="closeCheckout()">✖</span>
            <h2>Checkout</h2>
            <input type="text" id="checkoutName" placeholder="Full Name">
            <input type="text" id="checkoutAddress" placeholder="Shipping Address">
            <input type="text" id="checkoutPhone" placeholder="Phone Number">
            <button onclick="confirmCheckout()">Place Order ✓</button>
        </div>
    </div>

    <script>const SESSION_USERNAME = '<?php echo htmlspecialchars($_SESSION['username'] ?? 'guest', ENT_QUOTES); ?>';</script>
    <script src="assets/js/script.js"></script>
    <script>
        const DETAIL_PRODUCT_ID = <?php echo $productId; ?>;

        const SIZE_MAP = {
            men:         ['S','M','L','XL','XXL'],
            women:       ['XS','S','M','L','XL'],
            accessories: ['One Size']
        };

        let detailProduct   = null;
        let selectedSize    = null;

        async function loadDetail() {
            try {
                const res  = await fetch(`api/get_product.php?id=${DETAIL_PRODUCT_ID}`);
                const data = await res.json();
                if (!res.ok || data.error) { showError(); return; }
                detailProduct = normalizeProduct(data.product);
                renderDetail(detailProduct);
            } catch (e) {
                console.error(e);
                showError();
            }
        }

        function showError() {
            document.getElementById('detailLoading').style.display = 'none';
            document.getElementById('detailError').style.display   = 'block';
        }

        function renderDetail(p) {
            document.title = `VEXO | ${p.name}`;
            document.getElementById('bcProductName').textContent = p.name;

            document.getElementById('detailImg').src  = p.image;
            document.getElementById('detailImg').alt  = p.name;
            document.getElementById('detailName').textContent = p.name;

            const catLabels = { men: '👔 MEN', women: '👗 WOMEN', accessories: '🧢 ACCESSORIES' };
            document.getElementById('detailCategory').textContent = catLabels[p.category] || p.category.toUpperCase();

            document.getElementById('detailPrice').textContent = `$${p.price}`;

            if (p.oldPrice) {
                const discount = Math.round((1 - p.price / p.oldPrice) * 100);
                document.getElementById('detailOldPrice').textContent  = `$${p.oldPrice}`;
                document.getElementById('detailOldPrice').style.display = '';
                const badge = document.getElementById('detailDiscountBadge');
                badge.textContent    = `-${discount}%`;
                badge.style.display  = '';
            }

            // Stock badge
            const badge     = document.getElementById('detailStockBadge');
            const stockText = document.getElementById('detailStockCount');
            if (p.status === 'Available' && p.stock > 0) {
                badge.textContent     = 'In Stock';
                badge.className       = 'detail-stock-badge stock-available';
                stockText.textContent = `${p.stock} units available`;
            } else if (p.status === 'Pre Order') {
                badge.textContent     = 'Pre Order';
                badge.className       = 'detail-stock-badge stock-preorder';
                stockText.textContent = 'Ships in 7–14 days';
            } else {
                badge.textContent     = 'Out of Stock';
                badge.className       = 'detail-stock-badge stock-out';
                stockText.textContent = 'Currently unavailable';
            }

            document.getElementById('detailDesc').textContent =
                p.description || 'No description available for this product.';

            // Sizes
            const sizes   = SIZE_MAP[p.category] || ['S','M','L','XL'];
            const sizeWrap = document.getElementById('sizeOptions');
            if (sizes.length === 1 && sizes[0] === 'One Size') {
                selectedSize = 'One Size';
                document.getElementById('detailSizeBlock').style.display = 'none';
            } else {
                sizeWrap.innerHTML = sizes.map(s =>
                    `<button class="size-btn" onclick="selectSize('${s}', this)">${s}</button>`
                ).join('');
            }

            // Cart & Buy button state
            const btn    = document.getElementById('detailCartBtn');
            const buyBtn = document.getElementById('detailBuyBtn');
            if (p.status === 'Out of Stock' || (p.status === 'Available' && p.stock <= 0)) {
                btn.disabled    = true;
                btn.textContent = 'Out of Stock';
                btn.classList.add('disabled');
                buyBtn.disabled = true;
                buyBtn.classList.add('disabled');
            } else if (p.status === 'Pre Order') {
                btn.textContent    = 'Pre Order';
                buyBtn.textContent = 'Pre Order Now';
            }

            document.getElementById('detailLoading').style.display  = 'none';
            document.getElementById('detailContent').style.display  = '';
        }

        function selectSize(size, el) {
            selectedSize = size;
            document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
        }

        function requireSize() {
            const sizes = SIZE_MAP[detailProduct.category] || ['S','M','L','XL'];
            if (sizes.length > 1 && !selectedSize) {
                showNotification('Please select a size first!', 'error');
                const opts = document.getElementById('sizeOptions');
                opts.style.animation = 'none';
                requestAnimationFrame(() => { opts.style.animation = 'shake 0.3s ease'; });
                return false;
            }
            return true;
        }

        function detailAddToCart() {
            if (!detailProduct || !requireSize()) return;
            cachedProducts = [detailProduct];
            addToCart(detailProduct.id);
        }

        function detailBuyNow() {
            if (!detailProduct || !requireSize()) return;
            cachedProducts = [detailProduct];
            addToCart(detailProduct.id);
            window.location.href = 'checkout.php';
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadUserCart();
            renderUserCart();
            loadDetail();
            window.addEventListener('scroll', () => {
                document.getElementById('navbar')?.classList.toggle('scrolled', window.scrollY > 0);
            });
        });
    </script>
</body>
</html>
