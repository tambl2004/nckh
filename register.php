<?php
include 'config/connect.php';
include 'inc/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_otp'])) {
        // Xử lý xác thực OTP
        $email = $_POST['email'];
        $otp = $_POST['otp'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND otp = ? AND is_active = 0");
        $stmt->execute([$email, $otp]);
        $user = $stmt->fetch();
        
        if ($user && isValidOTP($user['otp_expiry'])) {
            // Kích hoạt tài khoản
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1, otp = NULL, otp_expiry = NULL WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            // Ghi log
            logUserActivity($user['user_id'], 'REGISTER', 'Kích hoạt tài khoản thành công');
            
            $success = "Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.";
        } else {
            $error = "Mã OTP không hợp lệ hoặc đã hết hạn.";
        }
    } elseif (isset($_POST['register'])) {
        // Xử lý đăng ký
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        // Kiểm tra dữ liệu
        if ($password !== $confirm_password) {
            $error = "Mật khẩu không khớp.";
        } else {
            // Kiểm tra username và email có tồn tại không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Tên đăng nhập hoặc email đã tồn tại.";
            } else {
                // Tạo OTP và thời gian hết hạn (30 phút)
                $otp = generateOTP();
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    // Thêm người dùng mới (chưa kích hoạt)
                    $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role_id, 
                        is_active, otp, otp_expiry, created_at) VALUES (?, ?, ?, ?, 2, 0, ?, ?, NOW())");
                    $stmt->execute([$username, $full_name, $email, $hashed_password, $otp, $otp_expiry]);
                    
                    // Gửi email xác thực
                    $subject = "Xác thực đăng ký tài khoản";
                    $message = "
                    <p>Xin chào {$full_name},</p>
                    <p>Cảm ơn bạn đã đăng ký tài khoản tại Hệ thống Quản lý Kho.</p>
                    <p>Mã OTP của bạn là: <strong>{$otp}</strong></p>
                    <p>Mã này sẽ hết hạn sau 30 phút.</p>
                    <p>Vui lòng nhập mã này để hoàn tất đăng ký.</p>
                    <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
                    
                    if (sendMail($email, $subject, $message)) {
                        // Hiển thị form xác thực OTP
                        $_SESSION['verify_email'] = $email;
                        $success = "Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra và nhập mã xác thực.";
                    } else {
                        $error = "Không thể gửi email xác thực. Vui lòng thử lại sau.";
                    }
                } catch (PDOException $e) {
                    $error = "Lỗi: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Quản Lý Kho Hàng</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            border: none;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            border-radius: 20px;
            padding: 50px;
            width: 100%;
            max-width: 600px;
            background: #ffffff;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 12px;
            font-size: 1.2rem;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px;
            font-size: 1.1rem;
        }
        .form-label {
            font-size: 1.2rem;
        }
        a {
            text-decoration: none;
            color: #007bff;
            font-size: 1.1rem;
        }
        a:hover {
            text-decoration: underline;
        }
        h3 {
            font-size: 2rem;
            font-weight: bold;
        }
        .error {
            color: red;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        .success {
            color: green;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-center mb-5">Đăng Ký</h3>
                        
                        <?php if ($error): ?>
                            <div class="error text-center"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="success text-center"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['verify_email'])): ?>
                            <!-- Form xác thực OTP -->
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="otp" class="form-label">Mã xác thực (OTP)</label>
                                    <input type="text" class="form-control" id="otp" name="otp" placeholder="Nhập mã OTP được gửi đến email của bạn" required>
                                    <input type="hidden" name="email" value="<?php echo $_SESSION['verify_email']; ?>">
                                </div>
                                <button type="submit" name="verify_otp" class="btn btn-primary w-100">Xác thực</button>
                            </form>
                            <div class="mt-4 text-center">
                                <a href="register.php">Quay lại đăng ký</a>
                            </div>
                        <?php else: ?>
                            <!-- Form đăng ký -->
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="username" class="form-label">Tên đăng nhập</label>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                                </div>
                                <div class="mb-4">
                                    <label for="full_name" class="form-label">Họ và tên</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Nhập họ và tên" required>
                                </div>
                                <div class="mb-4">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email" required>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Mật khẩu</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                                </div>
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
                                </div>
                                <button type="submit" name="register" class="btn btn-primary w-100">Đăng Ký</button>
                            </form>
                            <div class="mt-4 text-center">
                                <a href="login.php">Đã có tài khoản? Đăng nhập</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>