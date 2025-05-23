<?php
// ajax/kiemke/ajax_handler.php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy action từ request
$action = $_REQUEST['action'] ?? '';

// Xử lý theo action
switch ($action) {
    // Lấy số thứ tự tiếp theo cho mã kiểm kê
    case 'getNextCheckNumber':
        $date = $_POST['date'] ?? date('Ymd');
        
        // Tìm số thứ tự lớn nhất trong ngày
        $sql = "SELECT MAX(SUBSTRING(check_code, -3)) AS max_number 
                FROM inventory_checks 
                WHERE check_code LIKE 'KK{$date}%'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        
        $nextNumber = 1;
        if ($result && $result['max_number']) {
            $nextNumber = intval($result['max_number']) + 1;
        }
        
        echo json_encode(['success' => true, 'nextNumber' => $nextNumber]);
        break;
        
    // Lấy danh sách kho
    case 'getWarehouses':
        $sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $warehouses = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'warehouses' => $warehouses]);
        break;
        
    // Lấy danh sách khu vực theo kho
    case 'getZones':
        $warehouseId = intval($_GET['warehouse_id'] ?? 0);
        
        if ($warehouseId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kho không hợp lệ']);
            exit;
        }
        
        $sql = "SELECT zone_id, zone_code, zone_name 
                FROM warehouse_zones 
                WHERE warehouse_id = ? 
                ORDER BY zone_code";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$warehouseId]);
        $zones = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'zones' => $zones]);
        break;
        
    // Tạo lịch kiểm kê mới
    case 'createInventoryCheck':
        // Kiểm tra quyền
        if (!hasPermission('inventory_check')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện kiểm kê']);
            exit;
        }
        
        // Lấy dữ liệu từ request
        $warehouseId = intval($_POST['warehouse_id'] ?? 0);
        $zoneId = !empty($_POST['zone_id']) ? intval($_POST['zone_id']) : null;
        $scheduledDate = $_POST['scheduled_date'] ?? '';
        $scheduledTime = $_POST['scheduled_time'] ?? '';
        $checkType = $_POST['check_type'] ?? '';
        $checkCode = $_POST['check_code'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        // Kiểm tra dữ liệu
        if ($warehouseId <= 0 || empty($scheduledDate) || empty($scheduledTime) || empty($checkType) || empty($checkCode)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        // Kiểm tra mã kiểm kê đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_checks WHERE check_code = ?");
        $stmt->execute([$checkCode]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Mã kiểm kê đã tồn tại']);
            exit;
        }
        
        // Thêm lịch kiểm kê mới
        $sql = "INSERT INTO inventory_checks (check_code, warehouse_id, zone_id, scheduled_date, scheduled_time, 
                status, check_type, created_by, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, 'SCHEDULED', ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $checkCode, $warehouseId, $zoneId, $scheduledDate, $scheduledTime, 
            $checkType, $_SESSION['user_id'], $notes
        ]);
        
        if ($result) {
            // Ghi log
            logUserActivity($_SESSION['user_id'], 'CREATE_INVENTORY_CHECK', "Tạo lịch kiểm kê: {$checkCode}");
            
            echo json_encode(['success' => true, 'message' => 'Tạo lịch kiểm kê thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi tạo lịch kiểm kê']);
        }
        break;
        
    // Lấy danh sách kiểm kê theo trạng thái
    case 'getInventoryChecks':
        $status = $_GET['status'] ?? '';
        
        if (empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
            exit;
        }
        
        $sql = "SELECT ic.*, w.warehouse_name, wz.zone_name, u.full_name as created_by_name,
                (SELECT COUNT(*) FROM inventory_check_results WHERE check_id = ic.check_id) as checked_items,
                (
                    SELECT COUNT(*) 
                    FROM inventory i 
                    LEFT JOIN product_locations pl ON i.product_id = pl.product_id 
                    LEFT JOIN shelves s ON pl.shelf_id = s.shelf_id 
                    LEFT JOIN warehouse_zones wz ON s.zone_id = wz.zone_id 
                    WHERE i.warehouse_id = ic.warehouse_id 
                    AND (ic.zone_id IS NULL OR wz.zone_id = ic.zone_id)
                ) as total_items
                FROM inventory_checks ic
                LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
                LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
                LEFT JOIN users u ON ic.created_by = u.user_id
                WHERE ic.status = ?
                ORDER BY ic.scheduled_date DESC, ic.scheduled_time DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status]);
        $checks = $stmt->fetchAll();
        
        // Thêm thông tin tiến độ
        foreach ($checks as &$check) {
            $check['progress'] = $check['checked_items'] . '/' . $check['total_items'];
            
            // Tính tỷ lệ chính xác nếu đã hoàn thành
            if ($check['status'] === 'COMPLETED') {
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_results,
                        SUM(CASE WHEN difference = 0 THEN 1 ELSE 0 END) as matched_results
                    FROM inventory_check_results 
                    WHERE check_id = ?
                ");
                $stmt->execute([$check['check_id']]);
                $result = $stmt->fetch();
                
                $check['accuracy'] = $result['total_results'] > 0 
                    ? round(($result['matched_results'] / $result['total_results']) * 100, 2) 
                    : 0;
            }
        }
        
        echo json_encode(['success' => true, 'checks' => $checks]);
        break;
        
    // Lấy chi tiết kiểm kê
    case 'getInventoryCheckDetails':
        $checkId = intval($_GET['check_id'] ?? 0);
        
        if ($checkId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kiểm kê không hợp lệ']);
            exit;
        }
        
        // Lấy thông tin kiểm kê
        $sql = "SELECT ic.*, w.warehouse_name, wz.zone_name
                FROM inventory_checks ic
                LEFT JOIN warehouses w ON ic.warehouse_id = w.warehouse_id
                LEFT JOIN warehouse_zones wz ON ic.zone_id = wz.zone_id
                WHERE ic.check_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$checkId]);
        $check = $stmt->fetch();
        
        if (!$check) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin kiểm kê']);
            exit;
        }
        
        // Lấy kết quả kiểm kê
        $sql = "SELECT icr.*, p.product_code, p.product_name, s.shelf_code, wz.zone_code
                FROM inventory_check_results icr
                LEFT JOIN products p ON icr.product_id = p.product_id
                LEFT JOIN shelves s ON icr.shelf_id = s.shelf_id
                LEFT JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                WHERE icr.check_id = ?
                ORDER BY icr.result_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$checkId]);
        $results = $stmt->fetchAll();
        
        // Lấy danh sách sản phẩm trong kho/khu vực cần kiểm kê
        $sql = "SELECT p.product_id, p.product_code, p.product_name, 
                i.quantity as inventory_quantity, s.shelf_id, s.shelf_code, wz.zone_code
                FROM inventory i
                JOIN products p ON i.product_id = p.product_id
                LEFT JOIN product_locations pl ON i.product_id = pl.product_id
                LEFT JOIN shelves s ON pl.shelf_id = s.shelf_id
                LEFT JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                WHERE i.warehouse_id = ?";
        
        if ($check['zone_id']) {
            $sql .= " AND wz.zone_id = ?";
            $params = [$check['warehouse_id'], $check['zone_id']];
        } else {
            $params = [$check['warehouse_id']];
        }
        
        $sql .= " ORDER BY p.product_code";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $inventory = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true, 
            'check' => $check, 
            'results' => $results,
            'inventory' => $inventory
        ]);
        break;
        
    // Bắt đầu kiểm kê
    case 'startInventoryCheck':
        // Kiểm tra quyền
        if (!hasPermission('inventory_check')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện kiểm kê']);
            exit;
        }
        
        $checkId = intval($_POST['check_id'] ?? 0);
        
        if ($checkId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kiểm kê không hợp lệ']);
            exit;
        }
        
        // Kiểm tra trạng thái hiện tại
        $stmt = $pdo->prepare("SELECT status, check_code FROM inventory_checks WHERE check_id = ?");
        $stmt->execute([$checkId]);
        $check = $stmt->fetch();
        
        if (!$check) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin kiểm kê']);
            exit;
        }
        
        if ($check['status'] !== 'SCHEDULED') {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể bắt đầu kiểm kê ở trạng thái "Đã lên lịch"']);
            exit;
        }
        
        // Cập nhật trạng thái
        $stmt = $pdo->prepare("UPDATE inventory_checks SET status = 'IN_PROGRESS' WHERE check_id = ?");
        $result = $stmt->execute([$checkId]);
        
        if ($result) {
            // Ghi log
            logUserActivity($_SESSION['user_id'], 'START_INVENTORY_CHECK', "Bắt đầu kiểm kê: {$check['check_code']}");
            
            echo json_encode(['success' => true, 'message' => 'Bắt đầu kiểm kê thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật trạng thái']);
        }
        break;
        
    // Lấy danh sách sản phẩm cần kiểm kê
    case 'getCheckItems':
        $checkId = intval($_GET['check_id'] ?? 0);
        
        if ($checkId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kiểm kê không hợp lệ']);
            exit;
        }
        
        // Lấy thông tin kiểm kê
        $stmt = $pdo->prepare("SELECT warehouse_id, zone_id FROM inventory_checks WHERE check_id = ?");
        $stmt->execute([$checkId]);
        $check = $stmt->fetch();
        
        if (!$check) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin kiểm kê']);
            exit;
        }
        
        // Lấy danh sách sản phẩm cần kiểm kê và kết quả đã có
        $sql = "SELECT 
                    p.product_id, p.product_code, p.product_name,
                    pl.quantity as expected_quantity,
                    s.shelf_id, s.shelf_code, wz.zone_code,
                    icr.result_id, icr.actual_quantity, icr.notes
                FROM product_locations pl
                JOIN products p ON pl.product_id = p.product_id
                JOIN shelves s ON pl.shelf_id = s.shelf_id
                JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                LEFT JOIN inventory_check_results icr ON 
                    icr.check_id = ? AND 
                    icr.product_id = p.product_id AND 
                    (icr.shelf_id = s.shelf_id OR icr.shelf_id IS NULL)
                WHERE wz.warehouse_id = ?";
        
        if ($check['zone_id']) {
            $sql .= " AND wz.zone_id = ?";
            $params = [$checkId, $check['warehouse_id'], $check['zone_id']];
        } else {
            $params = [$checkId, $check['warehouse_id']];
        }
        
        $sql .= " ORDER BY p.product_code, s.shelf_code";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'items' => $items]);
        break;
        
    // Cập nhật kết quả kiểm kê
    case 'updateCheckResult':
        // Kiểm tra quyền
        if (!hasPermission('inventory_check')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện kiểm kê']);
            exit;
        }
        
        $checkId = intval($_POST['check_id'] ?? 0);
        $resultId = intval($_POST['result_id'] ?? 0);
        $productId = intval($_POST['product_id'] ?? 0);
        $actualQuantity = intval($_POST['actual_quantity'] ?? 0);
        $notes = $_POST['notes'] ?? '';
        
        if ($checkId <= 0 || $productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        // Kiểm tra trạng thái kiểm kê
        $stmt = $pdo->prepare("SELECT status FROM inventory_checks WHERE check_id = ?");
        $stmt->execute([$checkId]);
        $status = $stmt->fetchColumn();
        
        if ($status !== 'IN_PROGRESS') {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể cập nhật kết quả khi đang thực hiện kiểm kê']);
            exit;
        }
        
        // Lấy số lượng dự kiến từ inventory
        $sql = "SELECT pl.quantity, pl.shelf_id
                FROM product_locations pl
                JOIN shelves s ON pl.shelf_id = s.shelf_id
                JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                JOIN inventory_checks ic ON wz.warehouse_id = ic.warehouse_id
                WHERE ic.check_id = ? AND pl.product_id = ?";
        
        if ($resultId > 0) {
            $sql .= " AND pl.shelf_id = (SELECT shelf_id FROM inventory_check_results WHERE result_id = ?)";
            $params = [$checkId, $productId, $resultId];
        } else {
            $params = [$checkId, $productId];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $product = $stmt->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin sản phẩm trong kho']);
            exit;
        }
        
        $expectedQuantity = $product['quantity'];
        $shelfId = $product['shelf_id'];
        
        if ($resultId > 0) {
            // Cập nhật kết quả đã có
            $sql = "UPDATE inventory_check_results 
                    SET actual_quantity = ?, notes = ?
                    WHERE result_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$actualQuantity, $notes, $resultId]);
        } else {
            // Thêm kết quả mới
            $sql = "INSERT INTO inventory_check_results 
                    (check_id, product_id, expected_quantity, actual_quantity, shelf_id, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$checkId, $productId, $expectedQuantity, $actualQuantity, $shelfId, $notes]);
        }
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật kết quả kiểm kê thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật kết quả']);
        }
        break;
        
    // Quét barcode
    case 'scanBarcode':
        // Kiểm tra quyền
        if (!hasPermission('inventory_check')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện kiểm kê']);
            exit;
        }
        
        $checkId = intval($_POST['check_id'] ?? 0);
        $barcode = $_POST['barcode'] ?? '';
        
        if ($checkId <= 0 || empty($barcode)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        // Tìm sản phẩm theo barcode
        $stmt = $pdo->prepare("SELECT product_id FROM products WHERE barcode = ? OR product_code = ?");
        $stmt->execute([$barcode, $barcode]);
        $productId = $stmt->fetchColumn();
        
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm với mã barcode này']);
            exit;
        }
        
        // Lấy thông tin vị trí sản phẩm
        $sql = "SELECT pl.shelf_id, pl.quantity
                FROM product_locations pl
                JOIN shelves s ON pl.shelf_id = s.shelf_id
                JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                JOIN inventory_checks ic ON wz.warehouse_id = ic.warehouse_id
                WHERE ic.check_id = ? AND pl.product_id = ?";
        
        if ($check['zone_id']) {
            $sql .= " AND wz.zone_id = ?";
            $params = [$checkId, $productId, $check['zone_id']];
        } else {
            $params = [$checkId, $productId];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $location = $stmt->fetch();
        
        if (!$location) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không thuộc phạm vi kiểm kê này']);
            exit;
        }
        
        // Kiểm tra đã có kết quả chưa
        $stmt = $pdo->prepare("
            SELECT result_id, actual_quantity 
            FROM inventory_check_results 
            WHERE check_id = ? AND product_id = ? AND shelf_id = ?
        ");
        $stmt->execute([$checkId, $productId, $location['shelf_id']]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Cập nhật số lượng thực tế
            $actualQuantity = $result['actual_quantity'] + 1;
            $sql = "UPDATE inventory_check_results SET actual_quantity = ? WHERE result_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$actualQuantity, $result['result_id']]);
        } else {
            // Thêm kết quả mới
            $sql = "INSERT INTO inventory_check_results 
                    (check_id, product_id, expected_quantity, actual_quantity, shelf_id, created_at) 
                    VALUES (?, ?, ?, 1, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$checkId, $productId, $location['quantity'], $location['shelf_id']]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Quét barcode thành công']);
        break;
        
    // Bắt đầu quét RFID
    case 'startRFIDScan':
        // Kiểm tra quyền
        if (!hasPermission('inventory_check')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện kiểm kê']);
            exit;
        }
        
        $checkId = intval($_POST['check_id'] ?? 0);
        
        if ($checkId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kiểm kê không hợp lệ']);
            exit;
        }
        
        // Trong thực tế, tại đây sẽ gửi lệnh đến thiết bị RFID để bắt đầu quét
        // Trong demo này, chúng ta giả lập việc bắt đầu quét
        
        echo json_encode(['success' => true, 'message' => 'Đã bắt đầu quét RFID']);
        break;
        
    // Hoàn thành quét RFID
    case 'completeRFIDScan':
        // Kiểm tra quyền
        if (!hasPermission('inventory_check')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện kiểm kê']);
            exit;
        }
        
        $checkId = intval($_POST['check_id'] ?? 0);
        
        if ($checkId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kiểm kê không hợp lệ']);
            exit;
        }
        
        // Trong thực tế, tại đây sẽ nhận dữ liệu từ thiết bị RFID
        // Trong demo này, chúng ta giả lập việc nhận dữ liệu và cập nhật kết quả
        
        // Lấy danh sách sản phẩm cần kiểm kê
        $stmt = $pdo->prepare("SELECT warehouse_id, zone_id FROM inventory_checks WHERE check_id = ?");
        $stmt->execute([$checkId]);
        $check = $stmt->fetch();
        
        if (!$check) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin kiểm kê']);
            exit;
        }
        
        // Lấy danh sách sản phẩm trong kho/khu vực
        $sql = "SELECT pl.product_id, pl.shelf_id, pl.quantity
                FROM product_locations pl
                JOIN shelves s ON pl.shelf_id = s.shelf_id
                JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                WHERE wz.warehouse_id = ?";
        
        if ($check['zone_id']) {
            $sql .= " AND wz.zone_id = ?";
            $params = [$check['warehouse_id'], $check['zone_id']];
        } else {
            $params = [$check['warehouse_id']];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // Giả lập kết quả quét RFID
        $pdo->beginTransaction();
        
        try {
            foreach ($products as $product) {
                // Giả lập số lượng thực tế (ngẫu nhiên từ 90% đến 110% số lượng dự kiến)
                $expectedQuantity = $product['quantity'];
                $actualQuantity = rand(
                    max(0, floor($expectedQuantity * 0.9)), 
                    ceil($expectedQuantity * 1.1)
                );
                
                // Kiểm tra đã có kết quả chưa
                $stmt = $pdo->prepare("
                    SELECT result_id 
                    FROM inventory_check_results 
                    WHERE check_id = ? AND product_id = ? AND shelf_id = ?
                ");
                $stmt->execute([$checkId, $product['product_id'], $product['shelf_id']]);
                $resultId = $stmt->fetchColumn();
                
                if ($resultId) {
                    // Cập nhật kết quả đã có
                    $sql = "UPDATE inventory_check_results 
                            SET actual_quantity = ?
                            WHERE result_id = ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$actualQuantity, $resultId]);
                } else {
                    // Thêm kết quả mới
                    $sql = "INSERT INTO inventory_check_results 
                            (check_id, product_id, expected_quantity, actual_quantity, shelf_id, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $checkId, 
                        $product['product_id'], 
                        $expectedQuantity, 
                        $actualQuantity, 
                        $product['shelf_id']
                    ]);
                }
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Hoàn thành quét RFID']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
        break;
        
    // Hoàn thành kiểm kê
    case 'completeInventoryCheck':
        // Kiểm tra quyền
        if (!hasPermission('inventory_check')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện kiểm kê']);
            exit;
        }
        
        $checkId = intval($_POST['check_id'] ?? 0);
        
        if ($checkId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kiểm kê không hợp lệ']);
            exit;
        }
        
               // Kiểm tra trạng thái hiện tại
        $stmt = $pdo->prepare("SELECT status, check_code FROM inventory_checks WHERE check_id = ?");
        $stmt->execute([$checkId]);
        $check = $stmt->fetch();
        
        if (!$check) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin kiểm kê']);
            exit;
        }
        
        if ($check['status'] !== 'IN_PROGRESS') {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể hoàn thành kiểm kê ở trạng thái "Đang thực hiện"']);
            exit;
        }
        
        // Kiểm tra đã kiểm kê đủ sản phẩm chưa
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_products
            FROM product_locations pl
            JOIN shelves s ON pl.shelf_id = s.shelf_id
            JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
            JOIN inventory_checks ic ON wz.warehouse_id = ic.warehouse_id
            WHERE ic.check_id = ?
            AND (ic.zone_id IS NULL OR wz.zone_id = ic.zone_id)
        ");
        $stmt->execute([$checkId]);
        $totalProducts = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_check_results WHERE check_id = ?");
        $stmt->execute([$checkId]);
        $checkedProducts = $stmt->fetchColumn();
        
        if ($checkedProducts < $totalProducts) {
            echo json_encode([
                'success' => false, 
                'message' => "Chưa kiểm kê đủ sản phẩm ($checkedProducts/$totalProducts). Bạn có chắc chắn muốn hoàn thành?"
            ]);
            exit;
        }
        
        // Cập nhật trạng thái và thời gian hoàn thành
        $stmt = $pdo->prepare("
            UPDATE inventory_checks 
            SET status = 'COMPLETED', completed_at = NOW() 
            WHERE check_id = ?
        ");
        $result = $stmt->execute([$checkId]);
        
        if ($result) {
            // Ghi log
            logUserActivity($_SESSION['user_id'], 'COMPLETE_INVENTORY_CHECK', "Hoàn thành kiểm kê: {$check['check_code']}");
            
            echo json_encode(['success' => true, 'message' => 'Hoàn thành kiểm kê thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật trạng thái']);
        }
        break;
        
    // Xóa lịch kiểm kê
    case 'deleteInventoryCheck':
        // Kiểm tra quyền
        if (!hasPermission('inventory_check')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện kiểm kê']);
            exit;
        }
        
        $checkId = intval($_POST['check_id'] ?? 0);
        
        if ($checkId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kiểm kê không hợp lệ']);
            exit;
        }
        
        // Kiểm tra trạng thái hiện tại
        $stmt = $pdo->prepare("SELECT status, check_code FROM inventory_checks WHERE check_id = ?");
        $stmt->execute([$checkId]);
        $check = $stmt->fetch();
        
        if (!$check) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin kiểm kê']);
            exit;
        }
        
        if ($check['status'] !== 'SCHEDULED') {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể xóa lịch kiểm kê ở trạng thái "Đã lên lịch"']);
            exit;
        }
        
        // Xóa lịch kiểm kê
        $stmt = $pdo->prepare("DELETE FROM inventory_checks WHERE check_id = ?");
        $result = $stmt->execute([$checkId]);
        
        if ($result) {
            // Ghi log
            logUserActivity($_SESSION['user_id'], 'DELETE_INVENTORY_CHECK', "Xóa lịch kiểm kê: {$check['check_code']}");
            
            echo json_encode(['success' => true, 'message' => 'Xóa lịch kiểm kê thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa lịch kiểm kê']);
        }
        break;
        
    // Lấy báo cáo kiểm kê
    case 'getInventoryReports':
        $warehouseId = !empty($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : null;
        $dateFrom = !empty($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = !empty($_GET['date_to']) ? $_GET['date_to'] : null;
        
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
        
        echo json_encode(['success' => true, 'reports' => $reports]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Không hỗ trợ action này']);
        break;
}
?>