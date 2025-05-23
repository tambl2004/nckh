<?php
// Lấy dữ liệu cài đặt bảo mật từ database (nếu có)
$security_settings = array();
try {
    $stmt = $pdo->query("SELECT * FROM system_settings WHERE setting_group = 'security'");
    while ($row = $stmt->fetch()) {
        $security_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Khởi tạo giá trị mặc định nếu chưa có bảng cài đặt
    $security_settings = [
        'enable_2fa' => '1',
        'max_login_attempts' => '5',
        'otp_expiry_minutes' => '15',
        'password_min_length' => '8',
        'password_require_special' => '1',
        'password_require_numbers' => '1',
        'password_require_uppercase' => '1',
        'session_timeout_minutes' => '30',
        'log_user_actions' => '1',
        'log_system_actions' => '1',
    ];
}

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_security'])) {
    try {
        // Tạo bảng nếu chưa tồn tại
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_group VARCHAR(50) NOT NULL,
            setting_key VARCHAR(50) NOT NULL,
            setting_value TEXT,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (setting_group, setting_key)
        )");
        
        // Cập nhật cài đặt bảo mật
        $settings = [
            'enable_2fa' => isset($_POST['enable_2fa']) ? '1' : '0',
            'max_login_attempts' => intval($_POST['max_login_attempts']),
            'otp_expiry_minutes' => intval($_POST['otp_expiry_minutes']),
            'password_min_length' => intval($_POST['password_min_length']),
            'password_require_special' => isset($_POST['password_require_special']) ? '1' : '0',
            'password_require_numbers' => isset($_POST['password_require_numbers']) ? '1' : '0',
            'password_require_uppercase' => isset($_POST['password_require_uppercase']) ? '1' : '0',
            'session_timeout_minutes' => intval($_POST['session_timeout_minutes']),
            'log_user_actions' => isset($_POST['log_user_actions']) ? '1' : '0',
            'log_system_actions' => isset($_POST['log_system_actions']) ? '1' : '0',
        ];

        // Kiểm tra và xác thực dữ liệu đầu vào
        $errors = [];
        
        if ($settings['max_login_attempts'] < 1 || $settings['max_login_attempts'] > 10) {
            $errors[] = "Số lần đăng nhập sai phải từ 1 đến 10";
        }
        
        if ($settings['otp_expiry_minutes'] < 5 || $settings['otp_expiry_minutes'] > 60) {
            $errors[] = "Thời gian hết hạn OTP phải từ 5 đến 60 phút";
        }
        
        if ($settings['password_min_length'] < 6 || $settings['password_min_length'] > 20) {
            $errors[] = "Độ dài mật khẩu tối thiểu phải từ 6 đến 20 ký tự";
        }
        
        if ($settings['session_timeout_minutes'] < 5 || $settings['session_timeout_minutes'] > 120) {
            $errors[] = "Thời gian timeout phiên phải từ 5 đến 120 phút";
        }
        
        if (empty($errors)) {
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_group, setting_key, setting_value) 
                                      VALUES ('security', ?, ?) 
                                      ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'UPDATE_SECURITY', 'Cập nhật cài đặt bảo mật');
            logSystem('INFO', 'Cài đặt bảo mật đã được cập nhật bởi ' . $_SESSION['username'], 'security_settings');
            
            // Cập nhật lại các giá trị
            $security_settings = $settings;
            
            $success = "Cài đặt bảo mật đã được cập nhật thành công!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi cập nhật cài đặt: " . $e->getMessage();
    }
}

// Xử lý reset mật khẩu tất cả người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_all_passwords']) && isAdmin()) {
    try {
        // Lấy danh sách email người dùng
        $stmt = $pdo->query("SELECT user_id, email, full_name FROM users WHERE is_active = 1");
        $users = $stmt->fetchAll();
        
        $reset_count = 0;
        foreach ($users as $user) {
            // Tạo OTP và thời gian hết hạn
            $otp = generateOTP();
            $expiry = date('Y-m-d H:i:s', strtotime("+{$security_settings['otp_expiry_minutes']} minutes"));
            
            // Cập nhật OTP vào database
            $stmt = $pdo->prepare("UPDATE users SET otp = ?, otp_expiry = ?, is_locked = TRUE WHERE user_id = ?");
            $stmt->execute([$otp, $expiry, $user['user_id']]);
            
            // Gửi email thông báo reset mật khẩu
            $subject = "Yêu cầu đặt lại mật khẩu";
            $message = "
            <p>Xin chào {$user['full_name']},</p>
            <p>Quản trị viên đã yêu cầu đặt lại mật khẩu cho tất cả tài khoản trong hệ thống.</p>
            <p>Mã OTP của bạn là: <strong>{$otp}</strong></p>
            <p>Mã này sẽ hết hạn sau {$security_settings['otp_expiry_minutes']} phút.</p>
            <p>Vui lòng đăng nhập và sử dụng chức năng Quên mật khẩu để đặt lại mật khẩu của bạn.</p>
            <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
            
            if (sendMail($user['email'], $subject, $message)) {
                $reset_count++;
            }
        }
        
        // Ghi log hệ thống
        logSystem('WARNING', "Yêu cầu reset mật khẩu tất cả người dùng bởi {$_SESSION['username']}", 'security');
        
        $success = "Đã gửi yêu cầu đặt lại mật khẩu cho $reset_count người dùng!";
    } catch (PDOException $e) {
        $error = "Lỗi khi reset mật khẩu: " . $e->getMessage();
    }
}

// Xử lý xuất log hệ thống
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_logs']) && isAdmin()) {
    $log_type = $_POST['log_type'];
    $days = intval($_POST['days']);
    
    try {
        if ($log_type == 'user') {
            $stmt = $pdo->prepare("
                SELECT l.log_id, u.username, l.action_type, l.description, l.ip_address, l.created_at 
                FROM user_logs l
                JOIN users u ON l.user_id = u.user_id
                WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY l.created_at DESC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT log_id, log_level, message, source, created_at 
                FROM system_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY created_at DESC
            ");
        }
        
        $stmt->execute([$days]);
        $logs = $stmt->fetchAll();
        
        if (count($logs) > 0) {
            // Tạo file CSV
            $filename = ($log_type == 'user' ? 'user_logs_' : 'system_logs_') . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            
            $output = fopen('php://output', 'w');
            
            // Tiêu đề cột
            if ($log_type == 'user') {
                fputcsv($output, ['ID', 'Người dùng', 'Hành động', 'Mô tả', 'Địa chỉ IP', 'Thời gian']);
            } else {
                fputcsv($output, ['ID', 'Mức độ', 'Thông điệp', 'Nguồn', 'Thời gian']);
            }
            
            // Dữ liệu
            foreach ($logs as $log) {
                fputcsv($output, $log);
            }
            
            fclose($output);
            exit;
        } else {
            $error = "Không có dữ liệu nhật ký trong khoảng thời gian đã chọn.";
        }
    } catch (PDOException $e) {
        $error = "Lỗi khi xuất dữ liệu nhật ký: " . $e->getMessage();
    }
}

// Xử lý khóa tất cả tài khoản người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lock_all_accounts']) && isAdmin()) {
    try {
        // Khóa tất cả tài khoản trừ admin
        $stmt = $pdo->prepare("
            UPDATE users 
            SET is_locked = TRUE 
            WHERE role_id != 1 AND is_active = 1
        ");
        $stmt->execute();
        $locked_count = $stmt->rowCount();
        
        // Ghi log hệ thống
        logSystem('WARNING', "Đã khóa $locked_count tài khoản người dùng bởi {$_SESSION['username']}", 'security');
        
        $success = "Đã khóa $locked_count tài khoản người dùng thành công!";
    } catch (PDOException $e) {
        $error = "Lỗi khi khóa tài khoản: " . $e->getMessage();
    }
}

// Xử lý xóa nhật ký người dùng cũ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clean_user_logs']) && isAdmin()) {
    $days = intval($_POST['user_logs_days']);
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM user_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days]);
        $deleted_count = $stmt->rowCount();
        
        // Ghi log hệ thống
        logSystem('INFO', "Đã xóa $deleted_count bản ghi nhật ký người dùng cũ hơn $days ngày", 'maintenance');
        
        $success = "Đã xóa $deleted_count bản ghi nhật ký người dùng thành công!";
    } catch (PDOException $e) {
        $error = "Lỗi khi xóa nhật ký: " . $e->getMessage();
    }
}

// Xử lý xóa nhật ký hệ thống cũ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clean_system_logs']) && isAdmin()) {
    $days = intval($_POST['system_logs_days']);
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM system_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days]);
        $deleted_count = $stmt->rowCount();
        
        // Ghi log hệ thống
        logSystem('INFO', "Đã xóa $deleted_count bản ghi nhật ký hệ thống cũ hơn $days ngày", 'maintenance');
        
        $success = "Đã xóa $deleted_count bản ghi nhật ký hệ thống thành công!";
    } catch (PDOException $e) {
        $error = "Lỗi khi xóa nhật ký: " . $e->getMessage();
    }
}
?>

<div class="function-container">
    <h1 class="page-title">Cài đặt an toàn và bảo mật</h1>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Tab navigation -->
    <ul class="nav nav-tabs mb-3" id="securityTabs">
        <li class="nav-item">
            <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general">
                <i class="fas fa-shield-alt me-2"></i>Cài đặt chung
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="password-tab" data-bs-toggle="tab" href="#password">
                <i class="fas fa-key me-2"></i>Chính sách mật khẩu
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="logs-tab" data-bs-toggle="tab" href="#logs">
                <i class="fas fa-history me-2"></i>Nhật ký hệ thống
            </a>
        </li>
        <?php if (isAdmin()): ?>
        <li class="nav-item">
            <a class="nav-link" id="advanced-tab" data-bs-toggle="tab" href="#advanced">
                <i class="fas fa-wrench me-2"></i>Tùy chọn nâng cao
            </a>
        </li>
        <?php endif; ?>
    </ul>
    
    <!-- Tab content -->
    <div class="tab-content">
        <!-- Cài đặt chung -->
        <div class="tab-pane fade show active" id="general">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="settings-card mb-4">
                            <div class="settings-card-header">
                                <h5 class="settings-card-title">
                                    <i class="fas fa-user-shield"></i> Xác thực đa yếu tố
                                </h5>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enable_2fa" name="enable_2fa" <?php echo ($security_settings['enable_2fa'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_2fa">Bật xác thực đa yếu tố (OTP qua email)</label>
                                </div>
                                <div class="form-group">
                                    <label for="otp_expiry_minutes">Thời gian hết hạn mã OTP (phút)</label>
                                    <input type="number" class="form-control" id="otp_expiry_minutes" name="otp_expiry_minutes" 
                                           value="<?php echo htmlspecialchars($security_settings['otp_expiry_minutes']); ?>" min="5" max="60">
                                    <div class="form-text">Đề xuất: 15-30 phút</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="settings-card mb-4">
                            <div class="settings-card-header">
                                <h5 class="settings-card-title">
                                    <i class="fas fa-user-lock"></i> Kiểm soát truy cập
                                </h5>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-group mb-3">
                                    <label for="max_login_attempts">Số lần đăng nhập sai tối đa</label>
                                    <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                           value="<?php echo htmlspecialchars($security_settings['max_login_attempts']); ?>" min="1" max="10">
                                    <div class="form-text">Đề xuất: 5 lần</div>
                                </div>
                                <div class="form-group">
                                    <label for="session_timeout_minutes">Thời gian timeout phiên làm việc (phút)</label>
                                    <input type="number" class="form-control" id="session_timeout_minutes" name="session_timeout_minutes" 
                                           value="<?php echo htmlspecialchars($security_settings['session_timeout_minutes']); ?>" min="5" max="120">
                                    <div class="form-text">Đề xuất: 30 phút</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="settings-card mb-4">
                            <div class="settings-card-header">
                                <h5 class="settings-card-title">
                                    <i class="fas fa-clipboard-list"></i> Ghi nhật ký
                                </h5>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="log_user_actions" name="log_user_actions" 
                                           <?php echo ($security_settings['log_user_actions'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="log_user_actions">Ghi nhật ký hoạt động người dùng</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="log_system_actions" name="log_system_actions" 
                                           <?php echo ($security_settings['log_system_actions'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="log_system_actions">Ghi nhật ký hoạt động hệ thống</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" name="update_security" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Lưu cài đặt bảo mật
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Chính sách mật khẩu -->
        <div class="tab-pane fade" id="password">
            <form method="POST" action="">
                <div class="settings-card mb-4">
                    <div class="settings-card-header">
                        <h5 class="settings-card-title">
                            <i class="fas fa-key"></i> Yêu cầu độ bảo mật mật khẩu
                        </h5>
                    </div>
                    <div class="settings-card-body">
                        <div class="form-group mb-3">
                            <label for="password_min_length">Độ dài tối thiểu của mật khẩu</label>
                            <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                   value="<?php echo htmlspecialchars($security_settings['password_min_length']); ?>" min="6" max="20">
                            <div class="form-text">Đề xuất: 8 ký tự</div>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="password_require_uppercase" name="password_require_uppercase" 
                                   <?php echo ($security_settings['password_require_uppercase'] == '1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="password_require_uppercase">Yêu cầu ít nhất một ký tự viết hoa</label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="password_require_numbers" name="password_require_numbers" 
                                   <?php echo ($security_settings['password_require_numbers'] == '1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="password_require_numbers">Yêu cầu ít nhất một ký tự số</label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="password_require_special" name="password_require_special" 
                                   <?php echo ($security_settings['password_require_special'] == '1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="password_require_special">Yêu cầu ít nhất một ký tự đặc biệt</label>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" name="update_security" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Lưu chính sách mật khẩu
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Nhật ký hệ thống -->
        <div class="tab-pane fade" id="logs">
            <div class="settings-card mb-4">
                <div class="settings-card-header">
                    <h5 class="settings-card-title">
                        <i class="fas fa-file-export"></i> Xuất dữ liệu nhật ký
                    </h5>
                </div>
                <div class="settings-card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="log_type">Loại nhật ký</label>
                                    <select class="form-select" id="log_type" name="log_type">
                                        <option value="user">Nhật ký người dùng</option>
                                        <option value="system">Nhật ký hệ thống</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="days">Dữ liệu trong khoảng</label>
                                    <select class="form-select" id="days" name="days">
                                        <option value="7">7 ngày trước</option>
                                        <option value="30">30 ngày trước</option>
                                        <option value="90">90 ngày trước</option>
                                        <option value="365">1 năm trước</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="export_logs" class="btn btn-success">
                                <i class="fas fa-download me-2"></i>Xuất dữ liệu (CSV)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="settings-card">
                <div class="settings-card-header">
                    <h5 class="settings-card-title">
                        <i class="fas fa-trash-alt"></i> Dọn dẹp nhật ký
                    </h5>
                </div>
                <div class="settings-card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Cảnh báo: Hành động này sẽ xóa vĩnh viễn dữ liệu nhật ký cũ. Đảm bảo rằng bạn đã sao lưu dữ liệu quan trọng trước khi thực hiện.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cleanUserLogsModal">
                                    <i class="fas fa-user-clock me-2"></i>Xóa nhật ký người dùng cũ
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cleanSystemLogsModal">
                                    <i class="fas fa-server me-2"></i>Xóa nhật ký hệ thống cũ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tùy chọn nâng cao (chỉ admin) -->
        <?php if (isAdmin()): ?>
        <div class="tab-pane fade" id="advanced">
            <div class="settings-card mb-4">
                <div class="settings-card-header">
                    <h5 class="settings-card-title">
                        <i class="fas fa-user-shield"></i> Hành động bảo mật
                    </h5>
                </div>
                <div class="settings-card-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Cảnh báo: Các hành động trong phần này có tác động lớn đến hệ thống và người dùng. Hãy thận trọng!
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetPasswordsModal">
                                    <i class="fas fa-key me-2"></i>Yêu cầu đặt lại mật khẩu tất cả tài khoản
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#lockAccountsModal">
                                    <i class="fas fa-user-lock me-2"></i>Khóa tất cả tài khoản người dùng
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="settings-card">
                <div class="settings-card-header">
                    <h5 class="settings-card-title">
                        <i class="fas fa-database"></i> Kiểm tra toàn vẹn dữ liệu
                    </h5>
                </div>
                <div class="settings-card-body">
                    <p>Kiểm tra và khắc phục lỗi toàn vẹn dữ liệu trong cơ sở dữ liệu.</p>
                    <div class="d-grid">
                        <button type="button" class="btn btn-primary" id="checkIntegrityBtn">
                            <i class="fas fa-check-circle me-2"></i>Kiểm tra toàn vẹn dữ liệu
                        </button>
                    </div>
                    <div id="integrityResults" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal xác nhận reset mật khẩu tất cả tài khoản -->
<div class="modal fade" id="resetPasswordsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận đặt lại mật khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Cảnh báo: Hành động này sẽ yêu cầu tất cả người dùng đặt lại mật khẩu của họ. Mỗi người dùng sẽ nhận được email với mã OTP để đặt lại mật khẩu.
                </div>
                <p>Bạn có chắc chắn muốn tiếp tục không?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="">
                    <button type="submit" name="reset_all_passwords" class="btn btn-danger">Xác nhận đặt lại mật khẩu</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận khóa tất cả tài khoản -->
<div class="modal fade" id="lockAccountsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận khóa tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Cảnh báo: Hành động này sẽ khóa tất cả tài khoản người dùng (trừ tài khoản admin). Người dùng sẽ không thể đăng nhập cho đến khi bạn mở khóa thủ công.
                </div>
                <p>Bạn có chắc chắn muốn tiếp tục không?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="">
                    <button type="submit" name="lock_all_accounts" class="btn btn-danger">Xác nhận khóa tài khoản</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa nhật ký người dùng -->
<div class="modal fade" id="cleanUserLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa nhật ký</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="cleanUserLogsForm">
                    <div class="form-group mb-3">
                        <label for="user_logs_days">Xóa dữ liệu cũ hơn</label>
                        <select class="form-select" id="user_logs_days" name="user_logs_days">
                            <option value="90">90 ngày</option>
                            <option value="180">180 ngày</option>
                            <option value="365">1 năm</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Cảnh báo: Hành động này sẽ xóa vĩnh viễn dữ liệu nhật ký người dùng cũ hơn khoảng thời gian đã chọn.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="submit" form="cleanUserLogsForm" name="clean_user_logs" class="btn btn-danger">Xác nhận xóa</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa nhật ký hệ thống -->
<div class="modal fade" id="cleanSystemLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa nhật ký</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="cleanSystemLogsForm">
                    <div class="form-group mb-3">
                        <label for="system_logs_days">Xóa dữ liệu cũ hơn</label>
                        <select class="form-select" id="system_logs_days" name="system_logs_days">
                            <option value="90">90 ngày</option>
                            <option value="180">180 ngày</option>
                            <option value="365">1 năm</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Cảnh báo: Hành động này sẽ xóa vĩnh viễn dữ liệu nhật ký hệ thống cũ hơn khoảng thời gian đã chọn.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="submit" form="cleanSystemLogsForm" name="clean_system_logs" class="btn btn-danger">Xác nhận xóa</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra toàn vẹn dữ liệu khi click button
    const checkIntegrityBtn = document.getElementById('checkIntegrityBtn');
    const integrityResults = document.getElementById('integrityResults');
    
    if (checkIntegrityBtn) {
        checkIntegrityBtn.addEventListener('click', function() {
            // Hiển thị trạng thái đang kiểm tra
            integrityResults.style.display = 'block';
            integrityResults.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Đang kiểm tra toàn vẹn dữ liệu...</div>';
            
            // Gửi AJAX request để kiểm tra
            fetch('ajax/dulieu/kiemTraToanVenDuLieu.php')
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    
                    if (data.success) {
                        if (data.issues.length > 0) {
                            html = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Phát hiện vấn đề toàn vẹn dữ liệu:</div>';
                            html += '<ul class="list-group">';
                            data.issues.forEach(issue => {
                                html += `<li class="list-group-item">${issue}</li>`;
                            });
                            html += '</ul>';
                            
                            if (data.can_fix) {
                                html += `
                                <div class="d-grid mt-3">
                                    <button type="button" id="fixIntegrityBtn" class="btn btn-warning">
                                        <i class="fas fa-wrench me-2"></i>Sửa các vấn đề đã phát hiện
                                    </button>
                                </div>`;
                            }
                        } else {
                            html = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Không phát hiện vấn đề toàn vẹn dữ liệu.</div>';
                        }
                    } else {
                        html = `<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Lỗi khi kiểm tra: ${data.message}</div>`;
                    }
                    
                    integrityResults.innerHTML = html;
                    
                    // Thêm event listener cho nút sửa lỗi
                    const fixBtn = document.getElementById('fixIntegrityBtn');
                    if (fixBtn) {
                        fixBtn.addEventListener('click', function() {
                            // Gửi request để sửa lỗi
                            integrityResults.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Đang sửa lỗi toàn vẹn dữ liệu...</div>';
                            
                            fetch('ajax/dulieu/suaToanVenDuLieu.php')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        integrityResults.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Đã sửa lỗi toàn vẹn dữ liệu thành công.</div>';
                                        
                                        // Ghi log hệ thống
                                        console.log('Đã sửa lỗi toàn vẹn dữ liệu');
                                    } else {
                                        integrityResults.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Lỗi khi sửa: ${data.message}</div>`;
                                    }
                                })
                                .catch(error => {
                                    integrityResults.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Lỗi kết nối: ${error}</div>`;
                                });
                        });
                    }
                })
                .catch(error => {
                    integrityResults.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Lỗi kết nối: ${error}</div>`;
                });
        });
    }
    
    // Xử lý form khóa tài khoản tất cả người dùng
    const lockAccountsForm = document.getElementById('lockAccountsForm');
    if (lockAccountsForm) {
        lockAccountsForm.addEventListener('submit', function(e) {
            if (!confirm('Bạn có chắc chắn muốn khóa tất cả tài khoản người dùng?')) {
                e.preventDefault();
            }
        });
    }
    
    // Xử lý form dọn dẹp nhật ký
    const cleanUserLogsForm = document.getElementById('cleanUserLogsForm');
    const cleanSystemLogsForm = document.getElementById('cleanSystemLogsForm');
    
    if (cleanUserLogsForm) {
        cleanUserLogsForm.addEventListener('submit', function(e) {
            const days = document.getElementById('user_logs_days').value;
            if (!confirm(`Bạn có chắc chắn muốn xóa nhật ký người dùng cũ hơn ${days} ngày?`)) {
                e.preventDefault();
            }
        });
    }
    
    if (cleanSystemLogsForm) {
        cleanSystemLogsForm.addEventListener('submit', function(e) {
            const days = document.getElementById('system_logs_days').value;
            if (!confirm(`Bạn có chắc chắn muốn xóa nhật ký hệ thống cũ hơn ${days} ngày?`)) {
                e.preventDefault();
            }
        });
    }
});
</script>

