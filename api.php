<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function require_login(?string $role = null): void
{
    if (! isset($_SESSION['user_id'])) {
        respond(['error' => 'Authentication required'], 401);
    }

    if ($role !== null && $_SESSION['role'] !== $role) {
        respond(['error' => 'Forbidden'], 403);
    }
}

$action = $_GET['action'] ?? null;
if ($action === null) {
    respond(['error' => 'Missing action'], 400);
}

$conn = get_db_connection(true);

switch ($action) {
    case 'products':
        $result = $conn->query('SELECT * FROM products ORDER BY id');
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['promo'] = (bool) $row['promo'];
            $products[] = $row;
        }
        respond(['products' => $products]);
        break;

    case 'orders':
        require_login('admin');
        $orders = [];

        $orderResult = $conn->query('SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC');
        while ($order = $orderResult->fetch_assoc()) {
            $order['items'] = [];
            $orders[$order['id']] = $order;
        }

        if (! empty($orders)) {
            $ids = implode(',', array_keys($orders));
            $itemResult = $conn->query(
                'SELECT oi.order_id, p.name, oi.quantity, oi.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id IN (' . $ids . ')'
            );
            while ($item = $itemResult->fetch_assoc()) {
                $orders[$item['order_id']]['items'][] = $item;
            }
        }

        respond(['orders' => array_values($orders)]);
        break;

    case 'product_add':
        require_login('admin');
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        $price = floatval($data['price'] ?? 0);
        $oldPrice = isset($data['oldPrice']) ? floatval($data['oldPrice']) : null;
        $category = trim($data['category'] ?? '');
        $status = trim($data['status'] ?? 'Available');
        $stock = intval($data['stock'] ?? 0);
        $image = trim($data['image'] ?? '');
        $description = trim($data['description'] ?? '');
        $promo = $oldPrice !== null ? 1 : 0;

        if ($name === '' || $price <= 0 || $category === '') {
            respond(['error' => 'Invalid product data'], 400);
        }

        if ($oldPrice === null) {
            $stmt = $conn->prepare('INSERT INTO products (`name`, price, old_price, category, image, description, status, stock, promo) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sdssssii', $name, $price, $category, $image, $description, $status, $stock, $promo);
        } else {
            $stmt = $conn->prepare('INSERT INTO products (`name`, price, old_price, category, image, description, status, stock, promo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sddsssiii', $name, $price, $oldPrice, $category, $image, $description, $status, $stock, $promo);
        }

        if (! $stmt->execute()) {
            respond(['error' => 'Insert failed: ' . $stmt->error], 500);
        }

        $productId = $stmt->insert_id;
        $stmt->close();

        $product = $conn->query('SELECT * FROM products WHERE id = ' . intval($productId))->fetch_assoc();
        $product['promo'] = (bool) $product['promo'];
        respond(['product' => $product]);
        break;

    case 'product_update':
        require_login('admin');
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $price = floatval($data['price'] ?? 0);
        $oldPrice = isset($data['oldPrice']) ? floatval($data['oldPrice']) : null;
        $category = trim($data['category'] ?? '');
        $status = trim($data['status'] ?? 'Available');
        $stock = intval($data['stock'] ?? 0);
        $image = trim($data['image'] ?? '');
        $description = trim($data['description'] ?? '');
        $promo = $oldPrice !== null ? 1 : 0;

        if ($id <= 0 || $name === '' || $price <= 0 || $category === '') {
            respond(['error' => 'Invalid update data'], 400);
        }

        if ($oldPrice === null) {
            $stmt = $conn->prepare('UPDATE products SET `name` = ?, price = ?, old_price = NULL, category = ?, image = ?, description = ?, status = ?, stock = ?, promo = ? WHERE id = ?');
            $stmt->bind_param('sdssssiii', $name, $price, $category, $image, $description, $status, $stock, $promo, $id);
        } else {
            $stmt = $conn->prepare('UPDATE products SET `name` = ?, price = ?, old_price = ?, category = ?, image = ?, description = ?, status = ?, stock = ?, promo = ? WHERE id = ?');
            $stmt->bind_param('sddssssiii', $name, $price, $oldPrice, $category, $image, $description, $status, $stock, $promo, $id);
        }

        if (! $stmt->execute()) {
            respond(['error' => 'Update failed: ' . $stmt->error], 500);
        }

        $stmt->close();
        $product = $conn->query('SELECT * FROM products WHERE id = ' . intval($id))->fetch_assoc();
        $product['promo'] = (bool) $product['promo'];
        respond(['product' => $product]);
        break;

    case 'product_delete':
        require_login('admin');
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);

        if ($id <= 0) {
            respond(['error' => 'Invalid product id'], 400);
        }

        $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (! $stmt->execute()) {
            respond(['error' => 'Delete failed: ' . $stmt->error], 500);
        }

        respond(['success' => true]);
        break;

    case 'order_create':
        require_login('user');
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $_SESSION['user_id'];
        $shippingAddress = trim($data['shippingAddress'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $items = $data['items'] ?? [];
        $totalAmount = floatval($data['total'] ?? 0);

        if ($shippingAddress === '' || $phone === '' || empty($items) || $totalAmount <= 0) {
            respond(['error' => 'Invalid order data'], 400);
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('INSERT INTO orders (user_id, total_amount, shipping_address, phone, status) VALUES (?, ?, ?, ?, ?)');
            $status = 'pending';
            $stmt->bind_param('idsss', $userId, $totalAmount, $shippingAddress, $phone, $status);
            if (! $stmt->execute()) {
                throw new RuntimeException('Order insert failed: ' . $stmt->error);
            }

            $orderId = $stmt->insert_id;
            $stmt->close();

            $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
            $updateStock = $conn->prepare('UPDATE products SET stock = ?, status = ? WHERE id = ?');

            foreach ($items as $item) {
                $productId = intval($item['id'] ?? 0);
                $quantity = intval($item['quantity'] ?? 0);
                $price = floatval($item['price'] ?? 0);

                if ($productId <= 0 || $quantity <= 0 || $price <= 0) {
                    throw new RuntimeException('Invalid order item');
                }

                $itemStmt->bind_param('iiid', $orderId, $productId, $quantity, $price);
                if (! $itemStmt->execute()) {
                    throw new RuntimeException('Order item insert failed: ' . $itemStmt->error);
                }

                $product = $conn->query('SELECT stock, status FROM products WHERE id = ' . $productId)->fetch_assoc();
                if (! $product) {
                    throw new RuntimeException('Product not found');
                }

                $newStock = max(0, intval($product['stock']) - $quantity);
                $newStatus = $newStock === 0 && $product['status'] !== 'Pre Order' ? 'Out of Stock' : $product['status'];
                $updateStock->bind_param('isi', $newStock, $newStatus, $productId);
                if (! $updateStock->execute()) {
                    throw new RuntimeException('Stock update failed: ' . $updateStock->error);
                }
            }

            $itemStmt->close();
            $updateStock->close();
            $conn->commit();
            respond(['success' => true]);
        } catch (Throwable $e) {
            $conn->rollback();
            respond(['error' => $e->getMessage()], 500);
        }
        break;

    default:
        respond(['error' => 'Unknown action'], 400);
}
