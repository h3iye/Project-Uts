<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VEXO Admin | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-navbar">
            <div class="admin-logo"><div class="admin-logo-icon">👕</div><span class="admin-logo-text">VEXO<span>Admin</span></span></div>
            <div class="admin-nav-menu">
                <div class="admin-nav-item active" onclick="showProductsTab()">📦 Products</div>
                <div class="admin-nav-item" onclick="showOrdersTab()">📋 Orders</div>
                <a href="index.php" class="back-to-store">🏠 Back to Store</a>
                <button class="logout-btn" onclick="handleLogout()">🚪 Logout</button>
            </div>
        </nav>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-info"><h4>Total Products</h4><div class="stat-number" id="statTotal">0</div></div><div class="stat-icon">👕</div></div>
            <div class="stat-card"><div class="stat-info"><h4>Available Products</h4><div class="stat-number" id="statAvailable">0</div></div><div class="stat-icon">✅</div></div>
            <div class="stat-card"><div class="stat-info"><h4>Pre Order Products</h4><div class="stat-number" id="statPreOrder">0</div></div><div class="stat-icon">⏳</div></div>
            <div class="stat-card"><div class="stat-info"><h4>Total Stock</h4><div class="stat-number" id="statTotalStock">0</div></div><div class="stat-icon">📦</div></div>
        </div>
        <div class="admin-toolbar">
            <div class="search-filter">
                <div class="search-box-admin"><span>🔍</span><input type="text" id="adminSearchInput" placeholder="Search..." onkeyup="handleAdminSearch()"></div>
                <select id="filterCategoryAdmin" class="filter-select" onchange="handleAdminFilter()">
                    <option value="all">All Categories</option>
                    <option value="men">Men</option>
                    <option value="women">Women</option>
                    <option value="accessories">Accessories</option>
                </select>
            </div>
            <button class="btn-add-product" onclick="openAddModal()">➕ Add New Product</button>
        </div>
        <div id="productsGrid" class="products-grid-admin"></div>
        <div id="ordersSection" style="display: none;">
            <div class="orders-header">
                <h2>User Orders</h2>
                <p>Orders placed by customers will appear here.</p>
            </div>
            <div id="ordersList"></div>
        </div>
    </div>
    <div id="addModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header"><h3>✨ Add New Product</h3><span class="modal-close" onclick="closeAddModal()">&times;</span></div>
            <div class="modal-body">
                <form id="addForm" onsubmit="event.preventDefault(); addProduct();">
                    <div class="form-group"><label>Product Name</label><input type="text" id="addName" required placeholder="e.g., Oversized Tee"></div>
                    <div class="form-row-2">
                        <div class="form-group"><label>Price ($)</label><input type="number" id="addPrice" required placeholder="45"></div>
                        <div class="form-group"><label>Old Price ($)</label><input type="number" id="addOldPrice" placeholder="68"></div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group"><label>Category</label><select id="addCategory"><option value="men">Men</option><option value="women">Women</option><option value="accessories">Accessories</option></select></div>
                        <div class="form-group"><label>Status</label><select id="addStatus"><option>Available</option><option>Pre Order</option><option>Out of Stock</option></select></div>
                    </div>
                    <div class="form-group"><label>Stock Quantity</label><input type="number" id="addStock" value="10" required></div>
                    <div class="form-group"><label>Image URL</label><input type="text" id="addImageUrl" placeholder="https://..." oninput="previewAddImage()"></div>
                    <div class="form-group"><div class="image-upload-area" onclick="document.getElementById('addImageFile').click()">📸 Upload Image<input type="file" id="addImageFile" accept="image/*" style="display:none" onchange="handleAddImageUpload(this)"></div><div id="addImagePreview" class="image-preview"></div></div>
                    <div class="form-group"><label>Description</label><textarea id="addDescription" rows="3"></textarea></div>
                    <button type="submit" class="btn-save">💾 Save Product</button>
                </form>
            </div>
        </div>
    </div>
    <div id="editModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header"><h3>✏️ Edit Product</h3><span class="modal-close" onclick="closeEditModal()">&times;</span></div>
            <div class="modal-body">
                <form id="editForm" onsubmit="event.preventDefault(); updateProduct();">
                    <input type="hidden" id="editProductId">
                    <div class="form-group"><label>Product Name</label><input type="text" id="editName" required></div>
                    <div class="form-row-2"><div class="form-group"><label>Price ($)</label><input type="number" id="editPrice" required></div><div class="form-group"><label>Old Price ($)</label><input type="number" id="editOldPrice"></div></div>
                    <div class="form-row-2"><div class="form-group"><label>Category</label><select id="editCategory"><option value="men">Men</option><option value="women">Women</option><option value="accessories">Accessories</option></select></div><div class="form-group"><label>Status</label><select id="editStatus"><option>Available</option><option>Pre Order</option><option>Out of Stock</option></select></div></div>
                    <div class="form-group"><label>Stock Quantity</label><input type="number" id="editStock" required></div>
                    <div class="form-group"><label>Image URL</label><input type="text" id="editImageUrl" oninput="previewEditImage()"></div>
                    <div class="form-group"><div class="image-upload-area" onclick="document.getElementById('editImageFile').click()">📸 Upload New Image<input type="file" id="editImageFile" accept="image/*" style="display:none" onchange="handleEditImageUpload(this)"></div><div id="editImagePreview" class="image-preview"></div></div>
                    <div class="form-group"><label>Description</label><textarea id="editDescription" rows="3"></textarea></div>
                    <button type="submit" class="btn-save">💾 Update Product</button>
                </form>
            </div>
        </div>
    </div>
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-container"><div class="modal-body" style="text-align:center;"><div style="font-size:60px;">🗑️</div><h3>Delete Confirmation</h3><p>Are you sure you want to delete <strong id="deleteProductName"></strong>?</p><div class="delete-actions"><button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button><button class="btn-confirm-delete" onclick="confirmDelete()">Delete</button></div></div></div>
    </div>
    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>