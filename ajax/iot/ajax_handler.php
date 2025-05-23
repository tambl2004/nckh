<?php
// ajax/iot/ajax_handler.php
include_once '../../config/connect.php';
include_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
    exit;
}

// Lấy hành động từ tham số URL
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Xử lý các hành động
switch ($action) {
    // Lấy danh sách khu vực theo kho
    case 'getZones':
        $warehouseId = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : 0;
        $selectedZoneId = isset($_GET['selected_zone_id']) ? intval($_GET['selected_zone_id']) : 0;
        
        if ($warehouseId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kho không hợp lệ']);
            exit;
        }
        
        $sql = "SELECT zone_id, zone_code, zone_name FROM warehouse_zones WHERE warehouse_id = ? ORDER BY zone_code";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$warehouseId]);
        $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'zones' => $zones,
            'selected_zone_id' => $selectedZoneId
        ]);
        break;
        
    // Lấy thông tin thiết bị
    case 'getDevice':
        $deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : 0;
        
        if ($deviceId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID thiết bị không hợp lệ']);
            exit;
        }
        
        $sql = "SELECT * FROM iot_devices WHERE device_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$deviceId]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$device) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thiết bị']);
            exit;
        }
        
        echo json_encode(['success' => true, 'device' => $device]);
        break;
        
    // Thêm thiết bị mới
    case 'addDevice':
        // Kiểm tra quyền
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm thiết bị']);
            exit;
        }
        
        // Lấy dữ liệu từ form
        $deviceCode = $_POST['device_code'] ?? '';
        $deviceName = $_POST['device_name'] ?? '';
        $deviceType = $_POST['device_type'] ?? 'OTHER';
        $warehouseId = !empty($_POST['warehouse_id']) ? intval($_POST['warehouse_id']) : null;
        $zoneId = !empty($_POST['zone_id']) ? intval($_POST['zone_id']) : null;
        $macAddress = $_POST['mac_address'] ?? null;
        $ipAddress = $_POST['ip_address'] ?? null;
        $firmwareVersion = $_POST['firmware_version'] ?? null;
        $lastMaintenanceDate = !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null;
        $nextMaintenanceDate = !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null;
        $status = $_POST['status'] ?? 'ACTIVE';
        
        // Kiểm tra dữ liệu
        if (empty($deviceCode) || empty($deviceName)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
            exit;
        }
        
        // Kiểm tra mã thiết bị đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM iot_devices WHERE device_code = ?");
        $stmt->execute([$deviceCode]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Mã thiết bị đã tồn tại']);
            exit;
        }
        
        // Thêm thiết bị mới
        $sql = "INSERT INTO iot_devices (device_code, device_name, device_type, warehouse_id, zone_id, mac_address, 
                ip_address, firmware_version, last_maintenance_date, next_maintenance_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $deviceCode, $deviceName, $deviceType, $warehouseId, $zoneId, $macAddress,
            $ipAddress, $firmwareVersion, $lastMaintenanceDate, $nextMaintenanceDate, $status
        ]);
        
        if ($result) {
            // Ghi log
            $userId = $_SESSION['user_id'];
            logUserAction($userId, 'ADD_IOT_DEVICE', "Thêm thiết bị IoT: $deviceName");
            
            echo json_encode(['success' => true, 'message' => 'Thêm thiết bị thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm thiết bị']);
        }
        break;
        
    // Cập nhật thiết bị
    case 'updateDevice':
        // Kiểm tra quyền
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật thiết bị']);
            exit;
        }
        
        // Lấy dữ liệu từ form
        $deviceId = isset($_POST['device_id']) ? intval($_POST['device_id']) : 0;
        $deviceCode = $_POST['device_code'] ?? '';
        $deviceName = $_POST['device_name'] ?? '';
        $deviceType = $_POST['device_type'] ?? 'OTHER';
        $warehouseId = !empty($_POST['warehouse_id']) ? intval($_POST['warehouse_id']) : null;
        $zoneId = !empty($_POST['zone_id']) ? intval($_POST['zone_id']) : null;
        $macAddress = $_POST['mac_address'] ?? null;
        $ipAddress = $_POST['ip_address'] ?? null;
        $firmwareVersion = $_POST['firmware_version'] ?? null;
        $lastMaintenanceDate = !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null;
        $nextMaintenanceDate = !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null;
        $status = $_POST['status'] ?? 'ACTIVE';
        
        // Kiểm tra dữ liệu
        if ($deviceId <= 0 || empty($deviceCode) || empty($deviceName)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
            exit;
        }
        
        // Kiểm tra mã thiết bị đã tồn tại chưa (ngoại trừ thiết bị hiện tại)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM iot_devices WHERE device_code = ? AND device_id != ?");
        $stmt->execute([$deviceCode, $deviceId]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Mã thiết bị đã tồn tại']);
            exit;
        }
        
        // Cập nhật thiết bị
        $sql = "UPDATE iot_devices SET 
                device_code = ?, device_name = ?, device_type = ?, warehouse_id = ?, zone_id = ?, 
                mac_address = ?, ip_address = ?, firmware_version = ?, last_maintenance_date = ?, 
                next_maintenance_date = ?, status = ? 
                WHERE device_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $deviceCode, $deviceName, $deviceType, $warehouseId, $zoneId, $macAddress,
            $ipAddress, $firmwareVersion, $lastMaintenanceDate, $nextMaintenanceDate, $status, $deviceId
        ]);
        
        if ($result) {
            // Ghi log
            $userId = $_SESSION['user_id'];
            logUserAction($userId, 'UPDATE_IOT_DEVICE', "Cập nhật thiết bị IoT ID: $deviceId");
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật thiết bị thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật thiết bị']);
        }
        break;
        
    // Xóa thiết bị
    case 'deleteDevice':
        // Kiểm tra quyền
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa thiết bị']);
            exit;
        }
        
        $deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : 0;
        
        if ($deviceId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID thiết bị không hợp lệ']);
            exit;
        }
        
        // Xóa trạng thái thiết bị
        $stmt = $pdo->prepare("DELETE FROM iot_device_statuses WHERE device_id = ?");
        $stmt->execute([$deviceId]);
        
        // Xóa thiết bị
        $stmt = $pdo->prepare("DELETE FROM iot_devices WHERE device_id = ?");
        $result = $stmt->execute([$deviceId]);
        
        if ($result) {
            // Ghi log
            $userId = $_SESSION['user_id'];
            logUserAction($userId, 'DELETE_IOT_DEVICE', "Xóa thiết bị IoT ID: $deviceId");
            
            echo json_encode(['success' => true, 'message' => 'Xóa thiết bị thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa thiết bị']);
        }
        break;
        
    // Cập nhật trạng thái thiết bị
    case 'updateDeviceStatus':
        // Kiểm tra quyền
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật trạng thái thiết bị']);
            exit;
        }
        
        // Lấy dữ liệu từ form
        $deviceId = isset($_POST['device_id']) ? intval($_POST['device_id']) : 0;
        $powerStatus = $_POST['power_status'] ?? 'OFF';
        $batteryLevel = isset($_POST['battery_level']) ? intval($_POST['battery_level']) : 100;
        $isError = isset($_POST['is_error']) && $_POST['is_error'] == '1';
        $errorMessage = $isError ? ($_POST['error_message'] ?? '') : null;
        
        // Kiểm tra dữ liệu
        if ($deviceId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID thiết bị không hợp lệ']);
            exit;
        }
        
        // Giới hạn mức pin từ 0-100
        $batteryLevel = max(0, min(100, $batteryLevel));
        
        // Thêm trạng thái mới
        $sql = "INSERT INTO iot_device_statuses (device_id, power_status, battery_level, is_error, error_message) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$deviceId, $powerStatus, $batteryLevel, $isError ? 1 : 0, $errorMessage]);
        
        if ($result) {
            // Ghi log
            $userId = $_SESSION['user_id'];
            logUserAction($userId, 'UPDATE_IOT_STATUS', "Cập nhật trạng thái thiết bị IoT ID: $deviceId");
            
            // Cập nhật trạng thái thiết bị nếu có lỗi
            if ($isError) {
                $stmt = $pdo->prepare("UPDATE iot_devices SET status = 'INACTIVE' WHERE device_id = ?");
                $stmt->execute([$deviceId]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thiết bị thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái thiết bị']);
        }
        break;
        
    // Lấy trạng thái thiết bị
    case 'getDeviceStatus':
        $deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : 0;
        
        // Xây dựng câu truy vấn
        $sql = "SELECT s.*, d.device_code, d.device_name, d.device_type 
                FROM iot_device_statuses s
                JOIN iot_devices d ON s.device_id = d.device_id
                WHERE 1=1";
        
        $params = [];
        
        if ($deviceId > 0) {
            $sql .= " AND s.device_id = ?";
            $params[] = $deviceId;
        }
        
        // Lấy trạng thái mới nhất của mỗi thiết bị
        $sql .= " AND s.status_id IN (
                    SELECT MAX(status_id) 
                    FROM iot_device_statuses 
                    GROUP BY device_id
                )
                ORDER BY s.timestamp DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'devices' => $statuses]);
        break;
        
    // Lấy nhật ký thiết bị
    case 'getDeviceLogs':
        $deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : 0;
        $logType = isset($_GET['log_type']) ? $_GET['log_type'] : 'all';
        
        // Xây dựng câu truy vấn
        $sql = "SELECT s.*, d.device_code, d.device_name, d.device_type 
                FROM iot_device_statuses s
                JOIN iot_devices d ON s.device_id = d.device_id
                WHERE 1=1";
        
        $params = [];
        
        if ($deviceId > 0) {
            $sql .= " AND s.device_id = ?";
            $params[] = $deviceId;
        }
        
        if ($logType === 'status') {
            $sql .= " AND s.is_error = 0";
        } else if ($logType === 'error') {
            $sql .= " AND s.is_error = 1";
        }
        
        $sql .= " ORDER BY s.timestamp DESC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;
        
    // Hành động không hợp lệ
    default:
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    break;
}
?>