<?php
include_once '../config/connect.php';

header('Content-Type: application/json');

// Kiểm tra tham số đầu vào
if (!isset($_GET['device_id']) || empty($_GET['device_id'])) {
    echo json_encode(['error' => 'Thiếu thông tin thiết bị']);
    exit;
}

$device_id = $_GET['device_id'];

// Lấy trạng thái hiện tại của thiết bị
$current_sql = "SELECT * FROM iot_device_statuses 
                WHERE device_id = ? 
                ORDER BY timestamp DESC LIMIT 1";
$stmt = $pdo->prepare($current_sql);
$stmt->execute([$device_id]);
$current_status = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy lịch sử trạng thái của thiết bị
$history_sql = "SELECT * FROM iot_device_statuses 
                WHERE device_id = ? 
                ORDER BY timestamp DESC LIMIT 10";
$stmt = $pdo->prepare($history_sql);
$stmt->execute([$device_id]);
$status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'current_status' => $current_status,
    'status_history' => $status_history
]);
?>