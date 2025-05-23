<?php
// ajax/ajax_handler.php
session_start();
require_once '../../config/connect.php';
require_once '../../inc/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy module và action từ request
$module = $_REQUEST['module'] ?? '';
$action = $_REQUEST['action'] ?? '';

// Xử lý theo module và action
if ($module === 'products') {
    switch ($action) {
        // Lấy chi tiết sản phẩm
        case 'getProductDetails':
            $productId = intval($_GET['id'] ?? 0);
            
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
                exit;
            }
            
            // Lấy thông tin sản phẩm
            $sql = "SELECT p.*, c.category_name 
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE p.product_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
                exit;
            }
            
            // Lấy thông tin tồn kho
            $sql = "SELECT pl.*, s.shelf_code, wz.zone_code, w.warehouse_name
                    FROM product_locations pl
                    JOIN shelves s ON pl.shelf_id = s.shelf_id
                    JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                    JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
                    WHERE pl.product_id = ?
                    ORDER BY pl.expiry_date ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productId]);
            $inventory = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'product' => $product,
                'inventory' => $inventory
            ]);
            break;
            
        // Thêm sản phẩm mới
        case 'addProduct':
            // Kiểm tra quyền
            if (!hasPermission('add_products')) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm sản phẩm']);
                exit;
            }
            
            // Lấy dữ liệu từ form
            $productCode = $_POST['productCode'] ?? '';
            $productName = $_POST['productName'] ?? '';
            $categoryId = intval($_POST['categoryId'] ?? 0);
            $unit = $_POST['unit'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $minimumStock = intval($_POST['minimumStock'] ?? 10);
            $barcode = $_POST['barcode'] ?? null;
            $dimensions = $_POST['dimensions'] ?? null;
            $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
            $volume = !empty($_POST['volume']) ? floatval($_POST['volume']) : null;
            $description = $_POST['description'] ?? null;
            
            // Kiểm tra dữ liệu
            if (empty($productCode) || empty($productName) || $categoryId <= 0 || empty($unit) || $price < 0) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                exit;
            }
            
            // Kiểm tra mã sản phẩm đã tồn tại chưa
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
            $stmt->execute([$productCode]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Mã sản phẩm đã tồn tại']);
                exit;
            }
            
            // Xử lý hình ảnh nếu có
            $imageUrl = null;
            if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../image/products/';
                
                // Tạo thư mục nếu chưa tồn tại
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['productImage']['name']);
                $targetFile = $uploadDir . $fileName;
                
                // Kiểm tra định dạng file
                $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($imageFileType, $allowedTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh JPG, JPEG, PNG, GIF']);
                    exit;
                }
                
                // Di chuyển file tạm vào thư mục đích
                if (move_uploaded_file($_FILES['productImage']['tmp_name'], $targetFile)) {
                    $imageUrl = 'image/products/' . $fileName;
                }
            }
            
            // Thêm sản phẩm mới
            $sql = "INSERT INTO products (product_code, product_name, description, category_id, unit, price, 
                    image_url, volume, dimensions, weight, barcode, minimum_stock, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $productCode, $productName, $description, $categoryId, $unit, $price, 
                $imageUrl, $volume, $dimensions, $weight, $barcode, $minimumStock, $_SESSION['user_id']
            ]);
            
            if ($result) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'ADD_PRODUCT', "Thêm sản phẩm mới: {$productName}");
                
                echo json_encode(['success' => true, 'message' => 'Thêm sản phẩm thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm sản phẩm']);
            }
            break;
            
        // Cập nhật sản phẩm
        case 'updateProduct':
            // Kiểm tra quyền
            if (!hasPermission('edit_products')) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa sản phẩm']);
                exit;
            }
            
            // Lấy dữ liệu từ form
            $productId = intval($_POST['productId'] ?? 0);
            $productCode = $_POST['productCode'] ?? '';
            $productName = $_POST['productName'] ?? '';
            $categoryId = intval($_POST['categoryId'] ?? 0);
            $unit = $_POST['unit'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $minimumStock = intval($_POST['minimumStock'] ?? 10);
            $barcode = $_POST['barcode'] ?? null;
            $dimensions = $_POST['dimensions'] ?? null;
            $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
            $volume = !empty($_POST['volume']) ? floatval($_POST['volume']) : null;
            $description = $_POST['description'] ?? null;
            
            // Kiểm tra dữ liệu
            if ($productId <= 0 || empty($productCode) || empty($productName) || $categoryId <= 0 || empty($unit) || $price < 0) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                exit;
            }
            
            // Kiểm tra mã sản phẩm đã tồn tại chưa (trừ sản phẩm hiện tại)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ? AND product_id <> ?");
            $stmt->execute([$productCode, $productId]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Mã sản phẩm đã tồn tại']);
                exit;
            }
            
            // Lấy thông tin hình ảnh cũ
            $stmt = $pdo->prepare("SELECT image_url FROM products WHERE product_id = ?");
            $stmt->execute([$productId]);
            $oldImageUrl = $stmt->fetchColumn();
            
            // Xử lý hình ảnh nếu có
            $imageUrl = $oldImageUrl;
            if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../image/products/';
                
                // Tạo thư mục nếu chưa tồn tại
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['productImage']['name']);
                $targetFile = $uploadDir . $fileName;
                
                // Kiểm tra định dạng file
                $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($imageFileType, $allowedTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh JPG, JPEG, PNG, GIF']);
                    exit;
                }
                
                // Di chuyển file tạm vào thư mục đích
                if (move_uploaded_file($_FILES['productImage']['tmp_name'], $targetFile)) {
                    $imageUrl = 'image/products/' . $fileName;
                    
                    // Xóa file ảnh cũ nếu có
                    if ($oldImageUrl && file_exists('../' . $oldImageUrl)) {
                        unlink('../' . $oldImageUrl);
                    }
                }
            }
            
            // Cập nhật sản phẩm
            $sql = "UPDATE products SET 
                    product_code = ?, product_name = ?, description = ?, category_id = ?, 
                    unit = ?, price = ?, image_url = ?, volume = ?, dimensions = ?, 
                    weight = ?, barcode = ?, minimum_stock = ?, updated_at = NOW()
                    WHERE product_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $productCode, $productName, $description, $categoryId, 
                $unit, $price, $imageUrl, $volume, $dimensions, 
                $weight, $barcode, $minimumStock, $productId
            ]);
            
            if ($result) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'UPDATE_PRODUCT', "Cập nhật sản phẩm: {$productName}");
                
                echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật sản phẩm']);
            }
            break;
            
        // Xóa sản phẩm
        case 'deleteProduct':
            // Kiểm tra quyền
            if (!hasPermission('delete_products')) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa sản phẩm']);
                exit;
            }
            
            $productId = intval($_REQUEST['id'] ?? 0);
            
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
                exit;
            }
            
            // Kiểm tra sản phẩm có đang được sử dụng không
            $stmt = $pdo->prepare("SELECT SUM(quantity) FROM inventory WHERE product_id = ?");
            $stmt->execute([$productId]);
            $totalInventory = $stmt->fetchColumn();
            
            if ($totalInventory > 0) {
                echo json_encode(['success' => false, 'message' => 'Không thể xóa sản phẩm đang có tồn kho']);
                exit;
            }
            
            // Lấy thông tin sản phẩm để ghi log
            $stmt = $pdo->prepare("SELECT product_name, image_url FROM products WHERE product_id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
                exit;
            }
            
            // Xóa sản phẩm
            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $result = $stmt->execute([$productId]);
            
            if ($result) {
                // Xóa ảnh sản phẩm nếu có
                if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
                    unlink('../' . $product['image_url']);
                }
                
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'DELETE_PRODUCT', "Xóa sản phẩm: {$product['product_name']}");
                
                echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa sản phẩm']);
            }
            break;
            
        // Lấy chi tiết danh mục
        case 'getCategoryDetails':
            $categoryId = intval($_GET['id'] ?? 0);
            
            if ($categoryId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID danh mục không hợp lệ']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch();
            
            if (!$category) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy danh mục']);
                exit;
            }
            
            echo json_encode(['success' => true, 'category' => $category]);
            break;
            
        // Thêm danh mục mới
        case 'addCategory':
            // Kiểm tra quyền
            if (!hasPermission('add_products')) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm danh mục']);
                exit;
            }
            
            $categoryName = $_POST['categoryName'] ?? '';
            $parentId = !empty($_POST['parentId']) ? intval($_POST['parentId']) : null;
            $description = $_POST['categoryDescription'] ?? '';
            
            if (empty($categoryName)) {
                echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']);
                exit;
            }
            
            // Kiểm tra danh mục cha nếu có
            if ($parentId !== null) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_id = ?");
                $stmt->execute([$parentId]);
                if ($stmt->fetchColumn() == 0) {
                    echo json_encode(['success' => false, 'message' => 'Danh mục cha không tồn tại']);
                    exit;
                }
            }
            
            // Thêm danh mục mới
            $sql = "INSERT INTO categories (category_name, description, parent_id) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$categoryName, $description, $parentId]);
            
            if ($result) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'ADD_CATEGORY', "Thêm danh mục mới: {$categoryName}");
                
                echo json_encode(['success' => true, 'message' => 'Thêm danh mục thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm danh mục']);
            }
            break;
            
        // Cập nhật danh mục
        case 'updateCategory':
            // Kiểm tra quyền
            if (!hasPermission('edit_products')) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa danh mục']);
                exit;
            }
            
            $categoryId = intval($_POST['categoryId'] ?? 0);
            $categoryName = $_POST['categoryName'] ?? '';
            $parentId = !empty($_POST['parentId']) ? intval($_POST['parentId']) : null;
            $description = $_POST['categoryDescription'] ?? '';
            
            if ($categoryId <= 0 || empty($categoryName)) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                exit;
            }
            
            // Kiểm tra danh mục có tồn tại không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            if ($stmt->fetchColumn() == 0) {
                echo json_encode(['success' => false, 'message' => 'Danh mục không tồn tại']);
                exit;
            }
            
            // Không thể chọn chính nó làm danh mục cha
            if ($parentId == $categoryId) {
                echo json_encode(['success' => false, 'message' => 'Không thể chọn chính danh mục này làm danh mục cha']);
                exit;
            }
            
            // Kiểm tra danh mục cha nếu có
            if ($parentId !== null) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_id = ?");
                $stmt->execute([$parentId]);
                if ($stmt->fetchColumn() == 0) {
                    echo json_encode(['success' => false, 'message' => 'Danh mục cha không tồn tại']);
                    exit;
                }
                
                // Không thể chọn danh mục con làm danh mục cha (tránh tạo vòng lặp)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ? AND category_id = ?");
                $stmt->execute([$categoryId, $parentId]);
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Không thể chọn danh mục con làm danh mục cha']);
                    exit;
                }
            }
            
            // Cập nhật danh mục
            $sql = "UPDATE categories SET category_name = ?, description = ?, parent_id = ?, updated_at = NOW() WHERE category_id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$categoryName, $description, $parentId, $categoryId]);
            
            if ($result) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'UPDATE_CATEGORY', "Cập nhật danh mục: {$categoryName}");
                
                echo json_encode(['success' => true, 'message' => 'Cập nhật danh mục thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật danh mục']);
            }
            break;
            
        // Xóa danh mục
        case 'deleteCategory':
            // Kiểm tra quyền
            if (!hasPermission('delete_products')) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa danh mục']);
                exit;
            }
            
            $categoryId = intval($_REQUEST['id'] ?? 0);
            
            if ($categoryId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID danh mục không hợp lệ']);
                exit;
            }
            
            // Kiểm tra danh mục có chứa sản phẩm không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Không thể xóa danh mục đang chứa sản phẩm']);
                exit;
            }
            
            // Kiểm tra danh mục có danh mục con không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $stmt->execute([$categoryId]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Không thể xóa danh mục đang có danh mục con']);
                exit;
            }
            
            // Lấy thông tin danh mục để ghi log
            $stmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $categoryName = $stmt->fetchColumn();
            
            if (!$categoryName) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy danh mục']);
                exit;
            }
            
            // Xóa danh mục
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $result = $stmt->execute([$categoryId]);
            
            if ($result) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'DELETE_CATEGORY', "Xóa danh mục: {$categoryName}");
                
                echo json_encode(['success' => true, 'message' => 'Xóa danh mục thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa danh mục']);
            }
            break;
            
        // Lọc sản phẩm
        case 'filterProducts':
            $categoryId = !empty($_POST['categoryId']) ? intval($_POST['categoryId']) : null;
            $stockStatus = $_POST['stockStatus'] ?? '';
            $searchTerm = $_POST['searchTerm'] ?? '';
            
            // Xây dựng câu truy vấn SQL
            $sql = "SELECT p.*, c.category_name, 
                    IFNULL(SUM(i.quantity), 0) as total_stock
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    LEFT JOIN inventory i ON p.product_id = i.product_id";
            
            $conditions = [];
            $params = [];
            
            // Điều kiện danh mục
            if ($categoryId !== null) {
                // Lấy danh sách tất cả danh mục con
                $subCategoryIds = [];
                $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE parent_id = ?");
                $stmt->execute([$categoryId]);
                while ($row = $stmt->fetch()) {
                    $subCategoryIds[] = $row['category_id'];
                }
                
                // Nếu có danh mục con, lọc theo cả danh mục cha và con
                if (!empty($subCategoryIds)) {
                    $placeholders = implode(',', array_fill(0, count($subCategoryIds) + 1, '?'));
                    $conditions[] = "p.category_id IN ({$placeholders})";
                    $params[] = $categoryId;
                    foreach ($subCategoryIds as $id) {
                        $params[] = $id;
                    }
                } else {
                    $conditions[] = "p.category_id = ?";
                    $params[] = $categoryId;
                }
            }
            
            // Điều kiện tìm kiếm
            if (!empty($searchTerm)) {
                $conditions[] = "(p.product_code LIKE ? OR p.product_name LIKE ? OR p.barcode LIKE ?)";
                $searchParam = "%{$searchTerm}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            // Thêm các điều kiện vào câu truy vấn
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            // Nhóm và sắp xếp kết quả
            $sql .= " GROUP BY p.product_id";
            
            // Điều kiện trạng thái tồn kho (phải đặt sau GROUP BY)
            if (!empty($stockStatus)) {
                switch ($stockStatus) {
                    case 'low':
                        $sql .= " HAVING total_stock <= p.minimum_stock";
                        break;
                    case 'normal':
                        $sql .= " HAVING total_stock > p.minimum_stock AND total_stock <= p.minimum_stock * 1.5";
                        break;
                    case 'high':
                        $sql .= " HAVING total_stock > p.minimum_stock * 1.5";
                        break;
                }
            }
            
            $sql .= " ORDER BY p.product_code";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'products' => $products]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Không hỗ trợ action này']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Không hỗ trợ module này']);
}
?>