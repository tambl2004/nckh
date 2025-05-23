<?php
// Kiểm tra quyền truy cập
if (!hasPermission('manage_export')) {
    echo '<div class="alert alert-danger">Bạn không có quyền truy cập chức năng này!</div>';
    exit;
}

// Xử lý tạo phiếu xuất kho mới
if (isset($_POST['create_export'])) {
    $exportCode = $conn->real_escape_string($_POST['exportCode']);
    $warehouseId = $conn->real_escape_string($_POST['warehouseId']);
    $recipient = $conn->real_escape_string($_POST['recipient']);
    $recipientAddress = $conn->real_escape_string($_POST['recipientAddress']);
    $orderReference = $conn->real_escape_string($_POST['orderReference']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $userId = $_SESSION['user_id'];
    
    // Tạo phiếu xuất mới
    $sql = "INSERT INTO export_orders (export_code, warehouse_id, recipient, recipient_address, 
            order_reference, notes, created_by, status) 
            VALUES ('$exportCode', '$warehouseId', '$recipient', '$recipientAddress', 
            '$orderReference', '$notes', $userId, 'DRAFT')";
    
    if ($conn->query($sql)) {
        $exportId = $conn->insert_id;
        $totalAmount = 0;
        
        // Thêm chi tiết phiếu xuất
        if (isset($_POST['details']) && is_array($_POST['details'])) {
            foreach ($_POST['details'] as $detail) {
                $productId = $conn->real_escape_string($detail['productId']);
                $quantity = $conn->real_escape_string($detail['quantity']);
                $unitPrice = $conn->real_escape_string($detail['unitPrice']);
                $batchNumber = isset($detail['batchNumber']) ? $conn->real_escape_string($detail['batchNumber']) : null;
                $shelfId = !empty($detail['shelfId']) ? $conn->real_escape_string($detail['shelfId']) : null;
                $detailNotes = isset($detail['notes']) ? $conn->real_escape_string($detail['notes']) : null;
                
                // Kiểm tra số lượng tồn kho trước khi thêm
                $checkSql = "SELECT SUM(quantity) as available FROM inventory WHERE product_id = $productId AND warehouse_id = $warehouseId";
                $checkResult = $conn->query($checkSql);
                $available = $checkResult->fetch_assoc()['available'] ?? 0;
                
                if ($available < $quantity) {
                    echo '<div class="alert alert-danger">Số lượng xuất vượt quá số lượng tồn kho!</div>';
                    continue;
                }
                
                // Thêm chi tiết phiếu xuất
                $detailSql = "INSERT INTO export_order_details (export_id, product_id, quantity, unit_price, 
                            batch_number, shelf_id, notes) 
                            VALUES ($exportId, $productId, $quantity, $unitPrice, " . 
                            ($batchNumber ? "'$batchNumber'" : "NULL") . ", " .
                            ($shelfId ? "$shelfId" : "NULL") . ", " .
                            ($detailNotes ? "'$detailNotes'" : "NULL") . ")";
                
                if ($conn->query($detailSql)) {
                    $totalAmount += ($quantity * $unitPrice);
                } else {
                    echo '<div class="alert alert-danger">Lỗi thêm chi tiết phiếu xuất: ' . $conn->error . '</div>';
                }
            }
            
            // Cập nhật tổng giá trị phiếu xuất
            $conn->query("UPDATE export_orders SET total_amount = $totalAmount WHERE export_id = $exportId");
            
            // Ghi log
            logUserActivity($_SESSION['user_id'], 'CREATE_EXPORT', "Tạo phiếu xuất kho $exportCode");
            
            echo '<div class="alert alert-success">Tạo phiếu xuất kho thành công!</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Lỗi tạo phiếu xuất kho: ' . $conn->error . '</div>';
    }
}

// Xử lý duyệt phiếu xuất kho
if (isset($_POST['approve_export'])) {
    $exportId = $conn->real_escape_string($_POST['export_id']);
    $userId = $_SESSION['user_id'];
    
    // Kiểm tra quyền duyệt
    if (!hasPermission('manage_export')) {
        echo '<div class="alert alert-danger">Bạn không có quyền duyệt phiếu xuất kho!</div>';
    } else {
        // Kiểm tra lại số lượng tồn kho trước khi duyệt
        $checkSql = "SELECT eod.product_id, eod.quantity, i.quantity as available, p.product_name
                    FROM export_order_details eod
                    JOIN export_orders eo ON eod.export_id = eo.export_id
                    JOIN inventory i ON eod.product_id = i.product_id AND i.warehouse_id = eo.warehouse_id
                    JOIN products p ON eod.product_id = p.product_id
                    WHERE eod.export_id = $exportId";
        $checkResult = $conn->query($checkSql);
        $canApprove = true;
        $errorMessages = [];
        
        while ($row = $checkResult->fetch_assoc()) {
            if ($row['quantity'] > $row['available']) {
                $canApprove = false;
                $errorMessages[] = "Sản phẩm {$row['product_name']} không đủ số lượng trong kho (Yêu cầu: {$row['quantity']}, Tồn kho: {$row['available']})";
            }
        }
        
        if ($canApprove) {
            // Cập nhật trạng thái phiếu xuất
            $sql = "UPDATE export_orders SET status = 'COMPLETED', approved_by = $userId, approved_at = NOW() WHERE export_id = $exportId";
            
            if ($conn->query($sql)) {
                // Ghi log
                $getCodeSql = "SELECT export_code FROM export_orders WHERE export_id = $exportId";
                $codeResult = $conn->query($getCodeSql);
                $exportCode = $codeResult->fetch_assoc()['export_code'];
                
                logUserActivity($_SESSION['user_id'], 'APPROVE_EXPORT', "Duyệt phiếu xuất kho $exportCode");
                
                echo '<div class="alert alert-success">Duyệt phiếu xuất kho thành công!</div>';
            } else {
                echo '<div class="alert alert-danger">Lỗi duyệt phiếu xuất kho: ' . $conn->error . '</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Không thể duyệt phiếu xuất kho:<br>' . implode('<br>', $errorMessages) . '</div>';
        }
    }
}

// Xử lý hủy phiếu xuất kho
if (isset($_POST['cancel_export'])) {
    $exportId = $conn->real_escape_string($_POST['export_id']);
    
    // Kiểm tra quyền hủy
    if (!hasPermission('manage_export')) {
        echo '<div class="alert alert-danger">Bạn không có quyền hủy phiếu xuất kho!</div>';
    } else {
        // Chỉ cho phép hủy phiếu ở trạng thái DRAFT hoặc PENDING
        $checkSql = "SELECT status, export_code FROM export_orders WHERE export_id = $exportId";
        $checkResult = $conn->query($checkSql);
        $exportData = $checkResult->fetch_assoc();
        
        if ($exportData['status'] == 'COMPLETED') {
            echo '<div class="alert alert-danger">Không thể hủy phiếu xuất kho đã hoàn thành!</div>';
        } else {
            // Cập nhật trạng thái phiếu xuất
            $sql = "UPDATE export_orders SET status = 'CANCELLED' WHERE export_id = $exportId";
            
            if ($conn->query($sql)) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'CANCEL_EXPORT', "Hủy phiếu xuất kho {$exportData['export_code']}");
                
                echo '<div class="alert alert-success">Hủy phiếu xuất kho thành công!</div>';
            } else {
                echo '<div class="alert alert-danger">Lỗi hủy phiếu xuất kho: ' . $conn->error . '</div>';
            }
        }
    }
}

// Tạo mã phiếu xuất tự động
function generateExportCode() {
    global $conn;
    $today = date('Ymd');
    $prefix = 'XK' . $today;
    
    $sql = "SELECT MAX(export_code) as max_code FROM export_orders WHERE export_code LIKE '$prefix%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    if ($row['max_code']) {
        $number = intval(substr($row['max_code'], -3)) + 1;
    } else {
        $number = 1;
    }
    
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}

// Hàm định dạng trạng thái
function formatStatus($status) {
    switch ($status) {
        case 'DRAFT':
            return '<span class="badge bg-secondary">Nháp</span>';
        case 'PENDING':
            return '<span class="badge bg-warning">Chờ duyệt</span>';
        case 'COMPLETED':
            return '<span class="badge bg-success">Đã duyệt</span>';
        case 'CANCELLED':
            return '<span class="badge bg-danger">Đã hủy</span>';
        default:
            return '<span class="badge bg-secondary">' . $status . '</span>';
    }
}

// Hàm định dạng tiền tệ
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' đ';
}

// Hàm định dạng ngày
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d/m/Y H:i');
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="function-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="page-title">Quản lý xuất kho</h4>
                    <button class="btn btn-primary" onclick="openExportModal()">
                        <i class="fas fa-plus"></i> Tạo phiếu xuất kho
                    </button>
                </div>
                
                <!-- Bảng danh sách phiếu xuất kho -->
                <div class="table-responsive">
                    <table class="data-table table">
                        <thead>
                            <tr>
                                <th>Mã phiếu</th>
                                <th>Người nhận</th>
                                <th>Kho xuất</th>
                                <th>Ngày tạo</th>
                                <th>Tổng giá trị</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="exportOrdersList">
                            <?php
                            // Lấy danh sách phiếu xuất kho
                            $sql = "SELECT eo.*, w.warehouse_name 
                                    FROM export_orders eo
                                    JOIN warehouses w ON eo.warehouse_id = w.warehouse_id
                                    ORDER BY eo.created_at DESC";
                            $result = $conn->query($sql);
                            
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . $row['export_code'] . '</td>';
                                    echo '<td>' . $row['recipient'] . '</td>';
                                    echo '<td>' . $row['warehouse_name'] . '</td>';
                                    echo '<td>' . formatDate($row['created_at']) . '</td>';
                                    echo '<td>' . formatCurrency($row['total_amount']) . '</td>';
                                    echo '<td>' . formatStatus($row['status']) . '</td>';
                                    echo '<td>';
                                    echo '<div class="action-buttons">';
                                    
                                    // Nút xem chi tiết
                                    echo '<button type="button" class="btn btn-info btn-sm" onclick="viewExportDetails(' . $row['export_id'] . ')" title="Xem chi tiết">';
                                    echo '<i class="fas fa-eye"></i>';
                                    echo '</button>';
                                    
                                    // Nút duyệt phiếu (chỉ hiển thị khi trạng thái là DRAFT hoặc PENDING)
                                    if ($row['status'] == 'DRAFT' || $row['status'] == 'PENDING') {
                                        echo '<button type="button" class="btn btn-success btn-sm ms-1" onclick="approveExport(' . $row['export_id'] . ')" title="Duyệt phiếu">';
                                        echo '<i class="fas fa-check"></i>';
                                        echo '</button>';
                                        
                                        // Nút hủy phiếu
                                        echo '<button type="button" class="btn btn-danger btn-sm ms-1" onclick="cancelExport(' . $row['export_id'] . ')" title="Hủy phiếu">';
                                        echo '<i class="fas fa-times"></i>';
                                        echo '</button>';
                                    }
                                    
                                    // Nút in phiếu
                                    echo '<button type="button" class="btn btn-secondary btn-sm ms-1" onclick="printExport(' . $row['export_id'] . ')" title="In phiếu">';
                                    echo '<i class="fas fa-print"></i>';
                                    echo '</button>';
                                    
                                    echo '</div>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center">Không có dữ liệu</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal tạo phiếu xuất kho -->
<div class="custom-modal" id="exportModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h5 class="modal-title" id="exportModalTitle">Tạo phiếu xuất kho</h5>
            <button type="button" class="modal-close" onclick="closeExportModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="exportForm" method="post" action="">
                <input type="hidden" id="exportId" name="exportId" value="0">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="exportCode">Mã phiếu xuất <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="exportCode" name="exportCode" required readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="warehouseId">Kho xuất <span class="text-danger">*</span></label>
                            <select class="form-select" id="warehouseId" name="warehouseId" required onchange="loadWarehouseProducts()">
                                <option value="">Chọn kho</option>
                                <?php
                                $warehouses = $conn->query("SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name");
                                while ($warehouse = $warehouses->fetch_assoc()) {
                                    echo "<option value='{$warehouse['warehouse_id']}'>{$warehouse['warehouse_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="recipient">Người nhận <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="recipient" name="recipient" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="recipientAddress">Địa chỉ nhận</label>
                            <input type="text" class="form-control" id="recipientAddress" name="recipientAddress">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="orderReference">Mã đơn hàng liên kết</label>
                            <input type="text" class="form-control" id="orderReference" name="orderReference">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes">Ghi chú</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4">Chi tiết xuất kho</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="exportDetailsTable">
                        <thead>
                            <tr>
                                <th width="30%">Sản phẩm</th>
                                <th width="10%">Số lượng</th>
                                <th width="15%">Đơn giá</th>
                                <th width="15%">Thành tiền</th>
                                <th width="20%">Vị trí kệ</th>
                                <th width="10%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="exportDetailsList">
                            <!-- Chi tiết phiếu xuất sẽ được thêm vào đây -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">
                                    <button type="button" class="btn btn-sm btn-success" onclick="addExportDetail()">
                                        <i class="fas fa-plus"></i> Thêm sản phẩm
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tổng giá trị:</strong></td>
                                <td colspan="3">
                                    <span id="exportTotalAmount">0</span>
                                    <input type="hidden" id="totalAmount" name="totalAmount" value="0">
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeExportModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary" name="create_export">Lưu phiếu xuất</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết phiếu xuất -->
<div class="custom-modal" id="viewExportModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h5 class="modal-title">Chi tiết phiếu xuất kho</h5>
            <button type="button" class="modal-close" onclick="closeViewExportModal()">×</button>
        </div>
        <div class="modal-body" id="exportDetailsContent">
            <!-- Nội dung chi tiết phiếu xuất sẽ được thêm vào đây -->
        </div>
    </div>
</div>

<!-- Form ẩn để xử lý duyệt phiếu -->
<form id="approveExportForm" method="post" style="display: none;">
    <input type="hidden" name="export_id" id="approve_export_id">
    <input type="hidden" name="approve_export" value="1">
</form>

<!-- Form ẩn để xử lý hủy phiếu -->
<form id="cancelExportForm" method="post" style="display: none;">
    <input type="hidden" name="export_id" id="cancel_export_id">
    <input type="hidden" name="cancel_export" value="1">
</form>

<script>
    // Biến toàn cục
    let detailCounter = 0;
    let warehouseProducts = [];
    let productPrices = {};
    let availableQuantities = {};
    
    // Mở modal tạo phiếu xuất
    function openExportModal() {
        // Reset form
        document.getElementById('exportForm').reset();
        document.getElementById('exportId').value = '0';
        document.getElementById('exportCode').value = '<?php echo generateExportCode(); ?>';
        document.getElementById('exportDetailsList').innerHTML = '';
        document.getElementById('exportTotalAmount').innerText = '0';
        document.getElementById('totalAmount').value = '0';
        
        // Hiển thị modal
        document.getElementById('exportModal').classList.add('show');
    }
    
    // Đóng modal phiếu xuất
    function closeExportModal() {
        document.getElementById('exportModal').classList.remove('show');
    }
    
    // Đóng modal xem chi tiết
    function closeViewExportModal() {
        document.getElementById('viewExportModal').classList.remove('show');
    }
    
    // Thêm dòng chi tiết phiếu xuất
    function addExportDetail() {
        const warehouseId = document.getElementById('warehouseId').value;
        if (!warehouseId) {
            alert('Vui lòng chọn kho xuất trước khi thêm sản phẩm!');
            return;
        }
        
        detailCounter++;
        
        const newRow = document.createElement('tr');
        newRow.id = `exportDetailRow-${detailCounter}`;
        newRow.innerHTML = `
            <td>
                <select class="form-select product-select" name="details[${detailCounter}][productId]" onchange="loadProductInfo(this, ${detailCounter})" required>
                    <option value="">Chọn sản phẩm</option>
                    ${getProductOptions()}
                </select>
            </td>
            <td>
                <input type="number" class="form-control quantity-input" name="details[${detailCounter}][quantity]" min="1" value="1" onchange="calculateTotal(${detailCounter})" required>
                <small class="text-muted available-qty"></small>
            </td>
            <td>
                <input type="number" class="form-control price-input" name="details[${detailCounter}][unitPrice]" min="0" value="0" onchange="calculateTotal(${detailCounter})" required>
            </td>
            <td>
                <span class="total-price">0</span>
            </td>
            <td>
                <select class="form-select" name="details[${detailCounter}][shelfId]">
                    <option value="">Chọn kệ</option>
                </select>
                <input type="hidden" name="details[${detailCounter}][batchNumber]" class="batch-number">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeExportDetail(${detailCounter})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        document.getElementById('exportDetailsList').appendChild(newRow);
    }
    
    // Xóa dòng chi tiết phiếu xuất
    function removeExportDetail(id) {
        const row = document.getElementById(`exportDetailRow-${id}`);
        if (row) {
            row.remove();
            calculateOrderTotal();
        }
    }
    
    // Lấy danh sách sản phẩm trong kho
    function loadWarehouseProducts() {
        const warehouseId = document.getElementById('warehouseId').value;
        if (!warehouseId) return;
        
        // Reset danh sách chi tiết
        document.getElementById('exportDetailsList').innerHTML = '';
        detailCounter = 0;
        
        // Lấy danh sách sản phẩm trong kho
        fetch(`ajax/xuatkho/get_warehouse_products.php?warehouse_id=${warehouseId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    warehouseProducts = data.products;
                    productPrices = {};
                    availableQuantities = {};
                    
                    // Lưu giá và số lượng tồn kho của từng sản phẩm
                    data.products.forEach(product => {
                        productPrices[product.product_id] = parseFloat(product.price);
                        availableQuantities[product.product_id] = parseInt(product.quantity);
                    });
                    
                    // Thêm dòng chi tiết đầu tiên
                    addExportDetail();
                } else {
                    alert('Không thể lấy danh sách sản phẩm trong kho!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi lấy danh sách sản phẩm!');
            });
    }
    
    // Tạo options cho select sản phẩm
    function getProductOptions() {
        let options = '<option value="">Chọn sản phẩm</option>';
        warehouseProducts.forEach(product => {
            options += `<option value="${product.product_id}" data-price="${product.price}" data-quantity="${product.quantity}">${product.product_code} - ${product.product_name} (Tồn: ${product.quantity})</option>`;
        });
        return options;
    }
    
    // Load thông tin sản phẩm khi chọn
    function loadProductInfo(selectElement, rowId) {
        const productId = selectElement.value;
        const row = document.getElementById(`exportDetailRow-${rowId}`);
        
        if (productId && row) {
            const priceInput = row.querySelector('.price-input');
            const quantityInput = row.querySelector('.quantity-input');
            const availableQty = row.querySelector('.available-qty');
            
            // Thiết lập giá và số lượng tối đa
            if (productPrices[productId]) {
                priceInput.value = productPrices[productId];
            }
            
            if (availableQuantities[productId]) {
                quantityInput.max = availableQuantities[productId];
                availableQty.textContent = `Tồn kho: ${availableQuantities[productId]}`;
            }
            
            // Lấy danh sách kệ chứa sản phẩm
            const warehouseId = document.getElementById('warehouseId').value;
            fetch(`ajax/xuatkho/get_product_shelves.php?product_id=${productId}&warehouse_id=${warehouseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const shelfSelect = row.querySelector('select[name^="details"][name$="[shelfId]"]');
                        const batchInput = row.querySelector('.batch-number');
                        
                        // Cập nhật options cho select kệ
                        shelfSelect.innerHTML = '<option value="">Chọn kệ</option>';
                        data.shelves.forEach(shelf => {
                            shelfSelect.innerHTML += `<option value="${shelf.shelf_id}" data-batch="${shelf.batch_number}">${shelf.shelf_code} - SL: ${shelf.quantity}</option>`;
                        });
                        
                        // Xử lý sự kiện khi chọn kệ
                        shelfSelect.onchange = function() {
                            const selectedOption = this.options[this.selectedIndex];
                            if (selectedOption && selectedOption.dataset.batch) {
                                batchInput.value = selectedOption.dataset.batch;
                            } else {
                                batchInput.value = '';
                            }
                        };
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            
            calculateTotal(rowId);
        }
    }     // Tính tổng tiền cho một dòng chi tiết
    function calculateTotal(rowId) {
        const row = document.getElementById(`exportDetailRow-${rowId}`);
        if (!row) return;
        
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.price-input').value) || 0;
        const totalPrice = quantity * unitPrice;
        
        row.querySelector('.total-price').textContent = formatCurrency(totalPrice);
        
        // Tính lại tổng tiền đơn hàng
        calculateOrderTotal();
    }
    
    // Tính tổng tiền cho toàn bộ đơn hàng
    function calculateOrderTotal() {
        let total = 0;
        const rows = document.querySelectorAll('#exportDetailsList tr');
        
        rows.forEach(row => {
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.price-input').value) || 0;
            total += quantity * unitPrice;
        });
        
        document.getElementById('exportTotalAmount').textContent = formatCurrency(total);
        document.getElementById('totalAmount').value = total;
    }
    
    // Định dạng số tiền
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
    }
    
    // Xem chi tiết phiếu xuất
    function viewExportDetails(exportId) {
        fetch(`ajax/xuatkho/get_export_details.php?export_id=${exportId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hiển thị thông tin phiếu xuất
                    let html = `
                        <div class="export-details">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Mã phiếu xuất:</strong> ${data.export.export_code}</p>
                                    <p><strong>Người nhận:</strong> ${data.export.recipient}</p>
                                    <p><strong>Địa chỉ nhận:</strong> ${data.export.recipient_address || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Kho xuất:</strong> ${data.export.warehouse_name}</p>
                                    <p><strong>Ngày tạo:</strong> ${data.export.created_at}</p>
                                    <p><strong>Trạng thái:</strong> ${data.export.status_text}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <p><strong>Ghi chú:</strong> ${data.export.notes || 'Không có ghi chú'}</p>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Số lượng</th>
                                            <th>Đơn giá</th>
                                            <th>Thành tiền</th>
                                            <th>Vị trí kệ</th>
                                            <th>Mã lô</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;
                    
                    // Thêm chi tiết sản phẩm
                    data.details.forEach(detail => {
                        html += `
                            <tr>
                                <td>${detail.product_name}</td>
                                <td>${detail.quantity}</td>
                                <td>${formatCurrency(detail.unit_price)}</td>
                                <td>${formatCurrency(detail.total_price)}</td>
                                <td>${detail.shelf_code || 'N/A'}</td>
                                <td>${detail.batch_number || 'N/A'}</td>
                            </tr>
                        `;
                    });
                    
                    // Thêm tổng tiền
                    html += `
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Tổng giá trị:</strong></td>
                                            <td colspan="3"><strong>${formatCurrency(data.export.total_amount)}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    `;
                    
                    // Hiển thị thông tin người duyệt nếu có
                    if (data.export.approved_by) {
                        html += `
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p><strong>Người duyệt:</strong> ${data.export.approved_by_name}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Ngày duyệt:</strong> ${data.export.approved_at}</p>
                                </div>
                            </div>
                        `;
                    }
                    
                    // Hiển thị nút in phiếu
                    html += `
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-secondary" onclick="printExport(${exportId})">
                                <i class="fas fa-print"></i> In phiếu xuất
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('exportDetailsContent').innerHTML = html;
                    document.getElementById('viewExportModal').classList.add('show');
                } else {
                    alert('Không thể lấy thông tin chi tiết phiếu xuất!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi lấy thông tin chi tiết phiếu xuất!');
            });
    }
    
    // Duyệt phiếu xuất
    function approveExport(exportId) {
        if (confirm('Bạn có chắc chắn muốn duyệt phiếu xuất này không?')) {
            document.getElementById('approve_export_id').value = exportId;
            document.getElementById('approveExportForm').submit();
        }
    }
    
    // Hủy phiếu xuất
    function cancelExport(exportId) {
        if (confirm('Bạn có chắc chắn muốn hủy phiếu xuất này không?')) {
            document.getElementById('cancel_export_id').value = exportId;
            document.getElementById('cancelExportForm').submit();
        }
    }
    
    // In phiếu xuất
    function printExport(exportId) {
        window.open(`ajax/xuatkho/print_export.php?export_id=${exportId}`, '_blank');
    }
</script>
        