<?php
session_start();
include_once '../config/connect.php';
include_once '../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để tiếp tục']);
    exit;
}

// Lấy thông tin người dùng hiện tại
$currentUserId = $_SESSION['user_id'] ?? 0;
if ($currentUserId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Phiên làm việc không hợp lệ']);
    exit;
}
// Xử lý các yêu cầu API
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    // Lấy danh sách phiếu xuất kho
    case 'getExportOrders':
        getExportOrders();
        break;
    
    // Lấy chi tiết phiếu xuất
    case 'getExportDetails':
        getExportDetails();
        break;
    
    // Lưu phiếu xuất dưới dạng nháp
    case 'saveAsDraft':
        saveExport('DRAFT');
        break;
    
    // Hoàn thành xuất kho
    case 'submitExport':
        saveExport('PENDING');
        break;
    
    // Duyệt phiếu xuất
    case 'approveExport':
        approveExport();
        break;
    
    // Hủy phiếu xuất
    case 'cancelExport':
        cancelExport();
        break;
    
    // Xóa phiếu xuất
    case 'deleteExport':
        deleteExport();
        break;
    
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}

// Hàm lấy danh sách phiếu xuất kho
function getExportOrders() {
    global $conn;
    
    // Lấy các tham số lọc
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10; // Số phiếu xuất trên mỗi trang
    $offset = ($page - 1) * $limit;
    
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Xây dựng câu truy vấn
    $query = "SELECT e.*, w.warehouse_name 
              FROM export_orders e
              JOIN warehouses w ON e.warehouse_id = w.warehouse_id
              WHERE 1=1";
    
    $countQuery = "SELECT COUNT(*) as total FROM export_orders e WHERE 1=1";
    
    // Thêm điều kiện lọc
    if (!empty($status)) {
        $query .= " AND e.status = '$status'";
        $countQuery .= " AND e.status = '$status'";
    }
    
    if ($warehouseId > 0) {
        $query .= " AND e.warehouse_id = $warehouseId";
        $countQuery .= " AND e.warehouse_id = $warehouseId";
    }
    
    if (!empty($search)) {
        $query .= " AND (e.export_code LIKE '%$search%' OR e.recipient LIKE '%$search%')";
        $countQuery .= " AND (e.export_code LIKE '%$search%' OR e.recipient LIKE '%$search%')";
    }
    
    // Thêm sắp xếp và phân trang
    $query .= " ORDER BY e.created_at DESC LIMIT $offset, $limit";
    
    // Thực hiện truy vấn
    $result = $conn->query($query);
    $countResult = $conn->query($countQuery);
    
    if ($result && $countResult) {
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        $totalRows = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($totalRows / $limit);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $conn->error]);
    }
}

// Hàm lấy chi tiết phiếu xuất
function getExportDetails() {
    global $conn;
    
    $exportId = isset($_GET['exportId']) ? (int)$_GET['exportId'] : 0;
    
    if ($exportId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID phiếu xuất không hợp lệ']);
        return;
    }
    
    // Lấy thông tin phiếu xuất
    $query = "SELECT e.*, w.warehouse_name, u.full_name AS created_by_name
              FROM export_orders e
              JOIN warehouses w ON e.warehouse_id = w.warehouse_id
              JOIN users u ON e.created_by = u.user_id
              WHERE e.export_id = $exportId";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $export = $result->fetch_assoc();
        
        // Lấy chi tiết sản phẩm trong phiếu xuất
        $detailsQuery = "SELECT d.*, p.product_code, p.product_name, s.shelf_code
                         FROM export_order_details d
                         JOIN products p ON d.product_id = p.product_id
                         LEFT JOIN shelves s ON d.shelf_id = s.shelf_id
                         WHERE d.export_id = $exportId";
        
        $detailsResult = $conn->query($detailsQuery);
        
        if ($detailsResult) {
            $details = [];
            while ($row = $detailsResult->fetch_assoc()) {
                $details[] = $row;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'export' => $export,
                'details' => $details
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết sản phẩm: ' . $conn->error]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu xuất']);
    }
}

// Hàm lưu phiếu xuất
function saveExport($status) {
    global $conn, $currentUserId;
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Lấy dữ liệu từ form
        $exportId = isset($_POST['exportId']) ? (int)$_POST['exportId'] : 0;
        $exportCode = $_POST['exportCode'];
        $warehouseId = (int)$_POST['warehouseId'];
        $recipient = $_POST['recipient'] ?? '';
        $recipientAddress = $_POST['recipientAddress'] ?? '';
        $orderReference = $_POST['orderReference'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $totalAmount = (float)$_POST['totalAmount'];
        
        // Kiểm tra mã phiếu xuất đã tồn tại chưa (nếu là tạo mới)
        if ($exportId === 0) {
            $checkQuery = "SELECT export_id FROM export_orders WHERE export_code = '$exportCode'";
            $checkResult = $conn->query($checkQuery);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                throw new Exception('Mã phiếu xuất đã tồn tại');
            }
        }
        
        // Nếu là cập nhật, xóa chi tiết phiếu xuất cũ
        if ($exportId > 0) {
            $deleteQuery = "DELETE FROM export_order_details WHERE export_id = $exportId";
            if (!$conn->query($deleteQuery)) {
                throw new Exception('Lỗi khi xóa chi tiết phiếu xuất cũ: ' . $conn->error);
            }
            
            // Cập nhật phiếu xuất
            $updateQuery = "UPDATE export_orders SET 
                           export_code = '$exportCode',
                           warehouse_id = $warehouseId,
                           recipient = '$recipient',
                           recipient_address = '$recipientAddress',
                           total_amount = $totalAmount,
                           status = '$status',
                           order_reference = '$orderReference',
                           notes = '$notes'
                           WHERE export_id = $exportId";
            
            if (!$conn->query($updateQuery)) {
                throw new Exception('Lỗi khi cập nhật phiếu xuất: ' . $conn->error);
            }
        } else {
            // Tạo mới phiếu xuất
            $insertQuery = "INSERT INTO export_orders (
                            export_code, warehouse_id, recipient, recipient_address, 
                            total_amount, status, order_reference, notes, created_by)
                            VALUES (
                            '$exportCode', $warehouseId, '$recipient', '$recipientAddress',
                            $totalAmount, '$status', '$orderReference', '$notes', $currentUserId)";
            
            if (!$conn->query($insertQuery)) {
                throw new Exception('Lỗi khi tạo phiếu xuất: ' . $conn->error);
            }
            
            $exportId = $conn->insert_id;
        }
        
              // Xử lý chi tiết sản phẩm
              if (isset($_POST['details']) && is_array($_POST['details'])) {
                foreach ($_POST['details'] as $detail) {
                    if (empty($detail['productId'])) continue;
                    
                    $productId = (int)$detail['productId'];
                    $quantity = (int)$detail['quantity'];
                    $unitPrice = (float)$detail['unitPrice'];
                    $shelfId = !empty($detail['shelfId']) ? (int)$detail['shelfId'] : 'NULL';
                    $shelfIdSql = $shelfId === 'NULL' ? 'NULL' : $shelfId;
                    
                    // Nếu trạng thái là PENDING, cần kiểm tra tồn kho đủ cho xuất
                    if ($status === 'PENDING') {
                        $checkStock = "SELECT SUM(quantity) as total_quantity 
                                      FROM inventory 
                                      WHERE product_id = $productId AND warehouse_id = $warehouseId";
                        $stockResult = $conn->query($checkStock);
                        
                        if ($stockResult && $row = $stockResult->fetch_assoc()) {
                            $availableQuantity = (int)$row['total_quantity'];
                            
                            if ($quantity > $availableQuantity) {
                                throw new Exception("Sản phẩm ID: $productId không đủ số lượng. Còn: $availableQuantity, cần: $quantity");
                            }
                        }
                    }
                    
                    // Thêm chi tiết phiếu xuất
                    $insertDetailQuery = "INSERT INTO export_order_details (
                                         export_id, product_id, quantity, unit_price, shelf_id)
                                         VALUES (
                                         $exportId, $productId, $quantity, $unitPrice, $shelfIdSql)";
                    
                    if (!$conn->query($insertDetailQuery)) {
                        throw new Exception('Lỗi khi thêm chi tiết phiếu xuất: ' . $conn->error);
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Lưu log
            $action = $exportId > 0 ? 'UPDATE_EXPORT' : 'CREATE_EXPORT';
            $description = "Tạo/cập nhật phiếu xuất kho: $exportCode";
            logUserAction($currentUserId, $action, $description);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Đã lưu phiếu xuất thành công', 'exportId' => $exportId]);
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $conn->rollback();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Hàm duyệt phiếu xuất
    function approveExport() {
        global $conn, $currentUserId;
        
        // Lấy dữ liệu từ request
        $data = json_decode(file_get_contents('php://input'), true);
        $exportId = isset($data['exportId']) ? (int)$data['exportId'] : 0;
        
        if ($exportId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID phiếu xuất không hợp lệ']);
            return;
        }
        
        // Bắt đầu transaction
        $conn->begin_transaction();
        
        try {
            // Kiểm tra trạng thái hiện tại của phiếu xuất
            $checkQuery = "SELECT status, export_code FROM export_orders WHERE export_id = $exportId";
            $checkResult = $conn->query($checkQuery);
            
            if (!$checkResult || $checkResult->num_rows === 0) {
                throw new Exception('Không tìm thấy phiếu xuất');
            }
            
            $row = $checkResult->fetch_assoc();
            if ($row['status'] !== 'PENDING') {
                throw new Exception('Chỉ phiếu xuất có trạng thái "Chờ duyệt" mới có thể được duyệt');
            }
            
            $exportCode = $row['export_code'];
            
            // Cập nhật trạng thái phiếu xuất thành COMPLETED
            $updateQuery = "UPDATE export_orders SET 
                           status = 'COMPLETED',
                           approved_by = $currentUserId,
                           approved_at = NOW()
                           WHERE export_id = $exportId";
            
            if (!$conn->query($updateQuery)) {
                throw new Exception('Lỗi khi cập nhật trạng thái phiếu xuất: ' . $conn->error);
            }
            
            // Lưu log
            $description = "Duyệt phiếu xuất kho: $exportCode";
            logUserAction($currentUserId, 'APPROVE_EXPORT', $description);
            
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Đã duyệt phiếu xuất thành công']);
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $conn->rollback();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Hàm hủy phiếu xuất
    function cancelExport() {
        global $conn, $currentUserId;
        
        // Lấy dữ liệu từ request
        $data = json_decode(file_get_contents('php://input'), true);
        $exportId = isset($data['exportId']) ? (int)$data['exportId'] : 0;
        
        if ($exportId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID phiếu xuất không hợp lệ']);
            return;
        }
        
        // Bắt đầu transaction
        $conn->begin_transaction();
        
        try {
            // Kiểm tra trạng thái hiện tại của phiếu xuất
            $checkQuery = "SELECT status, export_code FROM export_orders WHERE export_id = $exportId";
            $checkResult = $conn->query($checkQuery);
            
            if (!$checkResult || $checkResult->num_rows === 0) {
                throw new Exception('Không tìm thấy phiếu xuất');
            }
            
            $row = $checkResult->fetch_assoc();
            if ($row['status'] === 'COMPLETED') {
                throw new Exception('Không thể hủy phiếu xuất đã hoàn thành');
            }
            
            $exportCode = $row['export_code'];
            
            // Cập nhật trạng thái phiếu xuất thành CANCELLED
            $updateQuery = "UPDATE export_orders SET 
                           status = 'CANCELLED'
                           WHERE export_id = $exportId";
            
            if (!$conn->query($updateQuery)) {
                throw new Exception('Lỗi khi cập nhật trạng thái phiếu xuất: ' . $conn->error);
            }
            
            // Lưu log
            $description = "Hủy phiếu xuất kho: $exportCode";
            logUserAction($currentUserId, 'CANCEL_EXPORT', $description);
            
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Đã hủy phiếu xuất thành công']);
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $conn->rollback();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Hàm xóa phiếu xuất
    function deleteExport() {
        global $conn, $currentUserId;
        
        // Lấy dữ liệu từ request
        $data = json_decode(file_get_contents('php://input'), true);
        $exportId = isset($data['exportId']) ? (int)$data['exportId'] : 0;
        
        if ($exportId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID phiếu xuất không hợp lệ']);
            return;
        }
        
        // Bắt đầu transaction
        $conn->begin_transaction();
        
        try {
            // Kiểm tra trạng thái hiện tại của phiếu xuất
            $checkQuery = "SELECT status, export_code FROM export_orders WHERE export_id = $exportId";
            $checkResult = $conn->query($checkQuery);
            
            if (!$checkResult || $checkResult->num_rows === 0) {
                throw new Exception('Không tìm thấy phiếu xuất');
            }
            
            $row = $checkResult->fetch_assoc();
            if ($row['status'] === 'COMPLETED') {
                throw new Exception('Không thể xóa phiếu xuất đã hoàn thành');
            }
            
            $exportCode = $row['export_code'];
            
            // Xóa chi tiết phiếu xuất
            $deleteDetailQuery = "DELETE FROM export_order_details WHERE export_id = $exportId";
            if (!$conn->query($deleteDetailQuery)) {
                throw new Exception('Lỗi khi xóa chi tiết phiếu xuất: ' . $conn->error);
            }
            
            // Xóa phiếu xuất
            $deleteQuery = "DELETE FROM export_orders WHERE export_id = $exportId";
            if (!$conn->query($deleteQuery)) {
                throw new Exception('Lỗi khi xóa phiếu xuất: ' . $conn->error);
            }
            
            // Lưu log
            $description = "Xóa phiếu xuất kho: $exportCode";
            logUserAction($currentUserId, 'DELETE_EXPORT', $description);
            
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Đã xóa phiếu xuất thành công']);
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $conn->rollback();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Hàm lưu log hành động người dùng
    function logUserAction($userId, $actionType, $description) {
        global $conn;
        
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        $sql = "INSERT INTO user_logs (user_id, action_type, description, ip_address, user_agent) 
                VALUES ($userId, '$actionType', '$description', '$ipAddress', '$userAgent')";
        
        $conn->query($sql);
    }
    ?>