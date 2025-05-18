<?php
// Kết nối database và khởi tạo session
require_once '../config/database.php';
require_once '../config/functions.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng chức năng này']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Xử lý các hành động AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'getInventoryChecks':
            getInventoryChecks($conn, $user_id);
            break;
        case 'getCheckDetails':
            getCheckDetails($conn);
            break;
        case 'getCheckResults':
            getCheckResults($conn);
            break;
        case 'getCheckHistory':
            getCheckHistory($conn);
            break;
        case 'getCompletedChecks':
            getCompletedChecks($conn);
            break;
        case 'getCheckReport':
            getCheckReport($conn);
            break;
        case 'generateDiscrepancyReport':
            generateDiscrepancyReport($conn);
            break;
        case 'simulateRfidScan':
            simulateRfidScan($conn);
            break;
        case 'exportToExcel':
            exportToExcel($conn);
            break;
        case 'exportToPDF':
            exportToPDF($conn);
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xử lý dữ liệu từ form hoặc JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

    switch ($action) {
        case 'saveInventoryCheck':
            saveInventoryCheck($conn, $user_id);
            break;
        case 'updateCheckStatus':
            updateCheckStatus($conn, $user_id);
            break;
        case 'addCheckResult':
            addCheckResult($conn, $user_id);
            break;
        case 'updateCheckResult':
            updateCheckResult($conn, $user_id);
            break;
        case 'completeCheck':
            completeCheck($conn, $user_id);
            break;
        case 'cancelCheck':
            cancelCheck($conn, $user_id);
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
            break;
    }
}

// Hàm lấy danh sách phiếu kiểm kê
function getInventoryChecks($conn, $user_id) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $warehouseId = isset($_GET['warehouseId']) ? $_GET['warehouseId'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $conditions = [];
    $params = [];

    if (!empty($status)) {
        $conditions[] = "ic.status = ?";
        $params[] = $status;
    }

    if (!empty($warehouseId)) {
        $conditions[] = "ic.warehouse_id = ?";
        $params[] = $warehouseId;
    }

    if (!empty($search)) {
        $conditions[] = "ic.check_code LIKE ?";
        $params[] = "%$search%";
    }

    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    // Đếm tổng số phiếu kiểm kê
    $countQuery = "SELECT COUNT(*) as total FROM inventory_checks ic $whereClause";
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $limit);

    // Lấy danh sách phiếu kiểm kê với phân trang
    $query = "SELECT ic.*, w.warehouse_name, wz.zone_name 
              FROM inventory_checks ic 
              LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
              LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
              $whereClause
              ORDER BY ic.created_at DESC
              LIMIT $offset, $limit";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $checks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'checks' => $checks,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
}

// Hàm lấy chi tiết phiếu kiểm kê
function getCheckDetails($conn) {
    $checkId = isset($_GET['checkId']) ? intval($_GET['checkId']) : 0;

    if ($checkId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID phiếu kiểm kê không hợp lệ']);
        return;
    }

    $query = "SELECT ic.*, w.warehouse_name, wz.zone_name, u.fullname as created_by_name
              FROM inventory_checks ic 
              LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
              LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
              LEFT JOIN users u ON ic.created_by = u.user_id
              WHERE ic.check_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([$checkId]);
    $check = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$check) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu kiểm kê']);
        return;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'check' => $check]);
}

// Hàm lấy kết quả kiểm kê
function getCheckResults($conn) {
    $checkId = isset($_GET['checkId']) ? intval($_GET['checkId']) : 0;

    if ($checkId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID phiếu kiểm kê không hợp lệ']);
        return;
    }

    $query = "SELECT icr.*, p.product_code, p.product_name, s.shelf_code 
              FROM inventory_check_results icr
              LEFT JOIN products p ON icr.product_id = p.product_id
              LEFT JOIN shelves s ON icr.shelf_id = s.shelf_id
              WHERE icr.check_id = ?
              ORDER BY icr.created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute([$checkId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'results' => $results]);
}

// Hàm lưu phiếu kiểm kê mới
function saveInventoryCheck($conn, $user_id) {
    $checkId = isset($_POST['checkId']) ? intval($_POST['checkId']) : 0;
    $checkCode = isset($_POST['checkCode']) ? $_POST['checkCode'] : '';
    $warehouseId = isset($_POST['warehouseId']) ? intval($_POST['warehouseId']) : 0;
    $zoneId = isset($_POST['zoneId']) && !empty($_POST['zoneId']) ? intval($_POST['zoneId']) : null;
    $checkType = isset($_POST['checkType']) ? $_POST['checkType'] : '';
    $scheduledDate = isset($_POST['scheduledDate']) ? $_POST['scheduledDate'] : '';
    $scheduledTime = isset($_POST['scheduledTime']) ? $_POST['scheduledTime'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'SCHEDULED';

    // Kiểm tra dữ liệu
    if (empty($checkCode) || $warehouseId <= 0 || empty($checkType) || empty($scheduledDate) || empty($scheduledTime)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
        return;
    }

    try {
        $conn->beginTransaction();

        if ($checkId > 0) {
            // Cập nhật phiếu kiểm kê
            $query = "UPDATE inventory_checks 
                      SET check_code = ?, warehouse_id = ?, zone_id = ?, check_type = ?, 
                          scheduled_date = ?, scheduled_time = ?, notes = ?, updated_at = NOW() 
                      WHERE check_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$checkCode, $warehouseId, $zoneId, $checkType, $scheduledDate, $scheduledTime, $notes, $checkId]);
        } else {
            // Tạo phiếu kiểm kê mới
            $query = "INSERT INTO inventory_checks (check_code, warehouse_id, zone_id, check_type, scheduled_date, 
                                                    scheduled_time, notes, status, created_by, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($query);
            $stmt->execute([$checkCode, $warehouseId, $zoneId, $checkType, $scheduledDate, $scheduledTime, $notes, $status, $user_id]);
            $checkId = $conn->lastInsertId();
        }

        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Lưu phiếu kiểm kê thành công', 'checkId' => $checkId]);
    } catch (PDOException $e) {
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu phiếu kiểm kê: ' . $e->getMessage()]);
    }
}

// Hàm cập nhật trạng thái phiếu kiểm kê
function updateCheckStatus($conn, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $checkId = isset($input['checkId']) ? intval($input['checkId']) : 0;
    $status = isset($input['status']) ? $input['status'] : '';

    if ($checkId <= 0 || empty($status)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }

    try {
        $query = "UPDATE inventory_checks SET status = ?, updated_at = NOW() WHERE check_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$status, $checkId]);

        // Nếu hoàn thành, cập nhật thời gian hoàn thành
        if ($status === 'COMPLETED') {
            $query = "UPDATE inventory_checks SET completed_at = NOW() WHERE check_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$checkId]);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()]);
    }
}

// Hàm thêm kết quả kiểm kê
function addCheckResult($conn, $user_id) {
    $checkId = isset($_POST['checkId']) ? intval($_POST['checkId']) : 0;
    $productId = isset($_POST['productId']) ? intval($_POST['productId']) : 0;
    $shelfId = isset($_POST['shelfId']) && !empty($_POST['shelfId']) ? intval($_POST['shelfId']) : null;
    $expectedQuantity = isset($_POST['expectedQuantity']) ? intval($_POST['expectedQuantity']) : 0;
    $actualQuantity = isset($_POST['actualQuantity']) ? intval($_POST['actualQuantity']) : 0;
    $batchNumber = isset($_POST['batchNumber']) ? $_POST['batchNumber'] : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    if ($checkId <= 0 || $productId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }

    try {
        // Kiểm tra xem sản phẩm đã tồn tại trong kết quả kiểm kê chưa
        $query = "SELECT result_id FROM inventory_check_results 
                  WHERE check_id = ? AND product_id = ? AND (shelf_id = ? OR (shelf_id IS NULL AND ? IS NULL))";
        $stmt = $conn->prepare($query);
        $stmt->execute([$checkId, $productId, $shelfId, $shelfId]);
        $existingResult = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingResult) {
            // Cập nhật kết quả hiện có
            $query = "UPDATE inventory_check_results 
                      SET expected_quantity = ?, actual_quantity = ?, batch_number = ?, notes = ?, updated_at = NOW() 
                      WHERE result_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$expectedQuantity, $actualQuantity, $batchNumber, $notes, $existingResult['result_id']]);
            $resultId = $existingResult['result_id'];
        } else {
            // Thêm kết quả mới
            $query = "INSERT INTO inventory_check_results (check_id, product_id, shelf_id, expected_quantity, 
                                                        actual_quantity, batch_number, notes, created_by, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($query);
            $stmt->execute([$checkId, $productId, $shelfId, $expectedQuantity, $actualQuantity, $batchNumber, $notes, $user_id]);
            $resultId = $conn->lastInsertId();
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Lưu kết quả kiểm kê thành công', 'resultId' => $resultId]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu kết quả kiểm kê: ' . $e->getMessage()]);
    }
}

// Hàm cập nhật kết quả kiểm kê
function updateCheckResult($conn, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $resultId = isset($input['resultId']) ? intval($input['resultId']) : 0;
    $actualQuantity = isset($input['actualQuantity']) ? intval($input['actualQuantity']) : 0;

    if ($resultId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID kết quả không hợp lệ']);
        return;
    }

    try {
        $query = "UPDATE inventory_check_results SET actual_quantity = ?, updated_at = NOW() WHERE result_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$actualQuantity, $resultId]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Cập nhật kết quả kiểm kê thành công']);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật kết quả kiểm kê: ' . $e->getMessage()]);
    }
}

// Hàm hoàn thành kiểm kê
function completeCheck($conn, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $checkId = isset($input['checkId']) ? intval($input['checkId']) : 0;

    if ($checkId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID phiếu kiểm kê không hợp lệ']);
        return;
    }

    try {
        $conn->beginTransaction();

        // Cập nhật trạng thái phiếu kiểm kê thành COMPLETED
        $query = "UPDATE inventory_checks SET status = 'COMPLETED', completed_at = NOW(), updated_at = NOW() WHERE check_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$checkId]);

        // Trong thực tế, có thể cần cập nhật tồn kho thực tế dựa trên kết quả kiểm kê
        // ...

        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Hoàn thành kiểm kê thành công']);
    } catch (PDOException $e) {
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi hoàn thành kiểm kê: ' . $e->getMessage()]);
    }
}

// Hàm hủy phiếu kiểm kê
function cancelCheck($conn, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $checkId = isset($input['checkId']) ? intval($input['checkId']) : 0;

    if ($checkId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID phiếu kiểm kê không hợp lệ']);
        return;
    }

    try {
        $query = "UPDATE inventory_checks SET status = 'CANCELLED', updated_at = NOW() WHERE check_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$checkId]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Hủy phiếu kiểm kê thành công']);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi hủy phiếu kiểm kê: ' . $e->getMessage()]);
    }
}

// Hàm lấy lịch sử kiểm kê
function getCheckHistory($conn) {
    $warehouseId = isset($_GET['warehouseId']) ? intval($_GET['warehouseId']) : 0;
    $dateFrom = isset($_GET['dateFrom']) && !empty($_GET['dateFrom']) ? $_GET['dateFrom'] : null;
    $dateTo = isset($_GET['dateTo']) && !empty($_GET['dateTo']) ? $_GET['dateTo'] : null;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $conditions = ["ic.status = 'COMPLETED'"];
    $params = [];

    if ($warehouseId > 0) {
        $conditions[] = "ic.warehouse_id = ?";
        $params[] = $warehouseId;
    }

    if ($dateFrom) {
        $conditions[] = "DATE(ic.completed_at) >= ?";
        $params[] = $dateFrom;
    }

    if ($dateTo) {
        $conditions[] = "DATE(ic.completed_at) <= ?";
        $params[] = $dateTo;
    }

    $whereClause = implode(" AND ", $conditions);

    // Đếm tổng số lịch sử
    $countQuery = "SELECT COUNT(*) as total FROM inventory_checks ic WHERE $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->execute($params);
    $totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $limit);

    // Lấy lịch sử kiểm kê
    $query = "SELECT ic.*, 
                     w.warehouse_name, 
                     wz.zone_name, 
                     u.fullname as created_by_name,
                     (SELECT COUNT(*) FROM inventory_check_results WHERE check_id = ic.check_id) as total_products,
                     (SELECT COUNT(*) FROM inventory_check_results 
                      WHERE check_id = ic.check_id AND expected_quantity != actual_quantity) as diff_products
              FROM inventory_checks ic 
              LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
              LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
              LEFT JOIN users u ON ic.created_by = u.user_id
              WHERE $whereClause
              ORDER BY ic.completed_at DESC
              LIMIT $offset, $limit";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'history' => $history,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
}

// Hàm lấy danh sách phiếu kiểm kê đã hoàn thành
function getCompletedChecks($conn) {
$query = "SELECT ic.check_id, ic.check_code, w.warehouse_name, ic.completed_at
          FROM inventory_checks ic
          LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
          WHERE ic.status = 'COMPLETED'
          ORDER BY ic.completed_at DESC
          LIMIT 100";

$stmt = $conn->prepare($query);
$stmt->execute();
$checks = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'checks' => $checks]);
}

// Hàm lấy báo cáo kiểm kê chi tiết
function getCheckReport($conn) {
$checkId = isset($_GET['checkId']) ? intval($_GET['checkId']) : 0;

if ($checkId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID phiếu kiểm kê không hợp lệ']);
    return;
}

// Lấy thông tin phiếu kiểm kê
$query = "SELECT ic.*, w.warehouse_name, wz.zone_name, u.fullname as created_by_name
          FROM inventory_checks ic 
          LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
          LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
          LEFT JOIN users u ON ic.created_by = u.user_id
          WHERE ic.check_id = ?";

$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$check = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$check) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu kiểm kê']);
    return;
}

// Lấy kết quả kiểm kê chi tiết
$query = "SELECT icr.*, p.product_code, p.product_name, s.shelf_code, p.price
          FROM inventory_check_results icr
          LEFT JOIN products p ON icr.product_id = p.product_id
          LEFT JOIN shelves s ON icr.shelf_id = s.shelf_id
          WHERE icr.check_id = ?
          ORDER BY p.product_code";

$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính toán thống kê
$totalProducts = count($results);
$matchedProducts = 0;
$diffProducts = 0;

foreach ($results as $result) {
    if ($result['expected_quantity'] == $result['actual_quantity']) {
        $matchedProducts++;
    } else {
        $diffProducts++;
    }
}

$stats = [
    'total_products' => $totalProducts,
    'matched_products' => $matchedProducts,
    'diff_products' => $diffProducts
];

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'report' => [
        'check_id' => $check['check_id'],
        'check_code' => $check['check_code'],
        'warehouse_id' => $check['warehouse_id'],
        'warehouse_name' => $check['warehouse_name'],
        'zone_id' => $check['zone_id'],
        'zone_name' => $check['zone_name'],
        'check_type' => $check['check_type'],
        'scheduled_date' => $check['scheduled_date'],
        'scheduled_time' => $check['scheduled_time'],
        'completed_at' => $check['completed_at'],
        'created_by_name' => $check['created_by_name'],
        'notes' => $check['notes'],
        'results' => $results,
        'stats' => $stats
    ]
]);
}

// Hàm tạo báo cáo chênh lệch
function generateDiscrepancyReport($conn) {
$checkId = isset($_GET['checkId']) ? intval($_GET['checkId']) : 0;
$warehouseId = isset($_GET['warehouseId']) ? intval($_GET['warehouseId']) : 0;

if ($checkId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID phiếu kiểm kê không hợp lệ']);
    return;
}

// Lấy thông tin phiếu kiểm kê
$query = "SELECT ic.*, w.warehouse_name, wz.zone_name, u.fullname as created_by_name
          FROM inventory_checks ic 
          LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
          LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
          LEFT JOIN users u ON ic.created_by = u.user_id
          WHERE ic.check_id = ?";

$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$check = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$check) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu kiểm kê']);
    return;
}

// Lấy danh sách sản phẩm chênh lệch
$query = "SELECT icr.*, p.product_code, p.product_name, s.shelf_code, p.price
          FROM inventory_check_results icr
          LEFT JOIN products p ON icr.product_id = p.product_id
          LEFT JOIN shelves s ON icr.shelf_id = s.shelf_id
          WHERE icr.check_id = ? AND icr.expected_quantity != icr.actual_quantity
          ORDER BY ABS(icr.expected_quantity - icr.actual_quantity) DESC";

$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$discrepancies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính toán thống kê
$query = "SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN expected_quantity = actual_quantity THEN 1 ELSE 0 END) as matched_products,
            SUM(CASE WHEN expected_quantity != actual_quantity THEN 1 ELSE 0 END) as diff_products
          FROM inventory_check_results
          WHERE check_id = ?";

$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'report' => [
        'check_id' => $check['check_id'],
        'check_code' => $check['check_code'],
        'warehouse_id' => $check['warehouse_id'],
        'warehouse_name' => $check['warehouse_name'],
        'zone_id' => $check['zone_id'],
        'zone_name' => $check['zone_name'],
        'check_type' => $check['check_type'],
        'scheduled_date' => $check['scheduled_date'],
        'scheduled_time' => $check['scheduled_time'],
        'completed_at' => $check['completed_at'],
        'created_by_name' => $check['created_by_name'],
        'notes' => $check['notes'],
        'discrepancies' => $discrepancies,
        'stats' => $stats
    ]
]);
}

// Hàm mô phỏng quét RFID
function simulateRfidScan($conn) {
$checkId = isset($_GET['checkId']) ? intval($_GET['checkId']) : 0;

if ($checkId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID phiếu kiểm kê không hợp lệ']);
    return;
}

// Lấy thông tin phiếu kiểm kê để biết kho và khu vực cần quét
$query = "SELECT warehouse_id, zone_id FROM inventory_checks WHERE check_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$check = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$check) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu kiểm kê']);
    return;
}

// Trong thực tế, sẽ kết nối với thiết bị RFID để quét sản phẩm
// Ở đây mô phỏng bằng cách lấy ngẫu nhiên một sản phẩm từ kho đó

$whereClause = "i.warehouse_id = ?";
$params = [$check['warehouse_id']];

if ($check['zone_id']) {
    $whereClause .= " AND s.zone_id = ?";
    $params[] = $check['zone_id'];
}

$query = "SELECT 
            p.product_id, 
            p.product_code, 
            p.product_name, 
            i.quantity as expected_quantity,
            s.shelf_id,
            s.shelf_code
          FROM inventory i
          JOIN products p ON i.product_id = p.product_id
          LEFT JOIN shelves s ON i.shelf_id = s.shelf_id
          WHERE $whereClause
          ORDER BY RAND()
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm để quét']);
    return;
}

// Mô phỏng số lượng thực tế (có thể giống hoặc khác với số lượng trong hệ thống)
$randomFactor = rand(0, 10);
$actualQuantity = $product['expected_quantity'];

if ($randomFactor < 3) { // 30% cơ hội có sự chênh lệch
    $diff = rand(-3, 3); // Chênh lệch từ -3 đến +3
    $actualQuantity = max(0, $product['expected_quantity'] + $diff);
}

// Lưu kết quả quét vào database
try {
    // Kiểm tra xem sản phẩm này đã được quét chưa
    $query = "SELECT result_id FROM inventory_check_results 
              WHERE check_id = ? AND product_id = ? AND (shelf_id = ? OR (shelf_id IS NULL AND ? IS NULL))";
    $stmt = $conn->prepare($query);
    $stmt->execute([$checkId, $product['product_id'], $product['shelf_id'], $product['shelf_id']]);
    $existingResult = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingResult) {
        // Cập nhật kết quả hiện có
        $query = "UPDATE inventory_check_results 
                  SET expected_quantity = ?, actual_quantity = ?, updated_at = NOW() 
                  WHERE result_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$product['expected_quantity'], $actualQuantity, $existingResult['result_id']]);
    } else {
        // Thêm kết quả mới
        $query = "INSERT INTO inventory_check_results 
                  (check_id, product_id, shelf_id, expected_quantity, actual_quantity, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($query);
        $stmt->execute([$checkId, $product['product_id'], $product['shelf_id'], $product['expected_quantity'], $actualQuantity]);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Đã quét sản phẩm ' . $product['product_name'], 
        'product' => $product
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu kết quả quét: ' . $e->getMessage()]);
}
}

// Hàm xuất báo cáo ra Excel
function exportToExcel($conn) {
$checkId = isset($_GET['checkId']) ? intval($_GET['checkId']) : 0;

if ($checkId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID phiếu kiểm kê không hợp lệ']);
    return;
}

// Trong thực tế, sẽ sử dụng thư viện như PhpSpreadsheet để tạo file Excel
// Ở đây mô phỏng bằng cách xuất CSV

// Lấy thông tin phiếu kiểm kê
$query = "SELECT ic.*, w.warehouse_name, wz.zone_name, u.fullname as created_by_name
          FROM inventory_checks ic 
          LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
          LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
          LEFT JOIN users u ON ic.created_by = u.user_id
          WHERE ic.check_id = ?";

$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$check = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$check) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu kiểm kê']);
    return;
}

// Lấy kết quả kiểm kê
$query = "SELECT icr.*, p.product_code, p.product_name, s.shelf_code, p.price
          FROM inventory_check_results icr
          LEFT JOIN products p ON icr.product_id = p.product_id
          LEFT JOIN shelves s ON icr.shelf_id = s.shelf_id
          WHERE icr.check_id = ?
          ORDER BY p.product_code";

$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tạo file CSV
$filename = "bao-cao-kiem-ke-" . $check['check_code'] . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Thêm BOM (Byte Order Mark) để Excel nhận diện UTF-8
fputs($output, "\xEF\xBB\xBF");

// Thông tin báo cáo
fputcsv($output, ['BÁO CÁO KIỂM KÊ KHO']);
fputcsv($output, ['Mã phiếu:', $check['check_code']]);
fputcsv($output, ['Kho:', $check['warehouse_name']]);
fputcsv($output, ['Khu vực:', $check['zone_name'] ?: 'Toàn bộ kho']);
fputcsv($output, ['Thời gian kiểm kê:', date('d/m/Y H:i', strtotime($check['completed_at']))]);
fputcsv($output, ['Người thực hiện:', $check['created_by_name']]);
fputcsv($output, []);

// Header cho kết quả kiểm kê
fputcsv($output, ['Mã SP', 'Tên sản phẩm', 'Kệ/Vị trí', 'SL hệ thống', 'SL thực tế', 'Chênh lệch', 'Giá trị chênh lệch']);

// Dữ liệu kết quả
$totalExpected = 0;
$totalActual = 0;
$totalDiff = 0;
$totalDiffValue = 0;

foreach ($results as $result) {
    $diff = $result['actual_quantity'] - $result['expected_quantity'];
    $diffValue = $diff * ($result['price'] ?: 0);
    
    $totalExpected += $result['expected_quantity'];
    $totalActual += $result['actual_quantity'];
    $totalDiff += $diff;
    $totalDiffValue += $diffValue;
    
    fputcsv($output, [
        $result['product_code'],
        $result['product_name'],
        $result['shelf_code'] ?: 'N/A',
        $result['expected_quantity'],
        $result['actual_quantity'],
        $diff,
        number_format($diffValue, 0, ',', '.')
    ]);
}

// Tổng cộng
fputcsv($output, []);
fputcsv($output, ['TỔNG CỘNG:', '', '', $totalExpected, $totalActual, $totalDiff, number_format($totalDiffValue, 0, ',', '.')]);

fclose($output);
exit;
}

// Hàm xuất báo cáo ra PDF
function exportToPDF($conn) {
$checkId = isset($_GET['checkId']) ? intval($_GET['checkId']) : 0;

if ($checkId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID phiếu kiểm kê không hợp lệ']);
    return;
}

// Trong thực tế, sẽ sử dụng thư viện như TCPDF, FPDF hoặc mPDF để tạo file PDF
// Ở đây mô phỏng bằng cách xuất HTML

// Lấy thông tin phiếu kiểm kê
$query = "SELECT ic.*, w.warehouse_name, wz.zone_name, u.fullname as created_by_name
          FROM inventory_checks ic 
          LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
          LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
          LEFT JOIN users u ON ic.created_by = u.user_id
          WHERE ic.check_id = ?";

$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$check = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$check) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu kiểm kê']);
    return;
}

// Lấy kết quả kiểm kê
$query = "SELECT icr.*, p.product_code, p.product_name, s.shelf_code, p.price
          FROM inventory_check_results icr
          LEFT JOIN products p ON icr.product_id = p.product_id
          LEFT JOIN shelves s ON icr.shelf_id = s.shelf_id
          WHERE icr.check_id = ?
          ORDER BY p.product_code";

$stmt = $conn->prepare($query);
$stmt->execute([$checkId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tạo nội dung HTML
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Kiểm Kê - ' . $check['check_code'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; }
        .info { margin-bottom: 20px; }
        .info p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .positive { color: blue; }
        .negative { color: red; }
        .total-row { font-weight: bold; background-color: #f8f8f8; }
    </style>
</head>
<body>
    <h1>BÁO CÁO KIỂM KÊ KHO</h1>
    
    <div class="info">
        <p><strong>Mã phiếu:</strong> ' . $check['check_code'] . '</p>
        <p><strong>Kho:</strong> ' . $check['warehouse_name'] . '</p>
        <p><strong>Khu vực:</strong> ' . ($check['zone_name'] ?: 'Toàn bộ kho') . '</p>
        <p><strong>Thời gian kiểm kê:</strong> ' . date('d/m/Y H:i', strtotime($check['completed_at'])) . '</p>
        <p><strong>Người thực hiện:</strong> ' . $check['created_by_name'] . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Mã SP</th>
                <th>Tên sản phẩm</th>
                <th>Kệ/Vị trí</th>
                <th class="text-right">SL hệ thống</th>
                <th class="text-right">SL thực tế</th>
                <th class="text-right">Chênh lệch</th>
                <th class="text-right">Giá trị chênh lệch</th>
            </tr>
        </thead>
        <tbody>';

$totalExpected = 0;
$totalActual = 0;
$totalDiff = 0;
$totalDiffValue = 0;

foreach ($results as $result) {
    $diff = $result['actual_quantity'] - $result['expected_quantity'];
    $diffValue = $diff * ($result['price'] ?: 0);
    
    $totalExpected += $result['expected_quantity'];
    $totalActual += $result['actual_quantity'];
    $totalDiff += $diff;
    $totalDiffValue += $diffValue;
    
    $diffClass = $diff == 0 ? '' : ($diff > 0 ? 'positive' : 'negative');
    $diffValueClass = $diffValue == 0 ? '' : ($diffValue > 0 ? 'positive' : 'negative');
    
    $html .= '<tr>
        <td>' . $result['product_code'] . '</td>
        <td>' . $result['product_name'] . '</td>
        <td>' . ($result['shelf_code'] ?: 'N/A') . '</td>
        <td class="text-right">' . $result['expected_quantity'] . '</td>
        <td class="text-right">' . $result['actual_quantity'] . '</td>
        <td class="text-right ' . $diffClass . '">' . ($diff > 0 ? '+' : '') . $diff . '</td>
        <td class="text-right ' . $diffValueClass . '">' . number_format($diffValue, 0, ',', '.') . ' đ</td>
    </tr>';
}

$totalDiffClass = $totalDiff == 0 ? '' : ($totalDiff > 0 ? 'positive' : 'negative');
$totalDiffValueClass = $totalDiffValue == 0 ? '' : ($totalDiffValue > 0 ? 'positive' : 'negative');

$html .= '</tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3" class="text-right">TỔNG CỘNG:</td>
            <td class="text-right">' . $totalExpected . '</td>
            <td class="text-right">' . $totalActual . '</td>
            <td class="text-right ' . $totalDiffClass . '">' . ($totalDiff > 0 ? '+' : '') . $totalDiff . '</td>
            <td class="text-right ' . $totalDiffValueClass . '">' . number_format($totalDiffValue, 0, ',', '.') . ' đ</td>
        </tr>
    </tfoot>
</table>
</body>
</html>';

// Đặt header cho file PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="bao-cao-kiem-ke-' . $check['check_code'] . '.pdf"');

// Chuyển đổi HTML thành PDF (cần cài đặt thư viện như mPDF, TCPDF, v.v.)
// Trong thực tế, sẽ sử dụng đoạn code như:
// $mpdf = new \Mpdf\Mpdf();
// $mpdf->WriteHTML($html);
// $mpdf->Output('bao-cao-kiem-ke-' . $check['check_code'] . '.pdf', 'D');

// Tuy nhiên, vì đây là mô phỏng, chúng ta sẽ chỉ xuất HTML
echo $html;
exit;
}
?>