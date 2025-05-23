<?php
/**
 * Model quản lý người dùng
 * Xử lý tương tác với CSDL liên quan đến người dùng, vai trò, quyền hạn
 */

class NguoiDungModel {
    private $pdo;

    /**
     * Khởi tạo model với kết nối PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Lấy danh sách người dùng
     */
    public function layDanhSachNguoiDung() {
        $query = "SELECT u.*, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                ORDER BY u.user_id DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thông tin người dùng theo ID
     */
    public function layThongTinNguoiDung($userId) {
        $query = "SELECT u.*, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.user_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm người dùng mới
     */
    public function themNguoiDung($username, $password, $email, $fullName, $phone, $roleId) {
        // Kiểm tra tài khoản đã tồn tại
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập hoặc email đã tồn tại!'
            ];
        }

        // Mã hóa mật khẩu
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Thêm người dùng mới
        $query = "INSERT INTO users (username, password, email, full_name, phone, role_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($query);
        
        if ($stmt->execute([$username, $hashedPassword, $email, $fullName, $phone, $roleId])) {
            return [
                'success' => true,
                'message' => 'Thêm người dùng thành công!',
                'user_id' => $this->pdo->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm người dùng!'
            ];
        }
    }

    /**
     * Cập nhật thông tin người dùng
     */
    public function capNhatNguoiDung($userId, $fullName, $phone, $roleId, $isActive) {
        $query = "UPDATE users 
                SET full_name = ?, phone = ?, role_id = ?, is_active = ? 
                WHERE user_id = ?";
        $stmt = $this->pdo->prepare($query);
        
        if ($stmt->execute([$fullName, $phone, $roleId, $isActive, $userId])) {
            return [
                'success' => true,
                'message' => 'Cập nhật người dùng thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật người dùng!'
            ];
        }
    }

    /**
     * Xóa người dùng
     */
    public function xoaNguoiDung($userId, $currentUserId) {
        // Không cho phép xóa chính mình
        if ($userId == $currentUserId) {
            return [
                'success' => false,
                'message' => 'Không thể xóa tài khoản đang đăng nhập!'
            ];
        }

        $query = "DELETE FROM users WHERE user_id = ?";
        $stmt = $this->pdo->prepare($query);
        
        if ($stmt->execute([$userId])) {
            return [
                'success' => true,
                'message' => 'Xóa người dùng thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa người dùng!'
            ];
        }
    }

    /**
     * Đặt lại mật khẩu người dùng
     */
    public function datLaiMatKhau($userId) {
        // Tạo mật khẩu ngẫu nhiên
        $newPassword = bin2hex(random_bytes(4)); // 8 ký tự
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $query = "UPDATE users 
                SET password = ?, is_locked = 0, login_attempts = 0 
                WHERE user_id = ?";
        $stmt = $this->pdo->prepare($query);
        
        if ($stmt->execute([$hashedPassword, $userId])) {
            return [
                'success' => true,
                'message' => 'Đặt lại mật khẩu thành công!',
                'new_password' => $newPassword
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt lại mật khẩu!'
            ];
        }
    }

    /**
     * Tạo OTP và cập nhật vào DB
     */
    public function taoOTP($email) {
        $otp = rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        $query = "UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?";
        $stmt = $this->pdo->prepare($query);
        
        if ($stmt->execute([$otp, $expiry, $email])) {
            return [
                'success' => true,
                'otp' => $otp
            ];
        } else {
            return [
                'success' => false
            ];
        }
    }

    /**
     * Lấy danh sách vai trò
     */
    public function layDanhSachVaiTro() {
        $query = "SELECT * FROM roles ORDER BY role_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm vai trò mới
     */
    public function themVaiTro($roleName, $description) {
        $query = "INSERT INTO roles (role_name, description) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($query);
        
        if ($stmt->execute([$roleName, $description])) {
            return [
                'success' => true,
                'message' => 'Thêm vai trò thành công!',
                'role_id' => $this->pdo->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm vai trò!'
            ];
        }
    }

    /**
     * Cập nhật vai trò
     */
    public function capNhatVaiTro($roleId, $roleName, $description) {
        $query = "UPDATE roles SET role_name = ?, description = ? WHERE role_id = ?";
        $stmt = $this->pdo->prepare($query);
        
        if ($stmt->execute([$roleName, $description, $roleId])) {
            return [
                'success' => true,
                'message' => 'Cập nhật vai trò thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật vai trò!'
            ];
        }
    }

    /**
     * Xóa vai trò
     */
    public function xoaVaiTro($roleId) {
        // Kiểm tra vai trò có phải mặc định không
        if ($roleId <= 3) {
            return [
                'success' => false,
                'message' => 'Không thể xóa vai trò mặc định của hệ thống!'
            ];
        }

        try {
            $this->pdo->beginTransaction();
            
            // Chuyển người dùng có vai trò này về vai trò "Người dùng" (id 3)
            $stmt = $this->pdo->prepare("UPDATE users SET role_id = 3 WHERE role_id = ?");
            $stmt->execute([$roleId]);
            
            // Xóa phân quyền của vai trò
            $stmt = $this->pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            
            // Xóa vai trò
            $stmt = $this->pdo->prepare("DELETE FROM roles WHERE role_id = ?");
            $stmt->execute([$roleId]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Xóa vai trò thành công!'
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy quyền của vai trò
     */
    public function layQuyenVaiTro($roleId) {
        $query = "SELECT permissions FROM role_permissions WHERE role_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$roleId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['permissions'])) {
            return json_decode($result['permissions'], true);
        }
        
        return [];
    }

    /**
     * Cập nhật quyền cho vai trò
     */
    public function capNhatQuyenVaiTro($roleId, $permissions) {
        $permissionsJson = json_encode($permissions);
        
        try {
            // Kiểm tra nếu đã có quyền cho vai trò này
            $stmt = $this->pdo->prepare("SELECT id FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            $existingId = $stmt->fetchColumn();
            
            if ($existingId) {
                // Cập nhật quyền
                $query = "UPDATE role_permissions SET permissions = ? WHERE role_id = ?";
                $stmt = $this->pdo->prepare($query);
                $result = $stmt->execute([$permissionsJson, $roleId]);
            } else {
                // Thêm mới quyền
                $query = "INSERT INTO role_permissions (role_id, permissions) VALUES (?, ?)";
                $stmt = $this->pdo->prepare($query);
                $result = $stmt->execute([$roleId, $permissionsJson]);
            }
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Cập nhật quyền thành công!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật quyền!'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy nhật ký hoạt động người dùng
     */
    public function layNhatKyNguoiDung($userId = null, $actionType = null, $date = null, $limit = 100) {
        $query = "SELECT l.*, u.username 
                FROM user_logs l 
                JOIN users u ON l.user_id = u.user_id 
                WHERE 1=1";
        $params = [];
        
        if ($userId) {
            $query .= " AND l.user_id = ?";
            $params[] = $userId;
        }
        
        if ($actionType) {
            $query .= " AND l.action_type = ?";
            $params[] = $actionType;
        }
        
        if ($date) {
            $query .= " AND DATE(l.created_at) = ?";
            $params[] = $date;
        }
        
        $query .= " ORDER BY l.created_at DESC LIMIT " . intval($limit);
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy nhật ký hệ thống
     */
    public function layNhatKyHeThong($level = null, $source = null, $date = null, $limit = 100) {
        $query = "SELECT * FROM system_logs WHERE 1=1";
        $params = [];
        
        if ($level) {
            $query .= " AND log_level = ?";
            $params[] = $level;
        }
        
        if ($source) {
            $query .= " AND source LIKE ?";
            $params[] = "%$source%";
        }
        
        if ($date) {
            $query .= " AND DATE(created_at) = ?";
            $params[] = $date;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT " . intval($limit);
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách người dùng đã có nhật ký
     */
    public function layDanhSachNguoiDungCoLog() {
        $query = "SELECT DISTINCT u.user_id, u.username 
                FROM users u 
                JOIN user_logs l ON u.user_id = l.user_id 
                ORDER BY u.username";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách loại hành động
     */
    public function layDanhSachLoaiHanhDong() {
        $query = "SELECT DISTINCT action_type FROM user_logs ORDER BY action_type";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Khóa tài khoản người dùng
     */
    public function khoaTaiKhoan($userId, $currentUserId) {
        // Không cho phép khóa chính mình
        if ($userId == $currentUserId) {
            return [
                'success' => false,
                'message' => 'Không thể khóa tài khoản đang đăng nhập!'
            ];
        }

        // Kiểm tra vai trò của người dùng bị khóa (không khóa được Admin)
        $stmt = $this->pdo->prepare("SELECT role_id FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $roleId = $stmt->fetchColumn();
        
        if ($roleId == 1) { // Admin role
            return [
                'success' => false,
                'message' => 'Không thể khóa tài khoản Administrator!'
            ];
        }

        $query = "UPDATE users SET is_locked = 1 WHERE user_id = ?";
        $stmt = $this->pdo->prepare($query);
        
        if ($stmt->execute([$userId])) {
            return [
                'success' => true,
                'message' => 'Đã khóa tài khoản thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi khóa tài khoản!'
            ];
        }
    }

    /**
     * Mở khóa tài khoản người dùng
     */
    public function moKhoaTaiKhoan($userId) {
        $query = "UPDATE users SET is_locked = 0, login_attempts = 0 WHERE user_id = ?";
        $stmt = $this->pdo->prepare($query);
        
        if ($stmt->execute([$userId])) {
            return [
                'success' => true,
                'message' => 'Đã mở khóa tài khoản thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi mở khóa tài khoản!'
            ];
        }
    }
} 