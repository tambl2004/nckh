<?php
// Kết nối đến CSDL đã được thiết lập từ trang admin chính

// Xử lý thêm sản phẩm mới
if (isset($_POST['add_product'])) {
    $product_code = trim($_POST['product_code']);
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $unit = trim($_POST['unit']);
    $price = $_POST['price'];
    $minimum_stock = $_POST['minimum_stock'] ?? 10;
    $barcode = trim($_POST['barcode'] ?? '');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $weight = $_POST['weight'] ?? 0;
    $volume = $_POST['volume'] ?? 0;
    
    $image_url = '';
    // Xử lý upload hình ảnh nếu có
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "image/sanpham/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'product_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $image_url = $target_file;
        }
    }
    
    // Kiểm tra mã sản phẩm đã tồn tại hay chưa
    $check_code = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
    $check_code->bind_param("s", $product_code);
    $check_code->execute();
    $check_code->bind_result($code_count);
    $check_code->fetch();
    $check_code->close();
    
    if ($code_count > 0) {
        $error_message = "Mã sản phẩm đã tồn tại trong hệ thống!";
    } else {
        // Thêm sản phẩm mới vào CSDL
        $stmt = $conn->prepare("INSERT INTO products (product_code, product_name, description, category_id, unit, price, image_url, volume, dimensions, weight, barcode, minimum_stock, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisdssdsii", $product_code, $product_name, $description, $category_id, $unit, $price, $image_url, $volume, $dimensions, $weight, $barcode, $minimum_stock, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success_message = "Thêm sản phẩm thành công!";
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'ADD_PRODUCT', "Đã thêm sản phẩm: $product_name");
        } else {
            $error_message = "Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Xử lý cập nhật sản phẩm
if (isset($_POST['update_product'])) {
    $product_id = $_POST['product_id'];
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $unit = trim($_POST['unit']);
    $price = $_POST['price'];
    $minimum_stock = $_POST['minimum_stock'] ?? 10;
    $barcode = trim($_POST['barcode'] ?? '');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $weight = $_POST['weight'] ?? 0;
    $volume = $_POST['volume'] ?? 0;
    
    $image_url = $_POST['current_image'] ?? '';
    
    // Xử lý upload hình ảnh mới nếu có
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "image/sanpham/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'product_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $image_url = $target_file;
        }
    }
    
    // Cập nhật thông tin sản phẩm
    $stmt = $conn->prepare("UPDATE products SET product_name = ?, description = ?, category_id = ?, unit = ?, price = ?, image_url = ?, volume = ?, dimensions = ?, weight = ?, barcode = ?, minimum_stock = ? WHERE product_id = ?");
    $stmt->bind_param("ssissdssdii", $product_name, $description, $category_id, $unit, $price, $image_url, $volume, $dimensions, $weight, $barcode, $minimum_stock, $product_id);
    
    if ($stmt->execute()) {
        $success_message = "Cập nhật sản phẩm thành công!";
        // Ghi log hoạt động
        logUserActivity($_SESSION['user_id'], 'UPDATE_PRODUCT', "Đã cập nhật sản phẩm: $product_name");
    } else {
        $error_message = "Lỗi: " . $stmt->error;
    }
    $stmt->close();
}

// Xử lý xóa sản phẩm
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    // Kiểm tra xem sản phẩm có tồn tại trong kho không
    $check_inventory = $conn->prepare("SELECT SUM(quantity) FROM inventory WHERE product_id = ?");
    $check_inventory->bind_param("i", $product_id);
    $check_inventory->execute();
    $check_inventory->bind_result($inventory_count);
    $check_inventory->fetch();
    $check_inventory->close();
    
    if ($inventory_count > 0) {
        $error_message = "Không thể xóa sản phẩm này vì còn tồn kho!";
    } else {
        // Lấy tên sản phẩm để ghi log
        $get_name = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
        $get_name->bind_param("i", $product_id);
        $get_name->execute();
        $get_name->bind_result($deleted_product_name);
        $get_name->fetch();
        $get_name->close();
        
        // Xóa sản phẩm
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            $success_message = "Xóa sản phẩm thành công!";
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'DELETE_PRODUCT', "Đã xóa sản phẩm: $deleted_product_name");
        } else {
            $error_message = "Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Lấy danh sách danh mục
$categories = [];
$get_categories = $conn->query("SELECT category_id, category_name, parent_id FROM categories ORDER BY category_name");
while ($category = $get_categories->fetch_assoc()) {
    $categories[] = $category;
}

// Hàm đệ quy để tạo cây danh mục
function buildCategoryTree($categories, $parent_id = null, $prefix = '') {
    $tree = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parent_id) {
            $category['category_name'] = $prefix . $category['category_name'];
            $tree[] = $category;
            $children = buildCategoryTree($categories, $category['category_id'], $prefix . '-- ');
            $tree = array_merge($tree, $children);
        }
    }
    return $tree;
}

$category_tree = buildCategoryTree($categories);

// Lấy danh sách sản phẩm với phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$where_clause = "WHERE 1=1";
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_clause .= " AND (p.product_code LIKE ? OR p.product_name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

if ($category_filter > 0) {
    $where_clause .= " AND p.category_id = ?";
    $params[] = $category_filter;
    $param_types .= "i";
}

// Đếm tổng số sản phẩm thỏa điều kiện
$count_query = "SELECT COUNT(*) FROM products p $where_clause";
$stmt_count = $conn->prepare($count_query);

if (!empty($params)) {
    $stmt_count->bind_param($param_types, ...$params);
}

$stmt_count->execute();
$stmt_count->bind_result($total_products);
$stmt_count->fetch();
$stmt_count->close();

$total_pages = ceil($total_products / $limit);

// Lấy danh sách sản phẩm
$products_query = "SELECT p.*, c.category_name FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  $where_clause 
                  ORDER BY p.product_code 
                  LIMIT $offset, $limit";

$stmt_products = $conn->prepare($products_query);

if (!empty($params)) {
    $stmt_products->bind_param($param_types, ...$params);
}

$stmt_products->execute();
$products_result = $stmt_products->get_result();
$products = [];

while ($product = $products_result->fetch_assoc()) {
    // Lấy thông tin tồn kho cho mỗi sản phẩm
    $inventory_query = "SELECT SUM(quantity) as total_stock FROM inventory WHERE product_id = ?";
    $stmt_inventory = $conn->prepare($inventory_query);
    $stmt_inventory->bind_param("i", $product['product_id']);
    $stmt_inventory->execute();
    $inventory_result = $stmt_inventory->get_result();
    $inventory_data = $inventory_result->fetch_assoc();
    
    $product['total_stock'] = $inventory_data['total_stock'] ?? 0;
    $product['stock_status'] = ($product['total_stock'] <= $product['minimum_stock']) ? 'low' : 'normal';
    
    $products[] = $product;
    $stmt_inventory->close();
}

$stmt_products->close();

// Lấy sản phẩm gần hết hạn
$expiring_products_query = "SELECT p.product_name, pl.expiry_date, pl.quantity, w.warehouse_name, 
                          DATEDIFF(pl.expiry_date, CURDATE()) AS days_left 
                          FROM product_locations pl
                          JOIN products p ON pl.product_id = p.product_id
                          JOIN shelves s ON pl.shelf_id = s.shelf_id
                          JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                          JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
                          WHERE pl.expiry_date IS NOT NULL 
                          AND DATEDIFF(pl.expiry_date, CURDATE()) BETWEEN 0 AND 30
                          ORDER BY days_left ASC
                          LIMIT 5";

$expiring_result = $conn->query($expiring_products_query);
$expiring_products = [];

if ($expiring_result) {
    while ($expiring = $expiring_result->fetch_assoc()) {
        $expiring_products[] = $expiring;
    }
}

// Lấy sản phẩm tồn kho thấp
$low_stock_query = "SELECT p.product_name, i.quantity, p.minimum_stock, w.warehouse_name
                   FROM inventory i
                   JOIN products p ON i.product_id = p.product_id
                   JOIN warehouses w ON i.warehouse_id = w.warehouse_id
                   WHERE i.quantity <= p.minimum_stock
                   ORDER BY (i.quantity / p.minimum_stock) ASC
                   LIMIT 5";

$low_stock_result = $conn->query($low_stock_query);
$low_stock_products = [];

if ($low_stock_result) {
    while ($low_stock = $low_stock_result->fetch_assoc()) {
        $low_stock_products[] = $low_stock;
    }
}
?>

<div class="container-fluid">
    <!-- Tiêu đề trang -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Quản lý sản phẩm</h1>
        <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus me-2"></i>Thêm sản phẩm
        </button>
    </div>
    
    <!-- Hiển thị thông báo -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Cảnh báo sản phẩm -->
    <div class="row mb-4">
        <!-- Cảnh báo sản phẩm gần hết hạn -->
        <div class="col-md-6">
            <div class="function-container">
                <h5 class="text-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Sản phẩm gần hết hạn</h5>
                <?php if (count($expiring_products) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Kho</th>
                                    <th>Số lượng</th>
                                    <th>Hạn sử dụng</th>
                                    <th>Còn lại</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiring_products as $expiring): ?>
                                <tr>
                                    <td><?php echo $expiring['product_name']; ?></td>
                                    <td><?php echo $expiring['warehouse_name']; ?></td>
                                    <td><?php echo $expiring['quantity']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($expiring['expiry_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($expiring['days_left'] <= 7) ? 'bg-danger' : 'bg-warning'; ?>">
                                            <?php echo $expiring['days_left']; ?> ngày
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Không có sản phẩm gần hết hạn.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Cảnh báo sản phẩm tồn kho thấp -->
        <div class="col-md-6">
            <div class="function-container">
                <h5 class="text-warning mb-3"><i class="fas fa-box-open me-2"></i>Sản phẩm tồn kho thấp</h5>
                <?php if (count($low_stock_products) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Kho</th>
                                    <th>Tồn kho</th>
                                    <th>Tối thiểu</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $low_stock): ?>
                                <tr>
                                    <td><?php echo $low_stock['product_name']; ?></td>
                                    <td><?php echo $low_stock['warehouse_name']; ?></td>
                                    <td><?php echo $low_stock['quantity']; ?></td>
                                    <td><?php echo $low_stock['minimum_stock']; ?></td>
                                    <td>
                                        <span class="badge <?php echo ($low_stock['quantity'] == 0) ? 'bg-danger' : 'bg-warning'; ?>">
                                            <?php echo ($low_stock['quantity'] == 0) ? 'Hết hàng' : 'Sắp hết'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Không có sản phẩm tồn kho thấp.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bộ lọc và tìm kiếm -->
    <div class="function-container mb-4">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="search">Tìm kiếm:</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Mã, tên hoặc mô tả sản phẩm">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="category">Danh mục:</label>
                        <select class="form-control" id="category" name="category">
                            <option value="0">Tất cả danh mục</option>
                            <?php foreach ($category_tree as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($category_filter == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i> Tìm kiếm
                    </button>
                    <a href="?option=sanpham" class="btn btn-secondary">
                        <i class="fas fa-sync-alt me-1"></i> Làm mới
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Bảng danh sách sản phẩm -->
    <div class="function-container">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%">Ảnh</th>
                        <th width="10%">Mã SP</th>
                        <th width="20%">Tên sản phẩm</th>
                        <th width="15%">Danh mục</th>
                        <th width="10%">Đơn vị</th>
                        <th width="10%">Giá</th>
                        <th width="10%">Tồn kho</th>
                        <th width="10%">Barcode</th>
                        <th width="10%">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['product_name']; ?>" width="40" height="40" class="rounded">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-box text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['product_code']; ?></td>
                                <td><?php echo $product['product_name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td><?php echo $product['unit']; ?></td>
                                <td><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</td>
                                <td>
                                    <span class="badge <?php echo ($product['stock_status'] == 'low') ? 'bg-warning' : 'bg-success'; ?>">
                                        <?php echo $product['total_stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($product['barcode'])): ?>
                                        <span class="d-inline-block text-truncate" style="max-width: 100px;">
                                            <?php echo $product['barcode']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editProductModal" 
                                            data-product='<?php echo json_encode($product); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteProductModal"
                                            data-product-id="<?php echo $product['product_id']; ?>"
                                            data-product-name="<?php echo $product['product_name']; ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-3">Không có sản phẩm nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?option=sanpham&page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?option=sanpham&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?option=sanpham&page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal thêm sản phẩm -->
<div class="modal custom-modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Thêm sản phẩm mới</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <!-- Thông tin cơ bản -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="product_code">Mã sản phẩm <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="product_code" name="product_code" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_name">Tên sản phẩm <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="product_name" name="product_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">Danh mục <span class="text-danger">*</span></label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">-- Chọn danh mục --</option>
                                    <?php foreach ($category_tree as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>">
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="unit">Đơn vị <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="unit" name="unit" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Giá <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="price" name="price" min="0" step="1000" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="minimum_stock">Tồn kho tối thiểu</label>
                                <input type="number" class="form-control" id="minimum_stock" name="minimum_stock" min="0" value="10">
                            </div>
                            
                            <div class="form-group">
                                <label for="barcode">Mã Barcode/RFID</label>
                                <input type="text" class="form-control" id="barcode" name="barcode">
                            </div>
                        </div>
                        
                        <!-- Thông số kỹ thuật và hình ảnh -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dimensions">Kích thước (DxRxC)</label>
                                <input type="text" class="form-control" id="dimensions" name="dimensions" placeholder="Ví dụ: 10x5x3 cm">
                            </div>
                            
                            <div class="form-group">
                                <label for="weight">Trọng lượng (kg)</label>
                                <input type="number" class="form-control" id="weight" name="weight" min="0" step="0.01">
                            </div>
                            
                            <div class="form-group">
                                <label for="volume">Thể tích (dm³)</label>
                                <input type="number" class="form-control" id="volume" name="volume" min="0" step="0.01">
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Mô tả sản phẩm</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_image">Hình ảnh sản phẩm</label>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                                <div class="image-preview-container mt-2">
                                    <img id="image_preview" class="image-preview" src="#" alt="Xem trước hình ảnh">
                                    <div id="preview_placeholder" class="text-muted">Chọn hình ảnh để xem trước</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Thêm sản phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sửa sản phẩm -->
<div class="modal custom-modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">Cập nhật sản phẩm</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="edit_product_id" name="product_id">
                    <div class="row">
                        <!-- Thông tin cơ bản -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_product_code">Mã sản phẩm</label>
                                <input type="text" class="form-control" id="edit_product_code" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_product_name">Tên sản phẩm <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_category_id">Danh mục <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_category_id" name="category_id" required>
                                    <option value="">-- Chọn danh mục --</option>
                                    <?php foreach ($category_tree as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>">
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_unit">Đơn vị <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_unit" name="unit" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_price">Giá <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_price" name="price" min="0" step="1000" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_minimum_stock">Tồn kho tối thiểu</label>
                                <input type="number" class="form-control" id="edit_minimum_stock" name="minimum_stock" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_barcode">Mã Barcode/RFID</label>
                                <input type="text" class="form-control" id="edit_barcode" name="barcode">
                            </div>
                        </div>
                        
                        <!-- Thông số kỹ thuật và hình ảnh -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_dimensions">Kích thước (DxRxC)</label>
                                <input type="text" class="form-control" id="edit_dimensions" name="dimensions" placeholder="Ví dụ: 10x5x3 cm">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_weight">Trọng lượng (kg)</label>
                                <input type="number" class="form-control" id="edit_weight" name="weight" min="0" step="0.01">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_volume">Thể tích (dm³)</label>
                                <input type="number" class="form-control" id="edit_volume" name="volume" min="0" step="0.01">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_description">Mô tả sản phẩm</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_product_image">Hình ảnh sản phẩm</label>
                                <input type="file" class="form-control" id="edit_product_image" name="product_image" accept="image/*">
                                <input type="hidden" name="current_image" id="edit_current_image">
                                <div class="image-preview-container mt-2">
                                    <img id="edit_image_preview" class="image-preview" src="#" alt="Xem trước hình ảnh">
                                    <div id="edit_preview_placeholder" class="text-muted">Chọn hình ảnh để xem trước</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_product" class="btn btn-primary">Cập nhật sản phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xóa sản phẩm -->
<div class="modal custom-modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProductModalLabel">Xác nhận xóa sản phẩm</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa sản phẩm <strong id="delete_product_name"></strong>?</p>
                <p class="text-danger">Lưu ý: Hành động này không thể hoàn tác!</p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="product_id" id="delete_product_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="delete_product" class="btn btn-danger">Xóa sản phẩm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript xử lý modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xem trước hình ảnh khi thêm mới
    const productImage = document.getElementById('product_image');
    const imagePreview = document.getElementById('image_preview');
    const previewPlaceholder = document.getElementById('preview_placeholder');
    
    // Xử lý xem trước ảnh khi thêm mới
    if (productImage) {
        productImage.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.add('has-image');
                    previewPlaceholder.style.display = 'none';
                }
                
                reader.readAsDataURL(this.files[0]);
            } else {
                imagePreview.src = '#';
                imagePreview.classList.remove('has-image');
                previewPlaceholder.style.display = 'block';
            }
        });
    }
    
    // Xử lý xem trước ảnh khi cập nhật
    const editProductImage = document.getElementById('edit_product_image');
    const editImagePreview = document.getElementById('edit_image_preview');
    const editPreviewPlaceholder = document.getElementById('edit_preview_placeholder');
    
    if (editProductImage) {
        editProductImage.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    editImagePreview.src = e.target.result;
                    editImagePreview.classList.add('has-image');
                    editPreviewPlaceholder.style.display = 'none';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Hiển thị dữ liệu trong modal chỉnh sửa
    const editProductModal = document.getElementById('editProductModal');
    if (editProductModal) {
        editProductModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const productData = JSON.parse(button.getAttribute('data-product'));
            
            // Điền dữ liệu vào form
            document.getElementById('edit_product_id').value = productData.product_id;
            document.getElementById('edit_product_code').value = productData.product_code;
            document.getElementById('edit_product_name').value = productData.product_name;
            document.getElementById('edit_category_id').value = productData.category_id;
            document.getElementById('edit_unit').value = productData.unit;
            document.getElementById('edit_price').value = productData.price;
            document.getElementById('edit_minimum_stock').value = productData.minimum_stock;
            document.getElementById('edit_barcode').value = productData.barcode || '';
            document.getElementById('edit_dimensions').value = productData.dimensions || '';
            document.getElementById('edit_weight').value = productData.weight || '';
            document.getElementById('edit_volume').value = productData.volume || '';
            document.getElementById('edit_description').value = productData.description || '';
            document.getElementById('edit_current_image').value = productData.image_url || '';
            
            // Hiển thị hình ảnh sản phẩm nếu có
            if (productData.image_url) {
                editImagePreview.src = productData.image_url;
                editImagePreview.classList.add('has-image');
                editPreviewPlaceholder.style.display = 'none';
            } else {
                editImagePreview.src = '#';
                editImagePreview.classList.remove('has-image');
                editPreviewPlaceholder.style.display = 'block';
            }
        });
    }
    
    // Hiển thị dữ liệu trong modal xóa
    const deleteProductModal = document.getElementById('deleteProductModal');
    if (deleteProductModal) {
        deleteProductModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const productId = button.getAttribute('data-product-id');
            const productName = button.getAttribute('data-product-name');
            
            document.getElementById('delete_product_id').value = productId;
            document.getElementById('delete_product_name').textContent = productName;
        });
    }
});

// Tự động ẩn thông báo sau vài giây
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        const closeBtn = alert.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.click();
        }
    }, 5000);
});
</script>   