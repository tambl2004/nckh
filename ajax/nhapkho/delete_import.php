<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Xóa phiếu nhập
if (isset($_GET['import_id'])) {
    $import_id = (int)$_GET['import_id'];
    $user_id = $_SESSION['user_id'];
    
    // Kiểm tra phiếu nhập có tồn tại và đang ở trạng thái nháp không
    $stmt = $conn->prepare("SELECT import_code FROM import_orders WHERE import_id = ? AND status = 'DRAFT'");
    $stmt->bind_param("i", $import_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $import = $result->fetch_assoc();
        $conn->begin_transaction();
        
        try {
            // Xóa chi tiết phiếu nhập
            $stmt = $conn->prepare("DELETE FROM import_order_details WHERE import_id = ?");
            $stmt->bind_param("i", $import_id);
            $stmt->execute();
            
            // Xóa phiếu nhập
            $stmt = $conn->prepare("DELETE FROM import_orders WHERE import_id = ?");
            $stmt->bind_param("i", $import_id);
            $stmt->execute();
            
            // Ghi log hoạt động
            logUserAction($user_id, 'DELETE_IMPORT', "Xóa phiếu nhập {$import['import_code']}");
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Phiếu nhập không tồn tại hoặc không ở trạng thái nháp']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin phiếu nhập']);
}
?>