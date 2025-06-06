<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập và quyền admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Từ chối phiếu nhập
if (isset($_GET['import_id'])) {
    $import_id = (int)$_GET['import_id'];
    $reason = isset($_GET['reason']) ? $conn->real_escape_string($_GET['reason']) : '';
    $user_id = $_SESSION['user_id'];
    
    // Kiểm tra phiếu nhập có tồn tại và đang ở trạng thái chờ duyệt không
    $stmt = $conn->prepare("SELECT import_code FROM import_orders WHERE import_id = ? AND status = 'PENDING'");
    $stmt->bind_param("i", $import_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $import = $result->fetch_assoc();
        
        // Cập nhật trạng thái phiếu nhập
        $stmt = $conn->prepare("UPDATE import_orders SET status = 'CANCELLED', notes = CONCAT(notes, ' | Từ chối: ', ?) WHERE import_id = ?");
        $stmt->bind_param("si", $reason, $import_id);
        
        if ($stmt->execute()) {
            // Ghi log hoạt động
            logUserAction($user_id, 'REJECT_IMPORT', "Từ chối phiếu nhập {$import['import_code']}");
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái phiếu nhập']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Phiếu nhập không tồn tại hoặc không ở trạng thái chờ duyệt']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin phiếu nhập']);
}
?>