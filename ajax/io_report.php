<?php
include_once '../config/connect.php';

header('Content-Type: application/json');

// Lấy các tham số từ URL
$warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
$dateRange = isset($_GET['dateRange']) ? $_GET['dateRange'] : '30';
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

// Tạo điều kiện lọc theo ngày
$dateCondition = "";
if ($dateRange === 'custom' && !empty($startDate) && !empty($endDate)) {
    $dateCondition = "BETWEEN '$startDate' AND '$endDate'";
} else {
    $days = (int)$dateRange;
    $dateCondition = ">= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
}

// Điều kiện lọc theo kho
$warehouseCondition = $warehouseId > 0 ? "AND warehouse_id = $warehouseId" : "";

// Lấy dữ liệu nhập kho
$importsSql = "SELECT * FROM import_statistics 
               WHERE import_date $dateCondition $warehouseCondition
               ORDER BY import_date DESC";
$importsResult = $conn->query($importsSql);

$imports = [];
if ($importsResult && $importsResult->num_rows > 0) {
    while ($row = $importsResult->fetch_assoc()) {
        $imports[] = $row;
    }
}

// Lấy dữ liệu xuất kho
$exportsSql = "SELECT * FROM export_statistics 
               WHERE export_date $dateCondition $warehouseCondition
               ORDER BY export_date DESC";
$exportsResult = $conn->query($exportsSql);

$exports = [];
if ($exportsResult && $exportsResult->num_rows > 0) {
    while ($row = $exportsResult->fetch_assoc()) {
        $exports[] = $row;
    }
}

// Tính tổng số lượng và giá trị nhập/xuất
$totalImports = 0;
$totalImportValue = 0;
foreach ($imports as $import) {
    $totalImports += (int)$import['total_imports'];
    $totalImportValue += (float)$import['total_amount'];
}

$totalExports = 0;
$totalExportValue = 0;
foreach ($exports as $export) {
    $totalExports += (int)$export['total_exports'];
    $totalExportValue += (float)$export['total_amount'];
}

// Lấy thống kê theo nhà cung cấp
$suppliersSql = "SELECT 
                   supplier_name,
                   COUNT(DISTINCT import_id) as total_imports,
                   SUM(total_quantity) as total_quantity,
                   SUM(total_amount) as total_amount
                 FROM import_statistics
                 WHERE import_date $dateCondition $warehouseCondition
                 GROUP BY supplier_name
                 ORDER BY total_amount DESC";
$suppliersResult = $conn->query($suppliersSql);

$suppliers = [];
$totalSupplierAmount = 0;

if ($suppliersResult && $suppliersResult->num_rows > 0) {
    while ($row = $suppliersResult->fetch_assoc()) {
        $totalSupplierAmount += (float)$row['total_amount'];
        $suppliers[] = $row;
    }
    
    // Tính phần trăm
    foreach ($suppliers as &$supplier) {
        $supplier['percentage'] = $totalSupplierAmount > 0 ? 
            number_format(((float)$supplier['total_amount'] / $totalSupplierAmount) * 100, 2) : 0;
    }
}

// Tạo dữ liệu time series cho biểu đồ
$timeSeriesData = [];

// Xác định thời gian bắt đầu và kết thúc
if ($dateRange === 'custom' && !empty($startDate) && !empty($endDate)) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
} else {
    $end = new DateTime();
    $start = clone $end;
    $start->modify("-$dateRange days");
}

// Tạo mảng các ngày
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($start, $interval, $end);

foreach ($period as $dt) {
    $date = $dt->format("Y-m-d");
    $timeSeriesData[$date] = [
        'date' => $date,
        'import_value' => 0,
        'export_value' => 0
    ];
}

// Điền dữ liệu nhập kho vào time series
foreach ($imports as $import) {
    $date = $import['import_date'];
    if (isset($timeSeriesData[$date])) {
        $timeSeriesData[$date]['import_value'] += (float)$import['total_amount'];
    }
}

// Điền dữ liệu xuất kho vào time series
foreach ($exports as $export) {
    $date = $export['export_date'];
    if (isset($timeSeriesData[$date])) {
        $timeSeriesData[$date]['export_value'] += (float)$export['total_amount'];
    }
}

// Chuyển đổi mảng kết hợp thành mảng tuần tự cho JSON
$timeSeriesArray = array_values($timeSeriesData);

// Trả về kết quả dưới dạng JSON
echo json_encode([
    'success' => true,
    'stats' => [
        'totalImports' => $totalImports,
        'totalImportValue' => $totalImportValue,
        'totalExports' => $totalExports,
        'totalExportValue' => $totalExportValue
    ],
    'imports' => $imports,
    'exports' => $exports,
    'suppliers' => $suppliers,
    'timeSeriesData' => $timeSeriesArray
]);
?>