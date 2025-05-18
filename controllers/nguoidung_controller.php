<?php
function get_browser_name($user_agent) {
    $user_agent = strtolower($user_agent);
    if (strpos($user_agent, 'firefox') !== false) {
        return 'Firefox';
    } elseif (strpos($user_agent, 'chrome') !== false) {
        return 'Chrome';
    } elseif (strpos($user_agent, 'safari') !== false) {
        return 'Safari';
    } elseif (strpos($user_agent, 'edge') !== false || strpos($user_agent, 'edg/') !== false) {
        return 'Edge';
    } elseif (strpos($user_agent, 'msie') !== false || strpos($user_agent, 'trident') !== false) {
        return 'Internet Explorer';
    } else {
        return 'Không xác định';
    }
}
// Kiểm tra quyền truy cập
if (!isAdmin()) {
    echo '<div class="alert alert-danger">Bạn không có quyền truy cập trang này!</div>';
    exit;
}

// Xử lý thêm tài khoản mới
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $role_id = $_POST['role_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        // Kiểm tra tài khoản đã tồn tại
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Tên đăng nhập hoặc email đã tồn tại!";
        } else {
            // Thêm người dùng mới
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, phone, role_id) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $password, $email, $full_name, $phone, $role_id])) {
                // Ghi log hoạt động
                logUserActivity($_SESSION['user_id'], 'ADD_USER', "Thêm người dùng mới: $username");
                $success = "Thêm người dùng thành công!";
                
                // Gửi mail thông báo
                $otp = generateOTP();
                $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                
                $stmt = $pdo->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
                $stmt->execute([$otp, $expiry, $email]);
                
                $subject = "Đăng ký tài khoản thành công";
                $message = "
                <p>Xin chào $full_name,</p>
                <p>Tài khoản của bạn đã được tạo thành công trên hệ thống quản lý kho.</p>
                <p>Thông tin đăng nhập của bạn:</p>
                <p>Tên đăng nhập: $username</p>
                <p>Mã OTP xác thực: $otp</p>
                <p>Vui lòng sử dụng mã OTP này để kích hoạt tài khoản của bạn.</p>
                <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
                
                sendMail($email, $subject, $message);
            } else {
                $error = "Có lỗi xảy ra khi thêm người dùng!";
            }
        }
    } catch (PDOException $e) {
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// Xử lý cập nhật người dùng
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $role_id = $_POST['role_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, role_id = ?, is_active = ? WHERE user_id = ?");
        if ($stmt->execute([$full_name, $phone, $role_id, $is_active, $user_id])) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'UPDATE_USER', "Cập nhật người dùng ID: $user_id");
            $success = "Cập nhật người dùng thành công!";
        } else {
            $error = "Có lỗi xảy ra khi cập nhật người dùng!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// Xử lý xóa người dùng
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['delete_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND user_id != ?"); // Không cho phép xóa chính mình
        if ($stmt->execute([$user_id, $_SESSION['user_id']])) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'DELETE_USER', "Xóa người dùng ID: $user_id");
            $success = "Xóa người dùng thành công!";
        } else {
            $error = "Có lỗi xảy ra khi xóa người dùng!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// Xử lý reset mật khẩu
if (isset($_POST['reset_password'])) {
    $user_id = $_POST['reset_id'];
    $email = $_POST['reset_email'];
    
    try {
        // Tạo mật khẩu ngẫu nhiên
        $new_password = bin2hex(random_bytes(4)); // 8 ký tự
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ?, is_locked = 0, login_attempts = 0 WHERE user_id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'RESET_PASSWORD', "Reset mật khẩu người dùng ID: $user_id");
            
            // Gửi email mật khẩu mới
            $subject = "Mật khẩu của bạn đã được đặt lại";
            $message = "
            <p>Xin chào,</p>
            <p>Mật khẩu của bạn đã được đặt lại. Dưới đây là thông tin đăng nhập mới của bạn:</p>
            <p>Mật khẩu mới: <strong>$new_password</strong></p>
            <p>Vui lòng đổi mật khẩu sau khi đăng nhập.</p>
            <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
            
            if (sendMail($email, $subject, $message)) {
                $success = "Đặt lại mật khẩu thành công và đã gửi email thông báo!";
            } else {
                $warning = "Đặt lại mật khẩu thành công nhưng không gửi được email!";
            }
        } else {
            $error = "Có lỗi xảy ra khi đặt lại mật khẩu!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// Lấy danh sách vai trò
$stmt = $pdo->query("SELECT * FROM roles ORDER BY role_id");
$roles = $stmt->fetchAll();

// Xác định tab đang hiển thị
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

?>