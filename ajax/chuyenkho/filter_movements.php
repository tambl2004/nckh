<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy tham số lọc
$status = isset($_GET['status']) ? $_GET['status'] : '';
$warehouse = isset($_GET['warehouse']) ? intval($_GET['warehouse']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Xây dựng câu truy vấn
$sql = "SELECT im.*, 
        p.product_code, p.product_name,
        sw.warehouse_name as source_warehouse, 
        tw.warehouse_name as target_warehouse,
        ss.shelf_code as source_shelf,
        ts.shelf_code as target_shelf,
        u.full_name as created_by_name
        FROM inventory_movements im
        JOIN products p ON im.product_id = p.product_id
        JOIN warehouses sw ON im.source_warehouse_id = sw.warehouse_id
        JOIN warehouses tw ON im.target_warehouse_id = tw.warehouse_id
        LEFT JOIN shelves ss ON im.source_shelf_id = ss.shelf_id
        LEFT JOIN shelves ts ON im.target_shelf_id = ts.shelf_id
        JOIN users u ON im.created_by = u.user_id
        WHERE 1=1";

// Thêm điều kiện lọc
if (!empty($status)) {
    $sql .= " AND im.status = '$status'";
}

if ($warehouse > 0) {
    $sql .= " AND (im.source_warehouse_id = $warehouse OR im.target_warehouse_id = $warehouse)";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (im.movement_code LIKE '%$search%' OR p.product_code LIKE '%$search%' OR p.product_name LIKE '%$search%')";
}

// Sắp xếp kết quả
$sql .= " ORDER BY im.created_at DESC";

// Thực hiện truy vấn
$result = $conn->query($sql);

// Xử lý kết quả
$movements = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Định dạng ngày tạo
        $row['created_at_formatted'] = date('d/m/Y H:i', strtotime($row['created_at']));
        $movements[] = $row;
    }
}

// Trả về kết quả
echo json_encode(['success' => true, 'movements' => $movements]);
?>