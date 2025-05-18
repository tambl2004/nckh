<?php
include_once '../config/connect.php';

header('Content-Type: application/json');

$warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
$utilization_level = isset($_GET['utilization_level']) ? $_GET['utilization_level'] : '';

$where = [];
if ($warehouse_id > 0) {
    $where[] = "w.warehouse_id = $warehouse_id";
}
if (!empty($utilization_level)) {
    $where[] = "utilization_level = '$utilization_level'";
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT * FROM shelf_utilization $whereClause ORDER BY utilization_percentage DESC";
$result = $conn->query($sql);

$report = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }
}

echo json_encode($report);
?>