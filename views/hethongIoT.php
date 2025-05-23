<?php
// Kiểm tra quyền truy cập
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Lấy danh sách thiết bị IoT
$sql = "SELECT d.*, w.warehouse_name, wz.zone_code 
        FROM iot_devices d
        LEFT JOIN warehouses w ON d.warehouse_id = w.warehouse_id
        LEFT JOIN warehouse_zones wz ON d.zone_id = wz.zone_id
        ORDER BY d.device_id DESC";
$result = $conn->query($sql);
$devices = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
}

// Lấy danh sách kho
$warehousesSql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
$warehousesResult = $conn->query($warehousesSql);
$warehouses = [];

if ($warehousesResult && $warehousesResult->num_rows > 0) {
    while ($row = $warehousesResult->fetch_assoc()) {
        $warehouses[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Quản lý hệ thống IoT</h1>
        <button class="btn btn-add" onclick="showAddDeviceModal()">
            <i class="fas fa-plus-circle me-2"></i>Thêm thiết bị
        </button>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="iotTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="devices-tab" data-bs-toggle="tab" data-bs-target="#devices" type="button" role="tab" aria-controls="devices" aria-selected="true">
                <i class="fas fa-microchip me-2"></i>Thiết bị
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button" role="tab" aria-controls="status" aria-selected="false">
                <i class="fas fa-heartbeat me-2"></i>Trạng thái
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab" aria-controls="logs" aria-selected="false">
                <i class="fas fa-history me-2"></i>Nhật ký
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="iotTabsContent">
        <!-- Thiết bị Tab -->
        <div class="tab-pane fade show active" id="devices" role="tabpanel" aria-labelledby="devices-tab">
            <div class="function-container">
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>Mã thiết bị</th>
                                <th>Tên thiết bị</th>
                                <th>Loại thiết bị</th>
                                <th>Vị trí</th>
                                <th>Địa chỉ MAC</th>
                                <th>Địa chỉ IP</th>
                                <th>Firmware</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($devices) > 0): ?>
                                <?php foreach ($devices as $device): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($device['device_code']); ?></td>
                                        <td><?php echo htmlspecialchars($device['device_name']); ?></td>
                                        <td>
                                            <?php 
                                            switch ($device['device_type']) {
                                                case 'RFID_SCANNER':
                                                    echo '<span class="badge bg-primary">Máy quét RFID</span>';
                                                    break;
                                                case 'BARCODE_SCANNER':
                                                    echo '<span class="badge bg-success">Máy quét mã vạch</span>';
                                                    break;
                                                case 'TEMPERATURE_SENSOR':
                                                    echo '<span class="badge bg-info">Cảm biến nhiệt độ</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-secondary">Khác</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($device['warehouse_name']) {
                                                echo htmlspecialchars($device['warehouse_name']);
                                                if ($device['zone_code']) {
                                                    echo ' - Khu ' . htmlspecialchars($device['zone_code']);
                                                }
                                            } else {
                                                echo 'Chưa gán';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($device['mac_address'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($device['ip_address'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($device['firmware_version'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($device['status'] == 'ACTIVE'): ?>
                                                <span class="badge bg-success">Hoạt động</span>
                                            <?php elseif ($device['status'] == 'INACTIVE'): ?>
                                                <span class="badge bg-danger">Không hoạt động</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Bảo trì</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-edit" onclick="showEditDeviceModal(<?php echo $device['device_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-delete" onclick="confirmDeleteDevice(<?php echo $device['device_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <button class="btn-toggle" onclick="showDeviceStatus(<?php echo $device['device_id']; ?>)">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">Không có thiết bị nào</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trạng thái Tab -->
        <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
            <div class="function-container">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select class="form-select" id="deviceFilter" onchange="loadDeviceStatus()">
                            <option value="0">Tất cả thiết bị</option>
                            <?php foreach ($devices as $device): ?>
                                <option value="<?php echo $device['device_id']; ?>">
                                    <?php echo htmlspecialchars($device['device_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary" onclick="loadDeviceStatus()">
                            <i class="fas fa-sync-alt me-2"></i>Cập nhật
                        </button>
                    </div>
                </div>

                <div id="deviceStatusContainer">
                    <div class="row" id="deviceStatusCards">
                        <!-- Thẻ trạng thái thiết bị sẽ được tải bằng AJAX -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Nhật ký Tab -->
        <div class="tab-pane fade" id="logs" role="tabpanel" aria-labelledby="logs-tab">
            <div class="function-container">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select class="form-select" id="logDeviceFilter" onchange="loadDeviceLogs()">
                            <option value="0">Tất cả thiết bị</option>
                            <?php foreach ($devices as $device): ?>
                                <option value="<?php echo $device['device_id']; ?>">
                                    <?php echo htmlspecialchars($device['device_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="logTypeFilter" onchange="loadDeviceLogs()">
                            <option value="all">Tất cả loại nhật ký</option>
                            <option value="status">Thay đổi trạng thái</option>
                            <option value="error">Lỗi thiết bị</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary" onclick="loadDeviceLogs()">
                            <i class="fas fa-sync-alt me-2"></i>Cập nhật
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Thiết bị</th>
                                <th>Trạng thái nguồn</th>
                                <th>Mức pin</th>
                                <th>Lỗi</th>
                                <th>Thông báo lỗi</th>
                            </tr>
                        </thead>
                        <tbody id="deviceLogsTable">
                            <!-- Dữ liệu nhật ký sẽ được tải bằng AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Thiết bị -->
<div class="custom-modal" id="addDeviceModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h5 class="modal-title">Thêm thiết bị mới</h5>
            <button type="button" class="modal-close" onclick="closeModal('addDeviceModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addDeviceForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="device_code">Mã thiết bị <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="device_code" name="device_code" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="device_name">Tên thiết bị <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="device_name" name="device_name" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="device_type">Loại thiết bị</label>
                            <select class="form-control" id="device_type" name="device_type">
                                <option value="RFID_SCANNER">Máy quét RFID</option>
                                <option value="BARCODE_SCANNER">Máy quét mã vạch</option>
                                <option value="TEMPERATURE_SENSOR">Cảm biến nhiệt độ</option>
                                <option value="OTHER">Khác</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="warehouse_id">Kho</label>
                            <select class="form-control" id="warehouse_id" name="warehouse_id" onchange="loadZones(this.value)">
                                <option value="">Chọn kho</option>
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
                        <div class="form-group">
                            <label for="zone_id">Khu vực</label>
                            <select class="form-control" id="zone_id" name="zone_id" disabled>
                                <option value="">Chọn khu vực</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mac_address">Địa chỉ MAC</label>
                            <input type="text" class="form-control" id="mac_address" name="mac_address">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ip_address">Địa chỉ IP</label>
                            <input type="text" class="form-control" id="ip_address" name="ip_address">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="firmware_version">Phiên bản firmware</label>
                            <input type="text" class="form-control" id="firmware_version" name="firmware_version">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_maintenance_date">Ngày bảo trì cuối</label>
                            <input type="date" class="form-control" id="last_maintenance_date" name="last_maintenance_date">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="next_maintenance_date">Ngày bảo trì tiếp theo</label>
                            <input type="date" class="form-control" id="next_maintenance_date" name="next_maintenance_date">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Trạng thái</label>
                    <select class="form-control" id="status" name="status">
                        <option value="ACTIVE">Hoạt động</option>
                        <option value="INACTIVE">Không hoạt động</option>
                        <option value="MAINTENANCE">Bảo trì</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addDeviceModal')">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveDevice()">Lưu</button>
        </div>
    </div>
</div>

<!-- Modal Chỉnh sửa Thiết bị -->
<div class="custom-modal" id="editDeviceModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h5 class="modal-title">Chỉnh sửa thiết bị</h5>
            <button type="button" class="modal-close" onclick="closeModal('editDeviceModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editDeviceForm">
                <input type="hidden" id="edit_device_id" name="device_id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_device_code">Mã thiết bị <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_device_code" name="device_code" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_device_name">Tên thiết bị <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_device_name" name="device_name" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_device_type">Loại thiết bị</label>
                            <select class="form-control" id="edit_device_type" name="device_type">
                                <option value="RFID_SCANNER">Máy quét RFID</option>
                                <option value="BARCODE_SCANNER">Máy quét mã vạch</option>
                                <option value="TEMPERATURE_SENSOR">Cảm biến nhiệt độ</option>
                                <option value="OTHER">Khác</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_warehouse_id">Kho</label>
                            <select class="form-control" id="edit_warehouse_id" name="warehouse_id" onchange="loadZones(this.value, 'edit')">
                                <option value="">Chọn kho</option>
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
                        <div class="form-group">
                            <label for="edit_zone_id">Khu vực</label>
                            <select class="form-control" id="edit_zone_id" name="zone_id">
                                <option value="">Chọn khu vực</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_mac_address">Địa chỉ MAC</label>
                            <input type="text" class="form-control" id="edit_mac_address" name="mac_address">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_ip_address">Địa chỉ IP</label>
                            <input type="text" class="form-control" id="edit_ip_address" name="ip_address">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_firmware_version">Phiên bản firmware</label>
                            <input type="text" class="form-control" id="edit_firmware_version" name="firmware_version">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_last_maintenance_date">Ngày bảo trì cuối</label>
                            <input type="date" class="form-control" id="edit_last_maintenance_date" name="last_maintenance_date">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_next_maintenance_date">Ngày bảo trì tiếp theo</label>
                            <input type="date" class="form-control" id="edit_next_maintenance_date" name="next_maintenance_date">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Trạng thái</label>
                    <select class="form-control" id="edit_status" name="status">
                        <option value="ACTIVE">Hoạt động</option>
                        <option value="INACTIVE">Không hoạt động</option>
                        <option value="MAINTENANCE">Bảo trì</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('editDeviceModal')">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="updateDevice()">Cập nhật</button>
        </div>
    </div>
</div>

<!-- Modal Cập nhật Trạng thái Thiết bị -->
<div class="custom-modal" id="deviceStatusModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h5 class="modal-title">Cập nhật trạng thái thiết bị</h5>
            <button type="button" class="modal-close" onclick="closeModal('deviceStatusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="updateStatusForm">
                <input type="hidden" id="status_device_id" name="device_id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="power_status">Trạng thái nguồn</label>
                            <select class="form-control" id="power_status" name="power_status">
                                <option value="ON">Bật</option>
                                <option value="OFF">Tắt</option>
                                <option value="SLEEP">Ngủ</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="battery_level">Mức pin (%)</label>
                            <input type="number" class="form-control" id="battery_level" name="battery_level" min="0" max="100" value="100">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_error" name="is_error" onchange="toggleErrorMessage()">
                        <label class="form-check-label" for="is_error">
                            Thiết bị đang gặp lỗi
                        </label>
                    </div>
                </div>
                
                <div class="form-group" id="error_message_group" style="display: none;">
                    <label for="error_message">Thông báo lỗi</label>
                    <textarea class="form-control" id="error_message" name="error_message" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('deviceStatusModal')">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="updateDeviceStatus()">Cập nhật</button>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div class="toast-container"></div>

<script>
// Hiển thị modal thêm thiết bị
function showAddDeviceModal() {
    document.getElementById('addDeviceForm').reset();
    document.getElementById('zone_id').disabled = true;
    document.getElementById('addDeviceModal').classList.add('show');
}

// Hiển thị modal chỉnh sửa thiết bị
function showEditDeviceModal(deviceId) {
    // Lấy thông tin thiết bị từ server
    fetch('ajax/iot/ajax_handler.php?action=getDevice&device_id=' + deviceId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const device = data.device;
                
                // Điền thông tin vào form
                document.getElementById('edit_device_id').value = device.device_id;
                document.getElementById('edit_device_code').value = device.device_code;
                document.getElementById('edit_device_name').value = device.device_name;
                document.getElementById('edit_device_type').value = device.device_type;
                document.getElementById('edit_warehouse_id').value = device.warehouse_id || '';
                document.getElementById('edit_mac_address').value = device.mac_address || '';
                document.getElementById('edit_ip_address').value = device.ip_address || '';
                document.getElementById('edit_firmware_version').value = device.firmware_version || '';
                document.getElementById('edit_last_maintenance_date').value = device.last_maintenance_date || '';
                document.getElementById('edit_next_maintenance_date').value = device.next_maintenance_date || '';
                document.getElementById('edit_status').value = device.status;
                
                // Tải danh sách khu vực
                if (device.warehouse_id) {
                    loadZones(device.warehouse_id, 'edit', device.zone_id);
                }
                
                // Hiển thị modal
                document.getElementById('editDeviceModal').classList.add('show');
            } else {
                showToast('error', 'Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Đã xảy ra lỗi khi tải thông tin thiết bị');
        });
}

// Hiển thị modal cập nhật trạng thái
function showDeviceStatus(deviceId) {
    // Reset form
    document.getElementById('updateStatusForm').reset();
    document.getElementById('status_device_id').value = deviceId;
    document.getElementById('error_message_group').style.display = 'none';
    
    // Hiển thị modal
    document.getElementById('deviceStatusModal').classList.add('show');
}

// Đóng modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Tải danh sách khu vực theo kho
function loadZones(warehouseId, prefix = '') {
    const zoneSelect = document.getElementById(prefix ? `${prefix}_zone_id` : 'zone_id');
    
    // Reset và disable select
    zoneSelect.innerHTML = '<option value="">Chọn khu vực</option>';
    zoneSelect.disabled = true;
    
    if (!warehouseId) return;
    
    // Lấy danh sách khu vực từ server
    fetch('ajax/iot/ajax_handler.php?action=getZones&warehouse_id=' + warehouseId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Thêm các option
                data.zones.forEach(zone => {
                    const option = document.createElement('option');
                    option.value = zone.zone_id;
                    option.textContent = `Khu ${zone.zone_code} - ${zone.zone_name || ''}`;
                    zoneSelect.appendChild(option);
                });
                
                // Enable select
                zoneSelect.disabled = false;
                
                // Nếu có zoneId được chỉ định (khi chỉnh sửa)
                if (prefix === 'edit' && data.selected_zone_id) {
                    zoneSelect.value = data.selected_zone_id;
                }
            } else {
                showToast('error', 'Lỗi: ' +                data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Đã xảy ra lỗi khi tải danh sách khu vực');
        });
}

// Lưu thiết bị mới
function saveDevice() {
    const form = document.getElementById('addDeviceForm');
    const formData = new FormData(form);
    
    fetch('ajax/iot/ajax_handler.php?action=addDevice', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Thêm thiết bị thành công');
            closeModal('addDeviceModal');
            // Tải lại trang sau khi thêm thành công
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', 'Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Đã xảy ra lỗi khi thêm thiết bị');
    });
}

// Cập nhật thiết bị
function updateDevice() {
    const form = document.getElementById('editDeviceForm');
    const formData = new FormData(form);
    
    fetch('ajax/iot/ajax_handler.php?action=updateDevice', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Cập nhật thiết bị thành công');
            closeModal('editDeviceModal');
            // Tải lại trang sau khi cập nhật thành công
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', 'Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Đã xảy ra lỗi khi cập nhật thiết bị');
    });
}

// Xác nhận xóa thiết bị
function confirmDeleteDevice(deviceId) {
    if (confirm('Bạn có chắc chắn muốn xóa thiết bị này?')) {
        deleteDevice(deviceId);
    }
}

// Xóa thiết bị
function deleteDevice(deviceId) {
    fetch('ajax/iot/ajax_handler.php?action=deleteDevice&device_id=' + deviceId, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Xóa thiết bị thành công');
            // Tải lại trang sau khi xóa thành công
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', 'Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Đã xảy ra lỗi khi xóa thiết bị');
    });
}

// Hiển thị/ẩn trường thông báo lỗi
function toggleErrorMessage() {
    const isError = document.getElementById('is_error').checked;
    document.getElementById('error_message_group').style.display = isError ? 'block' : 'none';
}

// Cập nhật trạng thái thiết bị
function updateDeviceStatus() {
    const form = document.getElementById('updateStatusForm');
    const formData = new FormData(form);
    
    // Chuyển checkbox thành boolean
    formData.set('is_error', document.getElementById('is_error').checked ? '1' : '0');
    
    fetch('ajax/iot/ajax_handler.php?action=updateDeviceStatus', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Cập nhật trạng thái thiết bị thành công');
            closeModal('deviceStatusModal');
            // Tải lại trạng thái thiết bị
            loadDeviceStatus();
        } else {
            showToast('error', 'Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Đã xảy ra lỗi khi cập nhật trạng thái thiết bị');
    });
}

// Tải trạng thái thiết bị
function loadDeviceStatus() {
    const deviceId = document.getElementById('deviceFilter').value;
    
    fetch('ajax/iot/ajax_handler.php?action=getDeviceStatus&device_id=' + deviceId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('deviceStatusCards');
                container.innerHTML = '';
                
                if (data.devices.length === 0) {
                    container.innerHTML = '<div class="col-12 text-center">Không có dữ liệu trạng thái thiết bị</div>';
                    return;
                }
                
                data.devices.forEach(device => {
                    let statusColor = 'success';
                    if (device.is_error === '1') {
                        statusColor = 'danger';
                    } else if (device.power_status === 'OFF') {
                        statusColor = 'secondary';
                    } else if (device.power_status === 'SLEEP') {
                        statusColor = 'warning';
                    } else if (device.battery_level < 20) {
                        statusColor = 'warning';
                    }
                    
                    let batteryIcon = '';
                    if (device.battery_level >= 80) {
                        batteryIcon = '<i class="fas fa-battery-full"></i>';
                    } else if (device.battery_level >= 50) {
                        batteryIcon = '<i class="fas fa-battery-three-quarters"></i>';
                    } else if (device.battery_level >= 30) {
                        batteryIcon = '<i class="fas fa-battery-half"></i>';
                    } else if (device.battery_level >= 10) {
                        batteryIcon = '<i class="fas fa-battery-quarter"></i>';
                    } else {
                        batteryIcon = '<i class="fas fa-battery-empty"></i>';
                    }
                    
                    const card = `
                        <div class="col-md-4 mb-4">
                            <div class="card border-${statusColor}">
                                <div class="card-header bg-${statusColor} text-white">
                                    <h5 class="mb-0">${device.device_name}</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Mã thiết bị:</strong> ${device.device_code}</p>
                                    <p><strong>Loại thiết bị:</strong> ${getDeviceTypeName(device.device_type)}</p>
                                    <p><strong>Trạng thái nguồn:</strong> ${getPowerStatusName(device.power_status)}</p>
                                    <p><strong>Mức pin:</strong> ${batteryIcon} ${device.battery_level}%</p>
                                    ${device.is_error === '1' ? `<p class="text-danger"><strong>Lỗi:</strong> ${device.error_message || 'Không xác định'}</p>` : ''}
                                    <p><strong>Cập nhật lúc:</strong> ${formatDateTime(device.timestamp)}</p>
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-sm btn-primary" onclick="showDeviceStatus(${device.device_id})">
                                        <i class="fas fa-edit me-1"></i>Cập nhật trạng thái
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    container.innerHTML += card;
                });
            } else {
                showToast('error', 'Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Đã xảy ra lỗi khi tải trạng thái thiết bị');
        });
}

// Tải nhật ký thiết bị
function loadDeviceLogs() {
    const deviceId = document.getElementById('logDeviceFilter').value;
    const logType = document.getElementById('logTypeFilter').value;
    
    fetch(`ajax/iot/ajax_handler.php?action=getDeviceLogs&device_id=${deviceId}&log_type=${logType}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tableBody = document.getElementById('deviceLogsTable');
                tableBody.innerHTML = '';
                
                if (data.logs.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Không có dữ liệu nhật ký</td></tr>';
                    return;
                }
                
                data.logs.forEach(log => {
                    const row = `
                        <tr>
                            <td>${formatDateTime(log.timestamp)}</td>
                            <td>${log.device_name}</td>
                            <td>${getPowerStatusName(log.power_status)}</td>
                            <td>${log.battery_level}%</td>
                            <td>${log.is_error === '1' ? '<span class="badge bg-danger">Có</span>' : '<span class="badge bg-success">Không</span>'}</td>
                            <td>${log.error_message || '-'}</td>
                        </tr>
                    `;
                    
                    tableBody.innerHTML += row;
                });
            } else {
                showToast('error', 'Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Đã xảy ra lỗi khi tải nhật ký thiết bị');
        });
}

// Hiển thị thông báo toast
function showToast(type, message) {
    const toastContainer = document.querySelector('.toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        </div>
        <div class="toast-message">${message}</div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Tự động xóa toast sau 3 giây
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Định dạng thời gian
function formatDateTime(dateTimeStr) {
    const date = new Date(dateTimeStr);
    return date.toLocaleString('vi-VN');
}

// Lấy tên loại thiết bị
function getDeviceTypeName(type) {
    switch (type) {
        case 'RFID_SCANNER':
            return 'Máy quét RFID';
        case 'BARCODE_SCANNER':
            return 'Máy quét mã vạch';
        case 'TEMPERATURE_SENSOR':
            return 'Cảm biến nhiệt độ';
        default:
            return 'Khác';
    }
}

// Lấy tên trạng thái nguồn
function getPowerStatusName(status) {
    switch (status) {
        case 'ON':
            return 'Bật';
        case 'OFF':
            return 'Tắt';
        case 'SLEEP':
            return 'Ngủ';
        default:
            return 'Không xác định';
    }
}

// Tải dữ liệu khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    // Tải trạng thái thiết bị khi chuyển đến tab trạng thái
    document.getElementById('status-tab').addEventListener('shown.bs.tab', function() {
        loadDeviceStatus();
    });
    
    // Tải nhật ký thiết bị khi chuyển đến tab nhật ký
    document.getElementById('logs-tab').addEventListener('shown.bs.tab', function() {
        loadDeviceLogs();
    });
});
</script>