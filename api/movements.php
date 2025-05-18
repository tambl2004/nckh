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
    // Lấy danh sách di chuyển nội bộ
    case 'getInventoryMovements':
        getInventoryMovements();
        break;
    
    // Lấy chi tiết phiếu di chuyển
    case 'getMovementDetails':
        getMovementDetails();
        break;
    
    // Tạo phiếu di chuyển
    case 'createMovement':
        createMovement();
        break;
    
    // Cập nhật trạng thái phiếu di chuyển
    case 'updateMovementStatus':
        updateMovementStatus();
        break;
    
    // Hủy phiếu di chuyển
    case 'cancelMovement':
        cancelMovement();
        break;
    
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}

// Hàm lấy danh sách di chuyển nội bộ
function getInventoryMovements() {
    global $conn;
    
    // Lấy các tham số lọc
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10; // Số phiếu di chuyển trên mỗi trang
    $offset = ($page - 1) * $limit;
    
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $sourceWarehouseId = isset($_GET['sourceWarehouseId']) ? (int)$_GET['sourceWarehouseId'] : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Xây dựng câu truy vấn
    $query = "SELECT m.*, p.product_name, p.product_code,
              sw.warehouse_name as source_warehouse, 
              tw.warehouse_name as target_warehouse
              FROM inventory_movements m
              JOIN products p ON m.product_id = p.product_id
              JOIN warehouses sw ON m.source_warehouse_id = sw.warehouse_id
              JOIN warehouses tw ON m.target_warehouse_id = tw.warehouse_id
              WHERE 1=1";
    
    $countQuery = "SELECT COUNT(*) as total FROM inventory_movements m WHERE 1=1";
    
    // Thêm điều kiện lọc
    if (!empty($status)) {
        $query .= " AND m.status = '$status'";
        $countQuery .= " AND m.status = '$status'";
    }
    
    if ($sourceWarehouseId > 0) {
        $query .= " AND m.source_warehouse_id = $sourceWarehouseId";
        $countQuery .= " AND m.source_warehouse_id = $sourceWarehouseId";
    }
    
    if (!empty($search)) {
        $query .= " AND (m.movement_code LIKE '%$search%' OR p.product_name LIKE '%$search%' OR p.product_code LIKE '%$search%')";
        $countQuery .= " AND (m.movement_code LIKE '%$search%' OR EXISTS (SELECT 1 FROM products p WHERE p.product_id = m.product_id AND (p.product_name LIKE '%$search%' OR p.product_code LIKE '%$search%')))";
    }
    
    // Thêm sắp xếp và phân trang
    $query .= " ORDER BY m.created_at DESC LIMIT $offset, $limit";
    
    // Thực hiện truy vấn
    $result = $conn->query($query);
    $countResult = $conn->query($countQuery);
    
    if ($result && $countResult) {
        $movements = [];
        while ($row = $result->fetch_assoc()) {
            $movements[] = $row;
        }
        
        $totalRows = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($totalRows / $limit);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'movements' => $movements,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $conn->error]);
    }
}

// Hàm lấy chi tiết phiếu di chuyển
function getMovementDetails() {
    global $conn;
    
    $movementId = isset($_GET['movementId']) ? (int)$_GET['movementId'] : 0;
    
    if ($movementId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID phiếu di chuyển không hợp lệ']);
        return;
    }
    
    // Lấy thông tin phiếu di chuyển
    $query = "SELECT m.*, p.product_name, p.product_code,
              sw.warehouse_name as source_warehouse, 
              tw.warehouse_name as target_warehouse,
              ss.shelf_code as source_shelf,
              ts.shelf_code as target_shelf,
              u.full_name as created_by_name
              FROM inventory_movements m
              JOIN products p ON m.product_id = p.product_id
              JOIN warehouses sw ON m.source_warehouse_id = sw.warehouse_id
              JOIN warehouses tw ON m.target_warehouse_id = tw.warehouse_id
              LEFT JOIN shelves ss ON m.source_shelf_id = ss.shelf_id
              LEFT JOIN shelves ts ON m.target_shelf_id = ts.shelf_id
              JOIN users u ON m.created_by = u.user_id
              WHERE m.movement_id = $movementId";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $movement = $result->fetch_assoc();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'movement' => $movement
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu di chuyển']);
    }
}

// Hàm tạo phiếu di chuyển
function createMovement() {
    global $conn, $currentUserId;
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Lấy dữ liệu từ form
        $movementCode = $_POST['movementCode'];
        $productId = (int)$_POST['productId'];
        $sourceWarehouseId = (int)$_POST['sourceWarehouseId'];
        $sourceShelfId = !empty($_POST['sourceShelfId']) ? (int)$_POST['sourceShelfId'] : null;
        $targetWarehouseId = (int)$_POST['targetWarehouseId'];
        $targetShelfId = !empty($_POST['targetShelfId']) ? (int)$_POST['targetShelfId'] : null;
        $quantity = (int)$_POST['quantity'];
        $batchNumber = $_POST['batchNumber'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        // Kiểm tra mã phiếu di chuyển đã tồn tại chưa
        $checkQuery = "SELECT movement_id FROM inventory_movements WHERE movement_code = '$movementCode'";
        $checkResult = $conn->query($checkQuery);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            throw new Exception('Mã phiếu di chuyển đã tồn tại');
        }
        
        // Kiểm tra số lượng tồn kho đủ cho di chuyển
        $checkStock = "SELECT SUM(quantity) as total_quantity 
                      FROM inventory 
                      WHERE product_id = $productId AND warehouse_id = $sourceWarehouseId";
        $stockResult = $conn->query($checkStock);
        
        if ($stockResult && $row = $stockResult->fetch_assoc()) {
            $availableQuantity = (int)$row['total_quantity'];
            
            if ($quantity > $availableQuantity) {
                throw new Exception("Không đủ số lượng tồn kho. Còn: $availableQuantity, cần: $quantity");
            }
        }
        
        // Tạo phiếu di chuyển
        $sourceShelfSql = $sourceShelfId ? $sourceShelfId : 'NULL';
        $targetShelfSql = $targetShelfId ? $targetShelfId : 'NULL';
        
        $insertQuery = "INSERT INTO inventory_movements (
                        movement_code, product_id, source_warehouse_id, source_shelf_id,
                        target_warehouse_id, target_shelf_id, quantity, batch_number,
                        status, reason, created_by)
                        VALUES (
                        '$movementCode', $productId, $sourceWarehouseId, $sourceShelfSql,
                        $targetWarehouseId, $targetShelfSql, $quantity, '$batchNumber',
                        'PENDING', '$reason', $currentUserId)";
        
        if (!$conn->query($insertQuery)) {
            throw new Exception('Lỗi khi tạo phiếu di chuyển: ' . $conn->error);
        }
        
        $movementId = $conn->insert_id;
        
        // Lưu log
        $description = "Tạo phiếu di chuyển nội bộ: $movementCode";
        logUserAction($currentUserId, 'CREATE_MOVEMENT', $description);
        
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Đã tạo phiếu di chuyển thành công', 'movementId' => $movementId]);
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Hàm cập nhật trạng thái phiếu di chuyển
function updateMovementStatus() {
    global $conn, $currentUserId;
    
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    $movementId = isset($data['movementId']) ? (int)$data['movementId'] : 0;
    $status = isset($data['status']) ? $data['status'] : '';
    
    if ($movementId <= 0 || !in_array($status, ['IN_TRANSIT', 'COMPLETED'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Kiểm tra trạng thái hiện tại của phiếu di chuyển
        $checkQuery = "SELECT status, movement_code, product_id, quantity, 
                      source_warehouse_id, target_warehouse_id, 
                      source_shelf_id, target_shelf_id, batch_number
                      FROM inventory_movements WHERE movement_id = $movementId";
        $checkResult = $conn->query($checkQuery);
        
        if (!$checkResult || $checkResult->num_rows === 0) {
            throw new Exception('Không tìm thấy phiếu di chuyển');
        }
        
        $movement = $checkResult->fetch_assoc();
        
        // Kiểm tra trạng thái có hợp lệ không
        if ($status === 'IN_TRANSIT' && $movement['status'] !== 'PENDING') {
            throw new Exception('Chỉ phiếu có trạng thái "Chờ xử lý" mới có thể chuyển sang "Đang vận chuyển"');
        }
        
        if ($status === 'COMPLETED' && $movement['status'] !== 'IN_TRANSIT') {
            throw new Exception('Chỉ phiếu có trạng thái "Đang vận chuyển" mới có thể chuyển sang "Hoàn thành"');
        }
        
        // Nếu hoàn thành di chuyển, cập nhật tồn kho
        if ($status === 'COMPLETED') {
            $productId = $movement['product_id'];
            $quantity = $movement['quantity'];
            $sourceWarehouseId = $movement['source_warehouse_id'];
            $targetWarehouseId = $movement['target_warehouse_id'];
            $sourceShelfId = $movement['source_shelf_id'];
            $targetShelfId = $movement['target_shelf_id'];
            $batchNumber = $movement['batch_number'];
            
            // Giảm số lượng trong kho nguồn
            $updateSourceQuery = "UPDATE inventory SET 
                                quantity = quantity - $quantity 
                                WHERE product_id = $productId AND warehouse_id = $sourceWarehouseId";
            
            if (!$conn->query($updateSourceQuery)) {
                throw new Exception('Lỗi khi cập nhật tồn kho nguồn: ' . $conn->error);
            }
            
            // Giảm số lượng trong vị trí kệ nguồn nếu có
            if ($sourceShelfId) {
                $updateSourceShelfQuery = "UPDATE product_locations SET 
                                         quantity = quantity - $quantity 
                                         WHERE product_id = $productId AND shelf_id = $sourceShelfId";
                
                if (!$conn->query($updateSourceShelfQuery)) {
                    throw new Exception('Lỗi khi cập nhật vị trí nguồn: ' . $conn->error);
                }
                
                // Xóa bản ghi nếu số lượng là 0
                $deleteEmptyLocationQuery = "DELETE FROM product_locations 
                                           WHERE product_id = $productId AND shelf_id = $sourceShelfId AND quantity <= 0";
                $conn->query($deleteEmptyLocationQuery);
            }
            
            // Tăng số lượng trong kho đích
            $checkTargetQuery = "SELECT inventory_id FROM inventory 
                               WHERE product_id = $productId AND warehouse_id = $targetWarehouseId";
            $targetResult = $conn->query($checkTargetQuery);
            
            if ($targetResult && $targetResult->num_rows > 0) {
                // Cập nhật nếu đã tồn tại
                $updateTargetQuery = "UPDATE inventory SET 
                                   quantity = quantity + $quantity 
                                   WHERE product_id = $productId AND warehouse_id = $targetWarehouseId";
                
                if (!$conn->query($updateTargetQuery)) {
                    throw new Exception('Lỗi khi cập nhật tồn kho đích: ' . $conn->error);
                }
            } else {
                // Thêm mới nếu chưa tồn tại
                $insertTargetQuery = "INSERT INTO inventory (product_id, warehouse_id, quantity) 
                                    VALUES ($productId, $targetWarehouseId, $quantity)";
                
                if (!$conn->query($insertTargetQuery)) {
                    throw new Exception('Lỗi khi thêm mới tồn kho đích: ' . $conn->error);
                }
            }
            
            // Cập nhật vị trí kệ đích nếu có
            if ($targetShelfId) {
                $checkTargetShelfQuery = "SELECT location_id FROM product_locations 
                                        WHERE product_id = $productId AND shelf_id = $targetShelfId";
                $targetShelfResult = $conn->query($checkTargetShelfQuery);
                
                if ($targetShelfResult && $targetShelfResult->num_rows > 0) {
                    // Cập nhật nếu đã tồn tại
                    $updateTargetShelfQuery = "UPDATE product_locations SET 
                                            quantity = quantity + $quantity 
                                            WHERE product_id = $productId AND shelf_id = $targetShelfId";
                    
                    if (!$conn->query($updateTargetShelfQuery)) {
                        throw new Exception('Lỗi khi cập nhật vị trí đích: ' . $conn->error);
                    }
                } else {
                    // Thêm mới nếu chưa tồn tại
                    $insertTargetShelfQuery = "INSERT INTO product_locations 
                                            (product_id, shelf_id, batch_number, quantity, entry_date) 
                                            VALUES ($productId, $targetShelfId, '$batchNumber', $quantity, NOW())";
                    
                    if (!$conn->query($insertTargetShelfQuery)) {
                        throw new Exception('Lỗi khi thêm mới vị trí đích: ' . $conn->error);
                    }
                }
            }
        }
        
        // Cập nhật trạng thái phiếu di chuyển
        $updateQuery = "UPDATE inventory_movements SET 
                       status = '$status'" . 
                       ($status === 'COMPLETED' ? ", completed_at = NOW()" : "") . 
                       " WHERE movement_id = $movementId";
        
        if (!$conn->query($updateQuery)) {
            throw new Exception('Lỗi khi cập nhật trạng thái phiếu di chuyển: ' . $conn->error);
        }
        
        // Lưu log
        $statusText = $status === 'IN_TRANSIT' ? 'Đang vận chuyển' : 'Hoàn thành';
        $description = "Cập nhật trạng thái phiếu di chuyển {$movement['movement_code']} thành $statusText";
        logUserAction($currentUserId, 'UPDATE_MOVEMENT_STATUS', $description);
        
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => "Đã cập nhật trạng thái thành $statusText"]);
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Hàm hủy phiếu di chuyển
function cancelMovement() {
    global $conn, $currentUserId;
    
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    $movementId = isset($data['movementId']) ? (int)$data['movementId'] : 0;
    
    if ($movementId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID phiếu di chuyển không hợp lệ']);
        return;
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Kiểm tra trạng thái hiện tại của phiếu di chuyển
        $checkQuery = "SELECT status, movement_code FROM inventory_movements WHERE movement_id = $movementId";
        $checkResult = $conn->query($checkQuery);
        
        if (!$checkResult || $checkResult->num_rows === 0) {
            throw new Exception('Không tìm thấy phiếu di chuyển');
        }
        
        $row = $checkResult->fetch_assoc();
        if ($row['status'] === 'COMPLETED') {
            throw new Exception('Không thể hủy phiếu di chuyển đã hoàn thành');
        }
        
        $movementCode = $row['movement_code'];
        
        // Cập nhật trạng thái phiếu di chuyển thành CANCELLED
        $updateQuery = "UPDATE inventory_movements SET 
                       status = 'CANCELLED'
                       WHERE movement_id = $movementId";
        
        if (!$conn->query($updateQuery)) {
            throw new Exception('Lỗi khi cập nhật trạng thái phiếu di chuyển: ' . $conn->error);
        }
        
        // Lưu log
        $description = "Hủy phiếu di chuyển nội bộ: $movementCode";
        logUserAction($currentUserId, 'CANCEL_MOVEMENT', $description);
        
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Đã hủy phiếu di chuyển thành công']);
        
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