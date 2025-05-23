<?php
// Kiểm tra quyền truy cập
if (!hasPermission('view_inventory')) {
    echo '<div class="alert alert-danger">Bạn không có quyền truy cập chức năng này!</div>';
    exit;
}

// Tạo mã chuyển kho tự động
function generateMovementCode() {
    global $conn;
    $today = date('Ymd');
    $prefix = 'DC' . $today;
    
    $sql = "SELECT MAX(movement_code) as max_code FROM inventory_movements WHERE movement_code LIKE '$prefix%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    if ($row['max_code']) {
        $number = intval(substr($row['max_code'], -3)) + 1;
    } else {
        $number = 1;
    }
    
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}

// Xử lý tạo phiếu chuyển kho mới
if (isset($_POST['create_movement'])) {
    $productId = intval($_POST['productId']);
    $sourceWarehouseId = intval($_POST['sourceWarehouseId']);
    $sourceShelfId = !empty($_POST['sourceShelfId']) ? intval($_POST['sourceShelfId']) : null;
    $targetWarehouseId = intval($_POST['targetWarehouseId']);
    $targetShelfId = !empty($_POST['targetShelfId']) ? intval($_POST['targetShelfId']) : null;
    $quantity = intval($_POST['quantity']);
    $batchNumber = $_POST['batchNumber'] ?? null;
    $reason = $_POST['reason'] ?? '';
    $userId = $_SESSION['user_id'];
    
    // Kiểm tra dữ liệu đầu vào
    if ($productId <= 0 || $sourceWarehouseId <= 0 || $targetWarehouseId <= 0 || $quantity <= 0) {
        echo '<div class="alert alert-danger">Thông tin không hợp lệ!</div>';
    } else {
        // Kiểm tra số lượng tồn kho
        $checkSql = "SELECT SUM(quantity) as available FROM inventory WHERE product_id = $productId AND warehouse_id = $sourceWarehouseId";
        $checkResult = $conn->query($checkSql);
        $available = $checkResult->fetch_assoc()['available'] ?? 0;
        
        if ($available < $quantity) {
            echo '<div class="alert alert-danger">Số lượng chuyển vượt quá số lượng tồn kho!</div>';
        } else {
            // Tạo mã chuyển kho
            $movementCode = generateMovementCode();
            
            // Tạo phiếu chuyển kho
            $sql = "INSERT INTO inventory_movements (movement_code, product_id, source_warehouse_id, source_shelf_id, 
                    target_warehouse_id, target_shelf_id, quantity, batch_number, status, reason, created_by) 
                    VALUES ('$movementCode', $productId, $sourceWarehouseId, " . 
                    ($sourceShelfId ? "$sourceShelfId" : "NULL") . ", $targetWarehouseId, " . 
                    ($targetShelfId ? "$targetShelfId" : "NULL") . ", $quantity, " . 
                    ($batchNumber ? "'$batchNumber'" : "NULL") . ", 'PENDING', '$reason', $userId)";
            
            if ($conn->query($sql)) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'CREATE_MOVEMENT', "Tạo phiếu chuyển kho $movementCode");
                
                echo '<div class="alert alert-success">Tạo phiếu chuyển kho thành công!</div>';
            } else {
                echo '<div class="alert alert-danger">Lỗi tạo phiếu chuyển kho: ' . $conn->error . '</div>';
            }
        }
    }
}

// Xử lý cập nhật trạng thái phiếu chuyển kho
if (isset($_POST['update_movement_status'])) {
    $movementId = intval($_POST['movement_id']);
    $status = $_POST['status'];
    $userId = $_SESSION['user_id'];
    
    // Kiểm tra quyền cập nhật
    if (!hasPermission('manage_import') && !hasPermission('manage_export')) {
        echo '<div class="alert alert-danger">Bạn không có quyền cập nhật phiếu chuyển kho!</div>';
    } else {
        // Cập nhật trạng thái
        $completedAt = ($status == 'COMPLETED') ? ", completed_at = NOW()" : "";
        $sql = "UPDATE inventory_movements SET status = '$status' $completedAt WHERE movement_id = $movementId";
        
        if ($conn->query($sql)) {
            // Nếu trạng thái là COMPLETED, cập nhật số lượng tồn kho
            if ($status == 'COMPLETED') {
                // Lấy thông tin phiếu chuyển kho
                $movementSql = "SELECT * FROM inventory_movements WHERE movement_id = $movementId";
                $movementResult = $conn->query($movementSql);
                $movement = $movementResult->fetch_assoc();
                
                if ($movement) {
                    // Giảm số lượng tồn kho ở kho nguồn
                    $decreaseSql = "UPDATE inventory SET quantity = quantity - {$movement['quantity']} 
                                    WHERE product_id = {$movement['product_id']} AND warehouse_id = {$movement['source_warehouse_id']}";
                    $conn->query($decreaseSql);
                    
                    // Tăng số lượng tồn kho ở kho đích
                    $increaseSql = "INSERT INTO inventory (product_id, warehouse_id, quantity) 
                                    VALUES ({$movement['product_id']}, {$movement['target_warehouse_id']}, {$movement['quantity']})
                                    ON DUPLICATE KEY UPDATE quantity = quantity + {$movement['quantity']}";
                    $conn->query($increaseSql);
                    
                    // Cập nhật vị trí sản phẩm nếu có kệ
                    if ($movement['source_shelf_id']) {
                        // Giảm số lượng ở kệ nguồn
                        $decreaseShelfSql = "UPDATE product_locations 
                                            SET quantity = quantity - {$movement['quantity']} 
                                            WHERE product_id = {$movement['product_id']} 
                                            AND shelf_id = {$movement['source_shelf_id']}";
                        $conn->query($decreaseShelfSql);
                    }
                    
                    if ($movement['target_shelf_id']) {
                        // Tăng số lượng ở kệ đích
                        $increaseShelfSql = "INSERT INTO product_locations 
                                            (product_id, shelf_id, batch_number, quantity, entry_date) 
                                            VALUES ({$movement['product_id']}, {$movement['target_shelf_id']}, " . 
                                            ($movement['batch_number'] ? "'{$movement['batch_number']}'" : "NULL") . 
                                            ", {$movement['quantity']}, NOW()) 
                                            ON DUPLICATE KEY UPDATE quantity = quantity + {$movement['quantity']}";
                        $conn->query($increaseShelfSql);
                    }
                }
            }
            
            // Ghi log
            logUserActivity($_SESSION['user_id'], 'UPDATE_MOVEMENT', "Cập nhật trạng thái phiếu chuyển kho thành $status");
            
            echo '<div class="alert alert-success">Cập nhật trạng thái phiếu chuyển kho thành công!</div>';
        } else {
            echo '<div class="alert alert-danger">Lỗi cập nhật phiếu chuyển kho: ' . $conn->error . '</div>';
        }
    }
}

// Hàm định dạng trạng thái
function formatMovementStatus($status) {
    switch ($status) {
        case 'PENDING':
            return '<span class="badge bg-warning">Chờ xử lý</span>';
        case 'IN_TRANSIT':
            return '<span class="badge bg-info">Đang vận chuyển</span>';
        case 'COMPLETED':
            return '<span class="badge bg-success">Hoàn thành</span>';
        case 'CANCELLED':
            return '<span class="badge bg-danger">Đã hủy</span>';
        default:
            return '<span class="badge bg-secondary">' . $status . '</span>';
    }
}
?>

<div class="function-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">Quản lý chuyển kho</h4>
        <div>
            <button class="btn btn-add" onclick="showMovementForm()">
                <i class="fas fa-exchange-alt me-2"></i>Tạo phiếu chuyển kho
            </button>
        </div>
    </div>
    
    <!-- Bộ lọc phiếu chuyển kho -->
    <div class="row mb-4">
        <div class="col-md-3">
            <select class="form-select" id="statusFilter">
                <option value="">Tất cả trạng thái</option>
                <option value="PENDING">Chờ xử lý</option>
                <option value="IN_TRANSIT">Đang vận chuyển</option>
                <option value="COMPLETED">Hoàn thành</option>
                <option value="CANCELLED">Đã hủy</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="warehouseFilter">
                <option value="">Tất cả kho</option>
                <?php
                $warehouses = $conn->query("SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name");
                while ($warehouse = $warehouses->fetch_assoc()) {
                    echo "<option value='{$warehouse['warehouse_id']}'>{$warehouse['warehouse_name']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" id="searchMovement" placeholder="Tìm kiếm mã phiếu, sản phẩm...">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100" onclick="filterMovements()">
                <i class="fas fa-filter me-2"></i>Lọc
            </button>
        </div>
    </div>
    
    <!-- Bảng danh sách phiếu chuyển kho -->
    <div class="table-responsive">
        <table class="data-table table">
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Sản phẩm</th>
                    <th>Kho nguồn</th>
                    <th>Kho đích</th>
                    <th>Số lượng</th>
                    <th>Lý do</th>
                    <th>Trạng thái</th>
                    <th>Người tạo</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="movementsList">
                <?php
                // Lấy danh sách phiếu chuyển kho
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
                        ORDER BY im.created_at DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['movement_code']}</td>
                            <td>{$row['product_code']} - {$row['product_name']}</td>
                            <td>{$row['source_warehouse']}" . ($row['source_shelf'] ? " (Kệ: {$row['source_shelf']})" : "") . "</td>
                            <td>{$row['target_warehouse']}" . ($row['target_shelf'] ? " (Kệ: {$row['target_shelf']})" : "") . "</td>
                            <td>{$row['quantity']}</td>
                            <td>" . (empty($row['reason']) ? "-" : $row['reason']) . "</td>
                            <td>" . formatMovementStatus($row['status']) . "</td>
                            <td>{$row['created_by_name']}</td>
                            <td>" . date('d/m/Y H:i', strtotime($row['created_at'])) . "</td>
                            <td class='action-buttons'>";
                        
                        if ($row['status'] == 'PENDING') {
                            echo "<button class='btn btn-sm btn-info' onclick='updateMovementStatus({$row['movement_id']}, \"IN_TRANSIT\")'>
                                    <i class='fas fa-truck'></i>
                                </button>
                                <button class='btn btn-sm btn-success ms-1' onclick='updateMovementStatus({$row['movement_id']}, \"COMPLETED\")'>
                                    <i class='fas fa-check'></i>
                                </button>
                                <button class='btn btn-sm btn-danger ms-1' onclick='updateMovementStatus({$row['movement_id']}, \"CANCELLED\")'>
                                    <i class='fas fa-times'></i>
                                </button>";
                        } elseif ($row['status'] == 'IN_TRANSIT') {
                            echo "<button class='btn btn-sm btn-success ms-1' onclick='updateMovementStatus({$row['movement_id']}, \"COMPLETED\")'>
                                    <i class='fas fa-check'></i>
                                </button>
                                <button class='btn btn-sm btn-danger ms-1' onclick='updateMovementStatus({$row['movement_id']}, \"CANCELLED\")'>
                                    <i class='fas fa-times'></i>
                                </button>";
                        }
                        
                        echo "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='10' class='text-center'>Không có phiếu chuyển kho nào</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal tạo phiếu chuyển kho -->
<div class="custom-modal" id="movementModal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h5 class="modal-title">Tạo phiếu chuyển kho</h5>
            <button type="button" class="modal-close" onclick="closeMovementModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="movementForm" method="post" action="">
                <input type="hidden" name="create_movement" value="1">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="productId">Sản phẩm <span class="text-danger">*</span></label>
                            <select class="form-select" id="productId" name="productId" required onchange="loadProductInfo(this.value)">
                                <option value="">Chọn sản phẩm</option>
                                <?php
                                $products = $conn->query("SELECT product_id, product_code, product_name FROM products ORDER BY product_name");
                                while ($product = $products->fetch_assoc()) {
                                    echo "<option value='{$product['product_id']}'>{$product['product_code']} - {$product['product_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="quantity">Số lượng <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sourceWarehouseId">Kho nguồn <span class="text-danger">*</span></label>
                            <select class="form-select" id="sourceWarehouseId" name="sourceWarehouseId" required onchange="loadSourceShelves(this.value)">
                                <option value="">Chọn kho nguồn</option>
                                <?php
                                $warehouses = $conn->query("SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name");
                                while ($warehouse = $warehouses->fetch_assoc()) {
                                    echo "<option value='{$warehouse['warehouse_id']}'>{$warehouse['warehouse_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sourceShelfId">Kệ nguồn</label>
                            <select class="form-select" id="sourceShelfId" name="sourceShelfId">
                                <option value="">Chọn kệ nguồn</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="targetWarehouseId">Kho đích <span class="text-danger">*</span></label>
                            <select class="form-select" id="targetWarehouseId" name="targetWarehouseId" required onchange="loadTargetShelves(this.value)">
                                <option value="">Chọn kho đích</option>
                                <?php
                                $warehouses = $conn->query("SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name");
                                while ($warehouse = $warehouses->fetch_assoc()) {
                                    echo "<option value='{$warehouse['warehouse_id']}'>{$warehouse['warehouse_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="targetShelfId">Kệ đích</label>
                            <select class="form-select" id="targetShelfId" name="targetShelfId">
                                <option value="">Chọn kệ đích</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="batchNumber">Số lô</label>
                            <input type="text" class="form-control" id="batchNumber" name="batchNumber">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="reason">Lý do chuyển kho</label>
                            <input type="text" class="form-control" id="reason" name="reason">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeMovementModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo phiếu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal cập nhật trạng thái -->
<div class="custom-modal" id="statusModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h5 class="modal-title">Cập nhật trạng thái</h5>
            <button type="button" class="modal-close" onclick="closeStatusModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="statusForm" method="post" action="">
                <input type="hidden" name="update_movement_status" value="1">
                <input type="hidden" id="movement_id" name="movement_id" value="">
                <input type="hidden" id="status" name="status" value="">
                
                <p id="statusConfirmMessage"></p>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary">Xác nhận</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Hiển thị modal tạo phiếu chuyển kho
function showMovementForm() {
    document.getElementById('movementModal').classList.add('show');
}

// Đóng modal tạo phiếu chuyển kho
function closeMovementModal() {
    document.getElementById('movementModal').classList.remove('show');
}

// Hiển thị modal cập nhật trạng thái
function updateMovementStatus(movementId, status) {
    document.getElementById('movement_id').value = movementId;
    document.getElementById('status').value = status;
    
    let statusText = '';
    switch (status) {
        case 'IN_TRANSIT':
            statusText = 'đang vận chuyển';
            break;
        case 'COMPLETED':
            statusText = 'hoàn thành';
            break;
        case 'CANCELLED':
            statusText = 'hủy';
            break;
    }
    
    document.getElementById('statusConfirmMessage').innerText = `Bạn có chắc chắn muốn cập nhật trạng thái phiếu chuyển kho thành "${statusText}"?`;
    document.getElementById('statusModal').classList.add('show');
}

// Đóng modal cập nhật trạng thái
function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('show');
}

// Lọc danh sách phiếu chuyển kho
function filterMovements() {
    const status = document.getElementById('statusFilter').value;
    const warehouse = document.getElementById('warehouseFilter').value;
    const search = document.getElementById('searchMovement').value;
    
    // Thực hiện lọc bằng AJAX
    fetch(`ajax/chuyenkho/filter_movements.php?status=${status}&warehouse=${warehouse}&search=${search}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('movementsList');
                tbody.innerHTML = '';
                
                if (data.movements.length > 0) {
                    data.movements.forEach(movement => {
                        let actions = '';
                        if (movement.status === 'PENDING') {
                            actions = `
                                <button class='btn btn-sm btn-info' onclick='updateMovementStatus(${movement.movement_id}, "IN_TRANSIT")'>
                                    <i class='fas fa-truck'></i>
                                </button>
                                <button class='btn btn-sm btn-success ms-1' onclick='updateMovementStatus(${movement.movement_id}, "COMPLETED")'>
                                    <i class='fas fa-check'></i>
                                </button>
                                <button class='btn btn-sm btn-danger ms-1' onclick='updateMovementStatus(${movement.movement_id}, "CANCELLED")'>
                                    <i class='fas fa-times'></i>
                                </button>
                            `;
                        } else if (movement.status === 'IN_TRANSIT') {
                            actions = `
                                <button class='btn btn-sm btn-success ms-1' onclick='updateMovementStatus(${movement.movement_id}, "COMPLETED")'>
                                    <i class='fas fa-check'></i>
                                </button>
                                <button class='btn btn-sm btn-danger ms-1' onclick='updateMovementStatus(${movement.movement_id}, "CANCELLED")'>
                                    <i class='fas fa-times'></i>
                                </button>
                            `;
                        }
                        
                        let statusBadge = '';
                        switch (movement.status) {
                            case 'PENDING':
                                statusBadge = '<span class="badge bg-warning">Chờ xử lý</span>';
                                break;
                            case 'IN_TRANSIT':
                                statusBadge = '<span class="badge bg-info">Đang vận chuyển</span>';
                                break;
                            case 'COMPLETED':
                                statusBadge = '<span class="badge bg-success">Hoàn thành</span>';
                                break;
                            case 'CANCELLED':
                                statusBadge = '<span class="badge bg-danger">Đã hủy</span>';
                                break;
                        }
                        
                        const row = `
                            <tr>
                                <td>${movement.movement_code}</td>
                                <td>${movement.product_code} - ${movement.product_name}</td>
                                <td>${movement.source_warehouse}${movement.source_shelf ? ` (Kệ: ${movement.source_shelf})` : ''}</td>
                                <td>${movement.target_warehouse}${movement.target_shelf ? ` (Kệ: ${movement.target_shelf})` : ''}</td>
                                <td>${movement.quantity}</td>
                                <td>${movement.reason || '-'}</td>
                                <td>${statusBadge}</td>
                                <td>${movement.created_by_name}</td>
                                <td>${new Date(movement.created_at).toLocaleString('vi-VN')}</td>
                                <td class='action-buttons'>${actions}</td>
                            </tr>
                        `;
                        
                        tbody.innerHTML += row;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center">Không có phiếu chuyển kho nào</td></tr>';
                }
            } else {
                alert('Có lỗi xảy ra khi lọc dữ liệu');
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
        });
}

// Lấy thông tin kệ theo kho nguồn
function loadSourceShelves(warehouseId) {
    if (!warehouseId) return;
    
    fetch(`ajax/nhapkho/get_shelves.php?warehouse_id=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const shelfSelect = document.getElementById('sourceShelfId');
                shelfSelect.innerHTML = '<option value="">Chọn kệ nguồn</option>';
                
                data.shelves.forEach(shelf => {
                    const option = document.createElement('option');
                    option.value = shelf.shelf_id;
                    option.textContent = `${shelf.shelf_code} - ${shelf.position || ''}`;
                    shelfSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
        });
}

// Lấy thông tin kệ theo kho đích
function loadTargetShelves(warehouseId) {
    if (!warehouseId) return;
    
    fetch(`ajax/nhapkho/get_shelves.php?warehouse_id=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const shelfSelect = document.getElementById('targetShelfId');
                shelfSelect.innerHTML = '<option value="">Chọn kệ đích</option>';
                
                data.shelves.forEach(shelf => {
                    const option = document.createElement('option');
                    option.value = shelf.shelf_id;
                    option.textContent = `${shelf.shelf_code} - ${shelf.position || ''}`;
                    shelfSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
        });
}

// Lấy thông tin sản phẩm và số lượng tồn kho
function loadProductInfo(productId) {
    if (!productId) return;
    
    fetch(`ajax/nhapkho/get_product_info.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Tự động chọn kho có sản phẩm
                fetch(`ajax/chuyenkho/get_product_warehouses.php?product_id=${productId}`)
                    .then(response => response.json())
                    .then(warehouseData => {
                        if (warehouseData.success && warehouseData.warehouses.length > 0) {
                            const sourceWarehouseSelect = document.getElementById('sourceWarehouseId');
                            // Nếu chỉ có một kho chứa sản phẩm, tự động chọn kho đó
                            if (warehouseData.warehouses.length === 1) {
                                sourceWarehouseSelect.value = warehouseData.warehouses[0].warehouse_id;
                                loadSourceShelves(sourceWarehouseSelect.value);
                                
                                // Tự động lấy thông tin kệ chứa sản phẩm
                                loadProductShelves(productId, sourceWarehouseSelect.value);
                            }
                        }
                    });
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
        });
}

// Lấy thông tin kệ chứa sản phẩm
function loadProductShelves(productId, warehouseId) {
    if (!productId || !warehouseId) return;
    
    fetch(`ajax/xuatkho/get_product_shelves.php?product_id=${productId}&warehouse_id=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const shelfSelect = document.getElementById('sourceShelfId');
                shelfSelect.innerHTML = '<option value="">Chọn kệ nguồn</option>';
                
                data.shelves.forEach(shelf => {
                    const option = document.createElement('option');
                    option.value = shelf.shelf_id;
                    option.textContent = `${shelf.shelf_code} - SL: ${shelf.quantity}${shelf.batch_number ? ` (Lô: ${shelf.batch_number})` : ''}`;
                    shelfSelect.appendChild(option);
                });
                
                // Nếu chỉ có một kệ chứa sản phẩm, tự động chọn kệ đó
                if (data.shelves.length === 1) {
                    shelfSelect.value = data.shelves[0].shelf_id;
                }
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
        });
}

// Kiểm tra số lượng tồn kho khi thay đổi số lượng
document.getElementById('quantity').addEventListener('change', function() {
    const productId = document.getElementById('productId').value;
    const warehouseId = document.getElementById('sourceWarehouseId').value;
    const shelfId = document.getElementById('sourceShelfId').value;
    const quantity = parseInt(this.value);
    
    if (productId && warehouseId && quantity > 0) {
        // Kiểm tra số lượng tồn kho
        fetch(`ajax/chuyenkho/check_inventory.php?product_id=${productId}&warehouse_id=${warehouseId}&shelf_id=${shelfId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (quantity > data.available) {
                        alert(`Số lượng vượt quá tồn kho! Tồn kho hiện tại: ${data.available}`);
                        this.value = data.available;
                    }
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
            });
    }
});

// Cập nhật thông tin kệ khi thay đổi kho nguồn
document.getElementById('sourceWarehouseId').addEventListener('change', function() {
    const productId = document.getElementById('productId').value;
    const warehouseId = this.value;
    
    if (productId && warehouseId) {
        loadSourceShelves(warehouseId);
        loadProductShelves(productId, warehouseId);
    }
});

// Ngăn chọn cùng một kho nguồn và đích
document.getElementById('targetWarehouseId').addEventListener('change', function() {
    const sourceWarehouseId = document.getElementById('sourceWarehouseId').value;
    
    if (this.value === sourceWarehouseId) {
        alert('Kho đích không thể trùng với kho nguồn!');
        this.value = '';
    } else if (this.value) {
        loadTargetShelves(this.value);
    }
});
</script>