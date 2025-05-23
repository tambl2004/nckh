<?php
// Ngăn truy cập trực tiếp vào file
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/nckh/');
}

// Nhúng controller trực tiếp
require_once ROOT_PATH . 'controllers/nguoidung_controller.php';

// Khởi tạo controller với kết nối PDO
$controller = new NguoiDungController($pdo);

// Xử lý action get_permissions (thay thế cho ajax)
if (isset($_GET['action']) && $_GET['action'] == 'get_permissions' && isset($_GET['role_id'])) {
    $roleId = $_GET['role_id'];
    $permissions = $controller->model->layQuyenVaiTro($roleId);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'permissions' => $permissions
    ]);
    exit;
}

// Lấy dữ liệu để hiển thị view
extract($controller->getData());

// Hàm lấy tên trình duyệt từ chuỗi user-agent
function get_browser_name($user_agent) {
    if (strpos($user_agent, 'Firefox') !== false) {
        return "Firefox";
    } elseif (strpos($user_agent, 'Chrome') !== false) {
        return "Chrome";
    } elseif (strpos($user_agent, 'Safari') !== false) {
        return "Safari";
    } elseif (strpos($user_agent, 'Edge') !== false) {
        return "Edge";
    } elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) {
        return "Internet Explorer";
    } else {
        return "Không xác định";
    }
}
?>

<div class="container-fluid">
    <!-- Thông báo -->
    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($warning)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?php echo $warning; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="function-container">
        <h1 class="page-title">Quản lý người dùng</h1>
        
        <!-- Tab navigation -->
        <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'users' ? 'active' : ''; ?>" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="<?php echo $active_tab == 'users' ? 'true' : 'false'; ?>">
                    <i class="fas fa-users me-2"></i>Danh sách người dùng
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'roles' ? 'active' : ''; ?>" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab" aria-controls="roles" aria-selected="<?php echo $active_tab == 'roles' ? 'true' : 'false'; ?>">
                    <i class="fas fa-user-tag me-2"></i>Vai trò & Phân quyền
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'logs' ? 'active' : ''; ?>" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab" aria-controls="logs" aria-selected="<?php echo $active_tab == 'logs' ? 'true' : 'false'; ?>">
                    <i class="fas fa-history me-2"></i>Nhật ký hoạt động
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'system_logs' ? 'active' : ''; ?>" id="system-logs-tab" data-bs-toggle="tab" data-bs-target="#system_logs" type="button" role="tab" aria-controls="system_logs" aria-selected="<?php echo $active_tab == 'system_logs' ? 'true' : 'false'; ?>">
                    <i class="fas fa-server me-2"></i>Nhật ký hệ thống
                </button>
            </li>
        </ul>
        
        <!-- Tab content -->
        <div class="tab-content" id="userTabsContent">
            <!-- Danh sách người dùng -->
            <div class="tab-pane fade <?php echo $active_tab == 'users' ? 'show active' : ''; ?>" id="users" role="tabpanel" aria-labelledby="users-tab">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-2"></i>Thêm người dùng
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="10%">Ảnh</th>
                                <th width="15%">Tên đăng nhập</th>
                                <th width="20%">Họ tên</th>
                                <th width="15%">Email</th>
                                <th width="10%">Vai trò</th>
                                <th width="10%">Trạng thái</th>
                                <th width="15%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td>
                                    <div class="employee-avatar">
                                        <?php if (!empty($user['avatar'])): ?>
                                            <img src="<?php echo $user['avatar']; ?>" alt="Avatar" class="avatar-img">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role_id'] == 1 ? 'bg-danger' : ($user['role_id'] == 2 ? 'bg-primary' : 'bg-success'); ?>">
                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['is_locked']): ?>
                                        <span class="status-badge status-inactive">Bị khóa</span>
                                    <?php elseif ($user['is_active']): ?>
                                        <span class="status-badge status-active">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Vô hiệu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                                data-id="<?php echo $user['user_id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($user['phone']); ?>"
                                                data-role="<?php echo $user['role_id']; ?>"
                                                data-active="<?php echo $user['is_active']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                                                data-id="<?php echo $user['user_id']; ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        
                                        <?php if ($user['is_locked']): ?>
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#unlockAccountModal"
                                                data-id="<?php echo $user['user_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['username']); ?>">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#lockAccountModal"
                                                data-id="<?php echo $user['user_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['username']); ?>">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): // Không cho phép xóa chính mình ?>
                                        <button class="btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                data-id="<?php echo $user['user_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['username']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Vai trò & Phân quyền -->
            <div class="tab-pane fade <?php echo $active_tab == 'roles' ? 'show active' : ''; ?>" id="roles" role="tabpanel" aria-labelledby="roles-tab">
                <div class="row">
                    <div class="col-md-5">
                        <div class="settings-card mb-3">
                            <div class="settings-card-header">
                                <h5 class="settings-card-title">
                                    <i class="fas fa-user-tag"></i> Vai trò người dùng
                                </h5>
                            </div>
                            <div class="settings-card-body">
                                <button class="btn btn-add mb-3" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                                    <i class="fas fa-plus me-2"></i>Thêm vai trò mới
                                </button>
                                
                                <div class="list-group">
                                    <?php foreach ($roles as $role): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($role['role_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($role['description']); ?></small>
                                        </div>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-edit" data-bs-toggle="modal" data-bs-target="#editRoleModal"
                                                    data-id="<?php echo $role['role_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($role['role_name']); ?>"
                                                    data-description="<?php echo htmlspecialchars($role['description']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($role['role_id'] > 3): // Không cho phép xóa vai trò mặc định ?>
                                            <button class="btn btn-sm btn-delete" data-bs-toggle="modal" data-bs-target="#deleteRoleModal"
                                                    data-id="<?php echo $role['role_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($role['role_name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h5 class="settings-card-title">
                                    <i class="fas fa-shield-alt"></i> Phân quyền chi tiết
                                </h5>
                            </div>
                            <div class="settings-card-body">
                                <form id="permissionForm" method="post" action="">
                                    <input type="hidden" name="option" value="nhanvien">
                                    <div class="mb-3">
                                        <label for="permissionRole" class="form-label">Chọn vai trò để phân quyền</label>
                                        <select class="form-select" id="permissionRole" name="permission_role_id">
                                            <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Nhóm quyền -->
                                    <div class="permission-group">
                                        <div class="permission-header">
                                            Quản lý sản phẩm
                                        </div>
                                        <div class="permission-body">
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_view_products" name="perm_view_products">
                                                <label class="form-check-label" for="perm_view_products">Xem sản phẩm</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_add_products" name="perm_add_products">
                                                <label class="form-check-label" for="perm_add_products">Thêm sản phẩm</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_edit_products" name="perm_edit_products">
                                                <label class="form-check-label" for="perm_edit_products">Sửa sản phẩm</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_delete_products" name="perm_delete_products">
                                                <label class="form-check-label" for="perm_delete_products">Xóa sản phẩm</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="permission-group">
                                        <div class="permission-header">
                                            Quản lý kho
                                        </div>
                                        <div class="permission-body">
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_view_inventory" name="perm_view_inventory">
                                                <label class="form-check-label" for="perm_view_inventory">Xem kho</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_manage_import" name="perm_manage_import">
                                                <label class="form-check-label" for="perm_manage_import">Quản lý nhập kho</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_manage_export" name="perm_manage_export">
                                                <label class="form-check-label" for="perm_manage_export">Quản lý xuất kho</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_inventory_check" name="perm_inventory_check">
                                                <label class="form-check-label" for="perm_inventory_check">Kiểm kê kho</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="permission-group">
                                        <div class="permission-header">
                                            Quản lý người dùng
                                        </div>
                                        <div class="permission-body">
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_view_users" name="perm_view_users">
                                                <label class="form-check-label" for="perm_view_users">Xem người dùng</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_add_users" name="perm_add_users">
                                                <label class="form-check-label" for="perm_add_users">Thêm người dùng</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_edit_users" name="perm_edit_users">
                                                <label class="form-check-label" for="perm_edit_users">Sửa người dùng</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_delete_users" name="perm_delete_users">
                                                <label class="form-check-label" for="perm_delete_users">Xóa người dùng</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="permission-group">
                                        <div class="permission-header">
                                            Báo cáo và hệ thống
                                        </div>
                                        <div class="permission-body">
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_view_reports" name="perm_view_reports">
                                                <label class="form-check-label" for="perm_view_reports">Xem báo cáo</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_view_logs" name="perm_view_logs">
                                                <label class="form-check-label" for="perm_view_logs">Xem nhật ký người dùng</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_view_system_logs" name="perm_view_system_logs">
                                                <label class="form-check-label" for="perm_view_system_logs">Xem nhật ký hệ thống</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_system_settings" name="perm_system_settings">
                                                <label class="form-check-label" for="perm_system_settings">Cài đặt hệ thống</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid mt-3">
                                        <button type="submit" id="savePermissionsBtn" name="save_permissions" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Lưu phân quyền
                                        </button>
                                    </div>
                                </form>
                                <!-- Thêm div để hiển thị thông báo kết quả Ajax -->
                                <div id="permissionResult" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Nhật ký hoạt động -->
            <div class="tab-pane fade <?php echo $active_tab == 'logs' ? 'show active' : ''; ?>" id="logs" role="tabpanel" aria-labelledby="logs-tab">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <form id="user-filter-form" method="get" action="">
                            <input type="hidden" name="option" value="nhanvien">
                            <input type="hidden" name="tab" value="logs" class="tab-input">
                            <select class="form-select" id="logUserFilter" name="user_id" onchange="this.form.submit()">
                                <option value="">Tất cả người dùng</option>
                                <?php foreach ($log_users as $log_user): ?>
                                <option value="<?php echo $log_user['user_id']; ?>" <?php echo isset($_GET['user_id']) && $_GET['user_id'] == $log_user['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($log_user['username']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form id="action-filter-form" method="get" action="">
                            <input type="hidden" name="option" value="nhanvien">
                            <input type="hidden" name="tab" value="logs" class="tab-input">
                            <?php if(isset($_GET['user_id'])): ?>
                            <input type="hidden" name="user_id" value="<?php echo $_GET['user_id']; ?>">
                            <?php endif; ?>
                            <select class="form-select" id="logActionFilter" name="action" onchange="this.form.submit()">
                                <option value="">Tất cả hành động</option>
                                <?php foreach ($action_types as $action): ?>
                                <option value="<?php echo $action['action_type']; ?>" <?php echo isset($_GET['action']) && $_GET['action'] == $action['action_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action['action_type']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form id="date-filter-form" method="get" action="">
                            <input type="hidden" name="option" value="nhanvien">
                            <input type="hidden" name="tab" value="logs" class="tab-input">
                            <?php if(isset($_GET['user_id'])): ?>
                            <input type="hidden" name="user_id" value="<?php echo $_GET['user_id']; ?>">
                            <?php endif; ?>
                            <?php if(isset($_GET['action'])): ?>
                            <input type="hidden" name="action" value="<?php echo $_GET['action']; ?>">
                            <?php endif; ?>
                            <input type="date" class="form-control" id="logDateFilter" name="date" value="<?php echo isset($_GET['date']) ? $_GET['date'] : ''; ?>" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="15%">Người dùng</th>
                                <th width="15%">Thời gian</th>
                                <th width="15%">Hành động</th>
                                <th width="30%">Mô tả</th>
                                <th width="10%">IP</th>
                                <th width="10%">Trình duyệt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): 
                                $browser = get_browser_name($log['user_agent']);
                            ?>
                            <tr>
                                <td><?php echo $log['log_id']; ?></td>
                                <td><?php echo htmlspecialchars($log['username']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $action_class = '';
                                    switch ($log['action_type']) {
                                        case 'LOGIN':
                                        case 'LOGOUT':
                                            $action_class = 'login';
                                            break;
                                        case 'ADD_USER':
                                        case 'ADD_PRODUCT':
                                        case 'ADD_ROLE':
                                            $action_class = 'add';
                                            break;
                                        case 'UPDATE_USER':
                                        case 'UPDATE_PRODUCT':
                                        case 'UPDATE_ROLE':
                                            $action_class = 'edit';
                                            break;
                                        case 'DELETE_USER':
                                        case 'DELETE_PRODUCT':
                                        case 'DELETE_ROLE':
                                            $action_class = 'delete';
                                            break;
                                    }
                                    ?>
                                    <span class="log-action <?php echo $action_class; ?>">
                                        <?php echo htmlspecialchars($log['action_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($browser); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Nhật ký hệ thống -->
            <div class="tab-pane fade <?php echo $active_tab == 'system_logs' ? 'show active' : ''; ?>" id="system_logs" role="tabpanel" aria-labelledby="system-logs-tab">
                <?php if (!$is_admin): ?>
                    <div class="alert alert-danger">Bạn không có quyền xem nhật ký hệ thống!</div>
                <?php else: ?>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <form id="syslog-level-form" method="get" action="">
                            <input type="hidden" name="option" value="nhanvien">
                            <input type="hidden" name="tab" value="system_logs" class="tab-input">
                            <select class="form-select" id="sysLogLevelFilter" name="level" onchange="this.form.submit()">
                                <option value="">Tất cả mức độ</option>
                                <option value="INFO" <?php echo isset($_GET['level']) && $_GET['level'] == 'INFO' ? 'selected' : ''; ?>>Thông tin</option>
                                <option value="WARNING" <?php echo isset($_GET['level']) && $_GET['level'] == 'WARNING' ? 'selected' : ''; ?>>Cảnh báo</option>
                                <option value="ERROR" <?php echo isset($_GET['level']) && $_GET['level'] == 'ERROR' ? 'selected' : ''; ?>>Lỗi</option>
                                <option value="CRITICAL" <?php echo isset($_GET['level']) && $_GET['level'] == 'CRITICAL' ? 'selected' : ''; ?>>Nghiêm trọng</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form id="syslog-source-form" method="get" action="">
                            <input type="hidden" name="option" value="nhanvien">
                            <input type="hidden" name="tab" value="system_logs" class="tab-input">
                            <?php if(isset($_GET['level'])): ?>
                            <input type="hidden" name="level" value="<?php echo $_GET['level']; ?>">
                            <?php endif; ?>
                            <input type="text" class="form-control" id="sysLogSourceFilter" name="source" placeholder="Lọc theo nguồn..." value="<?php echo isset($_GET['source']) ? htmlspecialchars($_GET['source']) : ''; ?>">
                            <button type="submit" class="btn btn-sm btn-primary mt-1">Lọc</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form id="syslog-date-form" method="get" action="">
                            <input type="hidden" name="option" value="nhanvien">
                            <input type="hidden" name="tab" value="system_logs" class="tab-input">
                            <?php if(isset($_GET['level'])): ?>
                            <input type="hidden" name="level" value="<?php echo $_GET['level']; ?>">
                            <?php endif; ?>
                            <?php if(isset($_GET['source'])): ?>
                            <input type="hidden" name="source" value="<?php echo $_GET['source']; ?>">
                            <?php endif; ?>
                            <input type="date" class="form-control" id="sysLogDateFilter" name="date" value="<?php echo isset($_GET['date']) ? $_GET['date'] : ''; ?>" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="15%">Thời gian</th>
                                <th width="10%">Mức độ</th>
                                <th width="15%">Nguồn</th>
                                <th width="55%">Thông điệp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($system_logs as $log): 
                                $level_class = '';
                                switch ($log['log_level']) {
                                    case 'INFO':
                                        $level_class = 'bg-info';
                                        break;
                                    case 'WARNING':
                                        $level_class = 'bg-warning';
                                        break;
                                    case 'ERROR':
                                        $level_class = 'bg-danger';
                                        break;
                                    case 'CRITICAL':
                                        $level_class = 'bg-dark text-white';
                                        break;
                                }
                            ?>
                            <tr>
                                <td><?php echo $log['log_id']; ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><span class="badge <?php echo $level_class; ?>"><?php echo htmlspecialchars($log['log_level']); ?></span></td>
                                <td><?php echo htmlspecialchars($log['source']); ?></td>
                                <td><?php echo htmlspecialchars($log['message']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm người dùng -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm người dùng mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="full_name" class="form-label">Họ tên</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="form-group mb-3">
                        <label for="role_id" class="form-label">Vai trò</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Thêm người dùng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa người dùng -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa thông tin người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="form-group mb-3">
                        <label for="edit_username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="edit_username" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_full_name" class="form-label">Họ tên</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_phone" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone">
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_role_id" class="form-label">Vai trò</label>
                        <select class="form-select" id="edit_role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">Tài khoản hoạt động</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xóa người dùng -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa người dùng <strong id="delete_user_name"></strong>?</p>
                <p class="text-danger">Lưu ý: Hành động này không thể hoàn tác!</p>
            </div>
            <form method="post" action="">
                <input type="hidden" id="delete_user_id" name="delete_id">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Xóa người dùng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reset mật khẩu -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đặt lại mật khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Hệ thống sẽ tạo một mật khẩu ngẫu nhiên và gửi qua email cho người dùng.</p>
                <p>Đồng thời mở khóa tài khoản nếu đang bị khóa.</p>
            </div>
            <form method="post" action="">
                <input type="hidden" id="reset_user_id" name="reset_id">
                <input type="hidden" id="reset_user_email" name="reset_email">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="reset_password" class="btn btn-warning">Đặt lại mật khẩu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Thêm vai trò -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm vai trò mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="role_name" class="form-label">Tên vai trò</label>
                        <input type="text" class="form-control" id="role_name" name="role_name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="role_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="role_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_role" class="btn btn-primary">Thêm vai trò</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa vai trò -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa vai trò</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_role_id" name="role_id">
                    <div class="form-group mb-3">
                        <label for="edit_role_name" class="form-label">Tên vai trò</label>
                        <input type="text" class="form-control" id="edit_role_name" name="role_name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_role_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="edit_role_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_role" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xóa vai trò -->
<div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa vai trò</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa vai trò <strong id="delete_role_name"></strong>?</p>
                <p class="text-danger">Lưu ý: Nếu có người dùng đang sử dụng vai trò này, họ sẽ được chuyển sang vai trò "Người dùng".</p>
            </div>
            <form method="post" action="">
                <input type="hidden" id="delete_role_id" name="delete_role_id">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="delete_role" class="btn btn-danger">Xóa vai trò</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Khóa tài khoản -->
<div class="modal fade" id="lockAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận khóa tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn khóa tài khoản của người dùng <strong id="lock_user_name"></strong>?</p>
                <p class="text-warning">Người dùng sẽ không thể đăng nhập cho đến khi tài khoản được mở khóa.</p>
            </div>
            <form method="post" action="">
                <input type="hidden" id="lock_user_id" name="user_id">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="lock_account" class="btn btn-warning">Khóa tài khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Mở khóa tài khoản -->
<div class="modal fade" id="unlockAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận mở khóa tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn mở khóa tài khoản của người dùng <strong id="unlock_user_name"></strong>?</p>
                <p class="text-success">Người dùng sẽ có thể đăng nhập bình thường sau khi tài khoản được mở khóa.</p>
            </div>
            <form method="post" action="">
                <input type="hidden" id="unlock_user_id" name="user_id">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="unlock_account" class="btn btn-success">Mở khóa tài khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Sửa người dùng
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        editUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-id');
            const username = button.getAttribute('data-username');
            const fullname = button.getAttribute('data-fullname');
            const email = button.getAttribute('data-email');
            const phone = button.getAttribute('data-phone');
            const role = button.getAttribute('data-role');
            const active = button.getAttribute('data-active');

            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_full_name').value = fullname;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_role_id').value = role;
            document.getElementById('edit_is_active').checked = active === '1';
        });
    }

    // Modal Xóa người dùng
    const deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-id');
            const username = button.getAttribute('data-name');

            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = username;
        });
    }

    // Modal Reset mật khẩu
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    if (resetPasswordModal) {
        resetPasswordModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-id');
            const email = button.getAttribute('data-email');

            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_user_email').value = email;
        });
    }

    // Modal Sửa vai trò
    const editRoleModal = document.getElementById('editRoleModal');
    if (editRoleModal) {
        editRoleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const roleId = button.getAttribute('data-id');
            const roleName = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');

            document.getElementById('edit_role_id').value = roleId;
            document.getElementById('edit_role_name').value = roleName;
            document.getElementById('edit_role_description').value = description;
        });
    }

    // Modal Xóa vai trò
    const deleteRoleModal = document.getElementById('deleteRoleModal');
    if (deleteRoleModal) {
        deleteRoleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const roleId = button.getAttribute('data-id');
            const roleName = button.getAttribute('data-name');

            document.getElementById('delete_role_id').value = roleId;
            document.getElementById('delete_role_name').textContent = roleName;
        });
    }

    // Modal Khóa tài khoản
    const lockAccountModal = document.getElementById('lockAccountModal');
    if (lockAccountModal) {
        lockAccountModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-id');
            const username = button.getAttribute('data-name');

            document.getElementById('lock_user_id').value = userId;
            document.getElementById('lock_user_name').textContent = username;
        });
    }

    // Modal Mở khóa tài khoản
    const unlockAccountModal = document.getElementById('unlockAccountModal');
    if (unlockAccountModal) {
        unlockAccountModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-id');
            const username = button.getAttribute('data-name');

            document.getElementById('unlock_user_id').value = userId;
            document.getElementById('unlock_user_name').textContent = username;
        });
    }

    // Xử lý chuyển tab
    const userTabs = document.querySelectorAll('#userTabs .nav-link');
    userTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            // Lấy tab được chọn
            const tabId = this.getAttribute('data-bs-target').substring(1);
            
            // Cập nhật URL với history API mà không reload trang
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('tab', tabId);
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            history.pushState({ tab: tabId }, '', newUrl);
            
            // Cập nhật các input hidden trong form filter
            document.querySelectorAll('.tab-input').forEach(input => {
                input.value = tabId;
            });
        });
    });
    
    // Xử lý sự kiện popstate khi người dùng sử dụng nút back/forward trên trình duyệt
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.tab) {
            // Kích hoạt tab tương ứng
            const tabToActivate = document.querySelector(`#userTabs .nav-link[data-bs-target="#${event.state.tab}"]`);
            if (tabToActivate) {
                const tab = new bootstrap.Tab(tabToActivate);
                tab.show();
            }
        }
    });
    
    // Xử lý hiển thị phân quyền khi chọn vai trò
    const permissionRoleSelect = document.getElementById('permissionRole');
    if (permissionRoleSelect) {
        permissionRoleSelect.addEventListener('change', function() {
            loadPermissions(this.value);
        });
        
        // Load phân quyền ban đầu
        loadPermissions(permissionRoleSelect.value);
    }

    // Hàm load phân quyền theo vai trò
    function loadPermissions(roleId) {
        console.log('Loading permissions for role ID:', roleId);
        
        // Reset tất cả checkbox
        document.querySelectorAll('.permission-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Gửi yêu cầu lấy phân quyền
        fetch('?option=nhanvien&action=get_permissions&role_id=' + roleId, {
            method: 'GET'
        })
        .then(response => {
            console.log('API Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('API Response data:', data);
            if (data.success && data.permissions) {
                // Đánh dấu các quyền được cấp
                data.permissions.forEach(permission => {
                    const checkbox = document.getElementById('perm_' + permission);
                    if (checkbox) {
                        checkbox.checked = true;
                    } else {
                        console.warn('Checkbox not found for permission:', permission);
                    }
                });
            } else {
                console.error('Không có dữ liệu phân quyền hoặc format không đúng', data);
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải quyền:', error);
        });
    }
    
    // Xử lý lưu phân quyền bằng Ajax
    const permissionForm = document.getElementById('permissionForm');
    if (permissionForm) {
        permissionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Hiển thị thông báo đang xử lý
            const resultDiv = document.getElementById('permissionResult');
            resultDiv.innerHTML = '<div class="alert alert-info">Đang cập nhật quyền...</div>';
            
            // Tạo FormData từ form
            const formData = new FormData(permissionForm);
            
            // Thêm header X-Requested-With để server nhận biết đây là Ajax request
            fetch('?option=nhanvien', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
                
                // Tự động ẩn thông báo sau 3 giây
                setTimeout(() => {
                    resultDiv.innerHTML = '';
                }, 3000);
            })
            .catch(error => {
                console.error('Lỗi khi cập nhật quyền:', error);
                resultDiv.innerHTML = '<div class="alert alert-danger">Có lỗi xảy ra khi cập nhật quyền!</div>';
            });
        });
    }
});
</script>