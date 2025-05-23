<?php
// ajax/kiemke/export_excel.php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';
require_once '../../vendor/autoload.php'; // Cần cài đặt PHPSpreadsheet qua Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    die('Bạn cần đăng nhập để thực hiện chức năng này');
}

// Lấy tham số từ URL
$warehouseId = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : null;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Xây dựng câu truy vấn
$sql = "SELECT ic.check_id, ic.check_code, ic.scheduled_date, ic.completed_at, 
        w.warehouse_name, wz.zone_name,
        (SELECT COUNT(*) FROM inventory_check_results WHERE check_id = ic.check_id) as total_items,
        (SELECT COUNT(*) FROM inventory_check_results WHERE check_id = ic.check_id AND difference <> 0) as discrepancy_items,
        (
            SELECT ROUND(
                (COUNT(CASE WHEN difference = 0 THEN 1 END) * 100.0 / COUNT(*)), 2
            )
            FROM inventory_check_results 
            WHERE check_id = ic.check_id
        ) as accuracy
        FROM inventory_checks ic
        LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
        LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
        WHERE ic.status = 'COMPLETED'";

$params = [];

if ($warehouseId) {
    $sql .= " AND ic.warehouse_id = ?";
    $params[] = $warehouseId;
}

if ($dateFrom && $dateTo) {
    $sql .= " AND ic.scheduled_date BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
} else if ($dateFrom) {
    $sql .= " AND ic.scheduled_date >= ?";
    $params[] = $dateFrom;
} else if ($dateTo) {
    $sql .= " AND ic.scheduled_date <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY ic.scheduled_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Tạo file Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thiết lập tiêu đề và header
$sheet->setCellValue('A1', 'BÁO CÁO TỔNG HỢP KIỂM KÊ KHO');
$sheet->setCellValue('A2', 'Ngày xuất báo cáo: ' . date('d/m/Y H:i:s'));

if ($dateFrom && $dateTo) {
    $sheet->setCellValue('A3', 'Thời gian: Từ ' . date('d/m/Y', strtotime($dateFrom)) . ' đến ' . date('d/m/Y', strtotime($dateTo)));
} else if ($dateFrom) {
    $sheet->setCellValue('A3', 'Thời gian: Từ ' . date('d/m/Y', strtotime($dateFrom)));
} else if ($dateTo) {
    $sheet->setCellValue('A3', 'Thời gian: Đến ' . date('d/m/Y', strtotime($dateTo)));
}

if ($warehouseId) {
    $stmt = $pdo->prepare("SELECT warehouse_name FROM warehouses WHERE warehouse_id = ?");
    $stmt->execute([$warehouseId]);
    $warehouseName = $stmt->fetchColumn();
    $sheet->setCellValue('A4', 'Kho: ' . $warehouseName);
}

// Tạo header bảng
$sheet->setCellValue('A6', 'STT');
$sheet->setCellValue('B6', 'Mã Kiểm Kê');
$sheet->setCellValue('C6', 'Kho');
$sheet->setCellValue('D6', 'Khu Vực');
$sheet->setCellValue('E6', 'Ngày Kiểm Kê');
$sheet->setCellValue('F6', 'Ngày Hoàn Thành');
$sheet->setCellValue('G6', 'Số Sản Phẩm Kiểm Tra');
$sheet->setCellValue('H6', 'Số Sản Phẩm Chênh Lệch');
$sheet->setCellValue('I6', 'Tỷ Lệ Chính Xác (%)');

// Điền dữ liệu
$row = 7;
$totalItems = 0;
$totalDiscrepancies = 0;
$averageAccuracy = 0;
$reportCount = count($reports);

foreach ($reports as $index => $report) {
    $sheet->setCellValue('A' . $row, $index + 1);
    $sheet->setCellValue('B' . $row, $report['check_code']);
    $sheet->setCellValue('C' . $row, $report['warehouse_name']);
    $sheet->setCellValue('D' . $row, $report['zone_name'] ?: 'Toàn kho');
    $sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($report['scheduled_date'])));
    $sheet->setCellValue('F' . $row, date('d/m/Y H:i:s', strtotime($report['completed_at'])));
    $sheet->setCellValue('G' . $row, $report['total_items']);
    $sheet->setCellValue('H' . $row, $report['discrepancy_items']);
    $sheet->setCellValue('I' . $row, $report['accuracy']);
    
    // Tô màu cho tỷ lệ chính xác
    if ($report['accuracy'] >= 95) {
        $sheet->getStyle('I' . $row)->getFont()->getColor()->setARGB('008000'); // Xanh lá
    } else if ($report['accuracy'] >= 80) {
        $sheet->getStyle('I' . $row)->getFont()->getColor()->setARGB('FFA500'); // Cam
    } else {
        $sheet->getStyle('I' . $row)->getFont()->getColor()->setARGB('FF0000'); // Đỏ
    }
    
    $totalItems += $report['total_items'];
    $totalDiscrepancies += $report['discrepancy_items'];
    $averageAccuracy += $report['accuracy'];
    
    $row++;
}

// Thêm dòng tổng
$sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
$sheet->mergeCells('A' . $row . ':D' . $row);
$sheet->setCellValue('G' . $row, $totalItems);
$sheet->setCellValue('H' . $row, $totalDiscrepancies);
$sheet->setCellValue('I' . $row, $reportCount > 0 ? round($averageAccuracy / $reportCount, 2) : 0);

// Định dạng bảng
$sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A6:I6')->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);

// Định dạng cột
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(20);
$sheet->getColumnDimension('H')->setWidth(25);
$sheet->getColumnDimension('I')->setWidth(20);

// Xuất file Excel
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Bao_cao_tong_hop_kiem_ke_' . date('YmdHis') . '.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
?>