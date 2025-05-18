<?php
include_once '../config/connect.php';

header('Content-Type: application/json');

$warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
$stock_level = isset($_GET['stock_level']) ? $_GET['stock_level'] : '';

$where = [];
if ($warehouse_id > 0) {
    $where[] = "w.warehouse_id = $warehouse_id";
}
if (!empty($stock_level)) {
    $where[] = "stock_level = '$stock_level'";
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT * FROM inventory_report $whereClause ORDER BY stock_level, product_name";
$result = $conn->query($sql);

$report = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }
}

echo json_encode($report);
?>