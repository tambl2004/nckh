<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy ID kho từ request
$warehouseId = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : 0;

if ($warehouseId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID kho không hợp lệ']);
    exit;
}

// Lấy danh sách sản phẩm có trong kho
$sql = "SELECT p.product_id, p.product_code, p.product_name, p.price, IFNULL(i.quantity, 0) as quantity
        FROM products p
        JOIN inventory i ON p.product_id = i.product_id
        WHERE i.warehouse_id = ? AND i.quantity > 0
        ORDER BY p.product_name";

$stmt = $pdo->prepare($sql);
$stmt->execute([$warehouseId]);
$products = $stmt->fetchAll();

echo json_encode(['success' => true, 'products' => $products]);