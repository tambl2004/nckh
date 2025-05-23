<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Lấy thông tin nhà cung cấp
if (isset($_GET['supplier_id'])) {
    $supplier_id = (int)$_GET['supplier_id'];
    
    // Lấy thông tin nhà cung cấp
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $supplier = $result->fetch_assoc();
        echo json_encode(['success' => true, 'supplier' => $supplier]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhà cung cấp']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin nhà cung cấp']);
}
?>