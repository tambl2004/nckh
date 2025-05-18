<?php
require_once 'mail_helper.php';

// Kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kiểm tra quyền admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

// Chuyển hướng nếu chưa đăng nhập
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Chuyển hướng nếu không phải admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php?error=permission");
        exit;
    }
}

// Ghi log hoạt động người dùng
function logUserActivity($user_id, $action_type, $description = '') {
    global $pdo;
    
    // Kiểm tra user_id hợp lệ
    if (empty($user_id)) {
        error_log("Lỗi ghi log: user_id không hợp lệ");
        return false;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action_type, description, ip_address, user_agent) 
                          VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $action_type, $description, $ip, $user_agent]);
}

// Tăng số lần đăng nhập sai
function incrementLoginAttempts($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Kiểm tra và khóa tài khoản nếu quá 5 lần
    $stmt = $pdo->prepare("SELECT login_attempts FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $attempts = $stmt->fetchColumn();
    
    if ($attempts >= 5) {
        $stmt = $pdo->prepare("UPDATE users SET is_locked = TRUE WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Gửi email thông báo khóa tài khoản
        $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $email = $stmt->fetchColumn();
        
        $subject = "Tài khoản của bạn đã bị khóa";
        $message = "
        <p>Xin chào,</p>
        <p>Tài khoản của bạn đã bị khóa do đăng nhập sai nhiều lần.</p>
        <p>Vui lòng sử dụng chức năng quên mật khẩu để mở khóa tài khoản.</p>
        <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
        
        sendMail($email, $subject, $message);
    }
}

// Reset số lần đăng nhập sai
function resetLoginAttempts($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, is_locked = FALSE WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

// Cập nhật thời gian đăng nhập cuối
function updateLastLogin($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

// Ghi nhật ký hệ thống
function logSystem($level, $message, $source = 'system') {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO system_logs (log_level, message, source) VALUES (?, ?, ?)");
    return $stmt->execute([$level, $message, $source]);
}

// Kiểm tra người dùng có quyền thực hiện hành động không
function hasPermission($permission) {
    // Nếu là admin, luôn có tất cả quyền
    if (isAdmin()) {
        return true;
    }
    
    // Lấy danh sách quyền của vai trò
    global $pdo;
    $role_id = $_SESSION['role_id'] ?? 0;
    
    // Trong thực tế, bạn sẽ cần bảng role_permissions để lưu quyền của từng vai trò
    $stmt = $pdo->prepare("SELECT permissions FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);
    $result = $stmt->fetch();
    
    if ($result && !empty($result['permissions'])) {
        $permissions = json_decode($result['permissions'], true);
        return in_array($permission, $permissions);
    }
    
    return false;
}
// Thêm vào cuối file auth.php
// Hàm ghi log cho mysqli
function logUserAction($userId, $actionType, $description) {
    global $conn;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "INSERT INTO user_logs (user_id, action_type, description, ip_address, user_agent) 
            VALUES ($userId, '$actionType', '$description', '$ipAddress', '$userAgent')";
    
    $conn->query($sql);
}

?>