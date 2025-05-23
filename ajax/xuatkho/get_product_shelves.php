<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy ID sản phẩm và kho từ request
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$warehouseId = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : 0;

if ($productId <= 0 || $warehouseId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
    exit;
}

// Lấy danh sách kệ chứa sản phẩm trong kho
$sql = "SELECT pl.shelf_id, s.shelf_code, pl.batch_number, pl.quantity
        FROM product_locations pl
        JOIN shelves s ON pl.shelf_id = s.shelf_id
        JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
        WHERE pl.product_id = ? AND wz.warehouse_id = ? AND pl.quantity > 0
        ORDER BY pl.expiry_date ASC, s.shelf_code";

$stmt = $pdo->prepare($sql);
$stmt->execute([$productId, $warehouseId]);
$shelves = $stmt->fetchAll();

echo json_encode(['success' => true, 'shelves' => $shelves]);