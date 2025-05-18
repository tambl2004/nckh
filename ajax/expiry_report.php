<?php
include_once '../config/connect.php';

header('Content-Type: application/json');

$days_threshold = isset($_GET['days_threshold']) ? (int)$_GET['days_threshold'] : 30;
$warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;

$where = ["DATEDIFF(pl.expiry_date, CURDATE()) <= $days_threshold", "pl.expiry_date > CURDATE()"];
if ($warehouse_id > 0) {
    $where[] = "w.warehouse_id = $warehouse_id";
}

$whereClause = " WHERE " . implode(" AND ", $where);

$sql = "SELECT 
            p.product_code,
            p.product_name,
            w.warehouse_name,
            s.shelf_code,
            pl.batch_number,
            pl.expiry_date,
            pl.quantity,
            DATEDIFF(pl.expiry_date, CURDATE()) AS days_until_expiry
        FROM 
            product_locations pl
            JOIN products p ON pl.product_id = p.product_id
            JOIN shelves s ON pl.shelf_id = s.shelf_id
            JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
            JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
        $whereClause
        ORDER BY 
            days_until_expiry";

$result = $conn->query($sql);

$report = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }
}

echo json_encode($report);
?>