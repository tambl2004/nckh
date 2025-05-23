<div class="function-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">Quản lý nhập kho</h4>
        <div>
            <button class="btn btn-add" onclick="showImportForm()">
                <i class="fas fa-plus-circle me-2"></i>Tạo phiếu nhập
            </button>
        </div>
    </div>

    <!-- Tabs cho nhập kho và nhà cung cấp -->
    <ul class="nav nav-tabs mb-4" id="importTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="import-orders-tab" data-bs-toggle="tab" data-bs-target="#import-orders" type="button" role="tab" aria-controls="import-orders" aria-selected="true">
                Phiếu nhập kho
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="suppliers-tab" data-bs-toggle="tab" data-bs-target="#suppliers" type="button" role="tab" aria-controls="suppliers" aria-selected="false">
                Nhà cung cấp
            </button>
        </li>
    </ul>

    <!-- Nội dung các tab -->
    <div class="tab-content" id="importTabsContent">
        <!-- Tab phiếu nhập kho -->
        <div class="tab-pane fade show active" id="import-orders" role="tabpanel" aria-labelledby="import-orders-tab">
            <!-- Bộ lọc phiếu nhập kho -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="DRAFT">Nháp</option>
                        <option value="PENDING">Chờ duyệt</option>
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
                    <input type="text" class="form-control" id="searchImport" placeholder="Tìm kiếm mã phiếu, nhà cung cấp...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100" onclick="filterImportOrders()">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
            </div>

            <!-- Bảng danh sách phiếu nhập kho -->
            <div class="table-responsive">
                <table class="data-table table">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Nhà cung cấp</th>
                            <th>Kho nhập</th>
                            <th>Ngày tạo</th>
                            <th>Tổng giá trị</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="importOrdersList">
                        <?php
                        // Lấy danh sách phiếu nhập kho
                        $sql = "SELECT io.*, s.supplier_name, w.warehouse_name 
                                FROM import_orders io
                                JOIN suppliers s ON io.supplier_id = s.supplier_id
                                JOIN warehouses w ON io.warehouse_id = w.warehouse_id
                                ORDER BY io.created_at DESC";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status_class = '';
                                $status_text = '';
                                
                                switch ($row['status']) {
                                    case 'DRAFT':
                                        $status_class = 'bg-secondary';
                                        $status_text = 'Nháp';
                                        break;
                                    case 'PENDING':
                                        $status_class = 'bg-warning';
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
                                
                                echo "<tr>
                                    <td>{$row['import_code']}</td>
                                    <td>{$row['supplier_name']}</td>
                                    <td>{$row['warehouse_name']}</td>
                                    <td>" . date('d/m/Y H:i', strtotime($row['created_at'])) . "</td>
                                    <td>" . number_format($row['total_amount'], 0, ',', '.') . " đ</td>
                                    <td><span class='badge $status_class'>$status_text</span></td>
                                    <td class='action-buttons'>
                                        <button class='btn btn-sm btn-info' onclick='viewImportDetails({$row['import_id']})'>
                                            <i class='fas fa-eye'></i>
                                        </button>";
                                
                                if ($row['status'] == 'DRAFT') {
                                    echo "<button class='btn btn-sm btn-primary ms-1' onclick='editImport({$row['import_id']})'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <button class='btn btn-sm btn-success ms-1' onclick='submitImport({$row['import_id']})'>
                                            <i class='fas fa-check'></i>
                                        </button>
                                        <button class='btn btn-sm btn-danger ms-1' onclick='deleteImport({$row['import_id']})'>
                                            <i class='fas fa-trash'></i>
                                        </button>";
                                } elseif ($row['status'] == 'PENDING' && isAdmin()) {
                                    echo "<button class='btn btn-sm btn-success ms-1' onclick='approveImport({$row['import_id']})'>
                                            <i class='fas fa-check-double'></i>
                                        </button>
                                        <button class='btn btn-sm btn-danger ms-1' onclick='rejectImport({$row['import_id']})'>
                                            <i class='fas fa-times'></i>
                                        </button>";
                                }
                                
                                echo "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Không có phiếu nhập nào</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab nhà cung cấp -->
        <div class="tab-pane fade" id="suppliers" role="tabpanel" aria-labelledby="suppliers-tab">
            <!-- Bộ lọc nhà cung cấp -->
            <div class="row mb-4">
                <div class="col-md-4 offset-md-6">
                    <input type="text" class="form-control" id="searchSupplier" placeholder="Tìm kiếm nhà cung cấp...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-add w-100" onclick="showSupplierForm()">
                        <i class="fas fa-plus-circle me-2"></i>Thêm mới
                    </button>
                </div>
            </div>

            <!-- Bảng danh sách nhà cung cấp -->
            <div class="table-responsive">
                <table class="data-table table">
                    <thead>
                        <tr>
                            <th>Mã NCC</th>
                            <th>Tên nhà cung cấp</th>
                            <th>Người liên hệ</th>
                            <th>Điện thoại</th>
                            <th>Email</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersList">
                        <?php
                        // Lấy danh sách nhà cung cấp
                        $sql = "SELECT * FROM suppliers ORDER BY supplier_name";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status_class = $row['is_active'] ? 'bg-success' : 'bg-danger';
                                $status_text = $row['is_active'] ? 'Hoạt động' : 'Ngưng hoạt động';
                                
                                echo "<tr>
                                    <td>{$row['supplier_code']}</td>
                                    <td>{$row['supplier_name']}</td>
                                    <td>{$row['contact_person']}</td>
                                    <td>{$row['phone']}</td>
                                    <td>{$row['email']}</td>
                                    <td><span class='badge $status_class'>$status_text</span></td>
                                    <td class='action-buttons'>
                                        <button class='btn btn-sm btn-primary' onclick='editSupplier({$row['supplier_id']})'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <button class='btn btn-sm btn-" . ($row['is_active'] ? 'warning' : 'success') . " ms-1' onclick='toggleSupplierStatus({$row['supplier_id']}, " . ($row['is_active'] ? 'false' : 'true') . ")'>
                                            <i class='fas fa-" . ($row['is_active'] ? 'ban' : 'check') . "'></i>
                                        </button>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Không có nhà cung cấp nào</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal tạo phiếu nhập kho -->
<div class="custom-modal" id="importModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h5 class="modal-title" id="importModalTitle">Tạo phiếu nhập kho</h5>
            <button type="button" class="modal-close" onclick="closeImportModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="importForm" method="post" action="">
                <input type="hidden" id="importId" name="importId" value="0">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="importCode">Mã phiếu nhập <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="importCode" name="importCode" required readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="warehouseId">Kho nhập <span class="text-danger">*</span></label>
                            <select class="form-select" id="warehouseId" name="warehouseId" required>
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
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplierId">Nhà cung cấp <span class="text-danger">*</span></label>
                            <select class="form-select" id="supplierId" name="supplierId" required>
                                <option value="">Chọn nhà cung cấp</option>
                                <?php
                                $suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
                                while ($supplier = $suppliers->fetch_assoc()) {
                                    echo "<option value='{$supplier['supplier_id']}'>{$supplier['supplier_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes">Ghi chú</label>
                            <textarea class="form-control" id="notes" name="notes" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-4">
                    <label>Chi tiết sản phẩm</label>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="importDetailsTable">
                            <thead>
                                <tr>
                                    <th width="25%">Sản phẩm</th>
                                    <th width="10%">Số lượng</th>
                                    <th width="15%">Đơn giá</th>
                                    <th width="15%">Thành tiền</th>
                                    <th width="10%">Kệ/Vị trí</th>
                                    <th width="10%">Số lô</th>
                                    <th width="10%">Hạn sử dụng</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="importDetails">
                                <tr id="importDetailRow-1">
                                    <td>
                                        <select class="form-select product-select" name="details[1][productId]" onchange="loadProductInfo(this, 1)" required>
                                            <option value="">Chọn sản phẩm</option>
                                            <?php
                                            $products = $conn->query("SELECT product_id, product_code, product_name FROM products ORDER BY product_name");
                                            while ($product = $products->fetch_assoc()) {
                                                echo "<option value='{$product['product_id']}'>{$product['product_code']} - {$product['product_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control quantity-input" name="details[1][quantity]" min="1" value="1" onchange="calculateTotal(1)" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control price-input" name="details[1][unitPrice]" min="0" value="0" onchange="calculateTotal(1)" required>
                                    </td>
                                    <td>
                                        <span class="total-price">0</span>
                                    </td>
                                    <td>
                                        <select class="form-select" name="details[1][shelfId]">
                                            <option value="">Chọn kệ</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="details[1][batchNumber]">
                                    </td>
                                    <td>
                                        <input type="date" class="form-control" name="details[1][expiryDate]">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeImportDetail(1)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="8">
                                        <button type="button" class="btn btn-sm btn-success" onclick="addImportDetail()">
                                            <i class="fas fa-plus-circle me-1"></i>Thêm sản phẩm
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Tổng giá trị:</td>
                                    <td colspan="5">
                                        <span id="importTotalAmount" class="fw-bold">0</span> đ
                                        <input type="hidden" id="totalAmount" name="totalAmount" value="0">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveImport()">Lưu phiếu nhập</button>
        </div>
    </div>
</div>

<!-- Modal nhà cung cấp -->
<div class="custom-modal" id="supplierModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h5 class="modal-title" id="supplierModalTitle">Thêm nhà cung cấp mới</h5>
            <button type="button" class="modal-close" onclick="closeSupplierModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="supplierForm">
                <input type="hidden" id="supplierId" name="supplierId" value="0">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplierCode">Mã nhà cung cấp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="supplierCode" name="supplierCode" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplierName">Tên nhà cung cấp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="supplierName" name="supplierName" required>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contactPerson">Người liên hệ</label>
                            <input type="text" class="form-control" id="contactPerson" name="contactPerson">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Điện thoại</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="taxCode">Mã số thuế</label>
                            <input type="text" class="form-control" id="taxCode" name="taxCode">
                        </div>
                    </div>
                </div>
                <div class="form-group mt-3">
                    <label for="address">Địa chỉ</label>
                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSupplierModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveSupplier()">Lưu nhà cung cấp</button>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết phiếu nhập -->
<div class="custom-modal" id="importDetailsModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h5 class="modal-title" id="importDetailsModalTitle">Chi tiết phiếu nhập</h5>
            <button type="button" class="modal-close" onclick="closeImportDetailsModal()">×</button>
        </div>
        <div class="modal-body" id="importDetailsContent">
            <!-- Nội dung chi tiết phiếu nhập sẽ được load bằng JavaScript -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeImportDetailsModal()">Đóng</button>
            <button type="button" class="btn btn-primary" id="btnPrintImport" onclick="printImportDetails()">In phiếu</button>
        </div>
    </div>
</div>

<script>
// Biến toàn cục
let detailCounter = 1;

// Hiển thị form tạo phiếu nhập
function showImportForm() {
    // Reset form
    document.getElementById('importForm').reset();
    document.getElementById('importId').value = 0;
    document.getElementById('importModalTitle').innerText = 'Tạo phiếu nhập kho';
    
    // Tạo mã phiếu nhập mới
    const now = new Date();
    const importCode = 'NK' + 
                      now.getFullYear() + 
                      (now.getMonth() + 1).toString().padStart(2, '0') + 
                      now.getDate().toString().padStart(2, '0') + 
                      now.getHours().toString().padStart(2, '0') + 
                      now.getMinutes().toString().padStart(2, '0');
    document.getElementById('importCode').value = importCode;
    
    // Reset bảng chi tiết
    document.getElementById('importDetails').innerHTML = `
        <tr id="importDetailRow-1">
            <td>
                <select class="form-select product-select" name="details[1][productId]" onchange="loadProductInfo(this, 1)" required>
                    <option value="">Chọn sản phẩm</option>
                    <?php
                    $products = $conn->query("SELECT product_id, product_code, product_name FROM products ORDER BY product_name");
                    while ($product = $products->fetch_assoc()) {
                        echo "<option value='{$product['product_id']}'>{$product['product_code']} - {$product['product_name']}</option>";
                    }
                    ?>
                </select>
            </td>
            <td>
                <input type="number" class="form-control quantity-input" name="details[1][quantity]" min="1" value="1" onchange="calculateTotal(1)" required>
            </td>
            <td>
                <input type="number" class="form-control price-input" name="details[1][unitPrice]" min="0" value="0" onchange="calculateTotal(1)" required>
            </td>
            <td>
                <span class="total-price">0</span>
            </td>
            <td>
                <select class="form-select" name="details[1][shelfId]">
                    <option value="">Chọn kệ</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control" name="details[1][batchNumber]">
            </td>
            <td>
                <input type="date" class="form-control" name="details[1][expiryDate]">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeImportDetail(1)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    // Reset counter và tổng tiền
    detailCounter = 1;
    document.getElementById('importTotalAmount').innerText = '0';
    document.getElementById('totalAmount').value = '0';
    
    // Hiển thị modal
    document.getElementById('importModal').classList.add('show');
}

// Đóng modal phiếu nhập
function closeImportModal() {
    document.getElementById('importModal').classList.remove('show');
}

// Thêm dòng chi tiết phiếu nhập
function addImportDetail() {
    detailCounter++;
    
    const newRow = document.createElement('tr');
    newRow.id = `importDetailRow-${detailCounter}`;
    newRow.innerHTML = `
        <td>
            <select class="form-select product-select" name="details[${detailCounter}][productId]" onchange="loadProductInfo(this, ${detailCounter})" required>
                <option value="">Chọn sản phẩm</option>
                <?php
                $products = $conn->query("SELECT product_id, product_code, product_name FROM products ORDER BY product_name");
                while ($product = $products->fetch_assoc()) {
                    echo "<option value='{$product['product_id']}'>{$product['product_code']} - {$product['product_name']}</option>";
                }
                ?>
            </select>
        </td>
        <td>
            <input type="number" class="form-control quantity-input" name="details[${detailCounter}][quantity]" min="1" value="1" onchange="calculateTotal(${detailCounter})" required>
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
        </td>
        <td>
            <input type="text" class="form-control" name="details[${detailCounter}][batchNumber]">
        </td>
        <td>
            <input type="date" class="form-control" name="details[${detailCounter}][expiryDate]">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeImportDetail(${detailCounter})">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    document.getElementById('importDetails').appendChild(newRow);
}

// Xóa dòng chi tiết phiếu nhập
function removeImportDetail(rowId) {
    if (document.querySelectorAll('#importDetails tr').length <= 1) {
        alert('Phiếu nhập phải có ít nhất một sản phẩm');
        return;
    }
    
    const row = document.getElementById(`importDetailRow-${rowId}`);
    if (row) {
        row.remove();
        updateTotalAmount();
    }
}

// Tính tổng tiền cho một dòng
function calculateTotal(rowId) {
    const row = document.getElementById(`importDetailRow-${rowId}`);
    if (row) {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const total = quantity * price;
        
        row.querySelector('.total-price').innerText = total.toLocaleString('vi-VN');
        
        updateTotalAmount();
    }
}

// Cập nhật tổng tiền phiếu nhập
function updateTotalAmount() {
    let total = 0;
    document.querySelectorAll('.total-price').forEach(element => {
        total += parseFloat(element.innerText.replace(/\./g, '').replace(/,/g, '.')) || 0;
    });
    
    document.getElementById('importTotalAmount').innerText = total.toLocaleString('vi-VN');
    document.getElementById('totalAmount').value = total;
}

// Load thông tin sản phẩm khi chọn sản phẩm
function loadProductInfo(selectElement, rowId) {
    const productId = selectElement.value;
    if (!productId) return;
    
    const warehouseId = document.getElementById('warehouseId').value;
    if (!warehouseId) {
        alert('Vui lòng chọn kho nhập trước khi chọn sản phẩm');
        selectElement.value = '';
        return;
    }
    
    // Gọi AJAX để lấy thông tin sản phẩm và giá nhập gần nhất
    fetch(`ajax/nhapkho/get_product_info.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById(`importDetailRow-${rowId}`);
                if (row) {
                    // Cập nhật giá nhập gần nhất
                    row.querySelector('.price-input').value = data.last_import_price || data.price;
                    
                    // Tính lại tổng tiền
                    calculateTotal(rowId);
                    
                    // Load danh sách kệ trong kho đã chọn
                    loadShelvesForWarehouse(warehouseId, rowId);
                }
            } else {
                alert('Không thể lấy thông tin sản phẩm');
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
            alert('Đã xảy ra lỗi khi lấy thông tin sản phẩm');
        });
}

// Load danh sách kệ trong kho
function loadShelvesForWarehouse(warehouseId, rowId) {
    fetch(`ajax/nhapkho/get_shelves.php?warehouse_id=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById(`importDetailRow-${rowId}`);
                if (row) {
                    const shelfSelect = row.querySelector('select[name^="details"][name$="[shelfId]"]');
                    shelfSelect.innerHTML = '<option value="">Chọn kệ</option>';
                    
                    data.shelves.forEach(shelf => {
                        const option = document.createElement('option');
                        option.value = shelf.shelf_id;
                        option.textContent = `${shelf.shelf_code} - ${shelf.position || ''}`;
                        shelfSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
        });
}

// Lưu phiếu nhập
function saveImport() {
    // Kiểm tra dữ liệu đầu vào
    const warehouseId = document.getElementById('warehouseId').value;
    const supplierId = document.getElementById('supplierId').value;
    
    if (!warehouseId) {
        alert('Vui lòng chọn kho nhập');
        return;
    }
    
    if (!supplierId) {
        alert('Vui lòng chọn nhà cung cấp');
        return;
    }
    
    // Kiểm tra chi tiết sản phẩm
    const productSelects = document.querySelectorAll('.product-select');
    for (let i = 0; i < productSelects.length; i++) {
        if (!productSelects[i].value) {
            alert('Vui lòng chọn sản phẩm cho tất cả các dòng');
            return;
        }
    }
    
    // Lấy dữ liệu form
    const formData = new FormData(document.getElementById('importForm'));
    
    // Gửi dữ liệu lên server
    fetch('ajax/nhapkho/save_import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Lưu phiếu nhập thành công');
            closeImportModal();
            // Reload trang để cập nhật danh sách phiếu nhập
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Đã xảy ra lỗi khi lưu phiếu nhập');
    });
}

// Chỉnh sửa phiếu nhập
function editImport(importId) {
    // Gọi AJAX để lấy thông tin phiếu nhập
    fetch(`ajax/nhapkho/get_import.php?import_id=${importId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Điền thông tin phiếu nhập vào form
                document.getElementById('importId').value = data.import.import_id;
                document.getElementById('importCode').value = data.import.import_code;
                document.getElementById('warehouseId').value = data.import.warehouse_id;
                document.getElementById('supplierId').value = data.import.supplier_id;
                document.getElementById('notes').value = data.import.notes;
                document.getElementById('totalAmount').value = data.import.total_amount;
                document.getElementById('importTotalAmount').innerText = parseFloat(data.import.total_amount).toLocaleString('vi-VN');
                
                // Xóa tất cả các dòng chi tiết hiện tại
                document.getElementById('importDetails').innerHTML = '';
                
                // Thêm các dòng chi tiết từ dữ liệu
                data.details.forEach((detail, index) => {
                    detailCounter = index + 1;
                    
                    const newRow = document.createElement('tr');
                    newRow.id = `importDetailRow-${detailCounter}`;
                    newRow.innerHTML = `
                        <td>
                            <select class="form-select product-select" name="details[${detailCounter}][productId]" onchange="loadProductInfo(this, ${detailCounter})" required>
                                <option value="">Chọn sản phẩm</option>
                                <?php
                                $products = $conn->query("SELECT product_id, product_code, product_name FROM products ORDER BY product_name");
                                while ($product = $products->fetch_assoc()) {
                                    echo "<option value='{$product['product_id']}'>{$product['product_code']} - {$product['product_name']}</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control quantity-input" name="details[${detailCounter}][quantity]" min="1" value="${detail.quantity}" onchange="calculateTotal(${detailCounter})" required>
                        </td>
                        <td>
                            <input type="number" class="form-control price-input" name="details[${detailCounter}][unitPrice]" min="0" value="${detail.unit_price}" onchange="calculateTotal(${detailCounter})" required>
                        </td>
                        <td>
                            <span class="total-price">${(detail.quantity * detail.unit_price).toLocaleString('vi-VN')}</span>
                        </td>
                        <td>
                            <select class="form-select" name="details[${detailCounter}][shelfId]">
                                <option value="">Chọn kệ</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="details[${detailCounter}][batchNumber]" value="${detail.batch_number || ''}">
                        </td>
                        <td>
                            <input type="date" class="form-control" name="details[${detailCounter}][expiryDate]" value="${detail.expiry_date || ''}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeImportDetail(${detailCounter})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    
                    document.getElementById('importDetails').appendChild(newRow);
                    
                    // Thiết lập giá trị cho sản phẩm và load kệ
                    const productSelect = newRow.querySelector('.product-select');
                    productSelect.value = detail.product_id;
                    
                    // Load danh sách kệ và thiết lập giá trị
                    loadShelvesForWarehouse(data.import.warehouse_id, detailCounter);
                    setTimeout(() => {
                        const shelfSelect = newRow.querySelector('select[name^="details"][name$="[shelfId]"]');
                        if (shelfSelect && detail.shelf_id) {
                            shelfSelect.value = detail.shelf_id;
                        }
                    }, 500);
                });
                
                // Cập nhật tiêu đề modal
                document.getElementById('importModalTitle').innerText = 'Chỉnh sửa phiếu nhập kho';
                
                // Hiển thị modal
                document.getElementById('importModal').classList.add('show');
            } else {
                alert('Không thể lấy thông tin phiếu nhập');
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
            alert('Đã xảy ra lỗi khi lấy thông tin phiếu nhập');
        });
}

// Gửi phiếu nhập để duyệt
function submitImport(importId) {
    if (confirm('Bạn có chắc muốn gửi phiếu nhập này để duyệt?')) {
        fetch(`ajax/nhapkho/submit_import.php?import_id=${importId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Gửi phiếu nhập thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert('Đã xảy ra lỗi khi gửi phiếu nhập');
            });
    }
}

// Duyệt phiếu nhập
function approveImport(importId) {
    if (confirm('Bạn có chắc muốn duyệt phiếu nhập này? Hàng hóa sẽ được cập nhật vào kho.')) {
        fetch(`ajax/nhapkho/approve_import.php?import_id=${importId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Duyệt phiếu nhập thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert('Đã xảy ra lỗi khi duyệt phiếu nhập');
            });
    }
}

// Từ chối phiếu nhập
function rejectImport(importId) {
    const reason = prompt('Nhập lý do từ chối:');
    if (reason !== null) {
        fetch(`ajax/nhapkho/reject_import.php?import_id=${importId}&reason=${encodeURIComponent(reason)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Từ chối phiếu nhập thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert('Đã xảy ra lỗi khi từ chối phiếu nhập');
            });
    }
}

// Xóa phiếu nhập
function deleteImport(importId) {
    if (confirm('Bạn có chắc muốn xóa phiếu nhập này?')) {
        fetch(`ajax/nhapkho/delete_import.php?import_id=${importId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Xóa phiếu nhập thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert('Đã xảy ra lỗi khi xóa phiếu nhập');
            });
    }
}

// Xem chi tiết phiếu nhập
function viewImportDetails(importId) {
    fetch(`ajax/nhapkho/get_import_details.php?import_id=${importId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Tạo HTML hiển thị chi tiết phiếu nhập
                let html = `
                    <div class="import-details">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Mã phiếu:</strong> ${data.import.import_code}</p>
                                <p><strong>Nhà cung cấp:</strong> ${data.import.supplier_name}</p>
                                <p><strong>Kho nhập:</strong> ${data.import.warehouse_name}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ngày tạo:</strong> ${new Date(data.import.created_at).toLocaleString('vi-VN')}</p>
                                <p><strong>Trạng thái:</strong> <span class="badge ${getStatusClass(data.import.status)}">${getStatusText(data.import.status)}</span></p>
                                <p><strong>Ghi chú:</strong> ${data.import.notes || 'Không có'}</p>
                            </div>
                        </div>
                        
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                    <th>Kệ/Vị trí</th>
                                    <th>Số lô</th>
                                    <th>Hạn sử dụng</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.details.forEach((detail, index) => {
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${detail.product_name}</td>
                            <td>${detail.quantity}</td>
                            <td>${parseFloat(detail.unit_price).toLocaleString('vi-VN')} đ</td>
                            <td>${(detail.quantity * detail.unit_price).toLocaleString('vi-VN')} đ</td>
                            <td>${detail.shelf_code || 'Không có'}</td>
                            <td>${detail.batch_number || 'Không có'}</td>
                            <td>${detail.expiry_date ? new Date(detail.expiry_date).toLocaleDateString('vi-VN') : 'Không có'}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Tổng cộng:</td>
                                    <td colspan="4" class="fw-bold">${parseFloat(data.import.total_amount).toLocaleString('vi-VN')} đ</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;
                
                document.getElementById('importDetailsContent').innerHTML = html;
                document.getElementById('importDetailsModal').classList.add('show');
            } else {
                alert('Không thể lấy thông tin chi tiết phiếu nhập');
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
            alert('Đã xảy ra lỗi khi lấy thông tin chi tiết phiếu nhập');
        });
}

// Đóng modal chi tiết phiếu nhập
function closeImportDetailsModal() {
    document.getElementById('importDetailsModal').classList.remove('show');
}

// In phiếu nhập
function printImportDetails() {
    const content = document.getElementById('importDetailsContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>In phiếu nhập kho</title>
            <link rel="stylesheet" href="css/bootstrap.min.css">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .import-details { max-width: 800px; margin: 0 auto; }
                @media print {
                    .no-print { display: none; }
                    button { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h3 class="text-center mb-4">PHIẾU NHẬP KHO</h3>
                ${content}
                <div class="row mt-5">
                    <div class="col-md-6 text-center">
                        <p><strong>Người lập phiếu</strong></p>
                        <p>(Ký, họ tên)</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <p><strong>Người nhận hàng</strong></p>
                        <p>(Ký, họ tên)</p>
                    </div>
                </div>
                <div class="text-center mt-4 no-print">
                    <button class="btn btn-primary" onclick="window.print()">In phiếu</button>
                </div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// Hiển thị form nhà cung cấp
function showSupplierForm() {
    // Reset form
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = 0;
    document.getElementById('supplierModalTitle').innerText = 'Thêm nhà cung cấp mới';
    
    // Hiển thị modal
    document.getElementById('supplierModal').classList.add('show');
}

// Đóng modal nhà cung cấp
function closeSupplierModal() {
    document.getElementById('supplierModal').classList.remove('show');
}

// Lưu nhà cung cấp
function saveSupplier() {
    // Kiểm tra dữ liệu đầu vào
    const supplierCode = document.getElementById('supplierCode').value;
    const supplierName = document.getElementById('supplierName').value;
    
    if (!supplierCode) {
        alert('Vui lòng nhập mã nhà cung cấp');
        return;
    }
    
    if (!supplierName) {
        alert('Vui lòng nhập tên nhà cung cấp');
        return;
    }
    
    // Lấy dữ liệu form
    const formData = new FormData(document.getElementById('supplierForm'));
    
    // Gửi dữ liệu lên server
    fetch('ajax/nhapkho/save_supplier.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Lưu nhà cung cấp thành công');
            closeSupplierModal();
            // Reload trang để cập nhật danh sách nhà cung cấp
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Đã xảy ra lỗi khi lưu nhà cung cấp');
    });
}

// Chỉnh sửa nhà cung cấp
function editSupplier(supplierId) {
    // Gọi AJAX để lấy thông tin nhà cung cấp
    fetch(`ajax/nhapkho/get_supplier.php?supplier_id=${supplierId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Điền thông tin nhà cung cấp vào form
                document.getElementById('supplierId').value = data.supplier.supplier_id;
                document.getElementById('supplierCode').value = data.supplier.supplier_code;
                document.getElementById('supplierName').value = data.supplier.supplier_name;
                document.getElementById('contactPerson').value = data.supplier.contact_person || '';
                document.getElementById('phone').value = data.supplier.phone || '';
                document.getElementById('email').value = data.supplier.email || '';
                document.getElementById('taxCode').value = data.supplier.tax_code || '';
                document.getElementById('address').value = data.supplier.address || '';
                
                // Cập nhật tiêu đề modal
                document.getElementById('supplierModalTitle').innerText = 'Chỉnh sửa nhà cung cấp';
                
                // Hiển thị modal
                document.getElementById('supplierModal').classList.add('show');
            } else {
                alert('Không thể lấy thông tin nhà cung cấp');
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
            alert('Đã xảy ra lỗi khi lấy thông tin nhà cung cấp');
        });
}

// Bật/tắt trạng thái nhà cung cấp
function toggleSupplierStatus(supplierId, isActive) {
    const action = isActive ? 'kích hoạt' : 'vô hiệu hóa';
    if (confirm(`Bạn có chắc muốn ${action} nhà cung cấp này?`)) {
        fetch(`ajax/nhapkho/toggle_supplier.php?supplier_id=${supplierId}&is_active=${isActive ? 1 : 0}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`${action.charAt(0).toUpperCase() + action.slice(1)} nhà cung cấp thành công`);
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert(`Đã xảy ra lỗi khi ${action} nhà cung cấp`);
            });
    }
}

// Lọc phiếu nhập kho
function filterImportOrders() {
    const status = document.getElementById('statusFilter').value;
    const warehouse = document.getElementById('warehouseFilter').value;
    const search = document.getElementById('searchImport').value;
    
    // Chuyển hướng với các tham số lọc
    window.location.href = `admin.php?option=nhapkho&status=${status}&warehouse=${warehouse}&search=${encodeURIComponent(search)}`;
}

// Hàm hỗ trợ lấy class cho trạng thái
function getStatusClass(status) {
    switch (status) {
        case 'DRAFT': return 'bg-secondary';
        case 'PENDING': return 'bg-warning';
        case 'COMPLETED': return 'bg-success';
        case 'CANCELLED': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// Hàm hỗ trợ lấy text cho trạng thái
function getStatusText(status) {
    switch (status) {
        case 'DRAFT': return 'Nháp';
        case 'PENDING': return 'Chờ duyệt';
        case 'COMPLETED': return 'Hoàn thành';
        case 'CANCELLED': return 'Đã hủy';
        default: return 'Không xác định';
    }
}

// Khởi tạo khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý tham số URL để áp dụng bộ lọc
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const warehouse = urlParams.get('warehouse');
    const search = urlParams.get('search');
    
    if (status) document.getElementById('statusFilter').value = status;
    if (warehouse) document.getElementById('warehouseFilter').value = warehouse;
    if (search) document.getElementById('searchImport').value = search;
});
</script>     