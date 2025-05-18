<?php
include_once '../config/connect.php';
require '../vendor/autoload.php'; // Nếu chưa có thư viện PHPSpreadsheet, cần cài đặt qua Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Kiểm tra yêu cầu xuất báo cáo nào
$reportType = isset($_GET['reportType']) ? $_GET['reportType'] : '';

// Thiết lập file Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Tạo tiêu đề và header cho file Excel dựa theo loại báo cáo
switch ($reportType) {
    case 'inventory':
        exportInventoryReport($conn, $sheet);
        break;
    case 'imports':
        exportImportsReport($conn, $sheet);
        break;
    case 'exports':
        exportExportsReport($conn, $sheet);
        break;
    case 'shelves':
        exportShelvesReport($conn, $sheet);
        break;
    case 'expiry':
        exportExpiryReport($conn, $sheet);
        break;
    default:
        die('Loại báo cáo không hợp lệ');
}

// Xuất file Excel
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Bao_cao_' . $reportType . '_' . date('YmdHis') . '.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;

// Hàm xuất báo cáo tồn kho
function exportInventoryReport($conn, $sheet) {
    // Lấy tham số từ URL
    $warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
    $categoryId = isset($_GET['categoryId']) ? (int)$_GET['categoryId'] : 0;
    $stockLevel = isset($_GET['stockLevel']) ? $_GET['stockLevel'] : '';
    
    // Xây dựng câu truy vấn
    $where = [];
    if ($warehouseId > 0) {
        $where[] = "warehouse_id = $warehouseId";
    }
    if ($categoryId > 0) {
        $where[] = "category_id = $categoryId";
    }
    if (!empty($stockLevel)) {
        $where[] = "stock_level = '$stockLevel'";
    }
    
    $whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";
    
    $sql = "SELECT * FROM inventory_report $whereClause ORDER BY stock_level, product_name";
    $result = $conn->query($sql);
    
    // Thiết lập tiêu đề và header
    $sheet->setCellValue('A1', 'BÁO CÁO TỒN KHO');
    $sheet->setCellValue('A2', 'Ngày xuất báo cáo: ' . date('d/m/Y H:i:s'));
    
    $sheet->setCellValue('A4', 'Mã SP');
    $sheet->setCellValue('B4', 'Tên sản phẩm');
    $sheet->setCellValue('C4', 'Danh mục');
    $sheet->setCellValue('D4', 'Kho');
    $sheet->setCellValue('E4', 'Số lượng');
    $sheet->setCellValue('F4', 'Đơn giá');
    $sheet->setCellValue('G4', 'Tổng giá trị');
    $sheet->setCellValue('H4', 'Mức tồn kho');
    
    // Điền dữ liệu
    $row = 5;
    $totalQuantity = 0;
    $totalValue = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $item['product_code']);
            $sheet->setCellValue('B' . $row, $item['product_name']);
            $sheet->setCellValue('C' . $row, $item['category_name']);
            $sheet->setCellValue('D' . $row, $item['warehouse_name']);
            $sheet->setCellValue('E' . $row, $item['quantity']);
            $sheet->setCellValue('F' . $row, $item['price']);
            $sheet->setCellValue('G' . $row, $item['total_value']);
            $sheet->setCellValue('H' . $row, $item['stock_level']);
            
            $totalQuantity += (int)$item['quantity'];
            $totalValue += (float)$item['total_value'];
            
            $row++;
        }
    }
    
    // Thêm dòng tổng
    $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
    $sheet->setCellValue('E' . $row, $totalQuantity);
    $sheet->setCellValue('G' . $row, $totalValue);
    
    // Format cột tiền tệ
    $sheet->getStyle('F5:G' . $row)->getNumberFormat()->setFormatCode('#,##0');
    
    // Định dạng bảng
    $sheet->getStyle('A4:H4')->getFont()->setBold(true);
    $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
}

// Hàm xuất báo cáo nhập kho
function exportImportsReport($conn, $sheet) {
    // Lấy tham số từ URL
    $warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
    $dateRange = isset($_GET['dateRange']) ? $_GET['dateRange'] : '30';
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
    
    // Tạo điều kiện lọc
    $dateCondition = "";
    if ($dateRange === 'custom' && !empty($startDate) && !empty($endDate)) {
        $dateCondition = "BETWEEN '$startDate' AND '$endDate'";
    } else {
        $days = (int)$dateRange;
        $dateCondition = ">= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
    }
    
    $warehouseCondition = $warehouseId > 0 ? "AND warehouse_id = $warehouseId" : "";
    
    $sql = "SELECT * FROM import_statistics 
           WHERE import_date $dateCondition $warehouseCondition
           ORDER BY import_date DESC";
    $result = $conn->query($sql);
    
    // Thiết lập tiêu đề và header
    $sheet->setCellValue('A1', 'BÁO CÁO NHẬP KHO');
    $sheet->setCellValue('A2', 'Ngày xuất báo cáo: ' . date('d/m/Y H:i:s'));
    
    $sheet->setCellValue('A4', 'Ngày nhập');
    $sheet->setCellValue('B4', 'Kho');
    $sheet->setCellValue('C4', 'Nhà cung cấp');
    $sheet->setCellValue('D4', 'Số phiếu nhập');
    $sheet->setCellValue('E4', 'Tổng số lượng');
    $sheet->setCellValue('F4', 'Tổng giá trị');
    
    // Điền dữ liệu
    $row = 5;
    $totalQuantity = 0;
    $totalValue = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $item['import_date']);
            $sheet->setCellValue('B' . $row, $item['warehouse_name']);
            $sheet->setCellValue('C' . $row, $item['supplier_name']);
            $sheet->setCellValue('D' . $row, $item['total_imports']);
            $sheet->setCellValue('E' . $row, $item['total_quantity']);
            $sheet->setCellValue('F' . $row, $item['total_amount']);
            
            $totalQuantity += (int)$item['total_quantity'];
            $totalValue += (float)$item['total_amount'];
            
            $row++;
        }
    }
    
    // Thêm dòng tổng
    $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
    $sheet->setCellValue('E' . $row, $totalQuantity);
    $sheet->setCellValue('F' . $row, $totalValue);
    
    // Format cột tiền tệ
    $sheet->getStyle('F5:F' . $row)->getNumberFormat()->setFormatCode('#,##0');
    
    // Định dạng bảng
    $sheet->getStyle('A4:F4')->getFont()->setBold(true);
    $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
}

// Hàm xuất báo cáo xuất kho
function exportExportsReport($conn, $sheet) {
    // Lấy tham số từ URL
    $warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
    $dateRange = isset($_GET['dateRange']) ? $_GET['dateRange'] : '30';
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
    
    // Tạo điều kiện lọc
    $dateCondition = "";
    if ($dateRange === 'custom' && !empty($startDate) && !empty($endDate)) {
        $dateCondition = "BETWEEN '$startDate' AND '$endDate'";
    } else {
        $days = (int)$dateRange;
        $dateCondition = ">= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
    }
    
    $warehouseCondition = $warehouseId > 0 ? "AND warehouse_id = $warehouseId" : "";
    
    $sql = "SELECT * FROM export_statistics 
           WHERE export_date $dateCondition $warehouseCondition
           ORDER BY export_date DESC";
    $result = $conn->query($sql);
    
    // Thiết lập tiêu đề và header
    $sheet->setCellValue('A1', 'BÁO CÁO XUẤT KHO');
    $sheet->setCellValue('A2', 'Ngày xuất báo cáo: ' . date('d/m/Y H:i:s'));
    
    $sheet->setCellValue('A4', 'Ngày xuất');
    $sheet->setCellValue('B4', 'Kho');
    $sheet->setCellValue('C4', 'Số phiếu xuất');
    $sheet->setCellValue('D4', 'Tổng số lượng');
    $sheet->setCellValue('E4', 'Tổng giá trị');
    
    // Điền dữ liệu
    $row = 5;
    $totalQuantity = 0;
    $totalValue = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $item['export_date']);
            $sheet->setCellValue('B' . $row, $item['warehouse_name']);
            $sheet->setCellValue('C' . $row, $item['total_exports']);
            $sheet->setCellValue('D' . $row, $item['total_quantity']);
            $sheet->setCellValue('E' . $row, $item['total_amount']);
            
            $totalQuantity += (int)$item['total_quantity'];
            $totalValue += (float)$item['total_amount'];
            
            $row++;
        }
    }
    
    // Thêm dòng tổng
    $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
    $sheet->setCellValue('D' . $row, $totalQuantity);
    $sheet->setCellValue('E' . $row, $totalValue);
    
    // Format cột tiền tệ
    $sheet->getStyle('E5:E' . $row)->getNumberFormat()->setFormatCode('#,##0');
    
    // Định dạng bảng
    $sheet->getStyle('A4:E4')->getFont()->setBold(true);
    $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
}

// Hàm xuất báo cáo kệ
function exportShelvesReport($conn, $sheet) {
    // Lấy tham số từ URL
    $warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
    
    // Xây dựng câu truy vấn
    $whereClause = $warehouseId > 0 ? " WHERE warehouse_id = $warehouseId" : "";
    
    $sql = "SELECT * FROM shelf_utilization $whereClause ORDER BY utilization_percentage DESC";
    $result = $conn->query($sql);
    
    // Thiết lập tiêu đề và header
    $sheet->setCellValue('A1', 'BÁO CÁO TÌNH TRẠNG KỆ');
    $sheet->setCellValue('A2', 'Ngày xuất báo cáo: ' . date('d/m/Y H:i:s'));
    
    $sheet->setCellValue('A4', 'Mã kệ');
    $sheet->setCellValue('B4', 'Khu vực');
    $sheet->setCellValue('C4', 'Kho');
    $sheet->setCellValue('D4', 'Công suất tối đa');
    $sheet->setCellValue('E4', 'Công suất đã dùng');
    $sheet->setCellValue('F4', '% Sử dụng');
    $sheet->setCellValue('G4', 'Mức độ sử dụng');
    
    // Điền dữ liệu
    $row = 5;
    
    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $item['shelf_code']);
            $sheet->setCellValue('B' . $row, $item['zone_code']);
            $sheet->setCellValue('C' . $row, $item['warehouse_name']);
            $sheet->setCellValue('D' . $row, $item['max_capacity']);
            $sheet->setCellValue('E' . $row, $item['used_capacity']);
            $sheet->setCellValue('F' . $row, $item['utilization_percentage'] . '%');
            $sheet->setCellValue('G' . $row, $item['utilization_level']);
            
            $row++;
        }
    }
    
    // Định dạng bảng
    $sheet->getStyle('A4:G4')->getFont()->setBold(true);
}

// Hàm xuất báo cáo hạn sử dụng
function exportExpiryReport($conn, $sheet) {
    // Lấy tham số từ URL
    $warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
    $daysThreshold = isset($_GET['daysThreshold']) ? (int)$_GET['daysThreshold'] : 30;
    
    // Xây dựng câu truy vấn
    $where = ["days_until_expiry <= $daysThreshold", "expiry_date > CURDATE()"];
    if ($warehouseId > 0) {
        $where[] = "warehouse_id = $warehouseId";
    }
    
    $whereClause = " WHERE " . implode(" AND ", $where);
    
    $sql = "SELECT * FROM expiring_products $whereClause ORDER BY days_until_expiry";
    $result = $conn->query($sql);
    
    // Thiết lập tiêu đề và header
    $sheet->setCellValue('A1', 'BÁO CÁO SẢN PHẨM SẮP HẾT HẠN');
    $sheet->setCellValue('A2', 'Ngày xuất báo cáo: ' . date('d/m/Y H:i:s'));
    $sheet->setCellValue('A3', "Thời gian: $daysThreshold ngày tới");
    
    $sheet->setCellValue('A5', 'Mã SP');
    $sheet->setCellValue('B5', 'Tên sản phẩm');
    $sheet->setCellValue('C5', 'Kho');
    $sheet->setCellValue('D5', 'Vị trí kệ');
    $sheet->setCellValue('E5', 'Lô hàng');
    $sheet->setCellValue('F5', 'Ngày hết hạn');
    $sheet->setCellValue('G5', 'Còn lại (ngày)');
    $sheet->setCellValue('H5', 'Số lượng');
    
    // Điền dữ liệu
    $row = 6;
    $totalQuantity = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $item['product_code']);
            $sheet->setCellValue('B' . $row, $item['product_name']);
            $sheet->setCellValue('C' . $row, $item['warehouse_name']);
            $sheet->setCellValue('D' . $row, $item['shelf_code']);
            $sheet->setCellValue('E' . $row, $item['batch_number'] ?: 'N/A');
            $sheet->setCellValue('F' . $row, $item['expiry_date']);
            $sheet->setCellValue('G' . $row, $item['days_until_expiry']);
            $sheet->setCellValue('H' . $row, $item['quantity']);
            
            $totalQuantity += (int)$item['quantity'];
            
            $row++;
        }
    }
    
    // Thêm dòng tổng
    $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
    $sheet->setCellValue('H' . $row, $totalQuantity);
    
    // Định dạng bảng
    $sheet->getStyle('A5:H5')->getFont()->setBold(true);
    $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
}
?>