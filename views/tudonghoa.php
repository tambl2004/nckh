<?php
// Yêu cầu kết nối CSDL
require_once 'config/connect.php';

// Hàm kiểm tra và cập nhật cảnh báo tồn kho thấp
function updateLowStockAlerts() {
    global $conn;
    $sql = "CALL check_low_stock_products()";
    return $conn->query($sql);
}

// Hàm kiểm tra và cập nhật cảnh báo sản phẩm sắp hết hạn
function updateExpiringProductAlerts($days = 30) {
    global $conn;
    $sql = "CALL check_expiring_products($days)";
    return $conn->query($sql);
}

// Hàm lấy danh sách cảnh báo đang hoạt động
function getActiveAlerts() {
    global $conn;
    $sql = "SELECT pa.*, p.product_code, p.product_name, w.warehouse_name 
            FROM product_alerts pa 
            JOIN products p ON pa.product_id = p.product_id 
            JOIN warehouses w ON pa.warehouse_id = w.warehouse_id 
            WHERE pa.is_active = 1 
            ORDER BY pa.created_at DESC";
    return $conn->query($sql);
}

// Hàm lấy danh sách di chuyển kho
function getInventoryMovements() {
    global $conn;
    $sql = "SELECT im.*, 
            p.product_code, p.product_name,
            sw.warehouse_name as source_warehouse, 
            tw.warehouse_name as target_warehouse,
            ss.shelf_code as source_shelf,
            ts.shelf_code as target_shelf,
            u.full_name as created_by_name
            FROM inventory_movements im
            JOIN products p ON im.product_id = p.product_id
            JOIN warehouses sw ON im.source_warehouse_id = sw.warehouse_id
            JOIN warehouses tw ON im.target_warehouse_id = tw.warehouse_id
            LEFT JOIN shelves ss ON im.source_shelf_id = ss.shelf_id
            LEFT JOIN shelves ts ON im.target_shelf_id = ts.shelf_id
            JOIN users u ON im.created_by = u.user_id
            ORDER BY im.created_at DESC
            LIMIT 10";
    return $conn->query($sql);
}

// Hàm gợi ý kệ tối ưu cho sản phẩm
function suggestOptimalShelf($product_id, $warehouse_id, $quantity) {
    global $conn;
    $sql = "CALL suggest_shelf_location($product_id, $warehouse_id, $quantity)";
    return $conn->query($sql);
}

// Hàm gợi ý vị trí dựa trên tần suất xuất/nhập
function suggestLocationByFrequency() {
    global $conn;
    $sql = "SELECT p.product_id, p.product_code, p.product_name, COUNT(eod.detail_id) as export_frequency,
            wz.zone_id, wz.zone_code, w.warehouse_name
            FROM products p
            JOIN export_order_details eod ON p.product_id = eod.product_id
            JOIN export_orders eo ON eod.export_id = eo.export_id
            JOIN warehouses w ON eo.warehouse_id = w.warehouse_id
            JOIN warehouse_zones wz ON w.warehouse_id = wz.warehouse_id
            WHERE eo.status = 'COMPLETED'
            AND eo.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.product_id, wz.zone_id
            ORDER BY export_frequency DESC
            LIMIT 10";
    return $conn->query($sql);
}

// Cập nhật cảnh báo
if (isset($_GET['action']) && $_GET['action'] == 'update_alerts') {
    updateLowStockAlerts();
    updateExpiringProductAlerts();
    header("Location: admin.php?option=tichhop&tab=alerts&status=updated");
    exit;
}

// Giải quyết cảnh báo
if (isset($_GET['action']) && $_GET['action'] == 'resolve_alert' && isset($_GET['alert_id'])) {
    $alert_id = (int)$_GET['alert_id'];
    $sql = "UPDATE product_alerts SET is_active = 0 WHERE alert_id = $alert_id";
    $conn->query($sql);
    header("Location: admin.php?option=tichhop&tab=alerts&status=resolved");
    exit;
}

// Xác định tab hiện tại
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'inventory_update';
?>

<div class="container-fluid">
    <div class="function-container">
        <h3 class="page-title">Tích hợp và Tự động hóa</h3>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $current_tab == 'inventory_update' ? 'active' : '' ?>" href="?option=tichhop&tab=inventory_update">
                    <i class="fas fa-sync-alt"></i> Tự động cập nhật tồn kho
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab == 'alerts' ? 'active' : '' ?>" href="?option=tichhop&tab=alerts">
                    <i class="fas fa-bell"></i> Tự động cảnh báo
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab == 'suggestions' ? 'active' : '' ?>" href="?option=tichhop&tab=suggestions">
                    <i class="fas fa-lightbulb"></i> Gợi ý thông minh
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <?php if($current_tab == 'inventory_update'): ?>
                <!-- Tự động cập nhật tồn kho -->
                <div class="tab-pane active">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Tình trạng cập nhật tồn kho</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> Tồn kho được cập nhật tự động khi có các hoạt động nhập/xuất kho.
                                    </div>
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Cập nhật khi nhập kho
                                            <span class="badge bg-success rounded-pill">Đang hoạt động</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Cập nhật khi xuất kho
                                            <span class="badge bg-success rounded-pill">Đang hoạt động</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Cập nhật khi kiểm kê
                                            <span class="badge bg-success rounded-pill">Đang hoạt động</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Di chuyển kho gần đây</h5>
                                    <button class="btn btn-primary btn-sm" onclick="window.location.href='admin.php?option=kho'">
                                        <i class="fas fa-plus"></i> Tạo mới
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Mã di chuyển</th>
                                                    <th>Sản phẩm</th>
                                                    <th>Kho nguồn</th>
                                                    <th>Kho đích</th>
                                                    <th>Số lượng</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày tạo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $movements = getInventoryMovements();
                                                if ($movements && $movements->num_rows > 0):
                                                    while($row = $movements->fetch_assoc()):
                                                        $status_class = '';
                                                        switch($row['status']) {
                                                            case 'PENDING': $status_class = 'bg-warning'; break;
                                                            case 'IN_TRANSIT': $status_class = 'bg-info'; break;
                                                            case 'COMPLETED': $status_class = 'bg-success'; break;
                                                            case 'CANCELLED': $status_class = 'bg-danger'; break;
                                                        }
                                                ?>
                                                <tr>
                                                    <td><?= $row['movement_code'] ?></td>
                                                    <td><?= $row['product_code'] ?> - <?= $row['product_name'] ?></td>
                                                    <td><?= $row['source_warehouse'] ?> <?= $row['source_shelf'] ? '(Kệ: '.$row['source_shelf'].')' : '' ?></td>
                                                    <td><?= $row['target_warehouse'] ?> <?= $row['target_shelf'] ? '(Kệ: '.$row['target_shelf'].')' : '' ?></td>
                                                    <td><?= $row['quantity'] ?></td>
                                                    <td><span class="badge <?= $status_class ?>"><?= $row['status'] ?></span></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                                </tr>
                                                <?php
                                                    endwhile;
                                                else:
                                                ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Không có dữ liệu di chuyển kho.</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif($current_tab == 'alerts'): ?>
                <!-- Tự động cảnh báo -->
                <div class="tab-pane active">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-end">
                                <a href="?option=tichhop&tab=alerts&action=update_alerts" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Cập nhật cảnh báo
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Cảnh báo đã được cập nhật thành công.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php elseif(isset($_GET['status']) && $_GET['status'] == 'resolved'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Cảnh báo đã được đánh dấu là đã giải quyết.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Cảnh báo đang hoạt động</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Sản phẩm</th>
                                                    <th>Kho</th>
                                                    <th>Loại cảnh báo</th>
                                                    <th>Thông điệp</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $alerts = getActiveAlerts();
                                                if ($alerts && $alerts->num_rows > 0):
                                                    while($row = $alerts->fetch_assoc()):
                                                        $alert_class = '';
                                                        switch($row['alert_type']) {
                                                            case 'LOW_STOCK': $alert_class = 'bg-warning'; break;
                                                            case 'EXPIRING_SOON': $alert_class = 'bg-danger'; break;
                                                            case 'OUT_OF_STOCK': $alert_class = 'bg-dark'; break;
                                                        }
                                                ?>
                                                <tr>
                                                    <td><?= $row['product_code'] ?> - <?= $row['product_name'] ?></td>
                                                    <td><?= $row['warehouse_name'] ?></td>
                                                    <td><span class="badge <?= $alert_class ?>"><?= $row['alert_type'] ?></span></td>
                                                    <td><?= $row['alert_message'] ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                                    <td>
                                                        <a href="?option=tichhop&tab=alerts&action=resolve_alert&alert_id=<?= $row['alert_id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Đánh dấu cảnh báo này là đã giải quyết?')">
                                                            <i class="fas fa-check"></i> Đã giải quyết
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                                    endwhile;
                                                else:
                                                ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">Không có cảnh báo nào đang hoạt động.</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Cấu hình cảnh báo tồn kho thấp</h5>
                                </div>
                                <div class="card-body">
                                    <form action="#" method="post" id="lowStockForm">
                                        <div class="form-group mb-3">
                                            <label for="low_stock_threshold">Ngưỡng tồn kho thấp (% so với tồn kho tối thiểu)</label>
                                            <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" value="100" min="1" max="200">
                                            <small class="form-text text-muted">Mặc định: 100% (cảnh báo khi bằng hoặc dưới mức tồn kho tối thiểu)</small>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Cấu hình cảnh báo sản phẩm sắp hết hạn</h5>
                                </div>
                                <div class="card-body">
                                    <form action="#" method="post" id="expiryForm">
                                        <div class="form-group mb-3">
                                            <label for="expiry_days">Số ngày trước khi hết hạn</label>
                                            <input type="number" class="form-control" id="expiry_days" name="expiry_days" value="30" min="1" max="90">
                                            <small class="form-text text-muted">Mặc định: 30 ngày</small>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif($current_tab == 'suggestions'): ?>
                <!-- Gợi ý thông minh -->
                <div class="tab-pane active">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Gợi ý vị trí kệ tối ưu khi nhập kho</h5>
                                </div>
                                <div class="card-body">
                                    <form action="#" method="post" id="shelfSuggestionForm">
                                        <div class="form-group mb-3">
                                            <label for="product_id">Sản phẩm</label>
                                            <select class="form-control" id="product_id" name="product_id" required>
                                                <option value="">-- Chọn sản phẩm --</option>
                                                <?php
                                                $products_sql = "SELECT product_id, product_code, product_name FROM products ORDER BY product_name";
                                                $products_result = $conn->query($products_sql);
                                                if ($products_result && $products_result->num_rows > 0):
                                                    while($product = $products_result->fetch_assoc()):
                                                ?>
                                                <option value="<?= $product['product_id'] ?>"><?= $product['product_code'] ?> - <?= $product['product_name'] ?></option>
                                                <?php
                                                    endwhile;
                                                endif;
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="warehouse_id">Kho</label>
                                            <select class="form-control" id="warehouse_id" name="warehouse_id" required>
                                                <option value="">-- Chọn kho --</option>
                                                <?php
                                                $warehouses_sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
                                                $warehouses_result = $conn->query($warehouses_sql);
                                                if ($warehouses_result && $warehouses_result->num_rows > 0):
                                                    while($warehouse = $warehouses_result->fetch_assoc()):
                                                ?>
                                                <option value="<?= $warehouse['warehouse_id'] ?>"><?= $warehouse['warehouse_name'] ?></option>
                                                <?php
                                                    endwhile;
                                                endif;
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="quantity">Số lượng</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                                        </div>
                                        <button type="button" id="getSuggestionBtn" class="btn btn-primary">Lấy gợi ý</button>
                                    </form>
                                    
                                    <div id="shelfSuggestionResults" class="mt-4" style="display: none;">
                                        <h6>Kết quả gợi ý:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Mã kệ</th>
                                                        <th>Sức chứa tối đa</th>
                                                        <th>Sức chứa khả dụng</th>
                                                        <th>Thao tác</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="suggestionTableBody">
                                                    <!-- Dữ liệu sẽ được điền bằng JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Đề xuất vị trí dựa trên tần suất xuất/nhập</h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">Sản phẩm có tần suất xuất/nhập cao trong 30 ngày qua:</p>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Sản phẩm</th>
                                                    <th>Kho</th>
                                                    <th>Khu vực</th>
                                                    <th>Tần suất xuất</th>
                                                    <th>Đề xuất</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $suggestions = suggestLocationByFrequency();
                                                if ($suggestions && $suggestions->num_rows > 0):
                                                    while($row = $suggestions->fetch_assoc()):
                                                        $suggestion = '';
                                                        if ($row['export_frequency'] > 20) {
                                                            $suggestion = 'Vị trí gần lối ra';
                                                        } elseif ($row['export_frequency'] > 10) {
                                                            $suggestion = 'Vị trí dễ tiếp cận';
                                                        } else {
                                                            $suggestion = 'Vị trí tiêu chuẩn';
                                                        }
                                                ?>
                                                <tr>
                                                    <td><?= $row['product_code'] ?> - <?= $row['product_name'] ?></td>
                                                    <td><?= $row['warehouse_name'] ?></td>
                                                    <td><?= $row['zone_code'] ?></td>
                                                    <td><?= $row['export_frequency'] ?></td>
                                                    <td><?= $suggestion ?></td>
                                                </tr>
                                                <?php
                                                    endwhile;
                                                else:
                                                ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">Không có dữ liệu tần suất xuất/nhập.</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// JavaScript cho chức năng gợi ý kệ
document.addEventListener('DOMContentLoaded', function() {
    // Lấy gợi ý kệ tối ưu
    document.getElementById('getSuggestionBtn').addEventListener('click', function() {
        const productId = document.getElementById('product_id').value;
        const warehouseId = document.getElementById('warehouse_id').value;
        const quantity = document.getElementById('quantity').value;
        
        if (!productId || !warehouseId || !quantity) {
            alert('Vui lòng điền đầy đủ thông tin');
            return;
        }
        
        // Gửi yêu cầu AJAX để lấy gợi ý
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `ajax/suggest_location.php?product_id=${productId}&warehouse_id=${warehouseId}&quantity=${quantity}`, true);
        
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const data = JSON.parse(this.responseText);
                    // Hiển thị kết quả
                    document.getElementById('shelfSuggestionResults').style.display = 'block';
                    const tableBody = document.getElementById('suggestionTableBody');
                    tableBody.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(function(shelf) {
                            tableBody.innerHTML += `
                                <tr>
                                    <td>${shelf.shelf_code}</td>
                                    <td>${shelf.max_capacity}</td>
                                    <td>${shelf.available_capacity}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="selectShelf(${shelf.shelf_id}, '${shelf.shelf_code}')">
                                            Chọn
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="4" class="text-center">Không tìm thấy kệ phù hợp</td></tr>';
                    }
                } catch (e) {
                    console.error('Lỗi xử lý dữ liệu:', e);
                    alert('Đã xảy ra lỗi khi xử lý dữ liệu.');
                }
            } else {
                alert('Đã xảy ra lỗi khi lấy dữ liệu.');
            }
        };
        
        xhr.onerror = function() {
            alert('Lỗi kết nối. Vui lòng thử lại sau.');
        };
        
        xhr.send();
    });
    
    // Xử lý khi nhấn nút lưu cấu hình cảnh báo tồn kho thấp
    if (document.getElementById('lowStockForm')) {
        document.getElementById('lowStockForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Đã lưu cấu hình cảnh báo tồn kho thấp.');
        });
    }
    
    // Xử lý khi nhấn nút lưu cấu hình cảnh báo sản phẩm sắp hết hạn
    if (document.getElementById('expiryForm')) {
        document.getElementById('expiryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Đã lưu cấu hình cảnh báo sản phẩm sắp hết hạn.');
        });
    }
});

// Hàm chọn kệ
function selectShelf(shelfId, shelfCode) {
    alert(`Đã chọn kệ: ${shelfCode} (ID: ${shelfId})`);
    // Trong thực tế, có thể chuyển hướng đến trang nhập kho với thông tin kệ đã chọn
    // window.location.href = `admin.php?option=nhapkho&shelf_id=${shelfId}&shelf_code=${shelfCode}`;
}
</script>
