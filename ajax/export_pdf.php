<?php
include_once '../config/connect.php';
require '../vendor/autoload.php'; // Nếu chưa có thư viện TCPDF, cần cài đặt qua Composer

use TCPDF as TCPDF;

// Kiểm tra yêu cầu xuất báo cáo nào
$reportType = isset($_GET['reportType']) ? $_GET['reportType'] : '';

// Thiết lập PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Hệ thống quản lý kho');
$pdf->SetTitle('Báo cáo ' . $reportType);

// Thiết lập font chữ utf-8
$pdf->SetFont('dejavusans', '', 10, '', true);

// Loại bỏ header và footer mặc định
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Tạo trang mới
$pdf->AddPage();

// Tạo nội dung PDF dựa theo loại báo cáo
switch ($reportType) {
    case 'inventory':
        exportInventoryReport($conn, $pdf);
        break;
    case 'expiry':
        exportExpiryReport($conn, $pdf);
        break;
    default:
        die('Loại báo cáo không hợp lệ');
}

// Xuất file PDF
$pdf->Output('Bao_cao_' . $reportType . '_' . date('YmdHis') . '.pdf', 'I');
exit;

// Hàm xuất báo cáo tồn kho
function exportInventoryReport($conn, $pdf) {
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
    
    // Tạo tiêu đề
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->Cell(0, 10, 'BÁO CÁO TỒN KHO', 0, 1, 'C');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, 'Ngày xuất báo cáo: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
    $pdf->Ln(5);
    
    // Tạo header bảng
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetFont('dejavusans', 'B', 10);
    
    $pdf->Cell(25, 7, 'Mã SP', 1, 0, 'C', 1);
    $pdf->Cell(50, 7, 'Tên sản phẩm', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Danh mục', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Kho', 1, 0, 'C', 1);
    $pdf->Cell(20, 7, 'Số lượng', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Đơn giá', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Tổng giá trị', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Mức tồn kho', 1, 1, 'C', 1);
    
    // Điền dữ liệu
    $pdf->SetFont('dejavusans', '', 9);
    $totalQuantity = 0;
    $totalValue = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $pdf->Cell(25, 6, $item['product_code'], 1, 0, 'L');
            $pdf->Cell(50, 6, $item['product_name'], 1, 0, 'L');
            $pdf->Cell(30, 6, $item['category_name'], 1, 0, 'L');
            $pdf->Cell(30, 6, $item['warehouse_name'], 1, 0, 'L');
            $pdf->Cell(20, 6, $item['quantity'], 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($item['price']), 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($item['total_value']), 1, 0, 'R');
            $pdf->Cell(25, 6, $item['stock_level'], 1, 1, 'C');
            
            $totalQuantity += (int)$item['quantity'];
            $totalValue += (float)$item['total_value'];
        }
    }
    
    // Thêm dòng tổng
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(135, 7, 'TỔNG CỘNG', 1, 0, 'R');
    $pdf->Cell(20, 7, $totalQuantity, 1, 0, 'R');
    $pdf->Cell(30, 7, '', 1, 0, 'R');
    $pdf->Cell(30, 7, number_format($totalValue), 1, 0, 'R');
    $pdf->Cell(25, 7, '', 1, 1, 'C');
}

// Hàm xuất báo cáo hạn sử dụng
function exportExpiryReport($conn, $pdf) {
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
    
    // Tạo tiêu đề
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->Cell(0, 10, 'BÁO CÁO SẢN PHẨM SẮP HẾT HẠN', 0, 1, 'C');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, 'Ngày xuất báo cáo: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
    $pdf->Cell(0, 10, "Thời gian: $daysThreshold ngày tới", 0, 1, 'L');
    $pdf->Ln(5);
    
    // Tạo header bảng
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetFont('dejavusans', 'B', 10);
    
    $pdf->Cell(25, 7, 'Mã SP', 1, 0, 'C', 1);
    $pdf->Cell(50, 7, 'Tên sản phẩm', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Kho', 1, 0, 'C', 1);
    $pdf->Cell(20, 7, 'Vị trí kệ', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Lô hàng', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Ngày hết hạn', 1, 0, 'C', 1);
    $pdf->Cell(15, 7, 'Còn lại', 1, 0, 'C', 1);
    $pdf->Cell(20, 7, 'Số lượng', 1, 1, 'C', 1);
    
    // Điền dữ liệu
    $pdf->SetFont('dejavusans', '', 9);
    $totalQuantity = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $daysRemaining = (int)$item['days_until_expiry'];
            $textColor = [0, 0, 0]; // Mặc định là màu đen
            
            // Thay đổi màu cho số ngày còn lại
            if ($daysRemaining <= 7) {
                $textColor = [255, 0, 0]; // Đỏ
            } else if ($daysRemaining <= 30) {
                $textColor = [255, 128, 0]; // Cam
            }
            
            $pdf->Cell(25, 6, $item['product_code'], 1, 0, 'L');
            $pdf->Cell(50, 6, $item['product_name'], 1, 0, 'L');
            $pdf->Cell(30, 6, $item['warehouse_name'], 1, 0, 'L');
            $pdf->Cell(20, 6, $item['shelf_code'], 1, 0, 'L');
            $pdf->Cell(25, 6, $item['batch_number'] ?: 'N/A', 1, 0, 'L');
            $pdf->Cell(25, 6, date('d/m/Y', strtotime($item['expiry_date'])), 1, 0, 'C');
            
            // Thay đổi màu cho số ngày còn lại
            $pdf->setTextColor($textColor[0], $textColor[1], $textColor[2]);
            $pdf->Cell(15, 6, $daysRemaining, 1, 0, 'C');
            $pdf->setTextColor(0, 0, 0); // Đặt lại màu đen
            
            $pdf->Cell(20, 6, $item['quantity'], 1, 1, 'R');
            
            $totalQuantity += (int)$item['quantity'];
        }
    }
    
    // Thêm dòng tổng
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(190, 7, 'TỔNG CỘNG: ' . $totalQuantity . ' sản phẩm', 1, 1, 'R');
}
?>