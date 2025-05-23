<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Xử lý lưu nhà cung cấp
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = isset($_POST['supplierId']) ? (int)$_POST['supplierId'] : 0;
    $supplier_code = $conn->real_escape_string($_POST['supplierCode']);
    $supplier_name = $conn->real_escape_string($_POST['supplierName']);
    $contact_person = $conn->real_escape_string($_POST['contactPerson'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $tax_code = $conn->real_escape_string($_POST['taxCode'] ?? '');
    $address = $conn->real_escape_string($_POST['address'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Kiểm tra mã nhà cung cấp đã tồn tại chưa
    $check_code = $conn->prepare("SELECT supplier_id FROM suppliers WHERE supplier_code = ? AND supplier_id != ?");
    $check_code->bind_param("si", $supplier_code, $supplier_id);
    $check_code->execute();
    $check_code->store_result();
    
    if ($check_code->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Mã nhà cung cấp đã tồn tại']);
        exit;
    }
    
    if ($supplier_id > 0) {
        // Cập nhật nhà cung cấp
        $stmt = $conn->prepare("UPDATE suppliers SET 
                              supplier_code = ?, 
                              supplier_name = ?, 
                              contact_person = ?, 
                              phone = ?, 
                              email = ?, 
                              tax_code = ?, 
                              address = ? 
                              WHERE supplier_id = ?");
        $stmt->bind_param("sssssssi", $supplier_code, $supplier_name, $contact_person, $phone, $email, $tax_code, $address, $supplier_id);
        
        if ($stmt->execute()) {
            // Ghi log hoạt động
            logUserAction($user_id, 'UPDATE_SUPPLIER', "Cập nhật nhà cung cấp: $supplier_name");
            
            echo json_encode(['success' => true, 'supplier_id' => $supplier_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật nhà cung cấp']);
        }
    } else {
        // Thêm nhà cung cấp mới
        $stmt = $conn->prepare("INSERT INTO suppliers (supplier_code, supplier_name, contact_person, phone, email, tax_code, address) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $supplier_code, $supplier_name, $contact_person, $phone, $email, $tax_code, $address);
        
        if ($stmt->execute()) {
            $supplier_id = $conn->insert_id;
            
            // Ghi log hoạt động
            logUserAction($user_id, 'ADD_SUPPLIER', "Thêm nhà cung cấp mới: $supplier_name");
            
            echo json_encode(['success' => true, 'supplier_id' => $supplier_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể thêm nhà cung cấp']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
}
?>