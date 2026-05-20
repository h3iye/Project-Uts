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
    <title>VEXO | Streetwear Apparel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- ── Navbar ── -->
    <header class="navbar" id="navbar">
        <div class="logo-text">VEXO</div>
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchInput" placeholder="Search products..." onkeyup="handleUserSearch()">
        </div>
        <ul class="menu">
            <li class="nav-item" onclick="filterProducts('all'); scrollToProducts()">Home</li>
            <li class="nav-item" onclick="filterProducts('men'); scrollToProducts()">Men</li>
            <li class="nav-item" onclick="filterProducts('women'); scrollToProducts()">Women</li>
            <li class="nav-item" onclick="filterProducts('accessories'); scrollToProducts()">Accessories</li>
        </ul>
        <div class="icons">
            <div class="profile-icon" onclick="window.location.href='my-orders.php'" title="My Orders">📦</div>
            <div class="cart-icon" onclick="toggleCart()" title="Cart">
                🛒 <span id="cartCount">0</span>
            </div>
            <div class="logout-icon" onclick="handleLogout()" title="Logout">🚪</div>
        </div>
    </header>

    <!-- ── Hero ── -->
    <section class="hero" id="heroSection">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="hero-badge">✦ NEW COLLECTION 2026 ✦</span>
            <h1 class="hero-title">DEFINE<br><span>YOUR STYLE</span></h1>
            <p class="hero-subtitle">Premium streetwear for the bold & fearless generation</p>
            <div class="hero-actions">
                <button class="hero-btn-primary" onclick="filterProducts('all'); scrollToProducts()">Shop Now →</button>
                <button class="hero-btn-secondary" onclick="scrollToPromo()">🔥 View Promos</button>
            </div>
        </div>
        <div class="hero-scroll-hint" onclick="scrollToCategories()">
            <span>Scroll Down</span>
            <div class="scroll-arrow">↓</div>
        </div>
    </section>

    <!-- ── Categories ── -->
    <section class="categories-section" id="categoriesSection">
        <div class="section-header">
            <h2>Shop by Category</h2>
            <p>Find your perfect style</p>
        </div>
        <div class="category-cards">
            <div class="cat-card" onclick="filterProducts('men'); scrollToProducts()">
                <img id="catImgMen" src="https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&h=700&fit=crop" alt="Men">
                <div class="cat-overlay">
                    <span class="cat-label">MEN</span>
                    <span class="cat-sub">View Collection →</span>
                </div>
            </div>
            <div class="cat-card" onclick="filterProducts('women'); scrollToProducts()">
                <img id="catImgWomen" src="https://images.unsplash.com/photo-1532456746303-ef1c93e6c2db?w=600&h=700&fit=crop" alt="Women">
                <div class="cat-overlay">
                    <span class="cat-label">WOMEN</span>
                    <span class="cat-sub">View Collection →</span>
                </div>
            </div>
            <div class="cat-card" onclick="filterProducts('accessories'); scrollToProducts()">
                <img id="catImgAccessories" src="https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=600&h=700&fit=crop" alt="Accessories">
                <div class="cat-overlay">
                    <span class="cat-label">ACCESSORIES</span>
                    <span class="cat-sub">View Collection →</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Promo Section ── -->
    <section class="promo-section" id="promoSection" style="display:none;">
        <div class="section-header">
            <h2>🔥 Hot Deals</h2>
            <p>Limited time offers — grab them before they're gone</p>
        </div>
        <div class="promo-track" id="promoContainer"></div>
    </section>

    <!-- ── All Products ── -->
    <section class="products-section" id="productsSection">
        <div class="section-header">
            <h2>All Products</h2>
            <div class="filter-tabs" id="filterTabs">
                <button class="filter-tab active" onclick="filterProducts('all'); setActiveTab(this)">All</button>
                <button class="filter-tab" onclick="filterProducts('men'); setActiveTab(this)">Men</button>
                <button class="filter-tab" onclick="filterProducts('women'); setActiveTab(this)">Women</button>
                <button class="filter-tab" onclick="filterProducts('accessories'); setActiveTab(this)">Accessories</button>
            </div>
        </div>
        <div class="products" id="productContainer"></div>
    </section>

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

    <!-- ── Footer ── -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-box">
                <h2 class="brand">VEXO</h2>
                <p>Urban streetwear for the modern generation.</p>
            </div>
            <div class="footer-box">
                <h3>SHOP</h3>
                <ul>
                    <li onclick="filterProducts('all'); scrollToProducts()">All Products</li>
                    <li onclick="filterProducts('men'); scrollToProducts()">Men</li>
                    <li onclick="filterProducts('women'); scrollToProducts()">Women</li>
                    <li onclick="filterProducts('accessories'); scrollToProducts()">Accessories</li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>SUPPORT</h3>
                <ul>
                    <li>Shipping Info</li>
                    <li>Returns</li>
                    <li>FAQ</li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>CONTACT US</h3>
                <ul class="contact-list">
                    <li>📧 support@vexostreet.com</li>
                    <li>📞 +62 812-3456-7890</li>
                    <li>📍 Jl. Sudirman No. 88,<br>&nbsp;&nbsp;&nbsp;&nbsp;Jakarta Pusat, 10220</li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>NEWSLETTER</h3>
                <p style="font-size:13px;margin-bottom:12px;">Get the latest drops & promos</p>
                <div class="newsletter">
                    <input type="email" placeholder="your@email.com">
                    <button>JOIN</button>
                </div>
            </div>
        </div>
        <div class="footer-bottom">© 2026 VEXO. All Rights Reserved.</div>
    </footer>

    <script>const SESSION_USERNAME = '<?php echo htmlspecialchars($_SESSION['username'] ?? 'guest', ENT_QUOTES); ?>';</script>
    <script src="assets/js/script.js"></script>
</body>
</html>
