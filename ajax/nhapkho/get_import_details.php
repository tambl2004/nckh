<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Lấy chi tiết phiếu nhập
if (isset($_GET['import_id'])) {
    $import_id = (int)$_GET['import_id'];
    
    // Lấy thông tin phiếu nhập
    $stmt = $conn->prepare("SELECT io.*, s.supplier_name, w.warehouse_name 
                          FROM import_orders io
                          JOIN suppliers s ON io.supplier_id = s.supplier_id
                          JOIN warehouses w ON io.warehouse_id = w.warehouse_id
                          WHERE io.import_id = ?");
    $stmt->bind_param("i", $import_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $import = $result->fetch_assoc();
        
        // Lấy chi tiết phiếu nhập
        $stmt = $conn->prepare("SELECT iod.*, p.product_name, s.shelf_code 
                              FROM import_order_details iod
                              JOIN products p ON iod.product_id = p.product_id
                              LEFT JOIN shelves s ON iod.shelf_id = s.shelf_id
                              WHERE iod.import_id = ?");
        $stmt->bind_param("i", $import_id);
        $stmt->execute();
        $details_result = $stmt->get_result();
        
        $details = [];
        while ($row = $details_result->fetch_assoc()) {
            $details[] = $row;
        }
        
        echo json_encode(['success' => true, 'import' => $import, 'details' => $details]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu nhập']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin phiếu nhập']);
}
?>