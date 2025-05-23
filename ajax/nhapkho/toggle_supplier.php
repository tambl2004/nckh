<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Bật/tắt trạng thái nhà cung cấp
if (isset($_GET['supplier_id']) && isset($_GET['is_active'])) {
    $supplier_id = (int)$_GET['supplier_id'];
    $is_active = (int)$_GET['is_active'];
    $user_id = $_SESSION['user_id'];
    
    // Lấy thông tin nhà cung cấp
    $stmt = $conn->prepare("SELECT supplier_name FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $supplier = $result->fetch_assoc();
        
        // Cập nhật trạng thái nhà cung cấp
        $stmt = $conn->prepare("UPDATE suppliers SET is_active = ? WHERE supplier_id = ?");
        $stmt->bind_param("ii", $is_active, $supplier_id);
        
        if ($stmt->execute()) {
            // Ghi log hoạt động
            $action = $is_active ? 'ACTIVATE_SUPPLIER' : 'DEACTIVATE_SUPPLIER';
            $message = $is_active ? "Kích hoạt nhà cung cấp: {$supplier['supplier_name']}" : "Vô hiệu hóa nhà cung cấp: {$supplier['supplier_name']}";
            logUserAction($user_id, $action, $message);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái nhà cung cấp']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhà cung cấp']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin nhà cung cấp hoặc trạng thái']);
}
?>