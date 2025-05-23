<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập và quyền admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Duyệt phiếu nhập
if (isset($_GET['import_id'])) {
    $import_id = (int)$_GET['import_id'];
    $user_id = $_SESSION['user_id'];
    
    // Kiểm tra phiếu nhập có tồn tại và đang ở trạng thái chờ duyệt không
    $stmt = $conn->prepare("SELECT import_code FROM import_orders WHERE import_id = ? AND status = 'PENDING'");
    $stmt->bind_param("i", $import_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $import = $result->fetch_assoc();
        $conn->begin_transaction();
        
        try {
            // Cập nhật trạng thái phiếu nhập
            $stmt = $conn->prepare("UPDATE import_orders SET status = 'COMPLETED', approved_by = ?, approved_at = NOW() WHERE import_id = ?");
            $stmt->bind_param("ii", $user_id, $import_id);
            $stmt->execute();
            
            // Lấy danh sách chi tiết phiếu nhập
            $stmt = $conn->prepare("SELECT * FROM import_order_details WHERE import_id = ?");
            $stmt->bind_param("i", $import_id);
            $stmt->execute();
            $details_result = $stmt->get_result();
            
            // Lấy warehouse_id của phiếu nhập
            $stmt = $conn->prepare("SELECT warehouse_id FROM import_orders WHERE import_id = ?");
            $stmt->bind_param("i", $import_id);
            $stmt->execute();
            $warehouse_result = $stmt->get_result();
            $warehouse_row = $warehouse_result->fetch_assoc();
            $warehouse_id = $warehouse_row['warehouse_id'];
            
            // Cập nhật tồn kho cho từng sản phẩm
            while ($detail = $details_result->fetch_assoc()) {
                // Cập nhật hoặc thêm mới vào bảng tồn kho
                $stmt = $conn->prepare("INSERT INTO inventory (product_id, warehouse_id, quantity) 
                                      VALUES (?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                $stmt->bind_param("iiii", $detail['product_id'], $warehouse_id, $detail['quantity'], $detail['quantity']);
                $stmt->execute();
                
                // Cập nhật vị trí sản phẩm trong kho nếu có shelf_id
                if (!empty($detail['shelf_id'])) {
                    $stmt = $conn->prepare("INSERT INTO product_locations (product_id, shelf_id, batch_number, expiry_date, quantity, entry_date) 
                                          VALUES (?, ?, ?, ?, ?, NOW()) 
                                          ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                    $stmt->bind_param("iissii", $detail['product_id'], $detail['shelf_id'], $detail['batch_number'], $detail['expiry_date'], $detail['quantity'], $detail['quantity']);
                    $stmt->execute();
                }
            }
            
            // Ghi log hoạt động
            logUserAction($user_id, 'APPROVE_IMPORT', "Duyệt phiếu nhập {$import['import_code']}");
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Phiếu nhập không tồn tại hoặc không ở trạng thái chờ duyệt']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin phiếu nhập']);
}
?>