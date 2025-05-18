<?php
session_start();
require_once "../config/connect.php";
require_once "../inc/auth.php";

// Kiểm tra quyền admin
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền thực hiện hành động này'
    ]);
    exit;
}

// Khởi tạo mảng lưu trữ các hành động đã thực hiện
$actions = [];

try {
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    // 1. Sửa role_id không hợp lệ trong users -> gán về role_id mặc định (3 - User)
    $stmt = $pdo->prepare("
        UPDATE users u 
        LEFT JOIN roles r ON u.role_id = r.role_id
        SET u.role_id = 3
        WHERE r.role_id IS NULL
    ");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($count > 0) {
        $actions[] = "Đã sửa $count người dùng có role_id không hợp lệ";
    }
    
    // 2. Sửa category_id không hợp lệ trong products -> gán về category_id mặc định (1)
    $stmt = $pdo->prepare("
        UPDATE products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
        SET p.category_id = 1
        WHERE c.category_id IS NULL AND p.category_id IS NOT NULL
    ");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($count > 0) {
        $actions[] = "Đã sửa $count sản phẩm có category_id không hợp lệ";
    }
    
    // 3. Sửa số lượng âm trong inventory
    $stmt = $pdo->prepare("UPDATE inventory SET quantity = 0 WHERE quantity < 0");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($count > 0) {
        $actions[] = "Đã sửa $count bản ghi tồn kho có số lượng âm";
    }
    
    // 4. Sửa số lượng âm trong product_locations
    $stmt = $pdo->prepare("UPDATE product_locations SET quantity = 0 WHERE quantity < 0");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($count > 0) {
        $actions[] = "Đã sửa $count vị trí sản phẩm có số lượng âm";
    }
    
    // 5. Gộp các bản ghi trùng lặp trong product_locations
    $stmt = $pdo->query("
        SELECT product_id, shelf_id, batch_number, GROUP_CONCAT(location_id) as location_ids, SUM(quantity) as total_quantity
        FROM product_locations
        GROUP BY product_id, shelf_id, batch_number
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll();
    
    foreach ($duplicates as $duplicate) {
        $location_ids = explode(',', $duplicate['location_ids']);
        $keep_id = array_shift($location_ids); // Giữ lại ID đầu tiên
        
        // Cập nhật số lượng cho bản ghi đầu tiên
        $stmt = $pdo->prepare("UPDATE product_locations SET quantity = ? WHERE location_id = ?");
        $stmt->execute([$duplicate['total_quantity'], $keep_id]);
        
        // Xóa các bản ghi còn lại
        $stmt = $pdo->prepare("DELETE FROM product_locations WHERE location_id IN (" . implode(',', $location_ids) . ")");
        $stmt->execute();
        
        $actions[] = "Đã gộp {$duplicate['product_id']} vị trí trùng lặp cho sản phẩm ID {$duplicate['product_id']} tại kệ {$duplicate['shelf_id']}";
    }
    
    // 6. Hủy bỏ các phiếu nhập/xuất treo quá lâu
    $stmt = $pdo->prepare("
        UPDATE import_orders 
        SET status = 'CANCELLED', notes = CONCAT(IFNULL(notes, ''), ' | Tự động hủy do treo quá lâu')
        WHERE status = 'PENDING' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($count > 0) {
        $actions[] = "Đã hủy $count phiếu nhập kho bị treo quá 7 ngày";
    }
    
    $stmt = $pdo->prepare("
        UPDATE export_orders 
        SET status = 'CANCELLED', notes = CONCAT(IFNULL(notes, ''), ' | Tự động hủy do treo quá lâu')
        WHERE status = 'PENDING' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($count > 0) {
        $actions[] = "Đã hủy $count phiếu xuất kho bị treo quá 7 ngày";
    }
    
    // Commit transaction nếu mọi thứ OK
    $pdo->commit();
    
    // Ghi log hệ thống
    logSystem('INFO', 'Đã sửa lỗi toàn vẹn dữ liệu: ' . implode('; ', $actions), 'data_integrity');
    
    // Phản hồi kết quả
    echo json_encode([
        'success' => true,
        'actions' => $actions
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction nếu có lỗi
    $pdo->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    logSystem('ERROR', 'Lỗi khi sửa toàn vẹn dữ liệu: ' . $e->getMessage(), 'data_integrity');
}
?>
