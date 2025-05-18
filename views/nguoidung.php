<?php
    include 'controllers/nguoidung_controller.php';
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
        <ul class="nav nav-tabs mb-3" id="userTabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'users' ? 'active' : ''; ?>" href="?option=nhanvien&tab=users">
                    <i class="fas fa-users me-2"></i>Danh sách người dùng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'roles' ? 'active' : ''; ?>" href="?option=nhanvien&tab=roles">
                    <i class="fas fa-user-tag me-2"></i>Vai trò & Phân quyền
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'logs' ? 'active' : ''; ?>" href="?option=nhanvien&tab=logs">
                    <i class="fas fa-history me-2"></i>Nhật ký hoạt động
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'system_logs' ? 'active' : ''; ?>" href="?option=nhanvien&tab=system_logs">
                    <i class="fas fa-server me-2"></i>Nhật ký hệ thống
                </a>
            </li>
        </ul>
        
        <!-- Tab content -->
        <div class="tab-content">
            <!-- Danh sách người dùng -->
            <?php if ($active_tab == 'users'): ?>
            <div class="tab-pane fade show active">
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
                                <th width="10%">Ảnh đại diện</th>
                                <th width="15%">Tên đăng nhập</th>
                                <th width="20%">Họ tên</th>
                                <th width="15%">Email</th>
                                <th width="10%">Vai trò</th>
                                <th width="10%">Trạng thái</th>
                                <th width="15%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Lấy danh sách người dùng
                            $stmt = $pdo->query("
                                SELECT u.*, r.role_name 
                                FROM users u 
                                JOIN roles r ON u.role_id = r.role_id 
                                ORDER BY u.user_id DESC
                            ");
                            while ($user = $stmt->fetch()): 
                            ?>
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
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Vai trò & Phân quyền -->
            <?php if ($active_tab == 'roles'): ?>
            <div class="tab-pane fade show active">
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
                                <form method="post" action="">
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
                                                <input class="form-check-input" type="checkbox" id="perm_view_products">
                                                <label class="form-check-label" for="perm_view_products">Xem sản phẩm</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_add_products">
                                                <label class="form-check-label" for="perm_add_products">Thêm sản phẩm</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_edit_products">
                                                <label class="form-check-label" for="perm_edit_products">Sửa sản phẩm</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_delete_products">
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
                                                <input class="form-check-input" type="checkbox" id="perm_view_inventory">
                                                <label class="form-check-label" for="perm_view_inventory">Xem kho</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_manage_import">
                                                <label class="form-check-label" for="perm_manage_import">Quản lý nhập kho</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_manage_export">
                                                <label class="form-check-label" for="perm_manage_export">Quản lý xuất kho</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_inventory_check">
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
                                                <input class="form-check-input" type="checkbox" id="perm_view_users">
                                                <label class="form-check-label" for="perm_view_users">Xem người dùng</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_add_users">
                                                <label class="form-check-label" for="perm_add_users">Thêm người dùng</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_edit_users">
                                                <label class="form-check-label" for="perm_edit_users">Sửa người dùng</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_delete_users">
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
                                                <input class="form-check-input" type="checkbox" id="perm_view_reports">
                                                <label class="form-check-label" for="perm_view_reports">Xem báo cáo</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_view_logs">
                                                <label class="form-check-label" for="perm_view_logs">Xem nhật ký người dùng</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_view_system_logs">
                                                <label class="form-check-label" for="perm_view_system_logs">Xem nhật ký hệ thống</label>
                                            </div>
                                            <div class="permission-item">
                                                <input class="form-check-input" type="checkbox" id="perm_system_settings">
                                                <label class="form-check-label" for="perm_system_settings">Cài đặt hệ thống</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid mt-3">
                                        <button type="submit" name="save_permissions" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Lưu phân quyền
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Nhật ký hoạt động -->
            <?php if ($active_tab == 'logs'): ?>
            <div class="tab-pane fade show active">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select class="form-select" id="logUserFilter">
                            <option value="">Tất cả người dùng</option>
                            <?php
                            $stmt = $pdo->query("SELECT DISTINCT u.user_id, u.username FROM users u JOIN user_logs l ON u.user_id = l.user_id ORDER BY u.username");
                            while ($log_user = $stmt->fetch()): 
                            ?>
                            <option value="<?php echo $log_user['user_id']; ?>"><?php echo htmlspecialchars($log_user['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="logActionFilter">
                            <option value="">Tất cả hành động</option>
                            <?php
                            $stmt = $pdo->query("SELECT DISTINCT action_type FROM user_logs ORDER BY action_type");
                            while ($action = $stmt->fetch()): 
                            ?>
                            <option value="<?php echo $action['action_type']; ?>"><?php echo htmlspecialchars($action['action_type']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="date" class="form-control" id="logDateFilter">
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
                            <?php
                            $stmt = $pdo->query("
                                SELECT l.*, u.username 
                                FROM user_logs l 
                                JOIN users u ON l.user_id = u.user_id 
                                ORDER BY l.created_at DESC 
                                LIMIT 100
                            ");
                            while ($log = $stmt->fetch()): 
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
                                            $action_class = 'login';
                                            break;
                                        case 'ADD_USER':
                                        case 'ADD_PRODUCT':
                                            $action_class = 'add';
                                            break;
                                        case 'UPDATE_USER':
                                        case 'UPDATE_PRODUCT':
                                            $action_class = 'edit';
                                            break;
                                        case 'DELETE_USER':
                                        case 'DELETE_PRODUCT':
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
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Nhật ký hệ thống -->
            <?php if ($active_tab == 'system_logs'): ?>
            <?php if (!isAdmin()): ?>
                <div class="alert alert-danger">Bạn không có quyền xem nhật ký hệ thống!</div>
            <?php else: ?>
            <div class="tab-pane fade show active">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select class="form-select" id="sysLogLevelFilter">
                            <option value="">Tất cả mức độ</option>
                            <option value="INFO">Thông tin</option>
                            <option value="WARNING">Cảnh báo</option>
                            <option value="ERROR">Lỗi</option>
                            <option value="CRITICAL">Nghiêm trọng</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="sysLogSourceFilter" placeholder="Lọc theo nguồn...">
                    </div>
                    <div class="col-md-4">
                        <input type="date" class="form-control" id="sysLogDateFilter">
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
                            <?php
                            $stmt = $pdo->query("
                                SELECT * 
                                FROM system_logs 
                                ORDER BY created_at DESC 
                                LIMIT 100
                            ");
                            while ($log = $stmt->fetch()): 
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
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
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
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Họ tên</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="role_id">Vai trò</label>
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
                    <div class="form-group">
                        <label for="edit_username">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="edit_username" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_full_name">Họ tên</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" class="form-control" id="edit_email" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_phone">Số điện thoại</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="edit_role_id">Vai trò</label>
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
                    <div class="form-group">
                        <label for="role_name">Tên vai trò</label>
                        <input type="text" class="form-control" id="role_name" name="role_name" required>
                    </div>
                    <div class="form-group">
                        <label for="role_description">Mô tả</label>
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
                    <div class="form-group">
                        <label for="edit_role_name">Tên vai trò</label>
                        <input type="text" class="form-control" id="edit_role_name" name="role_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_role_description">Mô tả</label>
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

<!-- JavaScript -->
<script>
// Hàm lấy tên trình duyệt từ chuỗi user-agent
function get_browser_name(user_agent) {
    if (user_agent.indexOf("Firefox") > -1) {
        return "Firefox";
    } else if (user_agent.indexOf("Chrome") > -1) {
        return "Chrome";
    } else if (user_agent.indexOf("Safari") > -1) {
        return "Safari";
    } else if (user_agent.indexOf("Edge") > -1) {
        return "Edge";
    } else if (user_agent.indexOf("MSIE") > -1 || user_agent.indexOf("Trident") > -1) {
        return "Internet Explorer";
    } else {
        return "Không xác định";
    }
}



// Xử lý dữ liệu cho các modal
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

    // Lọc nhật ký người dùng
    const logUserFilter = document.getElementById('logUserFilter');
    const logActionFilter = document.getElementById('logActionFilter');
    const logDateFilter = document.getElementById('logDateFilter');

    if (logUserFilter && logActionFilter && logDateFilter) {
        [logUserFilter, logActionFilter, logDateFilter].forEach(filter => {
            filter.addEventListener('change', filterLogs);
        });
    }

    function filterLogs() {
        const userId = logUserFilter.value;
        const actionType = logActionFilter.value;
        const date = logDateFilter.value;

        // Chuyển đến URL có các bộ lọc
        window.location.href = `?option=nhanvien&tab=logs&user_id=${userId}&action=${actionType}&date=${date}`;
    }
    
    // Lọc nhật ký hệ thống
    const sysLogLevelFilter = document.getElementById('sysLogLevelFilter');
    const sysLogSourceFilter = document.getElementById('sysLogSourceFilter');
    const sysLogDateFilter = document.getElementById('sysLogDateFilter');

    if (sysLogLevelFilter && sysLogSourceFilter && sysLogDateFilter) {
        [sysLogLevelFilter, sysLogSourceFilter, sysLogDateFilter].forEach(filter => {
            filter.addEventListener('change', filterSysLogs);
        });
    }

    function filterSysLogs() {
        const level = sysLogLevelFilter.value;
        const source = sysLogSourceFilter.value;
        const date = sysLogDateFilter.value;

        // Chuyển đến URL có các bộ lọc
        window.location.href = `?option=nhanvien&tab=system_logs&level=${level}&source=${source}&date=${date}`;
    }

    // Load quyền của vai trò khi thay đổi select
    const permissionRoleSelect = document.getElementById('permissionRole');
    if (permissionRoleSelect) {
        permissionRoleSelect.addEventListener('change', function() {
            const roleId = this.value;
            loadRolePermissions(roleId);
        });

        // Load quyền ban đầu
        loadRolePermissions(permissionRoleSelect.value);
    }

    function loadRolePermissions(roleId) {
        fetch('ajax/get_role_permissions.php?role_id=' + roleId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset tất cả checkbox
                    document.querySelectorAll('.permission-item input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });

                    // Đánh dấu các quyền được cấp
                    data.permissions.forEach(permission => {
                        const checkbox = document.getElementById('perm_' + permission);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải quyền:', error);
            });
    }
});
</script>

