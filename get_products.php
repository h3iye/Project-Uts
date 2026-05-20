<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$conn = get_db_connection(true);

$result = $conn->query('SELECT * FROM products ORDER BY id');
$products = [];
while ($row = $result->fetch_assoc()) {
    $row['promo'] = (bool) $row['promo'];
    $products[] = $row;
}

$conn->close();

echo json_encode(['products' => $products]);
