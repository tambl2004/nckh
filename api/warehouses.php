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
    // Lấy danh sách kho
    case 'getWarehouses':
        getWarehouses();
        break;
    
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}

// Hàm lấy danh sách kho
function getWarehouses() {
    global $conn;
    
    $query = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
    $result = $conn->query($query);
    
    if ($result) {
        $warehouses = [];
        while ($row = $result->fetch_assoc()) {
            $warehouses[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'warehouses' => $warehouses
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $conn->error]);
    }
}
?>