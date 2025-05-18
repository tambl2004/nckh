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

// Khởi tạo mảng lưu trữ các vấn đề toàn vẹn dữ liệu
$issues = [];
$can_fix = false;

try {
    // 1. Kiểm tra khóa ngoại không hợp lệ
    
    // Kiểm tra role_id trong users
    $stmt = $pdo->query("
        SELECT u.user_id, u.username 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE r.role_id IS NULL
    ");
    $invalid_roles = $stmt->fetchAll();
    
    if (count($invalid_roles) > 0) {
        foreach ($invalid_roles as $user) {
            $issues[] = "Người dùng '{$user['username']}' (ID: {$user['user_id']}) có role_id không hợp lệ";
        }
        $can_fix = true;
    }
    
    // Kiểm tra category_id trong products
    $stmt = $pdo->query("
        SELECT p.product_id, p.product_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE c.category_id IS NULL AND p.category_id IS NOT NULL
    ");
    $invalid_categories = $stmt->fetchAll();
    
    if (count($invalid_categories) > 0) {
        foreach ($invalid_categories as $product) {
            $issues[] = "Sản phẩm '{$product['product_name']}' (ID: {$product['product_id']}) có category_id không hợp lệ";
        }
        $can_fix = true;
    }
    
    // 2. Kiểm tra nhất quán dữ liệu kho
    
    // Kiểm tra sản phẩm có số lượng âm trong kho
    $stmt = $pdo->query("SELECT product_id, warehouse_id, quantity FROM inventory WHERE quantity < 0");
    $negative_inventory = $stmt->fetchAll();
    
    if (count($negative_inventory) > 0) {
        foreach ($negative_inventory as $item) {
            $issues[] = "Sản phẩm ID {$item['product_id']} có số lượng âm ({$item['quantity']}) tại kho ID {$item['warehouse_id']}";
        }
        $can_fix = true;
    }
    
    // Kiểm tra sản phẩm có số lượng âm trong vị trí kệ
    $stmt = $pdo->query("SELECT product_id, shelf_id, quantity FROM product_locations WHERE quantity < 0");
    $negative_locations = $stmt->fetchAll();
    
    if (count($negative_locations) > 0) {
        foreach ($negative_locations as $location) {
            $issues[] = "Sản phẩm ID {$location['product_id']} có số lượng âm ({$location['quantity']}) tại kệ ID {$location['shelf_id']}";
        }
        $can_fix = true;
    }
    
    // 3. Kiểm tra các bản ghi trùng lặp
    $stmt = $pdo->query("
        SELECT product_id, shelf_id, batch_number, COUNT(*) as count
        FROM product_locations
        GROUP BY product_id, shelf_id, batch_number
        HAVING COUNT(*) > 1
    ");
    $duplicate_locations = $stmt->fetchAll();
    
    if (count($duplicate_locations) > 0) {
        foreach ($duplicate_locations as $duplicate) {
            $issues[] = "Phát hiện {$duplicate['count']} bản ghi trùng lặp cho sản phẩm ID {$duplicate['product_id']} tại kệ ID {$duplicate['shelf_id']} (batch: {$duplicate['batch_number']})";
        }
        $can_fix = true;
    }
    
    // 4. Kiểm tra phiên không đóng đúng cách
    $stmt = $pdo->query("
        SELECT * FROM import_orders 
        WHERE status = 'PENDING' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $hanging_imports = $stmt->fetchAll();
    
    if (count($hanging_imports) > 0) {
        foreach ($hanging_imports as $order) {
            $issues[] = "Phiếu nhập kho ID {$order['import_id']} (mã: {$order['import_code']}) đang bị treo ở trạng thái PENDING quá 7 ngày";
        }
        $can_fix = true;
    }
    
    $stmt = $pdo->query("
        SELECT * FROM export_orders 
        WHERE status = 'PENDING' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $hanging_exports = $stmt->fetchAll();
    
    if (count($hanging_exports) > 0) {
        foreach ($hanging_exports as $order) {
            $issues[] = "Phiếu xuất kho ID {$order['export_id']} (mã: {$order['export_code']}) đang bị treo ở trạng thái PENDING quá 7 ngày";
        }
        $can_fix = true;
    }
    
    // Phản hồi kết quả kiểm tra
    echo json_encode([
        'success' => true,
        'issues' => $issues,
        'can_fix' => $can_fix
    ]);
    
    // Ghi log hệ thống
    if (count($issues) > 0) {
        logSystem('WARNING', 'Phát hiện ' . count($issues) . ' vấn đề toàn vẹn dữ liệu', 'data_integrity');
    } else {
        logSystem('INFO', 'Kiểm tra toàn vẹn dữ liệu không phát hiện vấn đề', 'data_integrity');
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    logSystem('ERROR', 'Lỗi khi kiểm tra toàn vẹn dữ liệu: ' . $e->getMessage(), 'data_integrity');
}
?>