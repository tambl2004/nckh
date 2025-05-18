<?php
include_once '../config/connect.php';

header('Content-Type: application/json');

$warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;

if ($warehouse_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT * FROM warehouse_zones WHERE warehouse_id = $warehouse_id ORDER BY zone_code";
$result = $conn->query($sql);

$zones = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $zones[] = $row;
    }
}

echo json_encode($zones);
?>