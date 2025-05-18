<?php
// Kết nối database và khởi tạo session
require_once '../config/database.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng chức năng này']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Lấy ID kho từ tham số URL
$warehouseId = isset($_GET['warehouseId']) ? intval($_GET['warehouseId']) : 0;

if ($warehouseId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID kho không hợp lệ']);
    exit;
}

// Truy vấn lấy danh sách khu vực theo kho
$query = "SELECT zone_id, zone_code, zone_name, description 
          FROM warehouse_zones 
          WHERE warehouse_id = ? AND is_active = 1
          ORDER BY zone_code";

$stmt = $conn->prepare($query);
$stmt->execute([$warehouseId]);
$zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Trả về dữ liệu dạng JSON
header('Content-Type: application/json');
echo json_encode(['success' => true, 'zones' => $zones]);
?>