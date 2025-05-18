<?php
include_once '../config/connect.php';

header('Content-Type: application/json');

$zone_id = isset($_GET['zone_id']) ? (int)$_GET['zone_id'] : 0;

if ($zone_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT * FROM shelves WHERE zone_id = $zone_id AND status = 'ACTIVE' ORDER BY shelf_code";
$result = $conn->query($sql);

$shelves = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shelves[] = $row;
    }
}

echo json_encode($shelves);
?>