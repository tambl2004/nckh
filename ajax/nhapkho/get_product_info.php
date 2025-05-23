<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Lấy thông tin sản phẩm
if (isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    
    // Lấy thông tin sản phẩm
    $stmt = $conn->prepare("SELECT p.*, 
                          (SELECT unit_price FROM import_order_details iod 
                           JOIN import_orders io ON iod.import_id = io.import_id 
                           WHERE iod.product_id = p.product_id AND io.status = 'COMPLETED' 
                           ORDER BY io.created_at DESC LIMIT 1) as last_import_price
                          FROM products p WHERE p.product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode(['success' => true, 'product' => $product, 'price' => $product['price'], 'last_import_price' => $product['last_import_price']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sản phẩm']);
}
?>