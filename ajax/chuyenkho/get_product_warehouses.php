<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy ID sản phẩm từ request
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
    exit;
}

// Lấy danh sách kho chứa sản phẩm
$sql = "SELECT w.warehouse_id, w.warehouse_name, i.quantity
        FROM inventory i
        JOIN warehouses w ON i.warehouse_id = w.warehouse_id
        WHERE i.product_id = ? AND i.quantity > 0
        ORDER BY w.warehouse_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

$warehouses = [];
while ($row = $result->fetch_assoc()) {
    $warehouses[] = $row;
}

echo json_encode(['success' => true, 'warehouses' => $warehouses]);
?>