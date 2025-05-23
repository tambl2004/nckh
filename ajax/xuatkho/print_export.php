<?php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo '<div class="alert alert-danger">Bạn cần đăng nhập để thực hiện chức năng này</div>';
    exit;
}

// Lấy ID phiếu xuất từ request
$exportId = isset($_GET['export_id']) ? intval($_GET['export_id']) : 0;

if ($exportId <= 0) {
    echo '<div class="alert alert-danger">ID phiếu xuất không hợp lệ</div>';
    exit;
}

// Lấy thông tin phiếu xuất
$sql = "SELECT eo.*, w.warehouse_name, 
        u_created.full_name as created_by_name,
        u_approved.full_name as approved_by_name,
        DATE_FORMAT(eo.created_at, '%d/%m/%Y %H:%i') as created_at_formatted,
        DATE_FORMAT(eo.approved_at, '%d/%m/%Y %H:%i') as approved_at_formatted
        FROM export_orders eo
        JOIN warehouses w ON eo.warehouse_id = w.warehouse_id
        JOIN users u_created ON eo.created_by = u_created.user_id
        LEFT JOIN users u_approved ON eo.approved_by = u_approved.user_id
        WHERE eo.export_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$exportId]);
$export = $stmt->fetch();

if (!$export) {
    echo '<div class="alert alert-danger">Không tìm thấy phiếu xuất</div>';
    exit;
}

// Lấy chi tiết phiếu xuất
$sql = "SELECT eod.*, p.product_name, p.product_code, s.shelf_code
        FROM export_order_details eod
        JOIN products p ON eod.product_id = p.product_id
        LEFT JOIN shelves s ON eod.shelf_id = s.shelf_id
        WHERE eod.export_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$exportId]);
$details = $stmt->fetchAll();

// Hàm định dạng tiền tệ
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' đ';
}

// Tạo nội dung phiếu xuất để in
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu xuất kho <?php echo $export['export_code']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .print-header p {
            margin: 5px 0;
        }
        .export-info {
            margin-bottom: 20px;
        }
        .export-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .export-info table td {
            padding: 5px;
        }
        .export-details table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .export-details table th,
        .export-details table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .export-details table th {
            background-color: #f2f2f2;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature-block {
            text-align: center;
            width: 30%;
        }
        .signature-block p {
            margin: 5px 0;
        }
        .signature-line {
            margin: 50px 0 10px;
            border-top: 1px dotted #000;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h1>Phiếu xuất kho</h1>
        <p>Mã phiếu: <?php echo $export['export_code']; ?></p>
        <p>Ngày tạo: <?php echo $export['created_at_formatted']; ?></p>
    </div>
    
    <div class="export-info">
        <table>
            <tr>
                <td width="50%"><strong>Kho xuất:</strong> <?php echo $export['warehouse_name']; ?></td>
                <td width="50%"><strong>Người nhận:</strong> <?php echo $export['recipient']; ?></td>
            </tr>
            <tr>
                <td><strong>Người lập phiếu:</strong> <?php echo $export['created_by_name']; ?></td>
                <td><strong>Địa chỉ nhận:</strong> <?php echo $export['recipient_address'] ?: 'N/A'; ?></td>
            </tr>
            <tr>
                <td><strong>Mã đơn hàng liên kết:</strong> <?php echo $export['order_reference'] ?: 'N/A'; ?></td>
                <td><strong>Trạng thái:</strong> 
                    <?php 
                    switch($export['status']) {
                        case 'DRAFT': echo 'Nháp'; break;
                        case 'PENDING': echo 'Chờ duyệt'; break;
                        case 'COMPLETED': echo 'Đã duyệt'; break;
                        case 'CANCELLED': echo 'Đã hủy'; break;
                        default: echo $export['status'];
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td colspan="2"><strong>Ghi chú:</strong> <?php echo $export['notes'] ?: 'Không có ghi chú'; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="export-details">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã sản phẩm</th>
                    <th>Tên sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                    <th>Vị trí kệ</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $index = 1;
                foreach ($details as $detail): 
                ?>
                <tr>
                    <td><?php echo $index++; ?></td>
                    <td><?php echo $detail['product_code']; ?></td>
                    <td><?php echo $detail['product_name']; ?></td>
                    <td><?php echo $detail['quantity']; ?></td>
                    <td><?php echo formatCurrency($detail['unit_price']); ?></td>
                    <td><?php echo formatCurrency($detail['total_price']); ?></td>
                    <td><?php echo $detail['shelf_code'] ?: 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right;"><strong>Tổng cộng:</strong></td>
                    <td colspan="2"><strong><?php echo formatCurrency($export['total_amount']); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="signatures">
        <div class="signature-block">
            <p><strong>Người lập phiếu</strong></p>
            <div class="signature-line"></div>
            <p><?php echo $export['created_by_name']; ?></p>
        </div>
        
        <div class="signature-block">
            <p><strong>Người giao hàng</strong></p>
            <div class="signature-line"></div>
            <p></p>
        </div>
        
        <div class="signature-block">
            <p><strong>Người nhận hàng</strong></p>
            <div class="signature-line"></div>
            <p><?php echo $export['recipient']; ?></p>
        </div>
    </div>
    
    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print();" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            In phiếu xuất
        </button>
        <button onclick="window.close();" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Đóng
        </button>
    </div>
</body>
</html>