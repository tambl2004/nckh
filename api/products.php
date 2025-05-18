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
    // Lấy danh sách sản phẩm
    case 'getProducts':
        getProducts();
        break;
    
    // Lấy thông tin sản phẩm (giá, tồn kho, kệ chứa)
    case 'getProductInfo':
        getProductInfo();
        break;
    
    // Lấy số lượng khả dụng của sản phẩm trong kho
    case 'getAvailableQuantity':
        getAvailableQuantity();
        break;
    
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}

// Hàm lấy danh sách sản phẩm
function getProducts() {
    global $conn;
    
    $query = "SELECT p.product_id, p.product_code, p.product_name, p.price 
              FROM products p 
              ORDER BY p.product_name";
    
    $result = $conn->query($query);
    
    if ($result) {
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $conn->error]);
    }
}

// Hàm lấy thông tin sản phẩm (giá, tồn kho, kệ chứa)
function getProductInfo() {
    global $conn;
    
    $productId = isset($_GET['productId']) ? (int)$_GET['productId'] : 0;
    $warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
    
    if ($productId <= 0 || $warehouseId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID sản phẩm hoặc kho không hợp lệ']);
        return;
    }
    
    // Lấy thông tin sản phẩm
    $query = "SELECT p.price, IFNULL(i.quantity, 0) as availableQuantity 
              FROM products p 
              LEFT JOIN inventory i ON p.product_id = i.product_id AND i.warehouse_id = $warehouseId
              WHERE p.product_id = $productId";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $productInfo = $result->fetch_assoc();
        
        // Lấy danh sách kệ chứa sản phẩm trong kho
        $shelvesQuery = "SELECT s.shelf_id, s.shelf_code, IFNULL(pl.quantity, 0) as quantity
                       FROM shelves s
                       JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                       LEFT JOIN product_locations pl ON s.shelf_id = pl.shelf_id 
                                                     AND pl.product_id = $productId
                       WHERE wz.warehouse_id = $warehouseId
                       ORDER BY s.shelf_code";
        
        $shelvesResult = $conn->query($shelvesQuery);
        
        $shelves = [];
        if ($shelvesResult) {
            while ($row = $shelvesResult->fetch_assoc()) {
                $shelves[] = $row;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'price' => $productInfo['price'],
            'availableQuantity' => $productInfo['availableQuantity'],
            'shelves' => $shelves
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin sản phẩm']);
    }
}

// Hàm lấy số lượng khả dụng của sản phẩm trong kho
function getAvailableQuantity() {
    global $conn;
    
    $productId = isset($_GET['productId']) ? (int)$_GET['productId'] : 0;
    $warehouseId = isset($_GET['warehouseId']) ? (int)$_GET['warehouseId'] : 0;
    
    if ($productId <= 0 || $warehouseId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID sản phẩm hoặc kho không hợp lệ']);
        return;
    }
    
    // Lấy số lượng tồn kho
    $query = "SELECT IFNULL(quantity, 0) as availableQuantity 
              FROM inventory 
              WHERE product_id = $productId AND warehouse_id = $warehouseId";
    
    $result = $conn->query($query);
    
    if ($result) {
        $availableQuantity = 0;
        if ($result->num_rows > 0) {
            $availableQuantity = (int)$result->fetch_assoc()['availableQuantity'];
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'availableQuantity' => $availableQuantity
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $conn->error]);
    }
}
?>