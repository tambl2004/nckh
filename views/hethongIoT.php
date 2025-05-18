<?php
// Kiểm tra quyền truy cập
if (!hasPermission('view_iot_devices')) {
    echo '<div class="alert alert-danger">Bạn không có quyền truy cập chức năng này</div>';
    exit;
}

// Xử lý thêm thiết bị mới
if (isset($_POST['add_device'])) {
    $device_code = $_POST['device_code'];
    $device_name = $_POST['device_name'];
    $device_type = $_POST['device_type'];
    $warehouse_id = $_POST['warehouse_id'];
    $zone_id = $_POST['zone_id'] ?? null;
    $mac_address = $_POST['mac_address'] ?? null;
    $ip_address = $_POST['ip_address'] ?? null;
    $firmware_version = $_POST['firmware_version'] ?? null;
    $status = $_POST['status'] ?? 'ACTIVE';
    
    // Kiểm tra mã thiết bị đã tồn tại chưa
    $check_sql = "SELECT COUNT(*) FROM iot_devices WHERE device_code = ?";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute([$device_code]);
    if ($stmt->fetchColumn() > 0) {
        $error_message = "Mã thiết bị đã tồn tại!";
    } else {
        // Thêm thiết bị mới
        $insert_sql = "INSERT INTO iot_devices (device_code, device_name, device_type, warehouse_id, zone_id, 
                        mac_address, ip_address, firmware_version, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insert_sql);
        if ($stmt->execute([$device_code, $device_name, $device_type, $warehouse_id, $zone_id, 
                            $mac_address, $ip_address, $firmware_version, $status])) {
            // Tạo trạng thái ban đầu cho thiết bị
            $device_id = $pdo->lastInsertId();
            $status_sql = "INSERT INTO iot_device_statuses (device_id, power_status, battery_level) 
                          VALUES (?, 'OFF', 100)";
            $stmt = $pdo->prepare($status_sql);
            $stmt->execute([$device_id]);
            
            $success_message = "Thêm thiết bị thành công!";
            
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'ADD_IOT_DEVICE', "Thêm thiết bị IoT: $device_name");
        } else {
            $error_message = "Lỗi: Không thể thêm thiết bị!";
        }
    }
}

// Xử lý cập nhật thiết bị
if (isset($_POST['update_device'])) {
    $device_id = $_POST['device_id'];
    $device_name = $_POST['device_name'];
    $device_type = $_POST['device_type'];
    $warehouse_id = $_POST['warehouse_id'];
    $zone_id = $_POST['zone_id'] ?? null;
    $mac_address = $_POST['mac_address'] ?? null;
    $ip_address = $_POST['ip_address'] ?? null;
    $firmware_version = $_POST['firmware_version'] ?? null;
    $last_maintenance_date = !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null;
    $next_maintenance_date = !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null;
    $status = $_POST['status'] ?? 'ACTIVE';
    
    $update_sql = "UPDATE iot_devices SET 
                   device_name = ?, device_type = ?, warehouse_id = ?, zone_id = ?, 
                   mac_address = ?, ip_address = ?, firmware_version = ?,
                   last_maintenance_date = ?, next_maintenance_date = ?, status = ? 
                   WHERE device_id = ?";
    
    $stmt = $pdo->prepare($update_sql);
    
    if ($stmt->execute([$device_name, $device_type, $warehouse_id, $zone_id, 
                       $mac_address, $ip_address, $firmware_version,
                       $last_maintenance_date, $next_maintenance_date, $status, $device_id])) {
        $success_message = "Cập nhật thiết bị thành công!";
        
        // Ghi log hoạt động
        logUserActivity($_SESSION['user_id'], 'UPDATE_IOT_DEVICE', "Cập nhật thiết bị IoT ID: $device_id");
    } else {
        $error_message = "Lỗi: Không thể cập nhật thiết bị!";
    }
}

// Xử lý xóa thiết bị
if (isset($_POST['delete_device'])) {
    $device_id = $_POST['device_id'];
    
    // Xóa trạng thái thiết bị trước
    $delete_status_sql = "DELETE FROM iot_device_statuses WHERE device_id = ?";
    $stmt = $pdo->prepare($delete_status_sql);
    $stmt->execute([$device_id]);
    
    // Sau đó xóa thiết bị
    $delete_device_sql = "DELETE FROM iot_devices WHERE device_id = ?";
    $stmt = $pdo->prepare($delete_device_sql);
    
    if ($stmt->execute([$device_id])) {
        $success_message = "Xóa thiết bị thành công!";
        
        // Ghi log hoạt động
        logUserActivity($_SESSION['user_id'], 'DELETE_IOT_DEVICE', "Xóa thiết bị IoT ID: $device_id");
    } else {
        $error_message = "Lỗi: Không thể xóa thiết bị!";
    }
}

// Cập nhật trạng thái thiết bị
if (isset($_POST['update_device_status'])) {
    $device_id = $_POST['device_id'];
    $power_status = $_POST['power_status'];
    $battery_level = $_POST['battery_level'];
    $is_error = isset($_POST['is_error']) ? 1 : 0;
    $error_message = $_POST['error_message'] ?? null;
    
    $update_sql = "INSERT INTO iot_device_statuses (device_id, power_status, battery_level, is_error, error_message) 
                   VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($update_sql);
    
    if ($stmt->execute([$device_id, $power_status, $battery_level, $is_error, $error_message])) {
        $success_message = "Cập nhật trạng thái thiết bị thành công!";
        
        // Ghi log hoạt động
        logUserActivity($_SESSION['user_id'], 'UPDATE_IOT_STATUS', "Cập nhật trạng thái thiết bị IoT ID: $device_id");
    } else {
        $error_message = "Lỗi: Không thể cập nhật trạng thái thiết bị!";
    }
}

// Lấy danh sách kho hàng
$warehouses_sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
$warehouses_stmt = $pdo->query($warehouses_sql);
$warehouses = $warehouses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Lấy tổng số lượng thiết bị
$count_sql = "SELECT COUNT(*) FROM iot_devices";
$total_devices = $pdo->query($count_sql)->fetchColumn();
$total_pages = ceil($total_devices / $limit);

// Lấy danh sách thiết bị có phân trang
$devices_sql = "SELECT d.*, w.warehouse_name, z.zone_name 
                FROM iot_devices d
                LEFT JOIN warehouses w ON d.warehouse_id = w.warehouse_id
                LEFT JOIN warehouse_zones z ON d.zone_id = z.zone_id
                ORDER BY d.device_id DESC
                LIMIT $offset, $limit";
$devices_stmt = $pdo->query($devices_sql);
$devices = $devices_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="function-container">
    <!-- Hiển thị thông báo thành công/lỗi -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="page-title">Quản lý thiết bị IoT</h3>
        
        <?php if (hasPermission('add_iot_devices')): ?>
        <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
            <i class="fas fa-plus-circle me-2"></i>Thêm thiết bị mới
        </button>
        <?php endif; ?>
    </div>

    <!-- Tabs cho các chức năng khác nhau -->
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="devices-tab" data-bs-toggle="tab" data-bs-target="#devices" type="button" role="tab" aria-controls="devices" aria-selected="true">
                <i class="fas fa-microchip me-1"></i>Danh sách thiết bị
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button" role="tab" aria-controls="status" aria-selected="false">
                <i class="fas fa-heartbeat me-1"></i>Trạng thái thiết bị
            </button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        <!-- Tab danh sách thiết bị -->
        <div class="tab-pane fade show active" id="devices" role="tabpanel" aria-labelledby="devices-tab">
            <div class="table-responsive">
                <table class="table data-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="10%">Mã thiết bị</th>
                            <th width="15%">Tên thiết bị</th>
                            <th width="10%">Loại</th>
                            <th width="15%">Vị trí</th>
                            <th width="10%">Firmware</th>
                            <th width="10%">Trạng thái</th>
                            <th width="15%">Bảo trì tiếp theo</th>
                            <th width="10%">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($device['device_id']); ?></td>
                                <td><?php echo htmlspecialchars($device['device_code']); ?></td>
                                <td><?php echo htmlspecialchars($device['device_name']); ?></td>
                                <td>
                                    <?php 
                                    $type_map = [
                                        'RFID_SCANNER' => '<span class="badge bg-primary">RFID Scanner</span>',
                                        'BARCODE_SCANNER' => '<span class="badge bg-info">Barcode Scanner</span>',
                                        'TEMPERATURE_SENSOR' => '<span class="badge bg-success">Nhiệt độ</span>',
                                        'OTHER' => '<span class="badge bg-secondary">Khác</span>'
                                    ];
                                    echo $type_map[$device['device_type']] ?? $device['device_type'];
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($device['warehouse_name'] ?? 'N/A');
                                    if (!empty($device['zone_name'])) {
                                        echo ' - ' . htmlspecialchars($device['zone_name']);
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($device['firmware_version'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $status_map = [
                                        'ACTIVE' => '<span class="status-badge status-active">Hoạt động</span>',
                                        'INACTIVE' => '<span class="status-badge status-inactive">Không hoạt động</span>',
                                        'MAINTENANCE' => '<span class="status-badge" style="background-color: #fff3cd; color: #856404;">Bảo trì</span>'
                                    ];
                                    echo $status_map[$device['status']] ?? $device['status'];
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($device['next_maintenance_date'])) {
                                        $next_date = new DateTime($device['next_maintenance_date']);
                                        $today = new DateTime();
                                        $days_remaining = $today->diff($next_date)->days;
                                        
                                        echo htmlspecialchars($device['next_maintenance_date']);
                                        
                                        if ($next_date < $today) {
                                            echo ' <span class="badge bg-danger">Quá hạn</span>';
                                        } elseif ($days_remaining <= 7) {
                                            echo ' <span class="badge bg-warning">Sắp tới</span>';
                                        }
                                    } else {
                                        echo 'Chưa lên lịch';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (hasPermission('edit_iot_devices')): ?>
                                        <button type="button" class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editDeviceModal" 
                                            data-device='<?php echo json_encode($device); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('delete_iot_devices')): ?>
                                        <button type="button" class="btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteDeviceModal" 
                                            data-device-id="<?php echo $device['device_id']; ?>" 
                                            data-device-name="<?php echo htmlspecialchars($device['device_name']); ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                                            data-bs-target="#deviceStatusModal" data-device-id="<?php echo $device['device_id']; ?>"
                                            data-device-name="<?php echo htmlspecialchars($device['device_name']); ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($devices)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Không có thiết bị nào</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?option=hethongIoT&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?option=hethongIoT&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?option=hethongIoT&page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
        
        <!-- Tab trạng thái thiết bị -->
        <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Theo dõi thiết bị IoT</h5>
                            <p class="card-text">Hiển thị trạng thái hiện tại của các thiết bị.</p>
                            
                            <div class="row" id="device-status-cards">
                                <!-- Thông tin trạng thái thiết bị sẽ được cập nhật bằng Ajax -->
                                <div class="col-12 text-center my-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Đang tải...</span>
                                    </div>
                                    <p class="mt-2">Đang tải dữ liệu thiết bị...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal thêm thiết bị mới -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDeviceModalLabel">Thêm thiết bị IoT mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="device_code">Mã thiết bị <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="device_code" name="device_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="device_name">Tên thiết bị <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="device_name" name="device_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="device_type">Loại thiết bị <span class="text-danger">*</span></label>
                                <select class="form-control" id="device_type" name="device_type" required>
                                    <option value="RFID_SCANNER">RFID Scanner</option>
                                    <option value="BARCODE_SCANNER">Barcode Scanner</option>
                                    <option value="TEMPERATURE_SENSOR">Cảm biến nhiệt độ</option>
                                    <option value="OTHER">Khác</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="warehouse_id">Kho hàng <span class="text-danger">*</span></label>
                                <select class="form-control" id="warehouse_id" name="warehouse_id" required>
                                    <option value="">Chọn kho hàng</option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                        <option value="<?php echo $warehouse['warehouse_id']; ?>">
                                            <?php echo htmlspecialchars($warehouse['warehouse_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="zone_id">Khu vực</label>
                                <select class="form-control" id="zone_id" name="zone_id">
                                    <option value="">Chọn khu vực</option>
                                    <!-- Khu vực sẽ được nạp bằng Ajax khi chọn kho hàng -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="status">Trạng thái</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="ACTIVE">Hoạt động</option>
                                    <option value="INACTIVE">Không hoạt động</option>
                                    <option value="MAINTENANCE">Đang bảo trì</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="mac_address">Địa chỉ MAC</label>
                                <input type="text" class="form-control" id="mac_address" name="mac_address">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="ip_address">Địa chỉ IP</label>
                                <input type="text" class="form-control" id="ip_address" name="ip_address">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="firmware_version">Phiên bản firmware</label>
                        <input type="text" class="form-control" id="firmware_version" name="firmware_version">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="add_device" class="btn btn-primary">Thêm thiết bị</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sửa thiết bị -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" aria-labelledby="editDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDeviceModalLabel">Sửa thông tin thiết bị</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_device_id" name="device_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_device_code">Mã thiết bị</label>
                                <input type="text" class="form-control" id="edit_device_code" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_device_name">Tên thiết bị <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_device_name" name="device_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_device_type">Loại thiết bị <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_device_type" name="device_type" required>
                                    <option value="RFID_SCANNER">RFID Scanner</option>
                                    <option value="BARCODE_SCANNER">Barcode Scanner</option>
                                    <option value="TEMPERATURE_SENSOR">Cảm biến nhiệt độ</option>
                                    <option value="OTHER">Khác</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_warehouse_id">Kho hàng <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_warehouse_id" name="warehouse_id" required>
                                    <option value="">Chọn kho hàng</option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                        <option value="<?php echo $warehouse['warehouse_id']; ?>">
                                            <?php echo htmlspecialchars($warehouse['warehouse_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_zone_id">Khu vực</label>
                                <select class="form-control" id="edit_zone_id" name="zone_id">
                                    <option value="">Chọn khu vực</option>
                                    <!-- Khu vực sẽ được nạp bằng Ajax khi chọn kho hàng -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_status">Trạng thái</label>
                                <select class="form-control" id="edit_status" name="status">
                                    <option value="ACTIVE">Hoạt động</option>
                                    <option value="INACTIVE">Không hoạt động</option>
                                    <option value="MAINTENANCE">Đang bảo trì</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_mac_address">Địa chỉ MAC</label>
                                <input type="text" class="form-control" id="edit_mac_address" name="mac_address">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_ip_address">Địa chỉ IP</label>
                                <input type="text" class="form-control" id="edit_ip_address" name="ip_address">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_firmware_version">Phiên bản firmware</label>
                                <input type="text" class="form-control" id="edit_firmware_version" name="firmware_version">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_last_maintenance_date">Bảo trì cuối cùng</label>
                                <input type="date" class="form-control" id="edit_last_maintenance_date" name="last_maintenance_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_next_maintenance_date">Bảo trì tiếp theo</label>
                        <input type="date" class="form-control" id="edit_next_maintenance_date" name="next_maintenance_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="update_device" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xóa thiết bị -->
<div class="modal fade" id="deleteDeviceModal" tabindex="-1" aria-labelledby="deleteDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDeviceModalLabel">Xác nhận xóa thiết bị</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa thiết bị "<span id="delete_device_name"></span>"?</p>
                <p class="text-danger">Lưu ý: Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <form method="post" action="">
                    <input type="hidden" id="delete_device_id" name="device_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="delete_device" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal chi tiết trạng thái thiết bị -->
<div class="modal fade" id="deviceStatusModal" tabindex="-1" aria-labelledby="deviceStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deviceStatusModalLabel">Trạng thái thiết bị: <span id="status_device_name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Thông tin hiện tại</h5>
                                <div id="device_status_info">
                                    <div class="text-center my-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Đang tải...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Cập nhật trạng thái</h5>
                                <form id="update_status_form" method="post" action="">
                                    <input type="hidden" id="status_update_device_id" name="device_id">
                                    
                                    <div class="form-group mb-3">
                                        <label for="power_status">Trạng thái nguồn</label>
                                        <select class="form-control" id="power_status" name="power_status">
                                            <option value="ON">Bật</option>
                                            <option value="OFF">Tắt</option>
                                            <option value="SLEEP">Ngủ</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="battery_level">Mức pin (%)</label>
                                        <input type="number" class="form-control" id="battery_level" name="battery_level" min="0" max="100" value="100">
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="is_error" name="is_error">
                                        <label class="form-check-label" for="is_error">Thiết bị đang gặp lỗi</label>
                                    </div>
                                    
                                    <div class="form-group mb-3" id="error_message_group" style="display: none;">
                                        <label for="error_message">Thông báo lỗi</label>
                                        <textarea class="form-control" id="error_message" name="error_message" rows="3"></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_device_status" class="btn btn-primary">Cập nhật trạng thái</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5>Lịch sử trạng thái</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                                <th>Mức pin</th>
                                <th>Tình trạng</th>
                            </tr>
                        </thead>
                        <tbody id="device_status_history">
                            <tr>
                                <td colspan="4" class="text-center">Đang tải lịch sử...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
// Lấy danh sách khu vực dựa vào kho hàng được chọn
function loadZones(warehouseId, targetId) {
    fetch(`api/get_zones.php?warehouse_id=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            // Xóa tất cả options cũ trừ option mặc định đầu tiên
            const selectElement = document.getElementById(targetId);
            while (selectElement.options.length > 1) {
                selectElement.remove(1);
            }
            
            // Thêm options mới từ dữ liệu trả về
            if (data.zones && data.zones.length > 0) {
                data.zones.forEach(zone => {
                    const option = document.createElement('option');
                    option.value = zone.zone_id;
                    option.textContent = zone.zone_name + ' (' + zone.zone_code + ')';
                    selectElement.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải khu vực:', error);
        });
}

// Lấy thông tin trạng thái thiết bị
function loadDeviceStatus(deviceId) {
    fetch(`api/device_status.php?device_id=${deviceId}`)
        .then(response => response.json())
        .then(data => {
            // Hiển thị thông tin trạng thái hiện tại
            let statusInfo = '';
            
            if (data.current_status) {
                const status = data.current_status;
                
                // Màu sắc trạng thái
                let powerStatusClass = 'text-secondary';
                if (status.power_status === 'ON') powerStatusClass = 'text-success';
                else if (status.power_status === 'SLEEP') powerStatusClass = 'text-warning';
                
                // Màu sắc pin
                let batteryClass = 'text-success';
                if (status.battery_level <= 20) batteryClass = 'text-danger';
                else if (status.battery_level <= 50) batteryClass = 'text-warning';
                
                statusInfo = `
                    <div class="d-flex align-items-center mb-3">
                        <div class="fs-4 me-2 ${powerStatusClass}">
                            <i class="fas fa-power-off"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold">Trạng thái nguồn</p>
                            <p class="mb-0 ${powerStatusClass}">${
                                status.power_status === 'ON' ? 'Đang bật' : 
                                status.power_status === 'OFF' ? 'Đã tắt' : 'Chế độ ngủ'
                            }</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="fs-4 me-2 ${batteryClass}">
                            <i class="fas fa-battery-${Math.ceil(status.battery_level / 25) * 25}"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold">Mức pin</p>
                            <p class="mb-0 ${batteryClass}">${status.battery_level}%</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <div class="fs-4 me-2 ${status.is_error ? 'text-danger' : 'text-success'}">
                            <i class="fas ${status.is_error ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold">Tình trạng</p>
                            <p class="mb-0 ${status.is_error ? 'text-danger' : 'text-success'}">
                                ${status.is_error ? 'Đang gặp lỗi' : 'Hoạt động bình thường'}
                            </p>
                            ${status.is_error && status.error_message ? 
                                `<p class="mb-0 text-danger small">${status.error_message}</p>` : ''}
                        </div>
                    </div>
                    
                    <hr>
                    <p class="text-muted small mb-0">Cập nhật: ${new Date(status.timestamp).toLocaleString()}</p>
                `;
            } else {
                statusInfo = '<p class="text-center">Không có thông tin trạng thái</p>';
            }
            
            document.getElementById('device_status_info').innerHTML = statusInfo;
            
            // Hiển thị lịch sử trạng thái
            let historyHTML = '';
            
            if (data.status_history && data.status_history.length > 0) {
                data.status_history.forEach(status => {
                    historyHTML += `
                        <tr>
                            <td>${new Date(status.timestamp).toLocaleString()}</td>
                            <td>
                                <span class="${
                                    status.power_status === 'ON' ? 'text-success' : 
                                    status.power_status === 'SLEEP' ? 'text-warning' : 'text-secondary'
                                }">
                                    <i class="fas fa-power-off me-1"></i>
                                    ${
                                        status.power_status === 'ON' ? 'Bật' : 
                                        status.power_status === 'OFF' ? 'Tắt' : 'Ngủ'
                                    }
                                </span>
                            </td>
                            <td>
                                <span class="${
                                    status.battery_level <= 20 ? 'text-danger' : 
                                    status.battery_level <= 50 ? 'text-warning' : 'text-success'
                                }">
                                    <i class="fas fa-battery-${Math.ceil(status.battery_level / 25) * 25} me-1"></i>
                                    ${status.battery_level}%
                                </span>
                            </td>
                            <td>
                                ${status.is_error ? 
                                    `<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Lỗi</span>
                                     <p class="mb-0 small">${status.error_message || ''}</p>` : 
                                    '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Bình thường</span>'
                                }
                            </td>
                        </tr>
                    `;
                });
            } else {
                historyHTML = '<tr><td colspan="4" class="text-center">Không có lịch sử trạng thái</td></tr>';
            }
            
            document.getElementById('device_status_history').innerHTML = historyHTML;
        })
        .catch(error => {
            console.error('Lỗi khi tải trạng thái thiết bị:', error);
            document.getElementById('device_status_info').innerHTML = '<p class="text-danger">Lỗi khi tải dữ liệu</p>';
            document.getElementById('device_status_history').innerHTML = '<tr><td colspan="4" class="text-danger">Lỗi khi tải dữ liệu</td></tr>';
        });
}

// Tải thông tin trạng thái tất cả thiết bị cho tab giám sát
function loadAllDeviceStatuses() {
    fetch('api/all_device_statuses.php')
        .then(response => response.json())
        .then(data => {
            let cardsHTML = '';
            
            if (data.devices && data.devices.length > 0) {
                data.devices.forEach(device => {
                    // Màu sắc trạng thái
                    let cardBorderClass = 'border-success';
                    let statusIconClass = 'text-success';
                    let statusText = 'Hoạt động bình thường';
                    
                    if (device.is_error) {
                        cardBorderClass = 'border-danger';
                        statusIconClass = 'text-danger';
                        statusText = 'Đang gặp lỗi';
                    } else if (device.power_status === 'OFF') {
                        cardBorderClass = 'border-secondary';
                        statusIconClass = 'text-secondary';
                        statusText = 'Thiết bị đã tắt';
                    } else if (device.power_status === 'SLEEP') {
                        cardBorderClass = 'border-warning';
                        statusIconClass = 'text-warning';
                        statusText = 'Chế độ ngủ';
                    } else if (device.battery_level <= 20) {
                        cardBorderClass = 'border-danger';
                        statusIconClass = 'text-danger';
                        statusText = 'Pin yếu';
                    }
                    
                    cardsHTML += `
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 ${cardBorderClass}" style="border-width: 2px;">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">${device.device_name}</h6>
                                    <span class="badge ${
                                        device.device_type === 'RFID_SCANNER' ? 'bg-primary' : 
                                        device.device_type === 'BARCODE_SCANNER' ? 'bg-info' : 
                                        device.device_type === 'TEMPERATURE_SENSOR' ? 'bg-success' : 'bg-secondary'
                                    }">
                                        ${
                                            device.device_type === 'RFID_SCANNER' ? 'RFID Scanner' : 
                                            device.device_type === 'BARCODE_SCANNER' ? 'Barcode Scanner' : 
                                            device.device_type === 'TEMPERATURE_SENSOR' ? 'Nhiệt độ' : 'Khác'
                                        }
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="fs-4 me-2 ${
                                            device.power_status === 'ON' ? 'text-success' : 
                                            device.power_status === 'SLEEP' ? 'text-warning' : 'text-secondary'
                                        }">
                                            <i class="fas fa-power-off"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 small">Trạng thái</p>
                                            <p class="mb-0 fw-bold ${
                                                device.power_status === 'ON' ? 'text-success' : 
                                                device.power_status === 'SLEEP' ? 'text-warning' : 'text-secondary'
                                            }">
                                                ${
                                                    device.power_status === 'ON' ? 'Đang bật' : 
                                                    device.power_status === 'OFF' ? 'Đã tắt' : 'Chế độ ngủ'
                                                }
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="fs-4 me-2 ${
                                            device.battery_level <= 20 ? 'text-danger' : 
                                            device.battery_level <= 50 ? 'text-warning' : 'text-success'
                                        }">
                                            <i class="fas fa-battery-${Math.ceil(device.battery_level / 25) * 25}"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 small">Pin</p>
                                            <p class="mb-0 fw-bold ${
                                                device.battery_level <= 20 ? 'text-danger' : 
                                                device.battery_level <= 50 ? 'text-warning' : 'text-success'
                                            }">
                                                ${device.battery_level}%
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-start">
                                        <div class="fs-4 me-2 ${statusIconClass}">
                                            <i class="fas ${device.is_error ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 small">Tình trạng</p>
                                            <p class="mb-0 fw-bold ${statusIconClass}">
                                                ${statusText}
                                            </p>
                                            ${device.is_error && device.error_message ? 
                                                `<p class="mb-0 text-danger small mt-1">${device.error_message}</p>` : ''}
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent text-muted small">
                                    <div class="d-flex justify-content-between">
                                        <span>Vị trí: ${device.warehouse_name}${device.zone_name ? ' - ' + device.zone_name : ''}</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#deviceStatusModal" 
                                            data-device-id="${device.device_id}" 
                                            data-device-name="${device.device_name}">
                                            Chi tiết
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                cardsHTML = '<div class="col-12 text-center">Không có thiết bị nào</div>';
            }
            
            document.getElementById('device-status-cards').innerHTML = cardsHTML;
        })
        .catch(error => {
            console.error('Lỗi khi tải trạng thái thiết bị:', error);
            document.getElementById('device-status-cards').innerHTML = 
                '<div class="col-12 text-center text-danger">Lỗi khi tải dữ liệu thiết bị</div>';
        });
}

// Xử lý khi chọn kho hàng trong modal thêm mới
document.getElementById('warehouse_id').addEventListener('change', function() {
    const warehouseId = this.value;
    if (warehouseId) {
        loadZones(warehouseId, 'zone_id');
    }
});

// Xử lý khi chọn kho hàng trong modal chỉnh sửa
document.getElementById('edit_warehouse_id').addEventListener('change', function() {
    const warehouseId = this.value;
    if (warehouseId) {
        loadZones(warehouseId, 'edit_zone_id');
    }
});

// Xử lý hiển thị/ẩn trường thông báo lỗi
document.getElementById('is_error').addEventListener('change', function() {
    document.getElementById('error_message_group').style.display = this.checked ? 'block' : 'none';
});

// Tải trạng thái thiết bị khi chuyển tab
document.getElementById('status-tab').addEventListener('click', function() {
    loadAllDeviceStatuses();
});

// Xử lý khi mở modal chỉnh sửa
document.getElementById('editDeviceModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const deviceData = JSON.parse(button.getAttribute('data-device'));
    
    // Điền dữ liệu vào form
    document.getElementById('edit_device_id').value = deviceData.device_id;
    document.getElementById('edit_device_code').value = deviceData.device_code;
    document.getElementById('edit_device_name').value = deviceData.device_name;
    document.getElementById('edit_device_type').value = deviceData.device_type;
    document.getElementById('edit_warehouse_id').value = deviceData.warehouse_id;
    loadZones(deviceData.warehouse_id, 'edit_zone_id');
    
    // Xử lý các trường có thể null
    setTimeout(() => {
        if (deviceData.zone_id) {
            document.getElementById('edit_zone_id').value = deviceData.zone_id;
        }
    }, 500);
    
    document.getElementById('edit_status').value = deviceData.status;
    document.getElementById('edit_mac_address').value = deviceData.mac_address || '';
    document.getElementById('edit_ip_address').value = deviceData.ip_address || '';
    document.getElementById('edit_firmware_version').value = deviceData.firmware_version || '';
    
    if (deviceData.last_maintenance_date) {
        document.getElementById('edit_last_maintenance_date').value = deviceData.last_maintenance_date.split(' ')[0];
    } else {
        document.getElementById('edit_last_maintenance_date').value = '';
    }
    
    if (deviceData.next_maintenance_date) {
        document.getElementById('edit_next_maintenance_date').value = deviceData.next_maintenance_date.split(' ')[0];
    } else {
        document.getElementById('edit_next_maintenance_date').value = '';
    }
});

// Xử lý khi mở modal xóa
document.getElementById('deleteDeviceModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const deviceId = button.getAttribute('data-device-id');
    const deviceName = button.getAttribute('data-device-name');
    
    document.getElementById('delete_device_id').value = deviceId;
    document.getElementById('delete_device_name').textContent = deviceName;
});

// Xử lý khi mở modal trạng thái thiết bị
document.getElementById('deviceStatusModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const deviceId = button.getAttribute('data-device-id');
    const deviceName = button.getAttribute('data-device-name');
    
    document.getElementById('status_device_name').textContent = deviceName;
    document.getElementById('status_update_device_id').value = deviceId;
    
    // Tải thông tin trạng thái thiết bị
    loadDeviceStatus(deviceId);
});

// Tự động cập nhật trạng thái thiết bị định kỳ
let statusUpdateInterval;

document.getElementById('status-tab').addEventListener('shown.bs.tab', function() {
    // Bắt đầu cập nhật trạng thái mỗi 30 giây
    statusUpdateInterval = setInterval(loadAllDeviceStatuses, 30000);
});

document.getElementById('devices-tab').addEventListener('shown.bs.tab', function() {
    // Dừng cập nhật khi chuyển sang tab khác
    clearInterval(statusUpdateInterval);
});

// Tải trạng thái ban đầu nếu tab đang hiện
if (document.getElementById('status-tab').classList.contains('active')) {
    loadAllDeviceStatuses();
}
</script>