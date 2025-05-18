<?php
// Kiểm tra kết nối
if (!isset($conn)) {
    include_once 'config/connect.php';
}

// Kiểm tra quyền truy cập
if (!hasPermission('manage_import')) {
    echo '<div class="alert alert-danger" role="alert">
            Bạn không có quyền truy cập chức năng này!
          </div>';
    exit;
}

// Lấy danh sách nhà cung cấp
$supplier_query = "SELECT supplier_id, supplier_code, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name";
$suppliers = $conn->query($supplier_query)->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách kho
$warehouse_query = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
$warehouses = $conn->query($warehouse_query)->fetch_all(MYSQLI_ASSOC);

// Hàm tạo mã phiếu nhập tự động
function generateImportCode($conn) {
    $today = date('Ymd');
    $query = "SELECT COUNT(*) as count FROM import_orders WHERE import_code LIKE 'NK{$today}%'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $count = $row['count'] + 1;
    return "NK{$today}" . str_pad($count, 3, '0', STR_PAD_LEFT);
}

// Lấy danh sách phiếu nhập
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $conn->real_escape_string($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? $conn->real_escape_string($_GET['date_to']) : '';

$where = "WHERE 1=1";
if (!empty($search)) {
    $where .= " AND (io.import_code LIKE '%$search%' OR s.supplier_name LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where .= " AND io.status = '$status_filter'";
}
if (!empty($date_from)) {
    $where .= " AND DATE(io.created_at) >= '$date_from'";
}
if (!empty($date_to)) {
    $where .= " AND DATE(io.created_at) <= '$date_to'";
}

$count_query = "SELECT COUNT(*) as total FROM import_orders io 
                JOIN suppliers s ON io.supplier_id = s.supplier_id 
                JOIN warehouses w ON io.warehouse_id = w.warehouse_id 
                $where";
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$import_query = "SELECT io.*, s.supplier_name, w.warehouse_name, 
                 u1.full_name as created_by_name, u2.full_name as approved_by_name 
                 FROM import_orders io 
                 JOIN suppliers s ON io.supplier_id = s.supplier_id 
                 JOIN warehouses w ON io.warehouse_id = w.warehouse_id 
                 JOIN users u1 ON io.created_by = u1.user_id 
                 LEFT JOIN users u2 ON io.approved_by = u2.user_id 
                 $where 
                 ORDER BY io.created_at DESC 
                 LIMIT $offset, $limit";
$import_orders = $conn->query($import_query)->fetch_all(MYSQLI_ASSOC);

// Xử lý AJAX cho việc tìm kiếm sản phẩm
if (isset($_GET['ajax']) && $_GET['ajax'] == 'search_products') {
    header('Content-Type: application/json');
    $keyword = $conn->real_escape_string($_GET['keyword']);
    $product_query = "SELECT p.*, c.category_name FROM products p 
                      JOIN categories c ON p.category_id = c.category_id 
                      WHERE p.product_code LIKE '%$keyword%' OR p.product_name LIKE '%$keyword%' 
                      ORDER BY p.product_name 
                      LIMIT 10";
    $products = $conn->query($product_query)->fetch_all(MYSQLI_ASSOC);
    echo json_encode($products);
    exit;
}

// Xử lý AJAX cho việc lấy thông tin kệ kho
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_shelves') {
    header('Content-Type: application/json');
    $warehouse_id = intval($_GET['warehouse_id']);
    $shelf_query = "SELECT s.shelf_id, s.shelf_code, s.position, wz.zone_code 
                   FROM shelves s 
                   JOIN warehouse_zones wz ON s.zone_id = wz.zone_id 
                   WHERE wz.warehouse_id = $warehouse_id AND s.status = 'ACTIVE' 
                   ORDER BY wz.zone_code, s.shelf_code";
    $shelves = $conn->query($shelf_query)->fetch_all(MYSQLI_ASSOC);
    echo json_encode($shelves);
    exit;
}

// Xử lý AJAX cho việc tạo phiếu nhập mới
if (isset($_POST['ajax']) && $_POST['ajax'] == 'create_import') {
    header('Content-Type: application/json');
    
    try {
        $conn->begin_transaction();
        
        $import_code = generateImportCode($conn);
        $supplier_id = intval($_POST['supplier_id']);
        $warehouse_id = intval($_POST['warehouse_id']);
        $user_id = $_SESSION['user_id'];
        $notes = $conn->real_escape_string($_POST['notes']);
        
        $sql = "INSERT INTO import_orders (import_code, supplier_id, warehouse_id, status, notes, created_by) 
                VALUES ('$import_code', $supplier_id, $warehouse_id, 'DRAFT', '$notes', $user_id)";
        $conn->query($sql);
        
        $import_id = $conn->insert_id;
        
        $conn->commit();
        
        logUserActivity($user_id, 'CREATE_IMPORT', "Tạo phiếu nhập kho $import_code");
        
        echo json_encode([
            'success' => true,
            'import_id' => $import_id,
            'import_code' => $import_code
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Xử lý AJAX cho việc thêm sản phẩm vào phiếu nhập
if (isset($_POST['ajax']) && $_POST['ajax'] == 'add_import_detail') {
    header('Content-Type: application/json');
    
    try {
        $conn->begin_transaction();
        
        $import_id = intval($_POST['import_id']);
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $unit_price = floatval($_POST['unit_price']);
        $batch_number = $conn->real_escape_string($_POST['batch_number']);
        $expiry_date = $conn->real_escape_string($_POST['expiry_date']);
        $shelf_id = !empty($_POST['shelf_id']) ? intval($_POST['shelf_id']) : NULL;
        $notes = $conn->real_escape_string($_POST['notes']);
        
        // Kiểm tra xem phiếu nhập có ở trạng thái DRAFT không
        $check_sql = "SELECT status FROM import_orders WHERE import_id = $import_id";
        $result = $conn->query($check_sql);
        $import_status = $result->fetch_assoc()['status'];
        
        if ($import_status != 'DRAFT') {
            throw new Exception("Không thể thêm sản phẩm vào phiếu nhập đã hoàn thành hoặc hủy");
        }
        
        // Kiểm tra xem sản phẩm đã có trong phiếu chưa
        $check_detail = "SELECT detail_id FROM import_order_details 
                        WHERE import_id = $import_id AND product_id = $product_id 
                        AND (batch_number = '$batch_number' OR (batch_number IS NULL AND '$batch_number' = ''))";
        $detail_result = $conn->query($check_detail);
        
        if ($detail_result->num_rows > 0) {
            // Cập nhật chi tiết hiện có
            $detail_id = $detail_result->fetch_assoc()['detail_id'];
            $update_sql = "UPDATE import_order_details SET 
                          quantity = quantity + $quantity, 
                          unit_price = $unit_price, 
                          expiry_date = " . (!empty($expiry_date) ? "'$expiry_date'" : "NULL") . ", 
                          shelf_id = " . ($shelf_id ? "$shelf_id" : "NULL") . ", 
                          notes = '$notes' 
                          WHERE detail_id = $detail_id";
            $conn->query($update_sql);
        } else {
            // Thêm chi tiết mới
            $insert_sql = "INSERT INTO import_order_details 
                          (import_id, product_id, quantity, unit_price, batch_number, expiry_date, shelf_id, notes) 
                          VALUES ($import_id, $product_id, $quantity, $unit_price, '$batch_number', " . 
                          (!empty($expiry_date) ? "'$expiry_date'" : "NULL") . ", " . 
                          ($shelf_id ? "$shelf_id" : "NULL") . ", '$notes')";
            $conn->query($insert_sql);
        }
        
        // Cập nhật tổng tiền cho phiếu nhập
        $update_total = "UPDATE import_orders io 
                        SET total_amount = (SELECT SUM(quantity * unit_price) 
                                          FROM import_order_details 
                                          WHERE import_id = io.import_id) 
                        WHERE import_id = $import_id";
        $conn->query($update_total);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Xử lý AJAX cho việc xóa sản phẩm khỏi phiếu nhập
if (isset($_POST['ajax']) && $_POST['ajax'] == 'remove_import_detail') {
    header('Content-Type: application/json');
    
    try {
        $conn->begin_transaction();
        
        $detail_id = intval($_POST['detail_id']);
        $import_id = intval($_POST['import_id']);
        
        // Kiểm tra xem phiếu nhập có ở trạng thái DRAFT không
        $check_sql = "SELECT status FROM import_orders WHERE import_id = $import_id";
        $result = $conn->query($check_sql);
        $import_status = $result->fetch_assoc()['status'];
        
        if ($import_status != 'DRAFT') {
            throw new Exception("Không thể xóa sản phẩm từ phiếu nhập đã hoàn thành hoặc hủy");
        }
        
        // Xóa chi tiết
        $delete_sql = "DELETE FROM import_order_details WHERE detail_id = $detail_id";
        $conn->query($delete_sql);
        
        // Cập nhật tổng tiền cho phiếu nhập
        $update_total = "UPDATE import_orders io 
                        SET total_amount = (SELECT IFNULL(SUM(quantity * unit_price), 0) 
                                          FROM import_order_details 
                                          WHERE import_id = io.import_id) 
                        WHERE import_id = $import_id";
        $conn->query($update_total);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Xử lý AJAX cho việc hoàn thành phiếu nhập
if (isset($_POST['ajax']) && $_POST['ajax'] == 'complete_import') {
    header('Content-Type: application/json');
    
    try {
        $conn->begin_transaction();
        
        $import_id = intval($_POST['import_id']);
        $user_id = $_SESSION['user_id'];
        
        // Kiểm tra xem phiếu nhập có ở trạng thái DRAFT hoặc PENDING không
        $check_sql = "SELECT status, import_code FROM import_orders WHERE import_id = $import_id";
        $result = $conn->query($check_sql);
        $import_data = $result->fetch_assoc();
        
        if (!in_array($import_data['status'], ['DRAFT', 'PENDING'])) {
            throw new Exception("Không thể hoàn thành phiếu nhập có trạng thái khác DRAFT hoặc PENDING");
        }
        
        // Kiểm tra xem phiếu nhập có chi tiết không
        $check_details = "SELECT COUNT(*) as count FROM import_order_details WHERE import_id = $import_id";
        $details_result = $conn->query($check_details);
        $details_count = $details_result->fetch_assoc()['count'];
        
        if ($details_count == 0) {
            throw new Exception("Không thể hoàn thành phiếu nhập không có sản phẩm nào");
        }
        
        // Hoàn thành phiếu nhập
        $update_sql = "UPDATE import_orders 
                      SET status = 'COMPLETED', approved_by = $user_id, approved_at = NOW() 
                      WHERE import_id = $import_id";
        $conn->query($update_sql);
        
        // Tự động cập nhật tồn kho (trigger after_import_detail_insert sẽ thực hiện)
        $conn->commit();
        
        logUserActivity($user_id, 'COMPLETE_IMPORT', "Hoàn thành phiếu nhập kho {$import_data['import_code']}");
        
        echo json_encode([
            'success' => true
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Xử lý AJAX cho việc hủy phiếu nhập
if (isset($_POST['ajax']) && $_POST['ajax'] == 'cancel_import') {
    header('Content-Type: application/json');
    
    try {
        $import_id = intval($_POST['import_id']);
        $user_id = $_SESSION['user_id'];
        
        // Kiểm tra xem phiếu nhập có ở trạng thái DRAFT hoặc PENDING không
        $check_sql = "SELECT status, import_code FROM import_orders WHERE import_id = $import_id";
        $result = $conn->query($check_sql);
        $import_data = $result->fetch_assoc();
        
        if (!in_array($import_data['status'], ['DRAFT', 'PENDING'])) {
            throw new Exception("Không thể hủy phiếu nhập đã hoàn thành");
        }
        
        // Hủy phiếu nhập
        $update_sql = "UPDATE import_orders SET status = 'CANCELLED' WHERE import_id = $import_id";
        $conn->query($update_sql);
        
        logUserActivity($user_id, 'CANCEL_IMPORT', "Hủy phiếu nhập kho {$import_data['import_code']}");
        
        echo json_encode([
            'success' => true
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Xử lý AJAX cho việc lấy thông tin phiếu nhập và chi tiết
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_import_details') {
    header('Content-Type: application/json');
    
    $import_id = intval($_GET['import_id']);
    
    // Lấy thông tin phiếu nhập
    $import_query = "SELECT io.*, s.supplier_name, w.warehouse_name, 
                    u1.full_name as created_by_name, u2.full_name as approved_by_name 
                    FROM import_orders io 
                    JOIN suppliers s ON io.supplier_id = s.supplier_id 
                    JOIN warehouses w ON io.warehouse_id = w.warehouse_id 
                    JOIN users u1 ON io.created_by = u1.user_id 
                    LEFT JOIN users u2 ON io.approved_by = u2.user_id 
                    WHERE io.import_id = $import_id";
    $import_result = $conn->query($import_query);
    $import_data = $import_result->fetch_assoc();
    
    // Lấy chi tiết phiếu nhập
    $details_query = "SELECT iod.*, p.product_code, p.product_name, p.unit, 
                     s.shelf_code, wz.zone_code 
                     FROM import_order_details iod 
                     JOIN products p ON iod.product_id = p.product_id 
                     LEFT JOIN shelves s ON iod.shelf_id = s.shelf_id 
                     LEFT JOIN warehouse_zones wz ON s.zone_id = wz.zone_id 
                     WHERE iod.import_id = $import_id";
    $details_result = $conn->query($details_query);
    $details = $details_result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'import' => $import_data,
        'details' => $details
    ]);
    exit;
}
?>

<div class="function-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">Quản lý nhập kho</h4>
        <button type="button" class="btn btn-add" id="btnAddImport">
            <i class="fas fa-plus-circle me-2"></i>Tạo phiếu nhập
        </button>
    </div>
    
    <!-- Bộ lọc -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <input type="hidden" name="option" value="nhapkho">
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search" class="form-label">Tìm kiếm</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo $search; ?>" placeholder="Mã phiếu, nhà cung cấp...">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Tất cả</option>
                            <option value="DRAFT" <?php echo $status_filter == 'DRAFT' ? 'selected' : ''; ?>>Nháp</option>
                            <option value="PENDING" <?php echo $status_filter == 'PENDING' ? 'selected' : ''; ?>>Chờ duyệt</option>
                            <option value="COMPLETED" <?php echo $status_filter == 'COMPLETED' ? 'selected' : ''; ?>>Hoàn thành</option>
                            <option value="CANCELLED" <?php echo $status_filter == 'CANCELLED' ? 'selected' : ''; ?>>Đã hủy</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="date_from" class="form-label">Từ ngày</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo $date_from; ?>">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="date_to" class="form-label">Đến ngày</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo $date_to; ?>">
                    </div>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Lọc
                    </button>
                    <a href="?option=nhapkho" class="btn btn-outline-secondary">
                        <i class="fas fa-sync-alt me-2"></i>Đặt lại
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Danh sách phiếu nhập -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Mã phiếu</th>
                            <th>Nhà cung cấp</th>
                            <th>Kho nhập</th>
                            <th style="width: 120px;">Ngày tạo</th>
                            <th>Người tạo</th>
                            <th style="width: 120px;">Tổng tiền</th>
                            <th style="width: 100px;">Trạng thái</th>
                            <th style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($import_orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Không có dữ liệu</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($import_orders as $order): ?>
                            <tr>
                                <td><?php echo $order['import_code']; ?></td>
                                <td><?php echo $order['supplier_name']; ?></td>
                                <td><?php echo $order['warehouse_name']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $order['created_by_name']; ?></td>
                                <td class="text-end"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> đ</td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch ($order['status']) {
                                        case 'DRAFT':
                                            $status_class = 'bg-secondary';
                                            $status_text = 'Nháp';
                                            break;
                                        case 'PENDING':
                                            $status_class = 'bg-warning text-dark';
                                            $status_text = 'Chờ duyệt';
                                            break;
                                        case 'COMPLETED':
                                            $status_class = 'bg-success';
                                            $status_text = 'Hoàn thành';
                                            break;
                                        case 'CANCELLED':
                                            $status_class = 'bg-danger';
                                            $status_text = 'Đã hủy';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-info btn-sm view-import" 
                                                data-id="<?php echo $order['import_id']; ?>" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($order['status'] == 'DRAFT'): ?>
                                        <button type="button" class="btn btn-primary btn-sm edit-import" 
                                                data-id="<?php echo $order['import_id']; ?>" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?option=nhapkho&page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?option=nhapkho&page=<?php echo $i; ?>&search=<?php echo $search; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?option=nhapkho&page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Tạo phiếu nhập -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalTitle">Tạo phiếu nhập kho mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importForm">
                    <input type="hidden" id="import_id" name="import_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="supplier_id" class="form-label">Nhà cung cấp <span class="text-danger">*</span></label>
                                <select class="form-select" id="supplier_id" name="supplier_id" required>
                                    <option value="">-- Chọn nhà cung cấp --</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>">
                                        <?php echo $supplier['supplier_name'] . ' (' . $supplier['supplier_code'] . ')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="warehouse_id" class="form-label">Kho nhập <span class="text-danger">*</span></label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                                    <option value="">-- Chọn kho nhập --</option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['warehouse_id']; ?>">
                                        <?php echo $warehouse['warehouse_name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="btnSaveImport">Tạo phiếu</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi tiết phiếu nhập -->
<div class="modal fade" id="importDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importDetailModalTitle">Chi tiết phiếu nhập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-none" id="draftAlert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Phiếu nhập đang ở trạng thái nháp. Bạn cần thêm sản phẩm và hoàn thành phiếu để cập nhật vào kho.
                </div>
                
                <div id="importInfo" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 150px;">Mã phiếu:</th>
                                    <td><span id="detail_import_code"></span></td>
                                </tr>
                                <tr>
                                    <th>Nhà cung cấp:</th>
                                    <td><span id="detail_supplier"></span></td>
                                </tr>
                                <tr>
                                    <th>Kho nhập:</th>
                                    <td><span id="detail_warehouse"></span></td>
                                </tr>
                                <tr>
                                    <th>Ghi chú:</th>
                                    <td><span id="detail_notes"></span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 150px;">Ngày tạo:</th>
                                    <td><span id="detail_created_at"></span></td>
                                </tr>
                                <tr>
                                    <th>Người tạo:</th>
                                    <td><span id="detail_created_by"></span></td>
                                </tr>
                                <tr>
                                    <th>Trạng thái:</th>
                                    <td><span id="detail_status" class="badge"></span></td>
                                </tr>
                                <tr id="approved_row" class="d-none">
                                    <th>Người duyệt:</th>
                                    <td><span id="detail_approved_by"></span> (<span id="detail_approved_at"></span>)</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Form thêm sản phẩm - chỉ hiển thị với trạng thái DRAFT -->
                <div id="addProductForm" class="card mb-4 d-none">
                    <div class="card-body">
                        <h6 class="card-title">Thêm sản phẩm</h6>
                        <form id="productForm">
                            <input type="hidden" id="detail_import_id" name="import_id">
                            <input type="hidden" id="edit_detail_id" name="detail_id">
                            <input type="hidden" id="product_id" name="product_id">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="product_search" class="form-label">Tìm sản phẩm <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="product_search" placeholder="Nhập mã hoặc tên sản phẩm..." required>
                                            <button class="btn btn-outline-secondary" type="button" id="btnSearchProduct">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="product_selected" class="form-label">Sản phẩm đã chọn</label>
                                        <input type="text" class="form-control" id="product_selected" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="quantity" class="form-label">Số lượng <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="unit_price" class="form-label">Đơn giá <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="unit_price" name="unit_price" min="0" required>
                                            <span class="input-group-text">đ</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="batch_number" class="form-label">Số lô</label>
                                        <input type="text" class="form-control" id="batch_number" name="batch_number">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="expiry_date" class="form-label">Hạn sử dụng</label>
                                        <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shelf_id" class="form-label">Vị trí kệ</label>
                                        <select class="form-select" id="shelf_id" name="shelf_id">
                                            <option value="">-- Chọn kệ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="detail_notes" class="form-label">Ghi chú</label>
                                        <input type="text" class="form-control" id="detail_notes" name="notes">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" id="btnCancelProduct">Hủy</button>
                                <button type="button" class="btn btn-primary" id="btnAddProduct">Thêm sản phẩm</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Danh sách sản phẩm -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Danh sách sản phẩm</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered" id="productTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã SP</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Đơn vị</th>
                                        <th>Số lô</th>
                                        <th>Hạn SD</th>
                                        <th>Vị trí</th>
                                        <th>Số lượng</th>
                                        <th>Đơn giá</th>
                                        <th>Thành tiền</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="productTableBody">
                                    <!-- Dữ liệu sẽ được điền bằng JavaScript -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="9" class="text-end fw-bold">Tổng giá trị:</td>
                                        <td class="fw-bold" id="totalAmount">0 đ</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div id="actionButtons" class="mt-3 d-none">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-danger" id="btnCancelImport">
                                        <i class="fas fa-ban me-2"></i>Hủy phiếu nhập
                                    </button>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-success" id="btnCompleteImport">
                                        <i class="fas fa-check-circle me-2"></i>Hoàn thành phiếu nhập
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal tìm kiếm sản phẩm -->
<div class="modal fade" id="searchProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tìm kiếm sản phẩm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchKeyword" placeholder="Nhập tên hoặc mã sản phẩm...">
                    <button class="btn btn-primary" type="button" id="btnSearchProductModal">
                        <i class="fas fa-search me-2"></i>Tìm kiếm
                    </button>
                </div>
                
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover" id="searchResultTable">
                        <thead class="table-light">
                            <tr>
                                <th>Mã SP</th>
                                <th>Tên sản phẩm</th>
                                <th>Đơn vị</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="searchResultBody">
                            <!-- Kết quả tìm kiếm sẽ được điền bằng JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <div id="searchEmpty" class="text-center p-3 d-none">
                    <p class="mb-0">Không tìm thấy sản phẩm nào phù hợp!</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
// Biến toàn cục
let importModal;
let importDetailModal;
let searchProductModal;
let currentImportId = null;
let productList = [];

// Khởi tạo khi tài liệu đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo các modal
    importModal = new bootstrap.Modal(document.getElementById('importModal'));
    importDetailModal = new bootstrap.Modal(document.getElementById('importDetailModal'));
    searchProductModal = new bootstrap.Modal(document.getElementById('searchProductModal'));
    
    // Sự kiện khi nhấn nút tạo phiếu nhập mới
    document.getElementById('btnAddImport').addEventListener('click', function() {
        document.getElementById('importForm').reset();
        document.getElementById('importModalTitle').textContent = 'Tạo phiếu nhập kho mới';
        document.getElementById('btnSaveImport').textContent = 'Tạo phiếu';
        importModal.show();
    });
    
    // Sự kiện khi lưu phiếu nhập
    document.getElementById('btnSaveImport').addEventListener('click', createImportOrder);
    
    // Sự kiện khi nhấn nút xem chi tiết phiếu nhập
    document.querySelectorAll('.view-import').forEach(button => {
        button.addEventListener('click', function() {
            const importId = this.getAttribute('data-id');
            loadImportDetails(importId, false);
        });
    });
    
    // Sự kiện khi nhấn nút sửa phiếu nhập
    document.querySelectorAll('.edit-import').forEach(button => {
        button.addEventListener('click', function() {
            const importId = this.getAttribute('data-id');
            loadImportDetails(importId, true);
        });
    });
    
    // Sự kiện khi thay đổi kho nhập để lấy danh sách kệ
    document.getElementById('warehouse_id').addEventListener('change', function() {
        if (this.value) {
            fetchShelves(this.value);
        } else {
            resetShelfDropdown();
        }
    });
    
    // Sự kiện khi nhấn nút tìm kiếm sản phẩm
    document.getElementById('btnSearchProduct').addEventListener('click', function() {
        const keyword = document.getElementById('product_search').value.trim();
        if (keyword) {
            document.getElementById('searchKeyword').value = keyword;
            searchProducts(keyword);
            searchProductModal.show();
        }
    });
    
    // Sự kiện khi nhấn nút tìm kiếm trong modal tìm kiếm
    document.getElementById('btnSearchProductModal').addEventListener('click', function() {
        const keyword = document.getElementById('searchKeyword').value.trim();
        if (keyword) {
            searchProducts(keyword);
        }
    });
    
    // Sự kiện khi nhập vào ô tìm kiếm và nhấn Enter
    document.getElementById('searchKeyword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const keyword = this.value.trim();
            if (keyword) {
                searchProducts(keyword);
            }
            e.preventDefault();
        }
    });
    
    // Sự kiện khi nhấn nút thêm sản phẩm
    document.getElementById('btnAddProduct').addEventListener('click', addProductToImport);
    
    // Sự kiện khi nhấn nút hủy thêm sản phẩm
    document.getElementById('btnCancelProduct').addEventListener('click', resetProductForm);
    
    // Sự kiện khi nhấn nút hoàn thành phiếu nhập
    document.getElementById('btnCompleteImport').addEventListener('click', completeImport);
    
    // Sự kiện khi nhấn nút hủy phiếu nhập
    document.getElementById('btnCancelImport').addEventListener('click', cancelImport);
});

// Hàm tạo phiếu nhập mới
function createImportOrder() {
    const form = document.getElementById('importForm');
    
    // Kiểm tra dữ liệu nhập
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    formData.append('ajax', 'create_import');
    
    // Gửi yêu cầu AJAX
    fetch('?option=nhapkho', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            importModal.hide();
            
            // Hiển thị chi tiết phiếu nhập vừa tạo
            loadImportDetails(data.import_id, true);
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Đã xảy ra lỗi khi tạo phiếu nhập');
    });
}

// Hàm tải thông tin chi tiết phiếu nhập
function loadImportDetails(importId, isEdit) {
    currentImportId = importId;
    
    fetch(`?option=nhapkho&ajax=get_import_details&import_id=${importId}`)
    .then(response => response.json())
    .then(data => {
        const importData = data.import;
        
        // Cập nhật thông tin cơ bản
        document.getElementById('detail_import_code').textContent = importData.import_code;
        document.getElementById('detail_supplier').textContent = importData.supplier_name;
        document.getElementById('detail_warehouse').textContent = importData.warehouse_name;
        document.getElementById('detail_notes').textContent = importData.notes || '(Không có)';
        document.getElementById('detail_created_at').textContent = formatDateTime(importData.created_at);
        document.getElementById('detail_created_by').textContent = importData.created_by_name;
        
        // Hiển thị trạng thái
        const statusElement = document.getElementById('detail_status');
        statusElement.textContent = getStatusText(importData.status);
        statusElement.className = 'badge ' + getStatusClass(importData.status);
        
        // Hiển thị thông tin người duyệt nếu có
        const approvedRow = document.getElementById('approved_row');
        if (importData.approved_by) {
            document.getElementById('detail_approved_by').textContent = importData.approved_by_name;
            document.getElementById('detail_approved_at').textContent = formatDateTime(importData.approved_at);
            approvedRow.classList.remove('d-none');
        } else {
            approvedRow.classList.add('d-none');
        }
        
        // Hiển thị cảnh báo và form thêm sản phẩm nếu là DRAFT và đang sửa
        const draftAlert = document.getElementById('draftAlert');
        const addProductForm = document.getElementById('addProductForm');
        const actionButtons = document.getElementById('actionButtons');
        
        if (importData.status === 'DRAFT' && isEdit) {
            draftAlert.classList.remove('d-none');
            addProductForm.classList.remove('d-none');
            actionButtons.classList.remove('d-none');
            
            // Thiết lập giá trị form
            document.getElementById('detail_import_id').value = importId;
            
            // Tải danh sách kệ
            fetchShelves(importData.warehouse_id);
        } else {
            draftAlert.classList.add('d-none');
            addProductForm.classList.add('d-none');
            actionButtons.classList.add('d-none');
        }
        
        // Hiển thị danh sách sản phẩm
        renderProductList(data.details);
        
        // Hiển thị modal
        importDetailModal.show();
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Đã xảy ra lỗi khi tải thông tin phiếu nhập');
    });
}

// Hàm hiển thị danh sách sản phẩm
function renderProductList(products) {
    const tableBody = document.getElementById('productTableBody');
    tableBody.innerHTML = '';
    
    let totalAmount = 0;
    
    products.forEach((product, index) => {
        const row = document.createElement('tr');
        
        const expiryDate = product.expiry_date ? formatDate(product.expiry_date) : '';
        const shelfLocation = product.shelf_code ? 
            `${product.zone_code}-${product.shelf_code}` : '(Chưa chọn)';
        const totalPrice = product.quantity * product.unit_price;
        totalAmount += totalPrice;
        
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${product.product_code}</td>
            <td>${product.product_name}</td>
            <td>${product.unit}</td>
            <td>${product.batch_number || '(Không có)'}</td>
            <td>${expiryDate || '(Không có)'}</td>
            <td>${shelfLocation}</td>
            <td class="text-end">${product.quantity}</td>
            <td class="text-end">${formatCurrency(product.unit_price)}</td>
            <td class="text-end">${formatCurrency(totalPrice)}</td>
            <td>
                ${canEditImport() ? `
                <button type="button" class="btn btn-danger btn-sm" 
                    onclick="removeProduct(${product.detail_id})">
                    <i class="fas fa-trash"></i>
                </button>
                ` : ''}
            </td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // Cập nhật tổng giá trị
    document.getElementById('totalAmount').textContent = formatCurrency(totalAmount);
    
    // Lưu danh sách sản phẩm
    productList = products;
}

// Hàm kiểm tra xem có thể sửa phiếu nhập không
function canEditImport() {
    const statusElement = document.getElementById('detail_status');
    return statusElement.textContent.trim() === 'Nháp';
}

// Hàm tìm kiếm sản phẩm
function searchProducts(keyword) {
    fetch(`?option=nhapkho&ajax=search_products&keyword=${encodeURIComponent(keyword)}`)
    .then(response => response.json())
    .then(products => {
        const resultBody = document.getElementById('searchResultBody');
        const emptyResult = document.getElementById('searchEmpty');
        
        resultBody.innerHTML = '';
        
        if (products.length === 0) {
            emptyResult.classList.remove('d-none');
            return;
        }
        
        emptyResult.classList.add('d-none');
        
        products.forEach(product => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${product.product_code}</td>
                <td>${product.product_name}</td>
                <td>${product.unit}</td>
                <td>${product.category_name}</td>
                <td class="text-end">${formatCurrency(product.price)}</td>
                <td>
                    <button type="button" class="btn btn-primary btn-sm" 
                        onclick="selectProduct(${product.product_id}, '${product.product_code}', '${product.product_name}', ${product.price})">
                        <i class="fas fa-plus-circle me-2"></i>Chọn
                    </button>
                </td>
            `;
            resultBody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Đã xảy ra lỗi khi tìm kiếm sản phẩm');
    });
}

// Hàm chọn sản phẩm
function selectProduct(productId, productCode, productName, price) {
    document.getElementById('product_id').value = productId;
    document.getElementById('product_search').value = productCode;
    document.getElementById('product_selected').value = productName;
    document.getElementById('unit_price').value = price;
    document.getElementById('quantity').value = 1;
    
    searchProductModal.hide();
}

// Hàm thêm sản phẩm vào phiếu nhập
function addProductToImport() {
    const form = document.getElementById('productForm');
    
    // Kiểm tra dữ liệu nhập
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    formData.append('ajax', 'add_import_detail');
    
    // Gửi yêu cầu AJAX
    fetch('?option=nhapkho', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resetProductForm();
            
            // Tải lại thông tin chi tiết phiếu nhập
            loadImportDetails(currentImportId, true);
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Đã xảy ra lỗi khi thêm sản phẩm');
    });
}

// Hàm xóa sản phẩm khỏi phiếu nhập
function removeProduct(detailId) {
    if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi phiếu nhập?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax', 'remove_import_detail');
    formData.append('detail_id', detailId);
    formData.append('import_id', currentImportId);
    
    // Gửi yêu cầu AJAX
    fetch('?option=nhapkho', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Tải lại thông tin chi tiết phiếu nhập
            loadImportDetails(currentImportId, true);
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Đã xảy ra lỗi khi xóa sản phẩm');
    });
}

// Hàm hoàn thành phiếu nhập
function completeImport() {
    if (productList.length === 0) {
        alert('Không thể hoàn thành phiếu nhập chưa có sản phẩm nào!');
        return;
    }
    
    if (!confirm('Bạn có chắc chắn muốn hoàn thành phiếu nhập này?\nSau khi hoàn thành, số lượng sản phẩm sẽ được cập nhật vào kho và không thể chỉnh sửa phiếu.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax', 'complete_import');
    formData.append('import_id', currentImportId);
    
    // Gửi yêu cầu AJAX
    fetch('?option=nhapkho', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Phiếu nhập đã được hoàn thành thành công!');
            
            // Tải lại thông tin chi tiết phiếu nhập
            loadImportDetails(currentImportId, false);
            
            // Tải lại trang sau 1 giây
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Đã xảy ra lỗi khi hoàn thành phiếu nhập');
    });
}

// Hàm hủy phiếu nhập
function cancelImport() {
    if (!confirm('Bạn có chắc chắn muốn hủy phiếu nhập này?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax', 'cancel_import');
    formData.append('import_id', currentImportId);
    
    // Gửi yêu cầu AJAX
    fetch('?option=nhapkho', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Phiếu nhập đã được hủy thành công!');
            
            // Tải lại trang
            window.location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Đã xảy ra lỗi khi hủy phiếu nhập');
    });
}

// Hàm lấy danh sách kệ theo kho
function fetchShelves(warehouseId) {
    fetch(`?option=nhapkho&ajax=get_shelves&warehouse_id=${warehouseId}`)
    .then(response => response.json())
    .then(shelves => {
        const shelfDropdown = document.getElementById('shelf_id');
        resetShelfDropdown();
        
        shelves.forEach(shelf => {
            const option = document.createElement('option');
            option.value = shelf.shelf_id;
            option.textContent = `${shelf.zone_code}-${shelf.shelf_code} (${shelf.position})`;
            shelfDropdown.appendChild(option);
        });
    })
    .catch(error => {
        console.error('Lỗi:', error);
        resetShelfDropdown();
    });
}

// Hàm reset dropdown kệ
function resetShelfDropdown() {
    const shelfDropdown = document.getElementById('shelf_id');
    shelfDropdown.innerHTML = '<option value="">-- Chọn kệ --</option>';
}

// Hàm reset form thêm sản phẩm
function resetProductForm() {
    document.getElementById('productForm').reset();
    document.getElementById('edit_detail_id').value = '';
    document.getElementById('product_id').value = '';
}

// Hàm định dạng ngày tháng
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

// Hàm định dạng ngày tháng giờ
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('vi-VN') + ' ' + 
           date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
}

// Hàm định dạng tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        minimumFractionDigits: 0
    }).format(amount);
}

// Hàm lấy tên trạng thái
function getStatusText(status) {
    switch (status) {
        case 'DRAFT': return 'Nháp';
        case 'PENDING': return 'Chờ duyệt';
        case 'COMPLETED': return 'Hoàn thành';
        case 'CANCELLED': return 'Đã hủy';
        default: return status;
    }
}

// Hàm lấy class CSS cho trạng thái
function getStatusClass(status) {
    switch (status) {
        case 'DRAFT': return 'bg-secondary';
        case 'PENDING': return 'bg-warning text-dark';
        case 'COMPLETED': return 'bg-success';
        case 'CANCELLED': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
</script>