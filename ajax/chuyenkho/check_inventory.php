<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy tham số
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$warehouseId = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : 0;
$shelfId = isset($_GET['shelf_id']) ? intval($_GET['shelf_id']) : 0;

if ($productId <= 0 || $warehouseId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
    exit;
}

// Kiểm tra số lượng tồn kho
if ($shelfId > 0) {
    // Kiểm tra số lượng trên kệ cụ thể
    $sql = "SELECT quantity FROM product_locations 
            WHERE product_id = ? AND shelf_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $productId, $shelfId);
} else {
    // Kiểm tra tổng số lượng trong kho
    $sql = "SELECT quantity FROM inventory 
            WHERE product_id = ? AND warehouse_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $productId, $warehouseId);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'available' => (int)$row['quantity']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin tồn kho', 'available' => 0]);
}
?>