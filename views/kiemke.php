<div class="container-fluid">
    <div class="function-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">Kiểm Kê Kho</h2>
            <button type="button" class="btn btn-add" id="btnAddInventoryCheck">
                <i class="fas fa-plus-circle me-2"></i>Tạo Lịch Kiểm Kê
            </button>
        </div>

        <!-- Tab navigation -->
        <ul class="nav nav-tabs mb-4" id="inventoryCheckTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="scheduled-tab" data-bs-toggle="tab" href="#scheduled" role="tab">
                    <i class="fas fa-calendar-alt me-2"></i>Lịch Kiểm Kê
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="in-progress-tab" data-bs-toggle="tab" href="#in-progress" role="tab">
                    <i class="fas fa-spinner me-2"></i>Đang Kiểm Kê
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="completed-tab" data-bs-toggle="tab" href="#completed" role="tab">
                    <i class="fas fa-check-circle me-2"></i>Đã Hoàn Thành
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="reports-tab" data-bs-toggle="tab" href="#reports" role="tab">
                    <i class="fas fa-chart-bar me-2"></i>Báo Cáo Kiểm Kê
                </a>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content" id="inventoryCheckTabContent">
            <!-- Lịch Kiểm Kê -->
            <div class="tab-pane fade show active" id="scheduled" role="tabpanel">
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>Mã Kiểm Kê</th>
                                <th>Kho</th>
                                <th>Khu Vực</th>
                                <th>Ngày Kiểm Kê</th>
                                <th>Giờ Kiểm Kê</th>
                                <th>Phương Thức</th>
                                <th>Trạng Thái</th>
                                <th>Người Tạo</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="scheduledInventoryCheckList">
                            <!-- Dữ liệu sẽ được nạp bằng AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Đang Kiểm Kê -->
            <div class="tab-pane fade" id="in-progress" role="tabpanel">
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>Mã Kiểm Kê</th>
                                <th>Kho</th>
                                <th>Khu Vực</th>
                                <th>Ngày Kiểm Kê</th>
                                <th>Giờ Kiểm Kê</th>
                                <th>Phương Thức</th>
                                <th>Tiến Độ</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="inProgressInventoryCheckList">
                            <!-- Dữ liệu sẽ được nạp bằng AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Đã Hoàn Thành -->
            <div class="tab-pane fade" id="completed" role="tabpanel">
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>Mã Kiểm Kê</th>
                                <th>Kho</th>
                                <th>Khu Vực</th>
                                <th>Ngày Kiểm Kê</th>
                                <th>Hoàn Thành</th>
                                <th>Phương Thức</th>
                                <th>Kết Quả</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="completedInventoryCheckList">
                            <!-- Dữ liệu sẽ được nạp bằng AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Báo Cáo Kiểm Kê -->
            <div class="tab-pane fade" id="reports" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reportWarehouse">Kho</label>
                            <select class="form-control" id="reportWarehouse">
                                <option value="">Tất cả kho</option>
                                <!-- Danh sách kho sẽ được nạp bằng AJAX -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reportDateFrom">Từ ngày</label>
                            <input type="date" class="form-control" id="reportDateFrom">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reportDateTo">Đến ngày</label>
                            <input type="date" class="form-control" id="reportDateTo">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-primary" id="btnFilterReports">
                            <i class="fas fa-filter me-2"></i>Lọc
                        </button>
                        <button type="button" class="btn btn-success" id="btnExportReport">
                            <i class="fas fa-file-excel me-2"></i>Xuất Excel
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>Mã Kiểm Kê</th>
                                <th>Kho</th>
                                <th>Khu Vực</th>
                                <th>Ngày Kiểm Kê</th>
                                <th>Số Sản Phẩm Kiểm Tra</th>
                                <th>Số Sản Phẩm Chênh Lệch</th>
                                <th>Tỷ Lệ Chính Xác</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="reportsList">
                            <!-- Dữ liệu sẽ được nạp bằng AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tạo Lịch Kiểm Kê -->
<div class="custom-modal" id="addInventoryCheckModal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h5 class="modal-title">Tạo Lịch Kiểm Kê</h5>
            <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addInventoryCheckForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="warehouse_id">Kho <span class="text-danger">*</span></label>
                            <select class="form-control" id="warehouse_id" name="warehouse_id" required>
                                <option value="">Chọn kho</option>
                                <!-- Danh sách kho sẽ được nạp bằng AJAX -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="zone_id">Khu vực</label>
                            <select class="form-control" id="zone_id" name="zone_id">
                                <option value="">Toàn kho</option>
                                <!-- Danh sách khu vực sẽ được nạp bằng AJAX -->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="scheduled_date">Ngày kiểm kê <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="scheduled_time">Giờ kiểm kê <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="check_type">Phương thức kiểm kê <span class="text-danger">*</span></label>
                            <select class="form-control" id="check_type" name="check_type" required>
                                <option value="AUTOMATIC_RFID">Tự động bằng RFID</option>
                                <option value="MANUAL_BARCODE">Thủ công bằng barcode</option>
                                <option value="MIXED">Kết hợp</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="check_code">Mã kiểm kê</label>
                            <input type="text" class="form-control" id="check_code" name="check_code" readonly>
                            <small class="text-muted">Mã sẽ được tạo tự động</small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="notes">Ghi chú</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
            <button type="button" class="btn btn-primary" id="btnSaveInventoryCheck">Lưu</button>
        </div>
    </div>
</div>

<!-- Modal Chi Tiết Kiểm Kê -->
<div class="custom-modal" id="inventoryCheckDetailModal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h5 class="modal-title">Chi Tiết Kiểm Kê</h5>
            <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="inventory-check-info mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Mã kiểm kê:</strong> <span id="detail_check_code"></span></p>
                        <p><strong>Kho:</strong> <span id="detail_warehouse"></span></p>
                        <p><strong>Khu vực:</strong> <span id="detail_zone"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Ngày kiểm kê:</strong> <span id="detail_date"></span></p>
                        <p><strong>Phương thức:</strong> <span id="detail_check_type"></span></p>
                        <p><strong>Trạng thái:</strong> <span id="detail_status"></span></p>
                    </div>
                </div>
                <p><strong>Ghi chú:</strong> <span id="detail_notes"></span></p>
            </div>
            <h6 class="mb-3">Kết quả kiểm kê</h6>
            <div class="table-responsive">
                <table class="table data-table">
                    <thead>
                        <tr>
                            <th>Mã SP</th>
                            <th>Tên Sản Phẩm</th>
                            <th>Vị Trí</th>
                            <th>Số Lượng Hệ Thống</th>
                            <th>Số Lượng Thực Tế</th>
                            <th>Chênh Lệch</th>
                            <th>Ghi Chú</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryCheckResultList">
                        <!-- Dữ liệu sẽ được nạp bằng AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            <button type="button" class="btn btn-primary" id="btnExportDetail">Xuất Báo Cáo</button>
        </div>
    </div>
</div>

<!-- Modal Thực Hiện Kiểm Kê -->
<div class="custom-modal" id="performInventoryCheckModal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h5 class="modal-title">Thực Hiện Kiểm Kê</h5>
            <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="inventory-check-info mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Mã kiểm kê:</strong> <span id="perform_check_code"></span></p>
                        <p><strong>Kho:</strong> <span id="perform_warehouse"></span></p>
                        <p><strong>Khu vực:</strong> <span id="perform_zone"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Ngày kiểm kê:</strong> <span id="perform_date"></span></p>
                        <p><strong>Phương thức:</strong> <span id="perform_check_type"></span></p>
                        <p><strong>Tiến độ:</strong> <span id="perform_progress">0/0</span> sản phẩm</p>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" id="barcode_input" placeholder="Quét mã barcode hoặc nhập mã sản phẩm">
                        <button class="btn btn-primary" type="button" id="btnScanBarcode">
                            <i class="fas fa-barcode me-2"></i>Quét
                        </button>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-success" id="btnStartRFIDScan">
                        <i class="fas fa-wifi me-2"></i>Bắt đầu quét RFID
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table data-table">
                    <thead>
                        <tr>
                            <th>Mã SP</th>
                            <th>Tên Sản Phẩm</th>
                            <th>Vị Trí</th>
                            <th>Số Lượng Hệ Thống</th>
                            <th>Số Lượng Thực Tế</th>
                            <th>Ghi Chú</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="performCheckItemsList">
                        <!-- Dữ liệu sẽ được nạp bằng AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tạm Dừng</button>
            <button type="button" class="btn btn-primary" id="btnCompleteCheck">Hoàn Thành Kiểm Kê</button>
        </div>
    </div>
</div>

<!-- Modal Cập Nhật Số Lượng -->
<div class="custom-modal" id="updateQuantityModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h5 class="modal-title">Cập Nhật Số Lượng</h5>
            <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="updateQuantityForm">
                <input type="hidden" id="update_result_id">
                <input type="hidden" id="update_product_id">
                <div class="form-group">
                    <label for="update_product_name">Sản phẩm</label>
                    <input type="text" class="form-control" id="update_product_name" readonly>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="update_expected_quantity">Số lượng hệ thống</label>
                            <input type="number" class="form-control" id="update_expected_quantity" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="update_actual_quantity">Số lượng thực tế</label>
                            <input type="number" class="form-control" id="update_actual_quantity" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="update_notes">Ghi chú</label>
                    <textarea class="form-control" id="update_notes" rows="2"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
            <button type="button" class="btn btn-primary" id="btnSaveQuantity">Lưu</button>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div class="toast-container"></div>

<script>
$(document).ready(function() {
    // Biến toàn cục để lưu ID kiểm kê đang thực hiện
    let currentCheckId = null;
    
    // Hàm hiển thị thông báo
    function showToast(message, type = 'success') {
        const toastId = 'toast-' + Date.now();
        const toast = `
            <div id="${toastId}" class="toast toast-${type}">
                <div class="toast-icon">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                </div>
                <div class="toast-message">${message}</div>
            </div>
        `;
        $('.toast-container').append(toast);
        
        setTimeout(() => {
            $(`#${toastId}`).remove();
        }, 3000);
    }
    
    // Hàm mở modal
    function openModal(modalId) {
        $(`#${modalId}`).addClass('show');
    }
    
    // Hàm đóng modal
    function closeModal(modalId) {
        $(`#${modalId}`).removeClass('show');
    }
    
    // Đóng modal khi click vào nút đóng hoặc nút hủy
    $('.modal-close, [data-dismiss="modal"]').click(function() {
        const modalId = $(this).closest('.custom-modal').attr('id');
        closeModal(modalId);
    });
    
    // Tạo mã kiểm kê tự động
    function generateCheckCode() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        // Lấy số thứ tự trong ngày
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'POST',
            data: {
                action: 'getNextCheckNumber',
                date: `${year}${month}${day}`
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const nextNumber = String(response.nextNumber).padStart(3, '0');
                    $('#check_code').val(`KK${year}${month}${day}${nextNumber}`);
                }
            }
        });
    }
    
    // Nạp danh sách kho
    function loadWarehouses() {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'GET',
            data: {
                action: 'getWarehouses'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let options = '<option value="">Chọn kho</option>';
                    response.warehouses.forEach(warehouse => {
                        options += `<option value="${warehouse.warehouse_id}">${warehouse.warehouse_name}</option>`;
                    });
                    $('#warehouse_id').html(options);
                    $('#reportWarehouse').html('<option value="">Tất cả kho</option>' + options);
                }
            }
        });
    }
    
    // Nạp danh sách khu vực theo kho
    function loadZones(warehouseId) {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'GET',
            data: {
                action: 'getZones',
                warehouse_id: warehouseId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let options = '<option value="">Toàn kho</option>';
                    response.zones.forEach(zone => {
                        options += `<option value="${zone.zone_id}">${zone.zone_code} - ${zone.zone_name}</option>`;
                    });
                    $('#zone_id').html(options);
                }
            }
        });
    }
    
    // Khi thay đổi kho, nạp danh sách khu vực
    $('#warehouse_id').change(function() {
        const warehouseId = $(this).val();
        if (warehouseId) {
            loadZones(warehouseId);
        } else {
            $('#zone_id').html('<option value="">Toàn kho</option>');
        }
    });
    
    // Nạp danh sách lịch kiểm kê đã lên lịch
    function loadScheduledChecks() {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'GET',
            data: {
                action: 'getInventoryChecks',
                status: 'SCHEDULED'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.checks.length === 0) {
                        html = '<tr><td colspan="9" class="text-center">Không có dữ liệu</td></tr>';
                    } else {
                        response.checks.forEach(check => {
                            html += `
                                <tr>
                                    <td>${check.check_code}</td>
                                    <td>${check.warehouse_name}</td>
                                    <td>${check.zone_name || 'Toàn kho'}</td>
                                    <td>${formatDate(check.scheduled_date)}</td>
                                    <td>${formatTime(check.scheduled_time)}</td>
                                    <td>${formatCheckType(check.check_type)}</td>
                                    <td><span class="badge bg-info">Đã lên lịch</span></td>
                                    <td>${check.created_by_name}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit btn-start-check" data-id="${check.check_id}" title="Bắt đầu kiểm kê">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <button class="btn-edit" onclick="viewCheckDetails(${check.check_id})" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-delete" onclick="deleteCheck(${check.check_id})" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#scheduledInventoryCheckList').html(html);
                    
                    // Gán sự kiện cho nút bắt đầu kiểm kê
                    $('.btn-start-check').click(function() {
                        const checkId = $(this).data('id');
                        startInventoryCheck(checkId);
                    });
                }
            }
        });
    }
    
    // Nạp danh sách kiểm kê đang thực hiện
    function loadInProgressChecks() {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'GET',
            data: {
                action: 'getInventoryChecks',
                status: 'IN_PROGRESS'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.checks.length === 0) {
                        html = '<tr><td colspan="8" class="text-center">Không có dữ liệu</td></tr>';
                    } else {
                        response.checks.forEach(check => {
                            html += `
                                <tr>
                                    <td>${check.check_code}</td>
                                    <td>${check.warehouse_name}</td>
                                    <td>${check.zone_name || 'Toàn kho'}</td>
                                    <td>${formatDate(check.scheduled_date)}</td>
                                    <td>${formatTime(check.scheduled_time)}</td>
                                    <td>${formatCheckType(check.check_type)}</td>
                                    <td>${check.progress || '0/0'} sản phẩm</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit btn-continue-check" data-id="${check.check_id}" title="Tiếp tục kiểm kê">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <button class="btn-edit" onclick="viewCheckDetails(${check.check_id})" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#inProgressInventoryCheckList').html(html);
                    
                    // Gán sự kiện cho nút tiếp tục kiểm kê
                    $('.btn-continue-check').click(function() {
                        const checkId = $(this).data('id');
                        continueInventoryCheck(checkId);
                    });
                }
            }
        });
    }
    
    // Nạp danh sách kiểm kê đã hoàn thành
    function loadCompletedChecks() {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'GET',
            data: {
                action: 'getInventoryChecks',
                status: 'COMPLETED'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.checks.length === 0) {
                        html = '<tr><td colspan="8" class="text-center">Không có dữ liệu</td></tr>';
                    } else {
                        response.checks.forEach(check => {
                            // Tính tỷ lệ chính xác
                            const accuracy = check.accuracy ? check.accuracy + '%' : 'N/A';
                            const badgeClass = check.accuracy >= 95 ? 'bg-success' : 
                                              (check.accuracy >= 80 ? 'bg-warning' : 'bg-danger');
                            
                            html += `
                                <tr>
                                    <td>${check.check_code}</td>
                                    <td>${check.warehouse_name}</td>
                                    <td>${check.zone_name || 'Toàn kho'}</td>
                                    <td>${formatDate(check.scheduled_date)}</td>
                                    <td>${formatDate(check.completed_at)}</td>
                                    <td>${formatCheckType(check.check_type)}</td>
                                    <td><span class="badge ${badgeClass}">${accuracy}</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit" onclick="viewCheckDetails(${check.check_id})" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-edit" onclick="exportCheckReport(${check.check_id})" title="Xuất báo cáo">
                                                <i class="fas fa-file-export"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#completedInventoryCheckList').html(html);
                }
            }
        });
    }
    
    // Nạp báo cáo kiểm kê
    function loadInventoryReports(warehouseId = '', dateFrom = '', dateTo = '') {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'GET',
            data: {
                action: 'getInventoryReports',
                warehouse_id: warehouseId,
                date_from: dateFrom,
                date_to: dateTo
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.reports.length === 0) {
                        html = '<tr><td colspan="8" class="text-center">Không có dữ liệu</td></tr>';
                    } else {
                        response.reports.forEach(report => {
                            // Tính tỷ lệ chính xác
                            const accuracy = report.accuracy ? report.accuracy + '%' : 'N/A';
                            const badgeClass = report.accuracy >= 95 ? 'bg-success' : 
                                              (report.accuracy >= 80 ? 'bg-warning' : 'bg-danger');
                            
                            html += `
                                <tr>
                                    <td>${report.check_code}</td>
                                    <td>${report.warehouse_name}</td>
                                    <td>${report.zone_name || 'Toàn kho'}</td>
                                    <td>${formatDate(report.scheduled_date)}</td>
                                    <td>${report.total_items}</td>
                                    <td>${report.discrepancy_items}</td>
                                    <td><span class="badge ${badgeClass}">${accuracy}</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit" onclick="viewCheckDetails(${report.check_id})" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-edit" onclick="exportCheckReport(${report.check_id})" title="Xuất báo cáo">
                                                <i class="fas fa-file-export"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#reportsList').html(html);
                }
            }
        });
    }
    
    // Format date function
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }
    
    // Format time function
    function formatTime(timeString) {
        if (!timeString) return '';
        return timeString.substring(0, 5); // Lấy HH:MM
    }
    
    // Format check type function
    function formatCheckType(checkType) {
        switch (checkType) {
            case 'AUTOMATIC_RFID':
                return 'RFID tự động';
            case 'MANUAL_BARCODE':
                return 'Barcode thủ công';
            case 'MIXED':
                return 'Kết hợp';
            default:
                return checkType;
        }
    }
    
    // Hiển thị modal tạo lịch kiểm kê
    $('#btnAddInventoryCheck').click(function() {
        // Reset form
        $('#addInventoryCheckForm')[0].reset();
        $('#zone_id').html('<option value="">Toàn kho</option>');
        
        // Nạp danh sách kho
        loadWarehouses();
        
        // Tạo mã kiểm kê
        generateCheckCode();
        
        // Đặt ngày mặc định là hôm nay
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        $('#scheduled_date').val(formattedDate);
        
        // Mở modal
        openModal('addInventoryCheckModal');
    });
    
    // Lưu lịch kiểm kê mới
    $('#btnSaveInventoryCheck').click(function() {
        // Kiểm tra form
        const form = $('#addInventoryCheckForm')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Lấy dữ liệu từ form
        const warehouseId = $('#warehouse_id').val();
        const zoneId = $('#zone_id').val() || null;
        const scheduledDate = $('#scheduled_date').val();
        const scheduledTime = $('#scheduled_time').val();
        const checkType = $('#check_type').val();
        const checkCode = $('#check_code').val();
        const notes = $('#notes').val();
        
        // Gửi dữ liệu lên server
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'POST',
            data: {
                action: 'createInventoryCheck',
                warehouse_id: warehouseId,
                zone_id: zoneId,
                scheduled_date: scheduledDate,
                scheduled_time: scheduledTime,
                check_type: checkType,
                check_code: checkCode,
                notes: notes
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Tạo lịch kiểm kê thành công', 'success');
                    closeModal('addInventoryCheckModal');
                    loadScheduledChecks();
                } else {
                    showToast(response.message || 'Có lỗi xảy ra', 'error');
                }
            },
            error: function() {
                showToast('Có lỗi xảy ra khi kết nối đến server', 'error');
            }
        });
    });
    
    // Bắt đầu kiểm kê
    function startInventoryCheck(checkId) {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'POST',
            data: {
                action: 'startInventoryCheck',
                check_id: checkId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Bắt đầu kiểm kê thành công', 'success');
                    loadInventoryCheckDetails(checkId, 'perform');
                } else {
                    showToast(response.message || 'Có lỗi xảy ra', 'error');
                }
            }
        });
    }
    
    // Tiếp tục kiểm kê
    function continueInventoryCheck(checkId) {
        loadInventoryCheckDetails(checkId, 'perform');
    }
    
    // Nạp chi tiết kiểm kê
    function loadInventoryCheckDetails(checkId, mode = 'view') {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'GET',
            data: {
                action: 'getInventoryCheckDetails',
                check_id: checkId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (mode === 'view') {
                        // Hiển thị chi tiết trong modal xem
                        $('#detail_check_code').text(response.check.check_code);
                        $('#detail_warehouse').text(response.check.warehouse_name);
                        $('#detail_zone').text(response.check.zone_name || 'Toàn kho');
                        $('#detail_date').text(formatDate(response.check.scheduled_date));
                        $('#detail_check_type').text(formatCheckType(response.check.check_type));
                        $('#detail_status').text(getStatusText(response.check.status));
                        $('#detail_notes').text(response.check.notes || '');
                        
                        // Nạp kết quả kiểm kê
                        let resultsHtml = '';
                        if (response.results.length === 0) {
                            resultsHtml = '<tr><td colspan="7" class="text-center">Chưa có kết quả kiểm kê</td></tr>';
                        } else {
                            response.results.forEach(result => {
                                const difference = result.difference;
                                const differenceClass = difference === 0 ? 'text-success' : (difference > 0 ? 'text-primary' : 'text-danger');
                                const differenceText = difference === 0 ? 'Khớp' : (difference > 0 ? `+${difference}` : difference);
                                
                                resultsHtml += `
                                    <tr>
                                        <td>${result.product_code}</td>
                                        <td>${result.product_name}</td>
                                        <td>${result.shelf_code || ''} ${result.zone_code ? `(${result.zone_code})` : ''}</td>
                                        <td>${result.expected_quantity}</td>
                                        <td>${result.actual_quantity}</td>
                                        <td class="${differenceClass}">${differenceText}</td>
                                        <td>${result.notes || ''}</td>
                                    </tr>
                                `;
                            });
                        }
                        $('#inventoryCheckResultList').html(resultsHtml);
                        
                        // Hiển thị modal xem chi tiết
                        openModal('inventoryCheckDetailModal');
                    } else if (mode === 'perform') {
                        // Lưu ID kiểm kê hiện tại
                        currentCheckId = checkId;
                        
                        // Hiển thị thông tin trong modal thực hiện
                        $('#perform_check_code').text(response.check.check_code);
                        $('#perform_warehouse').text(response.check.warehouse_name);
                        $('#perform_zone').text(response.check.zone_name || 'Toàn kho');
                        $('#perform_date').text(formatDate(response.check.scheduled_date));
                        $('#perform_check_type').text(formatCheckType(response.check.check_type));
                        
                        // Hiển thị tiến độ
                        const totalItems = response.inventory.length;
                        const checkedItems = response.results.length;
                        $('#perform_progress').text(`${checkedItems}/${totalItems}`);
                        
                        // Nạp danh sách sản phẩm cần kiểm kê
                        loadCheckItems();
                        
                        // Hiển thị modal thực hiện kiểm kê
                        openModal('performInventoryCheckModal');
                    }
                } else {
                    showToast(response.message || 'Có lỗi xảy ra khi tải dữ liệu', 'error');
                }
            }
        });
    }
    
    // Nạp danh sách sản phẩm cần kiểm kê
    function loadCheckItems() {
        if (!currentCheckId) return;
        
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'GET',
            data: {
                action: 'getCheckItems',
                check_id: currentCheckId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.items.length === 0) {
                        html = '<tr><td colspan="7" class="text-center">Không có sản phẩm nào</td></tr>';
                    } else {
                        response.items.forEach(item => {
                            const isChecked = item.actual_quantity !== null;
                            const rowClass = isChecked ? 'table-success' : '';
                            const actualQuantity = isChecked ? item.actual_quantity : '';
                            const notes = item.notes || '';
                            
                            html += `
                                <tr class="${rowClass}">
                                    <td>${item.product_code}</td>
                                    <td>${item.product_name}</td>
                                    <td>${item.shelf_code || ''} ${item.zone_code ? `(${item.zone_code})` : ''}</td>
                                    <td>${item.expected_quantity}</td>
                                    <td>${actualQuantity}</td>
                                    <td>${notes}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit" onclick="updateQuantity(${item.result_id || 0}, ${item.product_id}, '${item.product_name}', ${item.expected_quantity}, ${actualQuantity || 0}, '${notes}')" title="Cập nhật số lượng">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#performCheckItemsList').html(html);
                    
                    // Cập nhật tiến độ
                    const totalItems = response.items.length;
                    const checkedItems = response.items.filter(item => item.actual_quantity !== null).length;
                    $('#perform_progress').text(`${checkedItems}/${totalItems}`);
                }
            }
        });
    }
    
    // Mở modal cập nhật số lượng
    window.updateQuantity = function(resultId, productId, productName, expectedQuantity, actualQuantity, notes) {
        $('#update_result_id').val(resultId);
        $('#update_product_id').val(productId);
        $('#update_product_name').val(productName);
        $('#update_expected_quantity').val(expectedQuantity);
        $('#update_actual_quantity').val(actualQuantity);
        $('#update_notes').val(notes);
        
        openModal('updateQuantityModal');
    };
    
    // Lưu cập nhật số lượng
    $('#btnSaveQuantity').click(function() {
        // Kiểm tra form
        const form = $('#updateQuantityForm')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Lấy dữ liệu từ form
        const resultId = $('#update_result_id').val();
        const productId = $('#update_product_id').val();
        const actualQuantity = $('#update_actual_quantity').val();
        const notes = $('#update_notes').val();
        
        // Gửi dữ liệu lên server
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'POST',
            data: {
                action: 'updateCheckResult',
                check_id: currentCheckId,
                result_id: resultId,
                product_id: productId,
                actual_quantity: actualQuantity,
                notes: notes
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Cập nhật số lượng thành công', 'success');
                    closeModal('updateQuantityModal');
                    loadCheckItems();
                } else {
                    showToast(response.message || 'Có lỗi xảy ra', 'error');
                }
            }
        });
    });
    
    // Quét barcode
    $('#btnScanBarcode').click(function() {
        const barcode = $('#barcode_input').val().trim();
        if (!barcode) {
            showToast('Vui lòng nhập mã barcode', 'error');
            return;
        }
        
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'POST',
            data: {
                action: 'scanBarcode',
                check_id: currentCheckId,
                barcode: barcode
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Quét mã barcode thành công', 'success');
                    $('#barcode_input').val('');
                    loadCheckItems();
                } else {
                    showToast(response.message || 'Có lỗi xảy ra', 'error');
                }
            }
        });
    });
    
    // Bắt đầu quét RFID
    $('#btnStartRFIDScan').click(function() {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'POST',
            data: {
                action: 'startRFIDScan',
                check_id: currentCheckId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Đã bắt đầu quét RFID', 'success');
                    // Giả lập quét RFID thành công sau 3 giây
                    setTimeout(function() {
                        simulateRFIDScanComplete();
                    }, 3000);
                } else {
                    showToast(response.message || 'Có lỗi xảy ra', 'error');
                }
            }
        });
    });
    
    // Giả lập hoàn thành quét RFID
    function simulateRFIDScanComplete() {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'POST',
            data: {
                action: 'completeRFIDScan',
                check_id: currentCheckId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Quét RFID hoàn thành', 'success');
                    loadCheckItems();
                } else {
                    showToast(response.message || 'Có lỗi xảy ra', 'error');
                }
            }
        });
    }
    
    // Hoàn thành kiểm kê
    $('#btnCompleteCheck').click(function() {
        $.ajax({
            url: 'ajax/kiemke/ajax_handler.php',
            type: 'POST',
            data: {
                action: 'completeInventoryCheck',
                check_id: currentCheckId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Hoàn thành kiểm kê thành công', 'success');
                    closeModal('performInventoryCheckModal');
                    currentCheckId = null;
                    
                    // Nạp lại danh sách
                    loadScheduledChecks();
                    loadInProgressChecks();
                    loadCompletedChecks();
                } else {
                    showToast(response.message || 'Có lỗi xảy ra', 'error');
                }
            }
        });
    });
    
    // Xóa lịch kiểm kê
    window.deleteCheck = function(checkId) {
        if (confirm('Bạn có chắc chắn muốn xóa lịch kiểm kê này?')) {
            $.ajax({
                url: 'ajax/kiemke/ajax_handler.php',
                type: 'POST',
                data: {
                    action: 'deleteInventoryCheck',
                    check_id: checkId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Xóa lịch kiểm kê thành công', 'success');
                        loadScheduledChecks();
                    } else {
                        showToast(response.message || 'Có lỗi xảy ra', 'error');
                    }
                }
            });
        }
    };
    
    // Xem chi tiết kiểm kê
    window.viewCheckDetails = function(checkId) {
        loadInventoryCheckDetails(checkId, 'view');
    };
    
    // Xuất báo cáo kiểm kê
    window.exportCheckReport = function(checkId) {
        window.location.href = `ajax/kiemke/export_report.php?check_id=${checkId}`;
    };
    
    // Lọc báo cáo kiểm kê
    $('#btnFilterReports').click(function() {
        const warehouseId = $('#reportWarehouse').val();
        const dateFrom = $('#reportDateFrom').val();
        const dateTo = $('#reportDateTo').val();
        
        loadInventoryReports(warehouseId, dateFrom, dateTo);
    });
    
    // Xuất báo cáo Excel
    $('#btnExportReport').click(function() {
        const warehouseId = $('#reportWarehouse').val();
        const dateFrom = $('#reportDateFrom').val();
        const dateTo = $('#reportDateTo').val();
        
        window.location.href = `ajax/kiemke/export_excel.php?warehouse_id=${warehouseId}&date_from=${dateFrom}&date_to=${dateTo}`;
    });
    
    // Xuất báo cáo chi tiết
    $('#btnExportDetail').click(function() {
        const checkCode = $('#detail_check_code').text();
        if (checkCode) {
            window.location.href = `ajax/kiemke/export_report.php?check_code=${checkCode}`;
        }
    });
    
    // Hàm chuyển đổi trạng thái thành text
    function getStatusText(status) {
        switch (status) {
            case 'SCHEDULED':
                return 'Đã lên lịch';
            case 'IN_PROGRESS':
                return 'Đang thực hiện';
            case 'COMPLETED':
                return 'Đã hoàn thành';
            case 'CANCELLED':
                return 'Đã hủy';
            default:
                return status;
        }
    }
    
    // Nạp dữ liệu ban đầu
    loadScheduledChecks();
    loadInProgressChecks();
    loadCompletedChecks();
    loadInventoryReports();
    
    // Chuyển tab
    $('#inventoryCheckTabs a').on('shown.bs.tab', function(e) {
        const tabId = $(e.target).attr('id');
        
        if (tabId === 'scheduled-tab') {
            loadScheduledChecks();
        } else if (tabId === 'in-progress-tab') {
            loadInProgressChecks();
        } else if (tabId === 'completed-tab') {
            loadCompletedChecks();
        } else if (tabId === 'reports-tab') {
            loadInventoryReports();
        }
    });
});
</script>