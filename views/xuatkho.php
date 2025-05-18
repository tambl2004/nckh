<div class="function-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">Quản lý xuất kho</h4>
        <div>
            <button class="btn btn-add me-2" onclick="showExportForm()">
                <i class="fas fa-plus-circle me-2"></i>Tạo phiếu xuất
            </button>
            <button class="btn btn-primary" onclick="showInventoryMovementForm()">
                <i class="fas fa-exchange-alt me-2"></i>Di chuyển nội bộ
            </button>
        </div>
    </div>

    <!-- Tabs cho xuất kho và di chuyển nội bộ -->
    <ul class="nav nav-tabs mb-4" id="exportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="export-orders-tab" data-bs-toggle="tab" data-bs-target="#export-orders" type="button" role="tab" aria-controls="export-orders" aria-selected="true">
                Phiếu xuất kho
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="inventory-movements-tab" data-bs-toggle="tab" data-bs-target="#inventory-movements" type="button" role="tab" aria-controls="inventory-movements" aria-selected="false">
                Di chuyển nội bộ
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="suppliers-tab" data-bs-toggle="tab" data-bs-target="#suppliers" type="button" role="tab" aria-controls="suppliers" aria-selected="false">
                Nhà cung cấp
            </button>
        </li>
    </ul>

    <!-- Nội dung các tab -->
    <div class="tab-content" id="exportTabsContent">
        <!-- Tab phiếu xuất kho -->
        <div class="tab-pane fade show active" id="export-orders" role="tabpanel" aria-labelledby="export-orders-tab">
            <!-- Bộ lọc phiếu xuất kho -->
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
                        <!-- Danh sách kho sẽ được load bằng AJAX -->
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchExport" placeholder="Tìm kiếm mã phiếu, người nhận...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100" onclick="filterExportOrders()">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
            </div>

            <!-- Bảng danh sách phiếu xuất kho -->
            <div class="table-responsive">
                <table class="data-table table">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Kho xuất</th>
                            <th>Người nhận</th>
                            <th>Ngày tạo</th>
                            <th>Tổng giá trị</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="exportOrdersList">
                        <!-- Dữ liệu sẽ được load bằng AJAX -->
                    </tbody>
                </table>
            </div>
            <div id="exportPagination" class="d-flex justify-content-center mt-4">
                <!-- Phân trang sẽ được tạo bằng JavaScript -->
            </div>
        </div>

        <!-- Tab di chuyển nội bộ -->
        <div class="tab-pane fade" id="inventory-movements" role="tabpanel" aria-labelledby="inventory-movements-tab">
            <!-- Bộ lọc di chuyển nội bộ -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <select class="form-select" id="movementStatusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="PENDING">Chờ xử lý</option>
                        <option value="IN_TRANSIT">Đang vận chuyển</option>
                        <option value="COMPLETED">Hoàn thành</option>
                        <option value="CANCELLED">Đã hủy</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sourceWarehouseFilter">
                        <option value="">Tất cả kho nguồn</option>
                        <!-- Danh sách kho sẽ được load bằng AJAX -->
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchMovement" placeholder="Tìm kiếm mã phiếu, sản phẩm...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100" onclick="filterInventoryMovements()">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
            </div>

            <!-- Bảng danh sách di chuyển nội bộ -->
            <div class="table-responsive">
                <table class="data-table table">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Sản phẩm</th>
                            <th>Kho nguồn</th>
                            <th>Kho đích</th>
                            <th>Số lượng</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryMovementsList">
                        <!-- Dữ liệu sẽ được load bằng AJAX -->
                    </tbody>
                </table>
            </div>
            <div id="movementPagination" class="d-flex justify-content-center mt-4">
                <!-- Phân trang sẽ được tạo bằng JavaScript -->
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
                        <!-- Dữ liệu sẽ được load bằng AJAX -->
                    </tbody>
                </table>
            </div>
            <div id="supplierPagination" class="d-flex justify-content-center mt-4">
                <!-- Phân trang sẽ được tạo bằng JavaScript -->
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
            <form id="exportForm">
                <input type="hidden" id="exportId" name="exportId" value="0">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="exportCode">Mã phiếu xuất <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="exportCode" name="exportCode" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="warehouseId">Kho xuất <span class="text-danger">*</span></label>
                            <select class="form-select" id="warehouseId" name="warehouseId" required>
                                <option value="">Chọn kho</option>
                                <!-- Danh sách kho sẽ được load bằng AJAX -->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="recipient">Người nhận</label>
                            <input type="text" class="form-control" id="recipient" name="recipient">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="recipientAddress">Địa chỉ người nhận</label>
                            <input type="text" class="form-control" id="recipientAddress" name="recipientAddress">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="orderReference">Mã đơn hàng liên kết (nếu có)</label>
                    <input type="text" class="form-control" id="orderReference" name="orderReference">
                </div>
                
                <div class="form-group">
                    <label>Chi tiết sản phẩm</label>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="exportDetailsTable">
                            <thead>
                                <tr>
                                    <th width="35%">Sản phẩm</th>
                                    <th width="15%">Kệ/Vị trí</th>
                                    <th width="15%">Số lượng</th>
                                    <th width="15%">Đơn giá</th>
                                    <th width="15%">Thành tiền</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="exportDetails">
                                <tr id="exportDetailRow-1">
                                    <td>
                                        <select class="form-select product-select" name="details[1][productId]" onchange="loadProductInfo(this, 1)">
                                            <option value="">Chọn sản phẩm</option>
                                            <!-- Danh sách sản phẩm sẽ được load bằng AJAX -->
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select" name="details[1][shelfId]">
                                            <option value="">Chọn kệ</option>
                                            <!-- Danh sách kệ sẽ được load dựa vào sản phẩm và kho -->
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control quantity-input" name="details[1][quantity]" min="1" value="1" onchange="calculateTotal(1)">
                                        <small class="text-muted available-quantity">Còn: 0</small>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control price-input" name="details[1][unitPrice]" min="0" value="0" onchange="calculateTotal(1)">
                                    </td>
                                    <td>
                                        <span class="total-price">0</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeExportDetail(1)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addExportDetail()">
                                            <i class="fas fa-plus"></i> Thêm sản phẩm
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Tổng cộng:</td>
                                    <td colspan="2">
                                        <span id="exportTotalAmount">0</span>
                                        <input type="hidden" name="totalAmount" id="totalAmount" value="0">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Ghi chú</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeExportModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveExportAsDraft()">Lưu nháp</button>
            <button type="button" class="btn btn-success" onclick="submitExport()">Hoàn thành xuất kho</button>
        </div>
    </div>
</div>

<!-- Modal di chuyển nội bộ -->
<div class="custom-modal" id="movementModal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h5 class="modal-title" id="movementModalTitle">Tạo phiếu di chuyển nội bộ</h5>
            <button type="button" class="modal-close" onclick="closeMovementModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="movementForm">
                <input type="hidden" id="movementId" name="movementId" value="0">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="movementCode">Mã phiếu di chuyển <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="movementCode" name="movementCode" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="productId">Sản phẩm <span class="text-danger">*</span></label>
                            <select class="form-select" id="productId" name="productId" required onchange="checkProductAvailability()">
                                <option value="">Chọn sản phẩm</option>
                                <!-- Danh sách sản phẩm sẽ được load bằng AJAX -->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sourceWarehouseId">Kho nguồn <span class="text-danger">*</span></label>
                            <select class="form-select" id="sourceWarehouseId" name="sourceWarehouseId" required onchange="loadSourceShelves(); checkProductAvailability()">
                                <option value="">Chọn kho nguồn</option>
                                <!-- Danh sách kho sẽ được load bằng AJAX -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sourceShelfId">Kệ nguồn</label>
                            <select class="form-select" id="sourceShelfId" name="sourceShelfId">
                                <option value="">Chọn kệ nguồn</option>
                                <!-- Danh sách kệ sẽ được load dựa vào kho nguồn -->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="targetWarehouseId">Kho đích <span class="text-danger">*</span></label>
                            <select class="form-select" id="targetWarehouseId" name="targetWarehouseId" required onchange="loadTargetShelves()">
                                <option value="">Chọn kho đích</option>
                                <!-- Danh sách kho sẽ được load bằng AJAX -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="targetShelfId">Kệ đích</label>
                            <select class="form-select" id="targetShelfId" name="targetShelfId">
                                <option value="">Chọn kệ đích</option>
                                <!-- Danh sách kệ sẽ được load dựa vào kho đích -->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="quantity">Số lượng <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                            <small class="text-muted" id="availableQuantity">Số lượng khả dụng: 0</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="batchNumber">Số lô</label>
                            <input type="text" class="form-control" id="batchNumber" name="batchNumber">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="movementReason">Lý do di chuyển</label>
                    <textarea class="form-control" id="movementReason" name="reason" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeMovementModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="createMovement()">Tạo phiếu di chuyển</button>
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
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contactPerson">Người liên hệ</label>
                            <input type="text" class="form-control" id="contactPerson" name="contactPerson">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                </div>
                <div class="row">
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
                <div class="form-group">
                    <label for="address">Địa chỉ</label>
                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="isActive" name="isActive" checked>
                        <label class="form-check-label" for="isActive">Đang hoạt động</label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSupplierModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveSupplier()">Lưu</button>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết phiếu xuất -->
<div class="custom-modal" id="viewExportModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h5 class="modal-title">Chi tiết phiếu xuất</h5>
            <button type="button" class="modal-close" onclick="closeViewExportModal()">×</button>
        </div>
        <div class="modal-body" id="exportDetailContent">
            <!-- Nội dung chi tiết phiếu xuất sẽ được load bằng AJAX -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewExportModal()">Đóng</button>
            <button type="button" class="btn btn-primary" id="btnApproveExport" onclick="approveExport()">Duyệt phiếu xuất</button>
            <button type="button" class="btn btn-danger" id="btnCancelExport" onclick="cancelExport()">Hủy phiếu xuất</button>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết di chuyển nội bộ -->
<div class="custom-modal" id="viewMovementModal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h5 class="modal-title">Chi tiết phiếu di chuyển</h5>
            <button type="button" class="modal-close" onclick="closeViewMovementModal()">×</button>
        </div>
        <div class="modal-body" id="movementDetailContent">
            <!-- Nội dung chi tiết phiếu di chuyển sẽ được load bằng AJAX -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewMovementModal()">Đóng</button>
            <button type="button" class="btn btn-primary" id="btnUpdateMovementStatus" onclick="updateMovementStatus()">Cập nhật trạng thái</button>
            <button type="button" class="btn btn-danger" id="btnCancelMovement" onclick="cancelMovement()">Hủy phiếu di chuyển</button>
        </div>
    </div>
</div>

<script>
// Biến toàn cục
let detailCounter = 1;
let currentExportId = 0;
let currentMovementId = 0;
let currentSupplierId = 0;

// Load dữ liệu khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    // Load danh sách phiếu xuất kho
    loadExportOrders();
    
    // Load danh sách kho
    loadWarehouses();
    
    // Load danh sách sản phẩm
    loadProducts();
    
    // Load danh sách di chuyển nội bộ
    loadInventoryMovements();
    
    // Load danh sách nhà cung cấp
    loadSuppliers();
    
    // Thêm sự kiện cho các tab
    const exportTabs = document.querySelectorAll('#exportTabs .nav-link');
    exportTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target').substring(1);
            
            // Ẩn tất cả các tab content
            document.querySelectorAll('#exportTabsContent .tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Hiển thị tab được chọn
            document.getElementById(targetId).classList.add('show', 'active');
            
            // Cập nhật trạng thái active cho tab
            exportTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

// Hàm hiển thị form tạo phiếu xuất
function showExportForm() {
    // Reset form
    document.getElementById('exportForm').reset();
    document.getElementById('exportId').value = 0;
    document.getElementById('exportModalTitle').innerText = 'Tạo phiếu xuất kho';
    
    // Tạo mã phiếu xuất mới
    const now = new Date();
    const exportCode = 'EXP' + now.getFullYear() + 
                      (now.getMonth() + 1).toString().padStart(2, '0') + 
                      now.getDate().toString().padStart(2, '0') + 
                      now.getHours().toString().padStart(2, '0') + 
                      now.getMinutes().toString().padStart(2, '0');
    document.getElementById('exportCode').value = exportCode;
    
    // Reset chi tiết phiếu xuất
    detailCounter = 1;
    document.getElementById('exportDetails').innerHTML = `
        <tr id="exportDetailRow-1">
            <td>
                <select class="form-select product-select" name="details[1][productId]" onchange="loadProductInfo(this, 1)">
                    <option value="">Chọn sản phẩm</option>
                    <!-- Danh sách sản phẩm sẽ được load bằng AJAX -->
                </select>
            </td>
            <td>
                <select class="form-select" name="details[1][shelfId]">
                    <option value="">Chọn kệ</option>
                    <!-- Danh sách kệ sẽ được load dựa vào sản phẩm và kho -->
                </select>
            </td>
            <td>
                <input type="number" class="form-control quantity-input" name="details[1][quantity]" min="1" value="1" onchange="calculateTotal(1)">
                <small class="text-muted available-quantity">Còn: 0</small>
            </td>
            <td>
                <input type="number" class="form-control price-input" name="details[1][unitPrice]" min="0" value="0" onchange="calculateTotal(1)">
            </td>
            <td>
                <span class="total-price">0</span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeExportDetail(1)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    // Load danh sách sản phẩm vào select
    loadProductsForSelect();
    
    // Hiển thị modal
    document.getElementById('exportModal').classList.add('show');
}

// Đóng modal phiếu xuất
function closeExportModal() {
    document.getElementById('exportModal').classList.remove('show');
}

// Thêm dòng chi tiết phiếu xuất
function addExportDetail() {
    detailCounter++;
    
    const newRow = document.createElement('tr');
    newRow.id = `exportDetailRow-${detailCounter}`;
    newRow.innerHTML = `
        <td>
            <select class="form-select product-select" name="details[${detailCounter}][productId]" onchange="loadProductInfo(this, ${detailCounter})">
                <option value="">Chọn sản phẩm</option>
                <!-- Danh sách sản phẩm sẽ được load bằng AJAX -->
            </select>
        </td>
        <td>
            <select class="form-select" name="details[${detailCounter}][shelfId]">
                <option value="">Chọn kệ</option>
                <!-- Danh sách kệ sẽ được load dựa vào sản phẩm và kho -->
            </select>
        </td>
        <td>
            <input type="number" class="form-control quantity-input" name="details[${detailCounter}][quantity]" min="1" value="1" onchange="calculateTotal(${detailCounter})">
            <small class="text-muted available-quantity">Còn: 0</small>
        </td>
        <td>
            <input type="number" class="form-control price-input" name="details[${detailCounter}][unitPrice]" min="0" value="0" onchange="calculateTotal(${detailCounter})">
        </td>
        <td>
            <span class="total-price">0</span>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeExportDetail(${detailCounter})">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    document.getElementById('exportDetails').appendChild(newRow);
    
    // Load danh sách sản phẩm vào select
    loadProductsForSelect(detailCounter);
}

// Xóa dòng chi tiết phiếu xuất
function removeExportDetail(rowId) {
    const row = document.getElementById(`exportDetailRow-${rowId}`);
    if (row) {
        row.remove();
        updateTotalAmount();
    }
}

// Tính tổng tiền cho một dòng
function calculateTotal(rowId) {
    const row = document.getElementById(`exportDetailRow-${rowId}`);
    if (row) {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const total = quantity * price;
        
        row.querySelector('.total-price').innerText = total.toLocaleString('vi-VN');
        
        updateTotalAmount();
    }
}

// Cập nhật tổng tiền phiếu xuất
function updateTotalAmount() {
    let total = 0;
    document.querySelectorAll('.total-price').forEach(element => {
        total += parseFloat(element.innerText.replace(/\./g, '').replace(/,/g, '.')) || 0;
    });
    
    document.getElementById('exportTotalAmount').innerText = total.toLocaleString('vi-VN');
    document.getElementById('totalAmount').value = total;
}

// Load thông tin sản phẩm (giá, tồn kho, kệ)
function loadProductInfo(selectElement, rowId) {
    const productId = selectElement.value;
    const warehouseId = document.getElementById('warehouseId').value;
    
    if (!productId || !warehouseId) return;
    
    const row = document.getElementById(`exportDetailRow-${rowId}`);
    if (!row) return;
    
    // Gọi AJAX để lấy thông tin sản phẩm
    fetch(`api/products.php?action=getProductInfo&productId=${productId}&warehouseId=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật giá sản phẩm
                row.querySelector('.price-input').value = data.price;
                
                // Cập nhật số lượng khả dụng
                row.querySelector('.available-quantity').innerText = `Còn: ${data.availableQuantity}`;
                
                // Load danh sách kệ chứa sản phẩm
                const shelfSelect = row.querySelector('select[name^="details["][name$="][shelfId]"]');
                shelfSelect.innerHTML = '<option value="">Chọn kệ</option>';
                
                if (data.shelves && data.shelves.length > 0) {
                    data.shelves.forEach(shelf => {
                        const option = document.createElement('option');
                        option.value = shelf.shelf_id;
                        option.text = `${shelf.shelf_code} (Còn: ${shelf.quantity})`;
                        shelfSelect.appendChild(option);
                    });
                }
                
                // Tính lại tổng tiền
                calculateTotal(rowId);
            } else {
                // Hiển thị lỗi nếu có
                alert(data.message || 'Không thể lấy thông tin sản phẩm');
            }
        })
        .catch(error => {
            console.error('Lỗi khi lấy thông tin sản phẩm:', error);
            alert('Đã xảy ra lỗi khi lấy thông tin sản phẩm');
        });
}

// Lưu phiếu xuất dưới dạng nháp
function saveExportAsDraft() {
    // Kiểm tra thông tin bắt buộc
    if (!validateExportForm(true)) return;
    
    // Chuẩn bị dữ liệu form
    const formData = new FormData(document.getElementById('exportForm'));
    formData.append('action', 'saveAsDraft');
    formData.append('status', 'DRAFT');
    
    // Gửi yêu cầu AJAX lưu phiếu xuất
    fetch('api/exports.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật giao diện và đóng modal
            alert('Đã lưu phiếu xuất dưới dạng nháp');
            closeExportModal();
            loadExportOrders(); // Tải lại danh sách phiếu xuất
        } else {
            // Hiển thị lỗi
            alert(data.message || 'Không thể lưu phiếu xuất');
        }
    })
    .catch(error => {
        console.error('Lỗi khi lưu phiếu xuất:', error);
        alert('Đã xảy ra lỗi khi lưu phiếu xuất');
    });
}

// Hoàn thành xuất kho
function submitExport() {
    // Kiểm tra thông tin bắt buộc
    if (!validateExportForm(false)) return;
    
    // Xác nhận từ người dùng
    if (!confirm('Bạn có chắc chắn muốn hoàn thành xuất kho? Sau khi hoàn thành, số lượng tồn kho sẽ được cập nhật.')) {
        return;
    }
    
    // Chuẩn bị dữ liệu form
    const formData = new FormData(document.getElementById('exportForm'));
    formData.append('action', 'submitExport');
    formData.append('status', 'PENDING');
    
    // Gửi yêu cầu AJAX hoàn thành xuất kho
    fetch('api/exports.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật giao diện và đóng modal
            alert('Phiếu xuất kho đã được tạo và chờ duyệt');
            closeExportModal();
            loadExportOrders(); // Tải lại danh sách phiếu xuất
        } else {
            // Hiển thị lỗi
            alert(data.message || 'Không thể hoàn thành xuất kho');
        }
    })
    .catch(error => {
        console.error('Lỗi khi hoàn thành xuất kho:', error);
        alert('Đã xảy ra lỗi khi hoàn thành xuất kho');
    });
}

// Kiểm tra thông tin form xuất kho
function validateExportForm(isDraft) {
    const exportCode = document.getElementById('exportCode').value;
    const warehouseId = document.getElementById('warehouseId').value;
    
    // Kiểm tra các trường bắt buộc
    if (!exportCode) {
        alert('Vui lòng nhập mã phiếu xuất');
        return false;
    }
    
    if (!warehouseId) {
        alert('Vui lòng chọn kho xuất');
        return false;
    }
    
    // Kiểm tra chi tiết sản phẩm
    const productSelects = document.querySelectorAll('.product-select');
    if (productSelects.length === 0 || !productSelects[0].value) {
        alert('Vui lòng thêm ít nhất một sản phẩm');
        return false;
    }
    
    // Nếu không phải là nháp, kiểm tra số lượng
    if (!isDraft) {
        let hasInvalidQuantity = false;
        document.querySelectorAll('#exportDetails tr').forEach(row => {
            const productId = row.querySelector('.product-select').value;
            if (productId) {
                const quantityInput = row.querySelector('.quantity-input');
                const quantity = parseInt(quantityInput.value) || 0;
                const available = parseInt(row.querySelector('.available-quantity').innerText.replace('Còn: ', '')) || 0;
                
                if (quantity <= 0) {
                    alert('Số lượng phải lớn hơn 0');
                    hasInvalidQuantity = true;
                    return;
                }
                
                if (quantity > available) {
                    alert(`Sản phẩm không đủ số lượng tồn kho. Còn lại: ${available}`);
                    hasInvalidQuantity = true;
                    return;
                }
            }
        });
        
        if (hasInvalidQuantity) {
            return false;
        }
    }
    
    return true;
}

// Hiển thị form di chuyển nội bộ
function showInventoryMovementForm() {
    // Reset form
    document.getElementById('movementForm').reset();
    document.getElementById('movementId').value = 0;
    document.getElementById('movementModalTitle').innerText = 'Tạo phiếu di chuyển nội bộ';
    
    // Tạo mã phiếu di chuyển mới
    const now = new Date();
    const movementCode = 'MOV' + now.getFullYear() + 
                      (now.getMonth() + 1).toString().padStart(2, '0') + 
                      now.getDate().toString().padStart(2, '0') + 
                      now.getHours().toString().padStart(2, '0') + 
                      now.getMinutes().toString().padStart(2, '0');
    document.getElementById('movementCode').value = movementCode;
    
    // Load danh sách sản phẩm và kho
    loadProductsForMovement();
    document.getElementById('availableQuantity').innerText = 'Số lượng khả dụng: 0';
    
    // Hiển thị modal
    document.getElementById('movementModal').classList.add('show');
}

// Đóng modal di chuyển nội bộ
function closeMovementModal() {
    document.getElementById('movementModal').classList.remove('show');
}

// Tạo phiếu di chuyển nội bộ
function createMovement() {
    // Kiểm tra thông tin bắt buộc
    if (!validateMovementForm()) return;
    
    // Chuẩn bị dữ liệu form
    const formData = new FormData(document.getElementById('movementForm'));
    formData.append('action', 'createMovement');
    
    // Gửi yêu cầu AJAX tạo phiếu di chuyển
    fetch('api/movements.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật giao diện và đóng modal
            alert('Đã tạo phiếu di chuyển nội bộ thành công');
            closeMovementModal();
            loadInventoryMovements(); // Tải lại danh sách di chuyển
        } else {
            // Hiển thị lỗi
            alert(data.message || 'Không thể tạo phiếu di chuyển');
        }
    })
    .catch(error => {
        console.error('Lỗi khi tạo phiếu di chuyển:', error);
        alert('Đã xảy ra lỗi khi tạo phiếu di chuyển');
    });
}

// Kiểm tra thông tin form di chuyển
function validateMovementForm() {
    const movementCode = document.getElementById('movementCode').value;
    const productId = document.getElementById('productId').value;
    const sourceWarehouseId = document.getElementById('sourceWarehouseId').value;
    const targetWarehouseId = document.getElementById('targetWarehouseId').value;
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    
    // Kiểm tra các trường bắt buộc
    if (!movementCode) {
        alert('Vui lòng nhập mã phiếu di chuyển');
        return false;
    }
    
    if (!productId) {
        alert('Vui lòng chọn sản phẩm');
        return false;
    }
    
    if (!sourceWarehouseId) {
        alert('Vui lòng chọn kho nguồn');
        return false;
    }
    
    if (!targetWarehouseId) {
        alert('Vui lòng chọn kho đích');
        return false;
    }
    
    if (sourceWarehouseId === targetWarehouseId) {
        alert('Kho nguồn và kho đích không được trùng nhau');
        return false;
    }
    
    if (quantity <= 0) {
        alert('Số lượng phải lớn hơn 0');
        return false;
    }
    
    // Kiểm tra số lượng khả dụng
    const availableText = document.getElementById('availableQuantity').innerText;
    const available = parseInt(availableText.replace('Số lượng khả dụng: ', '')) || 0;
    
    if (quantity > available) {
        alert(`Sản phẩm không đủ số lượng. Khả dụng: ${available}`);
        return false;
    }
    
    return true;
}

// Kiểm tra số lượng khả dụng của sản phẩm trong kho nguồn
function checkProductAvailability() {
    const productId = document.getElementById('productId').value;
    const sourceWarehouseId = document.getElementById('sourceWarehouseId').value;
    
    if (!productId || !sourceWarehouseId) {
        document.getElementById('availableQuantity').innerText = 'Số lượng khả dụng: 0';
        return;
    }
    
    // Gọi AJAX để lấy số lượng khả dụng
    fetch(`api/products.php?action=getAvailableQuantity&productId=${productId}&warehouseId=${sourceWarehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('availableQuantity').innerText = `Số lượng khả dụng: ${data.availableQuantity}`;
            } else {
                document.getElementById('availableQuantity').innerText = 'Số lượng khả dụng: 0';
            }
        })
        .catch(error => {
            console.error('Lỗi khi kiểm tra số lượng:', error);
            document.getElementById('availableQuantity').innerText = 'Số lượng khả dụng: 0';
        });
}

// Hiển thị form nhà cung cấp
function showSupplierForm() {
    // Reset form
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = 0;
    document.getElementById('supplierModalTitle').innerText = 'Thêm nhà cung cấp mới';
    
    // Tạo mã nhà cung cấp mới
    const now = new Date();
    const supplierCode = 'SUP' + now.getFullYear() + 
                      (now.getMonth() + 1).toString().padStart(2, '0') + 
                      now.getDate().toString().padStart(2, '0') + 
                      now.getHours().toString().padStart(2, '0') + 
                      now.getMinutes().toString().padStart(2, '0');
    document.getElementById('supplierCode').value = supplierCode;
    
    // Hiển thị modal
    document.getElementById('supplierModal').classList.add('show');
}

// Đóng modal nhà cung cấp
function closeSupplierModal() {
    document.getElementById('supplierModal').classList.remove('show');
}

// Lưu thông tin nhà cung cấp
function saveSupplier() {
    // Kiểm tra thông tin bắt buộc
    if (!validateSupplierForm()) return;
    
    // Chuẩn bị dữ liệu form
    const formData = new FormData(document.getElementById('supplierForm'));
    formData.append('action', 'saveSupplier');
    
    // Gửi yêu cầu AJAX lưu nhà cung cấp
    fetch('api/suppliers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật giao diện và đóng modal
            alert('Đã lưu thông tin nhà cung cấp thành công');
            closeSupplierModal();
            loadSuppliers(); // Tải lại danh sách nhà cung cấp
        } else {
            // Hiển thị lỗi
            alert(data.message || 'Không thể lưu thông tin nhà cung cấp');
        }
    })
    .catch(error => {
        console.error('Lỗi khi lưu nhà cung cấp:', error);
        alert('Đã xảy ra lỗi khi lưu thông tin nhà cung cấp');
    });
}

// Kiểm tra thông tin form nhà cung cấp
function validateSupplierForm() {
    const supplierCode = document.getElementById('supplierCode').value;
    const supplierName = document.getElementById('supplierName').value;
    
    // Kiểm tra các trường bắt buộc
    if (!supplierCode) {
        alert('Vui lòng nhập mã nhà cung cấp');
        return false;
    }
    
    if (!supplierName) {
        alert('Vui lòng nhập tên nhà cung cấp');
        return false;
    }
    
    return true;
}

// Load danh sách phiếu xuất kho
function loadExportOrders(page = 1) {
    const status = document.getElementById('statusFilter').value;
    const warehouseId = document.getElementById('warehouseFilter').value;
    const search = document.getElementById('searchExport').value;
    
    // Hiển thị loading
    document.getElementById('exportOrdersList').innerHTML = '<tr><td colspan="7" class="text-center">Đang tải dữ liệu...</td></tr>';
    
    // Gọi AJAX để lấy danh sách phiếu xuất
    fetch(`api/exports.php?action=getExportOrders&page=${page}&status=${status}&warehouseId=${warehouseId}&search=${search}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị danh sách phiếu xuất
                let html = '';
                
                if (data.orders.length === 0) {
                    html = '<tr><td colspan="7" class="text-center">Không có phiếu xuất nào</td></tr>';
                } else {
                    data.orders.forEach(order => {
                        // Xác định class và text cho trạng thái
                        let statusClass = '';
                        let statusText = '';
                        
                        switch (order.status) {
                            case 'DRAFT':
                                statusClass = 'bg-secondary text-white';
                                statusText = 'Nháp';
                                break;
                            case 'PENDING':
                                statusClass = 'bg-warning text-dark';
                                statusText = 'Chờ duyệt';
                                break;
                            case 'COMPLETED':
                                statusClass = 'bg-success text-white';
                                statusText = 'Hoàn thành';
                                break;
                            case 'CANCELLED':
                                statusClass = 'bg-danger text-white';
                                statusText = 'Đã hủy';
                                break;
                        }
                        
                        html += `
                            <tr>
                                <td>${order.export_code}</td>
                                <td>${order.warehouse_name}</td>
                                <td>${order.recipient || 'N/A'}</td>
                                <td>${new Date(order.created_at).toLocaleString('vi-VN')}</td>
                                <td>${parseFloat(order.total_amount).toLocaleString('vi-VN')} đ</td>
                                <td><span class="badge ${statusClass}">${statusText}</span></td>
                                <td>
                                    <div class="d-flex">
                                        <button class="btn btn-sm btn-info me-1" onclick="viewExportDetails(${order.export_id})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        ${order.status === 'DRAFT' ? `
                                            <button class="btn btn-sm btn-primary me-1" onclick="editExport(${order.export_id})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        ` : ''}
                                        ${order.status === 'DRAFT' || order.status === 'PENDING' ? `
                                            <button class="btn btn-sm btn-danger" onclick="deleteExport(${order.export_id})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }
                
                document.getElementById('exportOrdersList').innerHTML = html;
                
                // Tạo phân trang
                createPagination('exportPagination', data.totalPages, page, 'loadExportOrders');
                
            } else {
                // Hiển thị lỗi
                document.getElementById('exportOrdersList').innerHTML = `<tr><td colspan="7" class="text-center text-danger">${data.message || 'Không thể tải danh sách phiếu xuất'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách phiếu xuất:', error);
            document.getElementById('exportOrdersList').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
        });
}

// Tạo phân trang
function createPagination(containerId, totalPages, currentPage, callbackFunction) {
    const container = document.getElementById(containerId);
    let html = '<ul class="pagination">';
    
    // Nút Previous
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="javascript:void(0)" onclick="${currentPage > 1 ? callbackFunction + '(' + (currentPage - 1) + ')' : ''}" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
        </a>
    </li>`;
    
    // Các trang
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, startPage + 4);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="${callbackFunction}(${i})">${i}</a>
        </li>`;
    }
    
    // Nút Next
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="javascript:void(0)" onclick="${currentPage < totalPages ? callbackFunction + '(' + (currentPage + 1) + ')' : ''}" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
        </a>
    </li>`;
    
    html += '</ul>';
    container.innerHTML = html;
}

// Load danh sách di chuyển nội bộ
function loadInventoryMovements(page = 1) {
    const status = document.getElementById('movementStatusFilter').value;
    const sourceWarehouseId = document.getElementById('sourceWarehouseFilter').value;
    const search = document.getElementById('searchMovement').value;
    
    // Hiển thị loading
    document.getElementById('inventoryMovementsList').innerHTML = '<tr><td colspan="8" class="text-center">Đang tải dữ liệu...</td></tr>';
    
    // Gọi AJAX để lấy danh sách di chuyển
    fetch(`api/movements.php?action=getInventoryMovements&page=${page}&status=${status}&sourceWarehouseId=${sourceWarehouseId}&search=${search}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị danh sách di chuyển
                let html = '';
                
                if (data.movements.length === 0) {
                    html = '<tr><td colspan="8" class="text-center">Không có phiếu di chuyển nào</td></tr>';
                } else {
                    data.movements.forEach(movement => {
                        // Xác định class và text cho trạng thái
                        let statusClass = '';
                        let statusText = '';
                        
                        switch (movement.status) {
                            case 'PENDING':
                                statusClass = 'bg-warning text-dark';
                                statusText = 'Chờ xử lý';
                                break;
                            case 'IN_TRANSIT':
                                statusClass = 'bg-info text-white';
                                statusText = 'Đang vận chuyển';
                                break;
                            case 'COMPLETED':
                                statusClass = 'bg-success text-white';
                                statusText = 'Hoàn thành';
                                break;
                            case 'CANCELLED':
                                statusClass = 'bg-danger text-white';
                                statusText = 'Đã hủy';
                                break;
                        }
                        
                        html += `
                            <tr>
                                <td>${movement.movement_code}</td>
                                <td>${movement.product_name}</td>
                                <td>${movement.source_warehouse}</td>
                                <td>${movement.target_warehouse}</td>
                                <td>${movement.quantity}</td>
                                <td>${new Date(movement.created_at).toLocaleString('vi-VN')}</td>
                                <td><span class="badge ${statusClass}">${statusText}</span></td>
                                <td>
                                    <div class="d-flex">
                                        <button class="btn btn-sm btn-info me-1" onclick="viewMovementDetails(${movement.movement_id})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        ${movement.status === 'PENDING' ? `
                                            <button class="btn btn-sm btn-primary me-1" onclick="updateMovementStatus(${movement.movement_id}, 'IN_TRANSIT')">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                        ` : ''}
                                        ${movement.status === 'IN_TRANSIT' ? `
                                            <button class="btn btn-sm btn-success me-1" onclick="updateMovementStatus(${movement.movement_id}, 'COMPLETED')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        ` : ''}
                                        ${movement.status !== 'COMPLETED' && movement.status !== 'CANCELLED' ? `
                                            <button class="btn btn-sm btn-danger" onclick="cancelMovement(${movement.movement_id})">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }
                
                document.getElementById('inventoryMovementsList').innerHTML = html;
                
                // Tạo phân trang
                createPagination('movementPagination', data.totalPages, page, 'loadInventoryMovements');
                
            } else {
                // Hiển thị lỗi
                document.getElementById('inventoryMovementsList').innerHTML = `<tr><td colspan="8" class="text-center text-danger">${data.message || 'Không thể tải danh sách di chuyển'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách di chuyển:', error);
            document.getElementById('inventoryMovementsList').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
        });
}

// Load danh sách nhà cung cấp
function loadSuppliers(page = 1) {
    const search = document.getElementById('searchSupplier').value;
    
    // Hiển thị loading
    document.getElementById('suppliersList').innerHTML = '<tr><td colspan="7" class="text-center">Đang tải dữ liệu...</td></tr>';
    
    // Gọi AJAX để lấy danh sách nhà cung cấp
    fetch(`api/suppliers.php?action=getSuppliers&page=${page}&search=${search}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị danh sách nhà cung cấp
                let html = '';
                
                if (data.suppliers.length === 0) {
                    html = '<tr><td colspan="7" class="text-center">Không có nhà cung cấp nào</td></tr>';
                } else {
                    data.suppliers.forEach(supplier => {
                        html += `
                            <tr>
                                <td>${supplier.supplier_code}</td>
                                <td>${supplier.supplier_name}</td>
                                <td>${supplier.contact_person || 'N/A'}</td>
                                <td>${supplier.phone || 'N/A'}</td>
                                <td>${supplier.email || 'N/A'}</td>
                                <td><span class="badge ${supplier.is_active ? 'bg-success' : 'bg-danger'} text-white">${supplier.is_active ? 'Đang hoạt động' : 'Không hoạt động'}</span></td>
                                <td>
                                    <div class="d-flex">
                                        <button class="btn btn-sm btn-primary me-1" onclick="editSupplier(${supplier.supplier_id})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-${supplier.is_active ? 'warning' : 'success'}" onclick="toggleSupplierStatus(${supplier.supplier_id}, ${supplier.is_active ? 0 : 1})">
                                            <i class="fas fa-${supplier.is_active ? 'ban' : 'check'}"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }
                
                document.getElementById('suppliersList').innerHTML = html;
                
                // Tạo phân trang
                createPagination('supplierPagination', data.totalPages, page, 'loadSuppliers');
                
            } else {
                // Hiển thị lỗi
                document.getElementById('suppliersList').innerHTML = `<tr><td colspan="7" class="text-center text-danger">${data.message || 'Không thể tải danh sách nhà cung cấp'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách nhà cung cấp:', error);
            document.getElementById('suppliersList').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
        });
}

// Load danh sách kho cho các select
function loadWarehouses() {
    // Lấy các select cần load
    const warehouseSelects = [
        document.getElementById('warehouseFilter'),
        document.getElementById('warehouseId'),
        document.getElementById('sourceWarehouseId'),
        document.getElementById('targetWarehouseId')
    ];
    
    // Gọi AJAX để lấy danh sách kho
    fetch('api/warehouses.php?action=getWarehouses')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Tạo options
                let options = '<option value="">Chọn kho</option>';
                data.warehouses.forEach(warehouse => {
                    options += `<option value="${warehouse.warehouse_id}">${warehouse.warehouse_name}</option>`;
                });
                
                // Thêm options vào các select
                warehouseSelects.forEach(select => {
                    if (select) select.innerHTML = options;
                });
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách kho:', error);
        });
}

// Load danh sách sản phẩm cho select
function loadProducts() {
    // Gọi AJAX để lấy danh sách sản phẩm
    fetch('api/products.php?action=getProducts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.productsList = data.products; // Lưu danh sách sản phẩm vào biến toàn cục
                loadProductsForSelect(); // Load danh sách sản phẩm vào select trong form xuất kho
                loadProductsForMovement(); // Load danh sách sản phẩm vào select trong form di chuyển
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách sản phẩm:', error);
        });
}

// Load danh sách sản phẩm vào select trong form xuất kho
function loadProductsForSelect(rowId = null) {
    if (!window.productsList) return;
    
    const options = '<option value="">Chọn sản phẩm</option>' +
                  window.productsList.map(product => `<option value="${product.product_id}">${product.product_code} - ${product.product_name}</option>`).join('');
    
    if (rowId) {
        // Load cho một dòng cụ thể
        const select = document.querySelector(`#exportDetailRow-${rowId} .product-select`);
        if (select) select.innerHTML = options;
    } else {
        // Load cho tất cả các dòng
        document.querySelectorAll('.product-select').forEach(select => {
            select.innerHTML = options;
        });
    }
}

// Load danh sách sản phẩm vào select trong form di chuyển
function loadProductsForMovement() {
    if (!window.productsList) return;
    
    const productSelect = document.getElementById('productId');
    if (!productSelect) return;
    
    productSelect.innerHTML = '<option value="">Chọn sản phẩm</option>' +
                           window.productsList.map(product => `<option value="${product.product_id}">${product.product_code} - ${product.product_name}</option>`).join('');
}

// Lọc danh sách phiếu xuất kho
function filterExportOrders() {
    loadExportOrders(1);
}

// Lọc danh sách di chuyển nội bộ
function filterInventoryMovements() {
    loadInventoryMovements(1);
}

// Xem chi tiết phiếu xuất
function viewExportDetails(exportId) {
    currentExportId = exportId;
    
    // Gọi AJAX để lấy chi tiết phiếu xuất
    fetch(`api/exports.php?action=getExportDetails&exportId=${exportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị chi tiết phiếu xuất
                let html = `
                    <div class="mb-4">
                        <h6 class="fw-bold">Thông tin chung</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Mã phiếu:</strong> ${data.export.export_code}</p>
                                <p><strong>Kho xuất:</strong> ${data.export.warehouse_name}</p>
                                <p><strong>Người nhận:</strong> ${data.export.recipient || 'N/A'}</p>
                                <p><strong>Địa chỉ:</strong> ${data.export.recipient_address || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ngày tạo:</strong> ${new Date(data.export.created_at).toLocaleString('vi-VN')}</p>
                                <p><strong>Trạng thái:</strong> <span class="badge ${getStatusBadgeClass(data.export.status)}">${getStatusText(data.export.status)}</span></p>
                                <p><strong>Người tạo:</strong> ${data.export.created_by_name}</p>
                                <p><strong>Mã đơn hàng:</strong> ${data.export.order_reference || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Chi tiết sản phẩm</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Kệ/Vị trí</th>
                                        <th>Số lượng</th>
                                        <th>Đơn giá</th>
                                        <th>Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                data.details.forEach(detail => {
                    html += `
                        <tr>
                            <td>${detail.product_code} - ${detail.product_name}</td>
                            <td>${detail.shelf_code || 'N/A'}</td>
                            <td>${detail.quantity}</td>
                            <td>${parseFloat(detail.unit_price).toLocaleString('vi-VN')} đ</td>
                            <td>${parseFloat(detail.total_price).toLocaleString('vi-VN')} đ</td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Tổng cộng:</td>
                                        <td>${parseFloat(data.export.total_amount).toLocaleString('vi-VN')} đ</td>
                                    </tr>
                                                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <div>
                        <h6 class="fw-bold">Ghi chú</h6>
                        <p>${data.export.notes || 'Không có ghi chú'}</p>
                    </div>
                `;
                
                document.getElementById('exportDetailContent').innerHTML = html;
                
                // Hiển thị/ẩn các nút thao tác dựa vào trạng thái
                const btnApproveExport = document.getElementById('btnApproveExport');
                const btnCancelExport = document.getElementById('btnCancelExport');
                
                if (data.export.status === 'PENDING') {
                    btnApproveExport.style.display = 'inline-block';
                    btnCancelExport.style.display = 'inline-block';
                } else if (data.export.status === 'DRAFT') {
                    btnApproveExport.style.display = 'none';
                    btnCancelExport.style.display = 'inline-block';
                } else {
                    btnApproveExport.style.display = 'none';
                    btnCancelExport.style.display = 'none';
                }
                
                // Hiển thị modal
                document.getElementById('viewExportModal').classList.add('show');
                
            } else {
                // Hiển thị lỗi
                alert(data.message || 'Không thể tải chi tiết phiếu xuất');
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải chi tiết phiếu xuất:', error);
            alert('Đã xảy ra lỗi khi tải chi tiết phiếu xuất');
        });
}

// Đóng modal xem chi tiết phiếu xuất
function closeViewExportModal() {
    document.getElementById('viewExportModal').classList.remove('show');
}

// Duyệt phiếu xuất
function approveExport() {
    if (!currentExportId) return;
    
    if (!confirm('Bạn có chắc chắn muốn duyệt phiếu xuất này? Hành động này sẽ cập nhật tồn kho và không thể hoàn tác.')) {
        return;
    }
    
    // Gọi AJAX để duyệt phiếu xuất
    fetch('api/exports.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'approveExport',
            exportId: currentExportId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã duyệt phiếu xuất thành công');
            closeViewExportModal();
            loadExportOrders(); // Tải lại danh sách phiếu xuất
        } else {
            alert(data.message || 'Không thể duyệt phiếu xuất');
        }
    })
    .catch(error => {
        console.error('Lỗi khi duyệt phiếu xuất:', error);
        alert('Đã xảy ra lỗi khi duyệt phiếu xuất');
    });
}

// Hủy phiếu xuất
function cancelExport() {
    if (!currentExportId) return;
    
    if (!confirm('Bạn có chắc chắn muốn hủy phiếu xuất này?')) {
        return;
    }
    
    // Gọi AJAX để hủy phiếu xuất
    fetch('api/exports.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'cancelExport',
            exportId: currentExportId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã hủy phiếu xuất thành công');
            closeViewExportModal();
            loadExportOrders(); // Tải lại danh sách phiếu xuất
        } else {
            alert(data.message || 'Không thể hủy phiếu xuất');
        }
    })
    .catch(error => {
        console.error('Lỗi khi hủy phiếu xuất:', error);
        alert('Đã xảy ra lỗi khi hủy phiếu xuất');
    });
}

// Xem chi tiết phiếu di chuyển
function viewMovementDetails(movementId) {
    currentMovementId = movementId;
    
    // Gọi AJAX để lấy chi tiết phiếu di chuyển
    fetch(`api/movements.php?action=getMovementDetails&movementId=${movementId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị chi tiết phiếu di chuyển
                const movement = data.movement;
                let html = `
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Mã phiếu:</strong> ${movement.movement_code}</p>
                                <p><strong>Sản phẩm:</strong> ${movement.product_code} - ${movement.product_name}</p>
                                <p><strong>Kho nguồn:</strong> ${movement.source_warehouse}</p>
                                <p><strong>Kệ nguồn:</strong> ${movement.source_shelf || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Kho đích:</strong> ${movement.target_warehouse}</p>
                                <p><strong>Kệ đích:</strong> ${movement.target_shelf || 'N/A'}</p>
                                <p><strong>Số lượng:</strong> ${movement.quantity}</p>
                                <p><strong>Số lô:</strong> ${movement.batch_number || 'N/A'}</p>
                            </div>
                        </div>
                        <p><strong>Trạng thái:</strong> <span class="badge ${getMovementStatusBadgeClass(movement.status)}">${getMovementStatusText(movement.status)}</span></p>
                        <p><strong>Ngày tạo:</strong> ${new Date(movement.created_at).toLocaleString('vi-VN')}</p>
                        <p><strong>Người tạo:</strong> ${movement.created_by_name}</p>
                        ${movement.completed_at ? `<p><strong>Ngày hoàn thành:</strong> ${new Date(movement.completed_at).toLocaleString('vi-VN')}</p>` : ''}
                        <p><strong>Lý do di chuyển:</strong> ${movement.reason || 'Không có ghi chú'}</p>
                    </div>
                `;
                
                document.getElementById('movementDetailContent').innerHTML = html;
                
                // Hiển thị/ẩn các nút thao tác dựa vào trạng thái
                const btnUpdateMovementStatus = document.getElementById('btnUpdateMovementStatus');
                const btnCancelMovement = document.getElementById('btnCancelMovement');
                
                if (movement.status === 'PENDING') {
                    btnUpdateMovementStatus.innerText = 'Cập nhật: Đang vận chuyển';
                    btnUpdateMovementStatus.onclick = function() { updateMovementStatus(movement.movement_id, 'IN_TRANSIT'); };
                    btnUpdateMovementStatus.style.display = 'inline-block';
                    btnCancelMovement.style.display = 'inline-block';
                } else if (movement.status === 'IN_TRANSIT') {
                    btnUpdateMovementStatus.innerText = 'Cập nhật: Hoàn thành';
                    btnUpdateMovementStatus.onclick = function() { updateMovementStatus(movement.movement_id, 'COMPLETED'); };
                    btnUpdateMovementStatus.style.display = 'inline-block';
                    btnCancelMovement.style.display = 'inline-block';
                } else {
                    btnUpdateMovementStatus.style.display = 'none';
                    btnCancelMovement.style.display = 'none';
                }
                
                // Hiển thị modal
                document.getElementById('viewMovementModal').classList.add('show');
                
            } else {
                // Hiển thị lỗi
                alert(data.message || 'Không thể tải chi tiết phiếu di chuyển');
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải chi tiết phiếu di chuyển:', error);
            alert('Đã xảy ra lỗi khi tải chi tiết phiếu di chuyển');
        });
}

// Đóng modal xem chi tiết phiếu di chuyển
function closeViewMovementModal() {
    document.getElementById('viewMovementModal').classList.remove('show');
}

// Cập nhật trạng thái phiếu di chuyển
function updateMovementStatus(movementId, newStatus) {
    if (!movementId || !newStatus) return;
    
    let confirmMessage = '';
    if (newStatus === 'IN_TRANSIT') {
        confirmMessage = 'Bạn có chắc chắn muốn cập nhật trạng thái phiếu di chuyển thành "Đang vận chuyển"?';
    } else if (newStatus === 'COMPLETED') {
        confirmMessage = 'Bạn có chắc chắn muốn hoàn thành phiếu di chuyển này? Hành động này sẽ cập nhật tồn kho và không thể hoàn tác.';
    }
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Gọi AJAX để cập nhật trạng thái
    fetch('api/movements.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'updateMovementStatus',
            movementId: movementId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã cập nhật trạng thái phiếu di chuyển thành công');
            closeViewMovementModal();
            loadInventoryMovements(); // Tải lại danh sách di chuyển
        } else {
            alert(data.message || 'Không thể cập nhật trạng thái phiếu di chuyển');
        }
    })
    .catch(error => {
        console.error('Lỗi khi cập nhật trạng thái phiếu di chuyển:', error);
        alert('Đã xảy ra lỗi khi cập nhật trạng thái phiếu di chuyển');
    });
}

// Hủy phiếu di chuyển
function cancelMovement(movementId) {
    if (!movementId) {
        movementId = currentMovementId;
    }
    
    if (!movementId) return;
    
    if (!confirm('Bạn có chắc chắn muốn hủy phiếu di chuyển này?')) {
        return;
    }
    
    // Gọi AJAX để hủy phiếu di chuyển
    fetch('api/movements.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'cancelMovement',
            movementId: movementId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã hủy phiếu di chuyển thành công');
            closeViewMovementModal();
            loadInventoryMovements(); // Tải lại danh sách di chuyển
        } else {
            alert(data.message || 'Không thể hủy phiếu di chuyển');
        }
    })
    .catch(error => {
        console.error('Lỗi khi hủy phiếu di chuyển:', error);
        alert('Đã xảy ra lỗi khi hủy phiếu di chuyển');
    });
}

// Chỉnh sửa nhà cung cấp
function editSupplier(supplierId) {
    currentSupplierId = supplierId;
    
    // Gọi AJAX để lấy thông tin nhà cung cấp
    fetch(`api/suppliers.php?action=getSupplierDetails&supplierId=${supplierId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const supplier = data.supplier;
                
                // Cập nhật form
                document.getElementById('supplierModalTitle').innerText = 'Chỉnh sửa nhà cung cấp';
                document.getElementById('supplierId').value = supplier.supplier_id;
                document.getElementById('supplierCode').value = supplier.supplier_code;
                document.getElementById('supplierName').value = supplier.supplier_name;
                document.getElementById('contactPerson').value = supplier.contact_person || '';
                document.getElementById('phone').value = supplier.phone || '';
                document.getElementById('email').value = supplier.email || '';
                document.getElementById('taxCode').value = supplier.tax_code || '';
                document.getElementById('address').value = supplier.address || '';
                document.getElementById('isActive').checked = supplier.is_active === 1;
                
                // Hiển thị modal
                document.getElementById('supplierModal').classList.add('show');
                
            } else {
                // Hiển thị lỗi
                alert(data.message || 'Không thể tải thông tin nhà cung cấp');
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải thông tin nhà cung cấp:', error);
            alert('Đã xảy ra lỗi khi tải thông tin nhà cung cấp');
        });
}

// Bật/tắt trạng thái nhà cung cấp
function toggleSupplierStatus(supplierId, isActive) {
    // Gọi AJAX để cập nhật trạng thái
    fetch('api/suppliers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggleSupplierStatus',
            supplierId: supplierId,
            isActive: isActive
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadSuppliers(); // Tải lại danh sách nhà cung cấp
        } else {
            alert(data.message || 'Không thể cập nhật trạng thái nhà cung cấp');
        }
    })
    .catch(error => {
        console.error('Lỗi khi cập nhật trạng thái nhà cung cấp:', error);
        alert('Đã xảy ra lỗi khi cập nhật trạng thái nhà cung cấp');
    });
}

// Hàm hỗ trợ lấy class cho badge trạng thái phiếu xuất
function getStatusBadgeClass(status) {
    switch (status) {
        case 'DRAFT':
            return 'bg-secondary text-white';
        case 'PENDING':
            return 'bg-warning text-dark';
        case 'COMPLETED':
            return 'bg-success text-white';
        case 'CANCELLED':
            return 'bg-danger text-white';
        default:
            return 'bg-secondary text-white';
    }
}

// Hàm hỗ trợ lấy text cho trạng thái phiếu xuất
function getStatusText(status) {
    switch (status) {
        case 'DRAFT':
            return 'Nháp';
        case 'PENDING':
            return 'Chờ duyệt';
        case 'COMPLETED':
            return 'Hoàn thành';
        case 'CANCELLED':
            return 'Đã hủy';
        default:
            return 'Không xác định';
    }
}

// Hàm hỗ trợ lấy class cho badge trạng thái phiếu di chuyển
function getMovementStatusBadgeClass(status) {
    switch (status) {
        case 'PENDING':
            return 'bg-warning text-dark';
        case 'IN_TRANSIT':
            return 'bg-info text-white';
        case 'COMPLETED':
            return 'bg-success text-white';
        case 'CANCELLED':
            return 'bg-danger text-white';
        default:
            return 'bg-secondary text-white';
    }
}

// Hàm hỗ trợ lấy text cho trạng thái phiếu di chuyển
function getMovementStatusText(status) {
    switch (status) {
        case 'PENDING':
            return 'Chờ xử lý';
        case 'IN_TRANSIT':
            return 'Đang vận chuyển';
        case 'COMPLETED':
            return 'Hoàn thành';
        case 'CANCELLED':
            return 'Đã hủy';
        default:
            return 'Không xác định';
    }
}

// Load danh sách kệ trong kho nguồn
function loadSourceShelves() {
    const warehouseId = document.getElementById('sourceWarehouseId').value;
    if (!warehouseId) return;
    
    // Gọi AJAX để lấy danh sách kệ
    fetch(`api/shelves.php?action=getShelvesByWarehouse&warehouseId=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('sourceShelfId');
                
                // Reset select
                select.innerHTML = '<option value="">Chọn kệ nguồn</option>';
                
                // Thêm options
                data.shelves.forEach(shelf => {
                    const option = document.createElement('option');
                    option.value = shelf.shelf_id;
                    option.text = `${shelf.shelf_code} - ${shelf.position || 'Không có vị trí'}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách kệ:', error);
        });
}

// Load danh sách kệ trong kho đích
function loadTargetShelves() {
    const warehouseId = document.getElementById('targetWarehouseId').value;
    if (!warehouseId) return;
    
    // Gọi AJAX để lấy danh sách kệ
    fetch(`api/shelves.php?action=getShelvesByWarehouse&warehouseId=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('targetShelfId');
                
                // Reset select
                select.innerHTML = '<option value="">Chọn kệ đích</option>';
                
                // Thêm options
                data.shelves.forEach(shelf => {
                    const option = document.createElement('option');
                    option.value = shelf.shelf_id;
                    option.text = `${shelf.shelf_code} - ${shelf.position || 'Không có vị trí'}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách kệ:', error);
        });
}

// Chỉnh sửa phiếu xuất
function editExport(exportId) {
    // Gọi AJAX để lấy thông tin phiếu xuất
    fetch(`api/exports.php?action=getExportDetails&exportId=${exportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật form
                document.getElementById('exportModalTitle').innerText = 'Chỉnh sửa phiếu xuất kho';
                document.getElementById('exportId').value = data.export.export_id;
                document.getElementById('exportCode').value = data.export.export_code;
                document.getElementById('warehouseId').value = data.export.warehouse_id;
                document.getElementById('recipient').value = data.export.recipient || '';
                document.getElementById('recipientAddress').value = data.export.recipient_address || '';
                document.getElementById('orderReference').value = data.export.order_reference || '';
                document.getElementById('notes').value = data.export.notes || '';
                
                // Xóa tất cả dòng chi tiết hiện tại
                document.getElementById('exportDetails').innerHTML = '';
                
                // Thêm các dòng chi tiết từ dữ liệu
                data.details.forEach((detail, index) => {
                    detailCounter = index + 1;
                    
                    const newRow = document.createElement('tr');
                    newRow.id = `exportDetailRow-${detailCounter}`;
                    newRow.innerHTML = `
                        <td>
                            <select class="form-select product-select" name="details[${detailCounter}][productId]" onchange="loadProductInfo(this, ${detailCounter})">
                                <option value="">Chọn sản phẩm</option>
                                <!-- Danh sách sản phẩm sẽ được load bằng AJAX -->
                            </select>
                        </td>
                        <td>
                            <select class="form-select" name="details[${detailCounter}][shelfId]">
                                <option value="">Chọn kệ</option>
                                <!-- Danh sách kệ sẽ được load dựa vào sản phẩm và kho -->
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control quantity-input" name="details[${detailCounter}][quantity]" min="1" value="${detail.quantity}" onchange="calculateTotal(${detailCounter})">
                            <small class="text-muted available-quantity">Còn: 0</small>
                        </td>
                        <td>
                            <input type="number" class="form-control price-input" name="details[${detailCounter}][unitPrice]" min="0" value="${detail.unit_price}" onchange="calculateTotal(${detailCounter})">
                        </td>
                        <td>
                            <span class="total-price">${parseFloat(detail.total_price).toLocaleString('vi-VN')}</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeExportDetail(${detailCounter})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    
                    document.getElementById('exportDetails').appendChild(newRow);
                    
                    // Load danh sách sản phẩm vào select
                    loadProductsForSelect(detailCounter);
                    
                    // Thiết lập giá trị cho select sản phẩm
                    setTimeout(() => {
                        const productSelect = document.querySelector(`#exportDetailRow-${detailCounter} .product-select`);
                        if (productSelect) {
                            productSelect.value = detail.product_id;
                            loadProductInfo(productSelect, detailCounter);
                            
                            // Thiết lập giá trị cho select kệ sau khi đã load xong
                            setTimeout(() => {
                                const shelfSelect = document.querySelector(`#exportDetailRow-${detailCounter} select[name^="details["][name$="][shelfId]"]`);
                                if (shelfSelect && detail.shelf_id) {
                                    shelfSelect.value = detail.shelf_id;
                                }
                            }, 500);
                        }
                    }, 200);
                });
                
                // Cập nhật tổng tiền
                document.getElementById('exportTotalAmount').innerText = parseFloat(data.export.total_amount).toLocaleString('vi-VN');
                document.getElementById('totalAmount').value = data.export.total_amount;
                
                // Hiển thị modal
                document.getElementById('exportModal').classList.add('show');
                
            } else {
                // Hiển thị lỗi
                alert(data.message || 'Không thể tải thông tin phiếu xuất');
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải thông tin phiếu xuất:', error);
            alert('Đã xảy ra lỗi khi tải thông tin phiếu xuất');
        });
}

// Xóa phiếu xuất
function deleteExport(exportId) {
    if (!confirm('Bạn có chắc chắn muốn xóa phiếu xuất này?')) {
        return;
    }
    
    // Gọi AJAX để xóa phiếu xuất
    fetch('api/exports.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'deleteExport',
            exportId: exportId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã xóa phiếu xuất thành công');
            loadExportOrders(); // Tải lại danh sách phiếu xuất
        } else {
            alert(data.message || 'Không thể xóa phiếu xuất');
        }
    })
    .catch(error => {
        console.error('Lỗi khi xóa phiếu xuất:', error);
        alert('Đã xảy ra lỗi khi xóa phiếu xuất');
    });
}
</script>