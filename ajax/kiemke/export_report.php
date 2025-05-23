<?php
// ajax/kiemke/export_report.php
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

// Lấy thông tin kiểm kê
$checkId = isset($_GET['check_id']) ? intval($_GET['check_id']) : 0;
$checkCode = isset($_GET['check_code']) ? $_GET['check_code'] : '';

if ($checkId <= 0 && empty($checkCode)) {
    die('Không tìm thấy thông tin kiểm kê');
}

// Lấy thông tin kiểm kê
if ($checkId > 0) {
    $sql = "SELECT ic.*, w.warehouse_name, wz.zone_name, u.full_name as created_by_name
            FROM inventory_checks ic
            LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
            LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
            LEFT JOIN users u ON ic.created_by = u.user_id
            WHERE ic.check_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$checkId]);
} else {
    $sql = "SELECT ic.*, w.warehouse_name, wz.zone_name, u.full_name as created_by_name
            FROM inventory_checks ic
            LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
            LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
            LEFT JOIN users u ON ic.created_by = u.user_id
            WHERE ic.check_code = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$checkCode]);
}

$check = $stmt->fetch();

if (!$check) {
    die('Không tìm thấy thông tin kiểm kê');
}

$checkId = $check['check_id'];

// Lấy kết quả kiểm kê
$sql = "SELECT icr.*, p.product_code, p.product_name, s.shelf_code, wz.zone_code
        FROM inventory_check_results icr
        LEFT JOIN products p ON icr.product_id = p.product_id
        LEFT JOIN shelves s ON icr.shelf_id = s.shelf_id
        LEFT JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
        WHERE icr.check_id = ?
        ORDER BY icr.difference DESC, p.product_code";

$stmt = $pdo->prepare($sql);
$stmt->execute([$checkId]);
$results = $stmt->fetchAll();

// Tính số liệu thống kê
$totalItems = count($results);
$matchedItems = 0;
$excessItems = 0;
$shortageItems = 0;

foreach ($results as $result) {
    if ($result['difference'] == 0) {
        $matchedItems++;
    } else if ($result['difference'] > 0) {
        $excessItems++;
    } else {
        $shortageItems++;
    }
}

$accuracy = $totalItems > 0 ? round(($matchedItems / $totalItems) * 100, 2) : 0;

// Tạo file Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thiết lập tiêu đề và header
$sheet->setCellValue('A1', 'BÁO CÁO KIỂM KÊ KHO');
$sheet->setCellValue('A2', 'Mã kiểm kê: ' . $check['check_code']);
$sheet->setCellValue('A3', 'Kho: ' . $check['warehouse_name']);
$sheet->setCellValue('A4', 'Khu vực: ' . ($check['zone_name'] ?: 'Toàn kho'));
$sheet->setCellValue('A5', 'Ngày kiểm kê: ' . date('d/m/Y', strtotime($check['scheduled_date'])));
$sheet->setCellValue('A6', 'Phương thức: ' . formatCheckType($check['check_type']));
$sheet->setCellValue('A7', 'Trạng thái: ' . formatStatus($check['status']));
$sheet->setCellValue('A8', 'Người tạo: ' . $check['created_by_name']);

if ($check['status'] === 'COMPLETED') {
    $sheet->setCellValue('A9', 'Ngày hoàn thành: ' . date('d/m/Y H:i:s', strtotime($check['completed_at'])));
    $sheet->setCellValue('A10', 'Tỷ lệ chính xác: ' . $accuracy . '%');
}

$sheet->setCellValue('A12', 'THỐNG KÊ KẾT QUẢ');
$sheet->setCellValue('A13', 'Tổng số sản phẩm kiểm kê: ' . $totalItems);
$sheet->setCellValue('A14', 'Số sản phẩm khớp: ' . $matchedItems);
$sheet->setCellValue('A15', 'Số sản phẩm thừa: ' . $excessItems);
$sheet->setCellValue('A16', 'Số sản phẩm thiếu: ' . $shortageItems);

// Tạo header bảng kết quả
$sheet->setCellValue('A18', 'STT');
$sheet->setCellValue('B18', 'Mã SP');
$sheet->setCellValue('C18', 'Tên Sản Phẩm');
$sheet->setCellValue('D18', 'Vị Trí');
$sheet->setCellValue('E18', 'Số Lượng Hệ Thống');
$sheet->setCellValue('F18', 'Số Lượng Thực Tế');
$sheet->setCellValue('G18', 'Chênh Lệch');
$sheet->setCellValue('H18', 'Ghi Chú');

// Điền dữ liệu
$row = 19;
foreach ($results as $index => $result) {
    $sheet->setCellValue('A' . $row, $index + 1);
    $sheet->setCellValue('B' . $row, $result['product_code']);
    $sheet->setCellValue('C' . $row, $result['product_name']);
    $sheet->setCellValue('D' . $row, $result['shelf_code'] . ($result['zone_code'] ? ' (' . $result['zone_code'] . ')' : ''));
    $sheet->setCellValue('E' . $row, $result['expected_quantity']);
    $sheet->setCellValue('F' . $row, $result['actual_quantity']);
    $sheet->setCellValue('G' . $row, $result['difference']);
    $sheet->setCellValue('H' . $row, $result['notes']);
    
    // Tô màu cho các dòng có chênh lệch
    if ($result['difference'] < 0) {
        $sheet->getStyle('G' . $row)->getFont()->getColor()->setARGB('FF0000'); // Màu đỏ cho thiếu
    } else if ($result['difference'] > 0) {
        $sheet->getStyle('G' . $row)->getFont()->getColor()->setARGB('008000'); // Màu xanh cho thừa
    }
    
    $row++;
}

// Định dạng bảng
$sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A12')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A18:H18')->getFont()->setBold(true);

// Định dạng cột
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(40);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(15);
$sheet->getColumnDimension('H')->setWidth(30);

// Xuất file Excel
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Bao_cao_kiem_ke_' . $check['check_code'] . '.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;

// Hàm định dạng loại kiểm kê
function formatCheckType($checkType) {
    switch ($checkType) {
        case 'AUTOMATIC_RFID':
            return 'RFID tự động';
        case 'MANUAL_BARCODE':
            return 'Barcode thủ công';
        case 'MIXED':
            return 'Kết hợp';
        default:
            return $checkType;
    }
}

// Hàm định dạng trạng thái
function formatStatus($status) {
    switch ($status) {
        case 'SCHEDULED':
            return 'Đã lên lịch';
        case 'IN_PROGRESS':
            return 'Đang thực hiện';
        case 'COMPLETED':
            return 'Đã hoàn thành';
        case 'CANCELLED':
            return 'Đã hủy';
        default:
            return $status;
    }
}
?>