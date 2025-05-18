<?php
include_once '../config/connect.php';

header('Content-Type: application/json');

// Truy vấn danh sách thiết bị với trạng thái mới nhất
$sql = "SELECT d.device_id, d.device_code, d.device_name, d.device_type, 
                w.warehouse_name, z.zone_name, s.power_status, s.battery_level, 
                s.is_error, s.error_message, s.timestamp
        FROM iot_devices d
        LEFT JOIN warehouses w ON d.warehouse_id = w.warehouse_id
        LEFT JOIN warehouse_zones z ON d.zone_id = z.zone_id
        LEFT JOIN (
            SELECT ds.device_id, ds.power_status, ds.battery_level, ds.is_error, ds.error_message, ds.timestamp
            FROM iot_device_statuses ds
            INNER JOIN (
                SELECT device_id, MAX(timestamp) as max_time
                FROM iot_device_statuses
                GROUP BY device_id
            ) as latest ON ds.device_id = latest.device_id AND ds.timestamp = latest.max_time
        ) as s ON d.device_id = s.device_id
        WHERE d.status = 'ACTIVE'
        ORDER BY d.device_name";

$stmt = $pdo->query($sql);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['devices' => $devices]);
?>