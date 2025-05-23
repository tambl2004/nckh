<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy ID phiếu xuất từ request
$exportId = isset($_GET['export_id']) ? intval($_GET['export_id']) : 0;

if ($exportId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID phiếu xuất không hợp lệ']);
    exit;
}

// Lấy thông tin phiếu xuất
$sql = "SELECT eo.*, w.warehouse_name, 
        CASE 
            WHEN eo.status = 'DRAFT' THEN 'Nháp'
            WHEN eo.status = 'PENDING' THEN 'Chờ duyệt'
            WHEN eo.status = 'COMPLETED' THEN 'Đã duyệt'
            WHEN eo.status = 'CANCELLED' THEN 'Đã hủy'
            ELSE eo.status
        END as status_text,
        DATE_FORMAT(eo.created_at, '%d/%m/%Y %H:%i') as created_at,
        DATE_FORMAT(eo.approved_at, '%d/%m/%Y %H:%i') as approved_at,
        u.full_name as approved_by_name
        FROM export_orders eo
        JOIN warehouses w ON eo.warehouse_id = w.warehouse_id
        LEFT JOIN users u ON eo.approved_by = u.user_id
        WHERE eo.export_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$exportId]);
$export = $stmt->fetch();

if (!$export) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu xuất']);
    exit;
}

// Lấy chi tiết phiếu xuất
$sql = "SELECT eod.*, p.product_name, p.product_code, s.shelf_code
        FROM export_order_details eod
        JOIN products p ON eod.product_id = p.product_id
        LEFT JOIN shelves s ON eod.shelf_id = s.shelf_id
        WHERE eod.export_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$exportId]);
$details = $stmt->fetchAll();

echo json_encode(['success' => true, 'export' => $export, 'details' => $details]);