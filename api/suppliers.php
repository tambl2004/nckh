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
    // Lấy danh sách nhà cung cấp
    case 'getSuppliers':
        getSuppliers();
        break;
    
    // Lấy chi tiết nhà cung cấp
    case 'getSupplierDetails':
        getSupplierDetails();
        break;
    
    // Lưu thông tin nhà cung cấp
    case 'saveSupplier':
        saveSupplier();
        break;
    
    // Bật/tắt trạng thái nhà cung cấp
    case 'toggleSupplierStatus':
        toggleSupplierStatus();
        break;
    
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}

// Hàm lấy danh sách nhà cung cấp
function getSuppliers() {
    global $conn;
    
    // Lấy các tham số lọc
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10; // Số nhà cung cấp trên mỗi trang
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Xây dựng câu truy vấn
    $query = "SELECT * FROM suppliers WHERE 1=1";
    $countQuery = "SELECT COUNT(*) as total FROM suppliers WHERE 1=1";
    
    // Thêm điều kiện lọc
    if (!empty($search)) {
        $query .= " AND (supplier_code LIKE '%$search%' OR supplier_name LIKE '%$search%' OR contact_person LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%')";
        $countQuery .= " AND (supplier_code LIKE '%$search%' OR supplier_name LIKE '%$search%' OR contact_person LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%')";
    }
    
    // Thêm sắp xếp và phân trang
    $query .= " ORDER BY supplier_name ASC LIMIT $offset, $limit";
    
        // Thực hiện truy vấn
        $result = $conn->query($query);
        $countResult = $conn->query($countQuery);
        
        if ($result && $countResult) {
            $suppliers = [];
            while ($row = $result->fetch_assoc()) {
                $suppliers[] = $row;
            }
            
            $totalRows = $countResult->fetch_assoc()['total'];
            $totalPages = ceil($totalRows / $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'suppliers' => $suppliers,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $conn->error]);
        }
    }
    
    // Hàm lấy chi tiết nhà cung cấp
    function getSupplierDetails() {
        global $conn;
        
        $supplierId = isset($_GET['supplierId']) ? (int)$_GET['supplierId'] : 0;
        
        if ($supplierId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID nhà cung cấp không hợp lệ']);
            return;
        }
        
        // Lấy thông tin nhà cung cấp
        $query = "SELECT * FROM suppliers WHERE supplier_id = $supplierId";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $supplier = $result->fetch_assoc();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'supplier' => $supplier
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhà cung cấp']);
        }
    }
    
    // Hàm lưu thông tin nhà cung cấp
    function saveSupplier() {
        global $conn, $currentUserId;
        
        // Bắt đầu transaction
        $conn->begin_transaction();
        
        try {
            // Lấy dữ liệu từ form
            $supplierId = isset($_POST['supplierId']) ? (int)$_POST['supplierId'] : 0;
            $supplierCode = $_POST['supplierCode'];
            $supplierName = $_POST['supplierName'];
            $contactPerson = $_POST['contactPerson'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $address = $_POST['address'] ?? '';
            $taxCode = $_POST['taxCode'] ?? '';
            $isActive = isset($_POST['isActive']) ? 1 : 0;
            
            // Kiểm tra mã nhà cung cấp đã tồn tại chưa (nếu là tạo mới)
            if ($supplierId === 0) {
                $checkQuery = "SELECT supplier_id FROM suppliers WHERE supplier_code = '$supplierCode'";
                $checkResult = $conn->query($checkQuery);
                
                if ($checkResult && $checkResult->num_rows > 0) {
                    throw new Exception('Mã nhà cung cấp đã tồn tại');
                }
            }
            
            if ($supplierId > 0) {
                // Cập nhật nhà cung cấp
                $updateQuery = "UPDATE suppliers SET 
                               supplier_code = '$supplierCode',
                               supplier_name = '$supplierName',
                               contact_person = '$contactPerson',
                               phone = '$phone',
                               email = '$email',
                               address = '$address',
                               tax_code = '$taxCode',
                               is_active = $isActive
                               WHERE supplier_id = $supplierId";
                
                if (!$conn->query($updateQuery)) {
                    throw new Exception('Lỗi khi cập nhật nhà cung cấp: ' . $conn->error);
                }
                
                // Lưu log
                $description = "Cập nhật thông tin nhà cung cấp: $supplierName";
                logUserAction($currentUserId, 'UPDATE_SUPPLIER', $description);
            } else {
                // Thêm mới nhà cung cấp
                $insertQuery = "INSERT INTO suppliers (
                                supplier_code, supplier_name, contact_person, phone,
                                email, address, tax_code, is_active, created_at)
                                VALUES (
                                '$supplierCode', '$supplierName', '$contactPerson', '$phone',
                                '$email', '$address', '$taxCode', $isActive, NOW())";
                
                if (!$conn->query($insertQuery)) {
                    throw new Exception('Lỗi khi thêm nhà cung cấp: ' . $conn->error);
                }
                
                $supplierId = $conn->insert_id;
                
                // Lưu log
                $description = "Thêm mới nhà cung cấp: $supplierName";
                logUserAction($currentUserId, 'CREATE_SUPPLIER', $description);
            }
            
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Đã lưu thông tin nhà cung cấp thành công', 
                'supplierId' => $supplierId
            ]);
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $conn->rollback();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Hàm bật/tắt trạng thái nhà cung cấp
    function toggleSupplierStatus() {
        global $conn, $currentUserId;
        
        // Lấy dữ liệu từ request
        $data = json_decode(file_get_contents('php://input'), true);
        $supplierId = isset($data['supplierId']) ? (int)$data['supplierId'] : 0;
        $isActive = isset($data['isActive']) ? (int)$data['isActive'] : 0;
        
        if ($supplierId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID nhà cung cấp không hợp lệ']);
            return;
        }
        
        // Bắt đầu transaction
        $conn->begin_transaction();
        
        try {
            // Kiểm tra nhà cung cấp có tồn tại không
            $checkQuery = "SELECT supplier_name FROM suppliers WHERE supplier_id = $supplierId";
            $checkResult = $conn->query($checkQuery);
            
            if (!$checkResult || $checkResult->num_rows === 0) {
                throw new Exception('Không tìm thấy nhà cung cấp');
            }
            
            $supplierName = $checkResult->fetch_assoc()['supplier_name'];
            
            // Cập nhật trạng thái
            $updateQuery = "UPDATE suppliers SET is_active = $isActive WHERE supplier_id = $supplierId";
            
            if (!$conn->query($updateQuery)) {
                throw new Exception('Lỗi khi cập nhật trạng thái nhà cung cấp: ' . $conn->error);
            }
            
            // Lưu log
            $status = $isActive ? 'kích hoạt' : 'vô hiệu hóa';
            $description = "$status nhà cung cấp: $supplierName";
            logUserAction($currentUserId, 'TOGGLE_SUPPLIER_STATUS', $description);
            
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => "Đã $status nhà cung cấp thành công"
            ]);
            
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