<?php
include_once '../config/connect.php';

header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
$quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 0;

if ($product_id <= 0 || $warehouse_id <= 0 || $quantity <= 0) {
    echo json_encode([]);
    exit;
}

// Gọi procedure suggest_shelf_location
$stmt = $conn->prepare("CALL suggest_shelf_location(?, ?, ?)");
$stmt->bind_param("iii", $product_id, $warehouse_id, $quantity);
$stmt->execute();
$result = $stmt->get_result();

$shelves = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shelves[] = [
            'shelf_id' => $row['shelf_id'],
            'shelf_code' => $row['shelf_code'],
            'max_capacity' => $row['max_capacity'],
            'available_capacity' => $row['available_capacity']
        ];
    }
    $result->free();
}

echo json_encode($shelves);
?>