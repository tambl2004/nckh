<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Lấy danh sách kệ trong kho
if (isset($_GET['warehouse_id'])) {
    $warehouse_id = (int)$_GET['warehouse_id'];
    
    // Lấy danh sách kệ
    $stmt = $conn->prepare("SELECT s.shelf_id, s.shelf_code, s.position 
                          FROM shelves s 
                          JOIN warehouse_zones wz ON s.zone_id = wz.zone_id 
                          WHERE wz.warehouse_id = ? AND s.status = 'ACTIVE'
                          ORDER BY wz.zone_code, s.shelf_code");
    $stmt->bind_param("i", $warehouse_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $shelves = [];
    while ($row = $result->fetch_assoc()) {
        $shelves[] = $row;
    }
    
    echo json_encode(['success' => true, 'shelves' => $shelves]);
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin kho']);
}
?>