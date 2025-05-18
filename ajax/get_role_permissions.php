<?php
session_start();
require_once "../config/connect.php";
require_once "../inc/auth.php";

// Kiểm tra quyền truy cập
if (!isAdmin()) {
    echo json_encode(["success" => false, "message" => "Bạn không có quyền thực hiện thao tác này"]);
    exit;
}

if (isset($_GET["role_id"])) {
    $role_id = intval($_GET["role_id"]);
    
    try {
        // Trong thực tế, bạn sẽ truy vấn bảng quyền dựa trên role_id
        // Đây là dữ liệu mẫu, bạn cần thay thế bằng truy vấn thực tế
        $permissions = [];
        
        if ($role_id == 1) { // Admin
            $permissions = [
                "view_products", "add_products", "edit_products", "delete_products",
                "view_inventory", "manage_import", "manage_export", "inventory_check",
                "view_users", "add_users", "edit_users", "delete_users",
                "view_reports", "view_logs", "view_system_logs", "system_settings"
            ];
        } elseif ($role_id == 2) { // Nhân viên
            $permissions = [
                "view_products", "add_products", "edit_products",
                "view_inventory", "manage_import", "manage_export", "inventory_check",
                "view_users",
                "view_reports"
            ];
        } elseif ($role_id == 3) { // Người dùng
            $permissions = [
                "view_products",
                "view_inventory"
            ];
        }
        
        echo json_encode([
            "success" => true,
            "permissions" => $permissions
        ]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Lỗi hệ thống: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Thiếu tham số role_id"]);
}
?>