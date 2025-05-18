<?php
session_start();
include_once '../config/connect.php';
include_once '../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để tiếp tục']);
    exit;
}

// Lấy thông tin người dùng hiện tại
$currentUserId = $_SESSION['user_id'] ?? 0;
if ($currentUserId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Phiên làm việc không hợp lệ']);
    exit;
}
// Xử lý các yêu cầu API
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    // Lấy danh sách kệ theo kho
    case 'getShelvesByWarehouse':
        getShelvesByWarehouse();
        break;
    
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}

// Hàm lấy danh sách kệ theo kho
function getShelvesByWarehouse() {
    global $conn;
    
    $warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
    
    if ($warehouseId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID kho không hợp lệ']);
        return;
    }
    
    $query = "SELECT s.shelf_id, s.shelf_code, s.position 
              FROM shelves s
              JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
              WHERE wz.warehouse_id = $warehouseId
              ORDER BY s.shelf_code";
    
    $result = $conn->query($query);
    
    if ($result) {
        $shelves = [];
        while ($row = $result->fetch_assoc()) {
            $shelves[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'shelves' => $shelves
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $conn->error]);
    }
}
?>