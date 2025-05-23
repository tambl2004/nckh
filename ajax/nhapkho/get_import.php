<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Lấy thông tin phiếu nhập
if (isset($_GET['import_id'])) {
    $import_id = (int)$_GET['import_id'];
    
    // Lấy thông tin phiếu nhập
    $stmt = $conn->prepare("SELECT * FROM import_orders WHERE import_id = ?");
    $stmt->bind_param("i", $import_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $import = $result->fetch_assoc();
        
        // Lấy chi tiết phiếu nhập
        $stmt = $conn->prepare("SELECT * FROM import_order_details WHERE import_id = ?");
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