<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Xử lý lưu phiếu nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    
    try {
        $import_id = isset($_POST['importId']) ? (int)$_POST['importId'] : 0;
        $import_code = $conn->real_escape_string($_POST['importCode']);
        $supplier_id = (int)$_POST['supplierId'];
        $warehouse_id = (int)$_POST['warehouseId'];
        $notes = $conn->real_escape_string($_POST['notes'] ?? '');
        $total_amount = (float)$_POST['totalAmount'];
        $user_id = $_SESSION['user_id'];
        
        if ($import_id > 0) {
            // Cập nhật phiếu nhập
            $stmt = $conn->prepare("UPDATE import_orders SET 
                                  supplier_id = ?, 
                                  warehouse_id = ?, 
                                  total_amount = ?, 
                                  notes = ? 
                                  WHERE import_id = ? AND status = 'DRAFT'");
            $stmt->bind_param("iidsi", $supplier_id, $warehouse_id, $total_amount, $notes, $import_id);
            $stmt->execute();
            
            // Xóa chi tiết phiếu nhập cũ
            $stmt = $conn->prepare("DELETE FROM import_order_details WHERE import_id = ?");
            $stmt->bind_param("i", $import_id);
            $stmt->execute();
        } else {
            // Tạo phiếu nhập mới
            $stmt = $conn->prepare("INSERT INTO import_orders (import_code, supplier_id, warehouse_id, total_amount, status, notes, created_by) 
                                  VALUES (?, ?, ?, ?, 'DRAFT', ?, ?)");
            $stmt->bind_param("siidsi", $import_code, $supplier_id, $warehouse_id, $total_amount, $notes, $user_id);
            $stmt->execute();
            $import_id = $conn->insert_id;
            
            // Ghi log hoạt động
            logUserAction($user_id, 'CREATE_IMPORT', "Tạo phiếu nhập kho $import_code");
        }
        
        // Thêm chi tiết phiếu nhập
        if (isset($_POST['details']) && is_array($_POST['details'])) {
            $stmt = $conn->prepare("INSERT INTO import_order_details (import_id, product_id, quantity, unit_price, batch_number, expiry_date, shelf_id) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['details'] as $detail) {
                $product_id = (int)$detail['productId'];
                $quantity = (int)$detail['quantity'];
                $unit_price = (float)$detail['unitPrice'];
                $batch_number = $conn->real_escape_string($detail['batchNumber'] ?? null);
                $expiry_date = !empty($detail['expiryDate']) ? $detail['expiryDate'] : null;
                $shelf_id = !empty($detail['shelfId']) ? (int)$detail['shelfId'] : null;
                
                $stmt->bind_param("iiidssi", $import_id, $product_id, $quantity, $unit_price, $batch_number, $expiry_date, $shelf_id);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'import_id' => $import_id]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
}
?>