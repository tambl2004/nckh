<div class="function-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">Quản lý kiểm kê kho</h4>
        <div>
            <button class="btn btn-add me-2" onclick="showInventoryCheckForm()">
                <i class="fas fa-plus-circle me-2"></i>Tạo phiếu kiểm kê
            </button>
        </div>
    </div>

    <!-- Tabs cho kiểm kê -->
    <ul class="nav nav-tabs mb-4" id="inventoryCheckTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="scheduled-checks-tab" data-bs-toggle="tab" data-bs-target="#scheduled-checks" type="button" role="tab" aria-controls="scheduled-checks" aria-selected="true">
                Phiếu kiểm kê
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="check-history-tab" data-bs-toggle="tab" data-bs-target="#check-history" type="button" role="tab" aria-controls="check-history" aria-selected="false">
                Lịch sử kiểm kê
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="check-reports-tab" data-bs-toggle="tab" data-bs-target="#check-reports" type="button" role="tab" aria-controls="check-reports" aria-selected="false">
                Báo cáo chênh lệch
            </button>
        </li>
    </ul>

    <!-- Nội dung các tab -->
    <div class="tab-content" id="inventoryCheckTabsContent">
        <!-- Tab phiếu kiểm kê -->
        <div class="tab-pane fade show active" id="scheduled-checks" role="tabpanel" aria-labelledby="scheduled-checks-tab">
            <!-- Bộ lọc phiếu kiểm kê -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="SCHEDULED">Đã lên lịch</option>
                        <option value="IN_PROGRESS">Đang thực hiện</option>
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
                    <input type="text" class="form-control" id="searchCheck" placeholder="Tìm kiếm mã phiếu...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100" onclick="filterInventoryChecks()">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
            </div>

            <!-- Bảng danh sách phiếu kiểm kê -->
            <div class="table-responsive">
                <table class="data-table table">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Kho</th>
                            <th>Khu vực</th>
                            <th>Thời gian lên lịch</th>
                            <th>Loại kiểm kê</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryChecksList">
                        <!-- Dữ liệu sẽ được load bằng AJAX -->
                    </tbody>
                </table>
            </div>
            <div id="checksPagination" class="d-flex justify-content-center mt-4">
                <!-- Phân trang sẽ được tạo bằng JavaScript -->
            </div>
        </div>

        <!-- Tab lịch sử kiểm kê -->
        <div class="tab-pane fade" id="check-history" role="tabpanel" aria-labelledby="check-history-tab">
            <!-- Bộ lọc lịch sử kiểm kê -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <select class="form-select" id="historyWarehouseFilter">
                        <option value="">Tất cả kho</option>
                        <!-- Danh sách kho sẽ được load bằng AJAX -->
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="dateFromFilter" placeholder="Từ ngày">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="dateToFilter" placeholder="Đến ngày">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-primary w-100" onclick="filterCheckHistory()">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
            </div>

            <!-- Bảng lịch sử kiểm kê -->
            <div class="table-responsive">
                <table class="data-table table">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Kho</th>
                            <th>Khu vực</th>
                            <th>Ngày kiểm kê</th>
                            <th>Người thực hiện</th>
                            <th>Tổng sản phẩm</th>
                            <th>Số SP chênh lệch</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="checkHistoryList">
                        <!-- Dữ liệu sẽ được load bằng AJAX -->
                    </tbody>
                </table>
            </div>
            <div id="historyPagination" class="d-flex justify-content-center mt-4">
                <!-- Phân trang sẽ được tạo bằng JavaScript -->
            </div>
        </div>

        <!-- Tab báo cáo chênh lệch -->
        <div class="tab-pane fade" id="check-reports" role="tabpanel" aria-labelledby="check-reports-tab">
            <!-- Bộ lọc báo cáo chênh lệch -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <select class="form-select" id="reportWarehouseFilter">
                        <option value="">Tất cả kho</option>
                        <!-- Danh sách kho sẽ được load bằng AJAX -->
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="reportCheckFilter">
                        <option value="">Chọn phiếu kiểm kê</option>
                        <!-- Danh sách phiếu kiểm kê sẽ được load bằng AJAX -->
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-primary w-100" onclick="generateDiscrepancyReport()">
                        <i class="fas fa-chart-bar me-2"></i>Tạo báo cáo
                    </button>
                </div>
            </div>

            <!-- Kết quả báo cáo -->
            <div id="reportResults" class="mt-4">
                <!-- Kết quả báo cáo sẽ được load bằng AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal tạo phiếu kiểm kê -->
<div class="custom-modal" id="checkModal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h5 class="modal-title" id="checkModalTitle">Tạo phiếu kiểm kê</h5>
            <button type="button" class="modal-close" onclick="closeCheckModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="inventoryCheckForm">
                <input type="hidden" id="checkId" name="checkId" value="0">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="checkCode">Mã phiếu kiểm kê <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="checkCode" name="checkCode" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="warehouseId">Kho kiểm kê <span class="text-danger">*</span></label>
                            <select class="form-select" id="warehouseId" name="warehouseId" required onchange="loadZones()">
                                <option value="">Chọn kho</option>
                                <!-- Danh sách kho sẽ được load bằng AJAX -->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="zoneId">Khu vực kiểm kê</label>
                            <select class="form-select" id="zoneId" name="zoneId">
                                <option value="">Toàn bộ kho</option>
                                <!-- Danh sách khu vực sẽ được load dựa vào kho đã chọn -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="checkType">Loại kiểm kê <span class="text-danger">*</span></label>
                            <select class="form-select" id="checkType" name="checkType" required>
                                <option value="">Chọn loại kiểm kê</option>
                                <option value="AUTOMATIC_RFID">Tự động (RFID)</option>
                                <option value="MANUAL_BARCODE">Thủ công (Barcode)</option>
                                <option value="MIXED">Kết hợp</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="scheduledDate">Ngày kiểm kê <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="scheduledDate" name="scheduledDate" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="scheduledTime">Thời gian bắt đầu <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="scheduledTime" name="scheduledTime" required>
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
            <button type="button" class="btn btn-secondary" onclick="closeCheckModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveInventoryCheck()">Lưu phiếu kiểm kê</button>
        </div>
    </div>
</div>

<!-- Modal thực hiện kiểm kê -->
<div class="custom-modal" id="performCheckModal">
    <div class="modal-content" style="max-width: 900px; max-height: 90vh;">
        <div class="modal-header">
            <h5 class="modal-title" id="performCheckModalTitle">Thực hiện kiểm kê</h5>
            <button type="button" class="modal-close" onclick="closePerformCheckModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="check-info mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Mã phiếu:</strong> <span id="viewCheckCode"></span></p>
                        <p><strong>Kho:</strong> <span id="viewWarehouse"></span></p>
                        <p><strong>Khu vực:</strong> <span id="viewZone"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Loại kiểm kê:</strong> <span id="viewCheckType"></span></p>
                        <p><strong>Thời gian lên lịch:</strong> <span id="viewScheduledTime"></span></p>
                        <p><strong>Trạng thái:</strong> <span id="viewStatus" class="badge"></span></p>
                    </div>
                </div>
            </div>

            <div id="rfidScannerSection" class="mb-4" style="display: none;">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-rss me-2"></i>Quét RFID</h6>
                        <div>
                            <button class="btn btn-sm btn-primary me-2" id="startRfidScan">
                                <i class="fas fa-play me-1"></i>Bắt đầu quét
                            </button>
                            <button class="btn btn-sm btn-danger" id="stopRfidScan" style="display: none;">
                                <i class="fas fa-stop me-1"></i>Dừng quét
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info" id="rfidScanStatus">
                            <i class="fas fa-info-circle me-2"></i>Sẵn sàng quét RFID
                        </div>
                        <div class="progress mb-3" style="height: 20px;">
                            <div id="rfidScanProgress" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <p><strong>Đã quét:</strong> <span id="rfidScannedCount">0</span> sản phẩm</p>
                    </div>
                </div>
            </div>

            <div id="barcodeScannerSection" class="mb-4" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-barcode me-2"></i>Quét Barcode</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="barcodeInput">Nhập/quét mã barcode</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="barcodeInput" placeholder="Mã barcode">
                                <button class="btn btn-primary" onclick="processBarcode()">
                                    <i class="fas fa-arrow-right me-1"></i>Xử lý
                                </button>
                            </div>
                        </div>
                        
                        <div id="barcodeResult" style="display: none;">
                            <div class="alert alert-success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0"><strong>Sản phẩm:</strong> <span id="barcodeProductName"></span></p>
                                        <p class="mb-0"><strong>Mã sản phẩm:</strong> <span id="barcodeProductCode"></span></p>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-success" onclick="addScannedProduct()">
                                            <i class="fas fa-plus me-1"></i>Thêm
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-2">
                                <label for="barcodeQuantity">Số lượng thực tế</label>
                                <input type="number" class="form-control" id="barcodeQuantity" value="1" min="1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Kết quả kiểm kê</h6>
                    <button class="btn btn-sm btn-success" onclick="addProductManually()">
                        <i class="fas fa-plus me-1"></i>Thêm sản phẩm thủ công
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Mã SP</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Kệ/Vị trí</th>
                                    <th>SL hệ thống</th>
                                    <th>SL thực tế</th>
                                    <th>Chênh lệch</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="checkResultsList">
                                <!-- Dữ liệu sẽ được load bằng AJAX và cập nhật khi quét -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Tổng số sản phẩm:</strong> <span id="totalProducts">0</span></p>
                        <p><strong>Số sản phẩm đã kiểm kê:</strong> <span id="checkedProducts">0</span></p>
                        <p><strong>Số sản phẩm chênh lệch:</strong> <span id="discrepancyProducts">0</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePerformCheckModal()">Đóng</button>
            <button type="button" class="btn btn-info" id="btnSaveProgress" onclick="saveCheckProgress()">Lưu tiến độ</button>
            <button type="button" class="btn btn-success" id="btnCompleteCheck" onclick="completeInventoryCheck()">Hoàn thành kiểm kê</button>
        </div>
    </div>
</div>

<!-- Modal thêm sản phẩm thủ công vào kết quả kiểm kê -->
<div class="custom-modal" id="addProductModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h5 class="modal-title">Thêm sản phẩm thủ công</h5>
            <button type="button" class="modal-close" onclick="closeAddProductModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="addProductForm">
                <input type="hidden" id="currentCheckId" name="currentCheckId">
                
                <div class="form-group mb-3">
                    <label for="manualProductId">Sản phẩm <span class="text-danger">*</span></label>
                    <select class="form-select" id="manualProductId" name="productId" required onchange="loadProductDetails()">
                        <option value="">Chọn sản phẩm</option>
                        <!-- Danh sách sản phẩm sẽ được load bằng AJAX -->
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label for="manualShelfId">Kệ/Vị trí</label>
                    <select class="form-select" id="manualShelfId" name="shelfId">
                        <option value="">Chọn kệ</option>
                        <!-- Danh sách kệ sẽ được load bằng AJAX -->
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label for="manualBatchNumber">Số lô</label>
                    <input type="text" class="form-control" id="manualBatchNumber" name="batchNumber">
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="manualExpectedQuantity">Số lượng trong hệ thống</label>
                            <input type="number" class="form-control" id="manualExpectedQuantity" name="expectedQuantity" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="manualActualQuantity">Số lượng thực tế <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="manualActualQuantity" name="actualQuantity" min="0" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="manualNotes">Ghi chú</label>
                    <textarea class="form-control" id="manualNotes" name="notes" rows="2"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeAddProductModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveManualProduct()">Thêm sản phẩm</button>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết báo cáo kiểm kê -->
<div class="custom-modal" id="viewReportModal">
    <div class="modal-content" style="max-width: 900px; max-height: 90vh;">
        <div class="modal-header">
            <h5 class="modal-title" id="viewReportModalTitle">Chi tiết báo cáo kiểm kê</h5>
            <button type="button" class="modal-close" onclick="closeViewReportModal()">×</button>
        </div>
        <div class="modal-body" id="reportDetailContent">
            <!-- Nội dung chi tiết báo cáo sẽ được load bằng AJAX -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewReportModal()">Đóng</button>
            <button type="button" class="btn btn-success" onclick="exportReportToExcel()">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </button>
            <button type="button" class="btn btn-danger" onclick="exportReportToPDF()">
                <i class="fas fa-file-pdf me-1"></i>Xuất PDF
            </button>
        </div>
    </div>
</div>

<script>
// Biến toàn cục
let currentCheckId = 0;
let scannedProducts = [];
let rfidScanInterval = null;
let totalScannedProducts = 0;

// Load dữ liệu khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    // Load danh sách phiếu kiểm kê
    loadInventoryChecks();
    
    // Load danh sách kho
    loadWarehouses();
    
    // Load danh sách phiếu kiểm kê đã hoàn thành cho báo cáo
    loadCompletedChecks();
    
    // Thêm sự kiện cho input barcode
    const barcodeInput = document.getElementById('barcodeInput');
    if (barcodeInput) {
        barcodeInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                processBarcode();
            }
        });
    }
    
    // Thêm sự kiện cho nút bắt đầu/dừng quét RFID
    const startRfidScan = document.getElementById('startRfidScan');
    const stopRfidScan = document.getElementById('stopRfidScan');
    
    if (startRfidScan && stopRfidScan) {
        startRfidScan.addEventListener('click', function() {
            startRfidScan.style.display = 'none';
            stopRfidScan.style.display = 'inline-block';
            simulateRfidScanning();
        });
        
        stopRfidScan.addEventListener('click', function() {
            stopRfidScan.style.display = 'none';
            startRfidScan.style.display = 'inline-block';
            stopRfidScanning();
        });
    }
});

// Hàm hiển thị form tạo phiếu kiểm kê
function showInventoryCheckForm() {
    // Reset form
    document.getElementById('inventoryCheckForm').reset();
    document.getElementById('checkId').value = 0;
    document.getElementById('checkModalTitle').innerText = 'Tạo phiếu kiểm kê';
    
    // Tạo mã phiếu kiểm kê mới
    const now = new Date();
    const checkCode = 'CHK' + now.getFullYear() + 
                      (now.getMonth() + 1).toString().padStart(2, '0') + 
                      now.getDate().toString().padStart(2, '0') + 
                      now.getHours().toString().padStart(2, '0') + 
                      now.getMinutes().toString().padStart(2, '0');
    document.getElementById('checkCode').value = checkCode;
    
    // Thiết lập ngày và giờ mặc định
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('scheduledDate').value = tomorrow.toISOString().split('T')[0];
    document.getElementById('scheduledTime').value = '08:00';
    
    // Hiển thị modal
    document.getElementById('checkModal').classList.add('show');
}

// Đóng modal tạo phiếu kiểm kê
function closeCheckModal() {
    document.getElementById('checkModal').classList.remove('show');
}

// Lưu phiếu kiểm kê
function saveInventoryCheck() {
    // Kiểm tra thông tin bắt buộc
    if (!validateCheckForm()) return;
    
    // Chuẩn bị dữ liệu form
    const formData = new FormData(document.getElementById('inventoryCheckForm'));
    formData.append('action', 'saveInventoryCheck');
    formData.append('status', 'SCHEDULED');
    
    // Gửi yêu cầu AJAX lưu phiếu kiểm kê
    fetch('api/inventory_checks.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã lưu phiếu kiểm kê thành công');
            closeCheckModal();
            loadInventoryChecks(); // Tải lại danh sách phiếu kiểm kê
        } else {
            alert(data.message || 'Không thể lưu phiếu kiểm kê');
        }
    })
    .catch(error => {
        console.error('Lỗi khi lưu phiếu kiểm kê:', error);
        alert('Đã xảy ra lỗi khi lưu phiếu kiểm kê');
    });
}

// Kiểm tra thông tin form kiểm kê
function validateCheckForm() {
    const checkCode = document.getElementById('checkCode').value;
    const warehouseId = document.getElementById('warehouseId').value;
    const checkType = document.getElementById('checkType').value;
    const scheduledDate = document.getElementById('scheduledDate').value;
    const scheduledTime = document.getElementById('scheduledTime').value;
    
    // Kiểm tra các trường bắt buộc
    if (!checkCode) {
        alert('Vui lòng nhập mã phiếu kiểm kê');
        return false;
    }
    
    if (!warehouseId) {
        alert('Vui lòng chọn kho kiểm kê');
        return false;
    }
    
    if (!checkType) {
        alert('Vui lòng chọn loại kiểm kê');
        return false;
    }
    
    if (!scheduledDate) {
        alert('Vui lòng chọn ngày kiểm kê');
        return false;
    }
    
    if (!scheduledTime) {
        alert('Vui lòng chọn thời gian kiểm kê');
        return false;
    }
    
    return true;
}

// Load danh sách phiếu kiểm kê
function loadInventoryChecks(page = 1) {
    const status = document.getElementById('statusFilter').value;
    const warehouseId = document.getElementById('warehouseFilter').value;
    const search = document.getElementById('searchCheck').value;
    
    // Hiển thị loading
    document.getElementById('inventoryChecksList').innerHTML = '<tr><td colspan="7" class="text-center">Đang tải dữ liệu...</td></tr>';
    
    // Gọi AJAX để lấy danh sách phiếu kiểm kê
    fetch(`api/inventory_checks.php?action=getInventoryChecks&page=${page}&status=${status}&warehouseId=${warehouseId}&search=${search}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị danh sách phiếu kiểm kê
                let html = '';
                
                if (data.checks.length === 0) {
                    html = '<tr><td colspan="7" class="text-center">Không có phiếu kiểm kê nào</td></tr>';
                } else {
                    data.checks.forEach(check => {
                        // Xác định class và text cho trạng thái
                        let statusClass = '';
                        let statusText = '';
                        
                        switch (check.status) {
                            case 'SCHEDULED':
                                statusClass = 'bg-info text-white';
                                statusText = 'Đã lên lịch';
                                break;
                            case 'IN_PROGRESS':
                                statusClass = 'bg-warning text-dark';
                                statusText = 'Đang thực hiện';
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
                        
                        // Xác định loại kiểm kê
                        let checkTypeText = '';
                        
                        switch (check.check_type) {
                            case 'AUTOMATIC_RFID':
                                checkTypeText = 'Tự động (RFID)';
                                break;
                            case 'MANUAL_BARCODE':
                                checkTypeText = 'Thủ công (Barcode)';
                                break;
                            case 'MIXED':
                                checkTypeText = 'Kết hợp';
                                break;
                        }
                        
                        html += `
                            <tr>
                                <td>${check.check_code}</td>
                                <td>${check.warehouse_name}</td>
                                <td>${check.zone_name || 'Toàn bộ kho'}</td>
                                <td>${new Date(check.scheduled_date + ' ' + check.scheduled_time).toLocaleString('vi-VN')}</td>
                                <td>${checkTypeText}</td>
                                <td><span class="badge ${statusClass}">${statusText}</span></td>
                                <td>
                                    <div class="d-flex">
                                        ${check.status === 'SCHEDULED' || check.status === 'IN_PROGRESS' ? `
                                            <button class="btn btn-sm btn-primary me-1" onclick="performInventoryCheck(${check.check_id})">
                                                <i class="fas fa-clipboard-check"></i>
                                            </button>
                                        ` : ''}
                                        ${check.status === 'SCHEDULED' ? `
                                            <button class="btn btn-sm btn-info me-1" onclick="editInventoryCheck(${check.check_id})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="cancelInventoryCheck(${check.check_id})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        ` : ''}
                                        ${check.status === 'COMPLETED' ? `
                                            <button class="btn btn-sm btn-success me-1" onclick="viewCheckReport(${check.check_id})">
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }
                
                document.getElementById('inventoryChecksList').innerHTML = html;
                
                // Tạo phân trang
                createPagination('checksPagination', data.totalPages, page, 'loadInventoryChecks');
                
            } else {
                // Hiển thị lỗi
                document.getElementById('inventoryChecksList').innerHTML = `<tr><td colspan="7" class="text-center text-danger">${data.message || 'Không thể tải danh sách phiếu kiểm kê'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách phiếu kiểm kê:', error);
            document.getElementById('inventoryChecksList').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
        });
}

// Tạo phân trang
function createPagination(containerId, totalPages, currentPage, callbackFunction) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
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

// Load danh sách kho
function loadWarehouses() {
    // Các select cần load danh sách kho
    const warehouseSelects = [
        document.getElementById('warehouseFilter'),
        document.getElementById('historyWarehouseFilter'),
        document.getElementById('reportWarehouseFilter'),
        document.getElementById('warehouseId')
    ];
    
    // Gọi AJAX để lấy danh sách kho
    fetch(`api/warehouses.php?action=getWarehouses`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.warehouses) {
                const options = data.warehouses.map(warehouse => 
                    `<option value="${warehouse.warehouse_id}">${warehouse.warehouse_name}</option>`
                ).join('');
                
                // Cập nhật các select
                warehouseSelects.forEach(select => {
                    if (select) {
                        const defaultOption = select.querySelector('option:first-child');
                        select.innerHTML = defaultOption.outerHTML + options;
                    }
                });
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách kho:', error);
        });
}

// Load danh sách khu vực theo kho đã chọn
function loadZones() {
    const warehouseId = document.getElementById('warehouseId').value;
    const zoneSelect = document.getElementById('zoneId');
    
    if (!warehouseId || !zoneSelect) return;
    
    // Reset select zone
    zoneSelect.innerHTML = '<option value="">Toàn bộ kho</option>';
    
    // Gọi AJAX để lấy danh sách khu vực
    fetch(`api/get_zones.php?warehouseId=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.zones) {
                data.zones.forEach(zone => {
                    const option = document.createElement('option');
                    option.value = zone.zone_id;
                    option.textContent = `${zone.zone_code} - ${zone.zone_name}`;
                    zoneSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách khu vực:', error);
        });
}

// Load danh sách phiếu kiểm kê đã hoàn thành cho báo cáo
function loadCompletedChecks() {
    const reportCheckFilter = document.getElementById('reportCheckFilter');
    
    if (!reportCheckFilter) return;
    
    // Gọi AJAX để lấy danh sách phiếu kiểm kê đã hoàn thành
    fetch(`api/inventory_checks.php?action=getCompletedChecks`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.checks) {
                // Reset select
                reportCheckFilter.innerHTML = '<option value="">Chọn phiếu kiểm kê</option>';
                
                // Thêm options
                data.checks.forEach(check => {
                    const option = document.createElement('option');
                    option.value = check.check_id;
                    option.textContent = `${check.check_code} - ${check.warehouse_name} (${new Date(check.completed_at).toLocaleDateString('vi-VN')})`;
                    reportCheckFilter.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách phiếu kiểm kê đã hoàn thành:', error);
        });
}

// Hàm thực hiện kiểm kê
function performInventoryCheck(checkId) {
    currentCheckId = checkId;
    
    // Gọi AJAX để lấy thông tin phiếu kiểm kê
    fetch(`api/inventory_checks.php?action=getCheckDetails&checkId=${checkId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const checkInfo = data.check;
                
                // Hiển thị thông tin phiếu kiểm kê
                document.getElementById('viewCheckCode').textContent = checkInfo.check_code;
                document.getElementById('viewWarehouse').textContent = checkInfo.warehouse_name;
                document.getElementById('viewZone').textContent = checkInfo.zone_name || 'Toàn bộ kho';
                
                // Hiển thị loại kiểm kê
                let checkTypeText = '';
                switch (checkInfo.check_type) {
                    case 'AUTOMATIC_RFID':
                        checkTypeText = 'Tự động (RFID)';
                        document.getElementById('rfidScannerSection').style.display = 'block';
                        document.getElementById('barcodeScannerSection').style.display = 'none';
                        break;
                    case 'MANUAL_BARCODE':
                        checkTypeText = 'Thủ công (Barcode)';
                        document.getElementById('rfidScannerSection').style.display = 'none';
                        document.getElementById('barcodeScannerSection').style.display = 'block';
                        break;
                    case 'MIXED':
                        checkTypeText = 'Kết hợp';
                        document.getElementById('rfidScannerSection').style.display = 'block';
                        document.getElementById('barcodeScannerSection').style.display = 'block';
                        break;
                }
                document.getElementById('viewCheckType').textContent = checkTypeText;
                
                // Hiển thị thời gian lên lịch
                document.getElementById('viewScheduledTime').textContent = new Date(checkInfo.scheduled_date + ' ' + checkInfo.scheduled_time).toLocaleString('vi-VN');
                
                // Hiển thị trạng thái
                const viewStatus = document.getElementById('viewStatus');
                let statusClass = '';
                let statusText = '';
                
                switch (checkInfo.status) {
                    case 'SCHEDULED':
                        statusClass = 'bg-info text-white';
                        statusText = 'Đã lên lịch';
                        break;
                    case 'IN_PROGRESS':
                        statusClass = 'bg-warning text-dark';
                        statusText = 'Đang thực hiện';
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
                
                viewStatus.className = `badge ${statusClass}`;
                viewStatus.textContent = statusText;
                
                // Load danh sách kết quả kiểm kê đã có (nếu có)
                loadCheckResults(checkId);
                
                // Cập nhật trạng thái phiếu kiểm kê thành "Đang thực hiện" nếu đang ở trạng thái "Đã lên lịch"
                if (checkInfo.status === 'SCHEDULED') {
                    updateCheckStatus(checkId, 'IN_PROGRESS');
                }
                
                // Hiển thị modal thực hiện kiểm kê
                document.getElementById('performCheckModal').classList.add('show');
            } else {
                alert(data.message || 'Không thể tải thông tin phiếu kiểm kê');
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải thông tin phiếu kiểm kê:', error);
            alert('Đã xảy ra lỗi khi tải thông tin phiếu kiểm kê');
        });
}

// Đóng modal thực hiện kiểm kê
function closePerformCheckModal() {
    // Dừng quét RFID nếu đang quét
    stopRfidScanning();
    
    // Đóng modal
    document.getElementById('performCheckModal').classList.remove('show');
}

// Cập nhật trạng thái phiếu kiểm kê
function updateCheckStatus(checkId, status) {
    fetch('api/inventory_checks.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'updateCheckStatus',
            checkId: checkId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Lỗi khi cập nhật trạng thái:', data.message);
        }
    })
    .catch(error => {
        console.error('Lỗi khi cập nhật trạng thái:', error);
    });
}

// Load danh sách kết quả kiểm kê
function loadCheckResults(checkId) {
    fetch(`api/inventory_checks.php?action=getCheckResults&checkId=${checkId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const results = data.results || [];
                
                // Hiển thị danh sách kết quả
                renderCheckResults(results);
                
                // Cập nhật thống kê
                updateCheckStats(results);
                
                // Lưu kết quả vào biến toàn cục
                scannedProducts = results;
            } else {
                console.error('Lỗi khi tải kết quả kiểm kê:', data.message);
                document.getElementById('checkResultsList').innerHTML = '<tr><td colspan="7" class="text-center">Chưa có kết quả kiểm kê</td></tr>';
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải kết quả kiểm kê:', error);
            document.getElementById('checkResultsList').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
        });
}

// Hiển thị danh sách kết quả kiểm kê
function renderCheckResults(results) {
    const tbody = document.getElementById('checkResultsList');
    
    if (!results || results.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có kết quả kiểm kê</td></tr>';
        return;
    }
    
    let html = '';
    
    results.forEach(result => {
        const difference = result.actual_quantity - result.expected_quantity;
        const differenceClass = difference === 0 ? 'text-success' : (difference > 0 ? 'text-primary' : 'text-danger');
        
        html += `
            <tr>
                <td>${result.product_code}</td>
                <td>${result.product_name}</td>
                <td>${result.shelf_code || 'N/A'}</td>
                <td>${result.expected_quantity}</td>
                <td>${result.actual_quantity}</td>
                <td class="${differenceClass}">${difference > 0 ? '+' : ''}${difference}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editCheckResult(${result.result_id})">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Cập nhật thống kê kiểm kê
function updateCheckStats(results) {
    if (!results) results = [];
    
    const totalProducts = results.length;
    const checkedProducts = results.filter(r => r.actual_quantity !== null).length;
    const discrepancyProducts = results.filter(r => r.actual_quantity !== null && r.actual_quantity !== r.expected_quantity).length;
    
    document.getElementById('totalProducts').textContent = totalProducts;
    document.getElementById('checkedProducts').textContent = checkedProducts;
    document.getElementById('discrepancyProducts').textContent = discrepancyProducts;
}

// Lưu tiến độ kiểm kê
function saveCheckProgress() {
    if (!currentCheckId) return;
    
    alert('Đã lưu tiến độ kiểm kê thành công');
}

// Hoàn thành kiểm kê
function completeInventoryCheck() {
    if (!currentCheckId) return;
    
    if (!confirm('Bạn có chắc chắn muốn hoàn thành kiểm kê? Sau khi hoàn thành, kết quả kiểm kê sẽ được chốt.')) {
        return;
    }
    
    fetch('api/inventory_checks.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'completeCheck',
            checkId: currentCheckId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã hoàn thành kiểm kê thành công');
            closePerformCheckModal();
            loadInventoryChecks(); // Tải lại danh sách phiếu kiểm kê
        } else {
            alert(data.message || 'Không thể hoàn thành kiểm kê');
        }
    })
    .catch(error => {
        console.error('Lỗi khi hoàn thành kiểm kê:', error);
        alert('Đã xảy ra lỗi khi hoàn thành kiểm kê');
    });
}

// Thêm sản phẩm thủ công vào kết quả kiểm kê
function addProductManually() {
    document.getElementById('currentCheckId').value = currentCheckId;
    document.getElementById('addProductForm').reset();
    
    // Load danh sách sản phẩm
    loadProductsForManualAdd();
    
    // Hiển thị modal
    document.getElementById('addProductModal').classList.add('show');
}

// Đóng modal thêm sản phẩm thủ công
function closeAddProductModal() {
    document.getElementById('addProductModal').classList.remove('show');
}

// Load danh sách sản phẩm cho thêm thủ công
function loadProductsForManualAdd() {
    const productSelect = document.getElementById('manualProductId');
    
    if (!productSelect) return;
    
    // Gọi AJAX để lấy danh sách sản phẩm
    fetch(`api/products.php?action=getProducts`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products) {
                // Reset select
                productSelect.innerHTML = '<option value="">Chọn sản phẩm</option>';
                
                // Thêm options
                data.products.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.product_id;
                    option.textContent = `${product.product_code} - ${product.product_name}`;
                    productSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải danh sách sản phẩm:', error);
        });
}

// Load thông tin chi tiết sản phẩm khi chọn sản phẩm trong form thêm thủ công
function loadProductDetails() {
    const productId = document.getElementById('manualProductId').value;
    const warehouseId = document.getElementById('warehouseId').value;
    
    if (!productId || !warehouseId) return;
    
    // Gọi AJAX để lấy thông tin sản phẩm và số lượng trong kho
    fetch(`api/products.php?action=getProductDetails&productId=${productId}&warehouseId=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị số lượng trong hệ thống
                document.getElementById('manualExpectedQuantity').value = data.quantity || 0;
                
                // Load danh sách kệ chứa sản phẩm
                const shelfSelect = document.getElementById('manualShelfId');
                shelfSelect.innerHTML = '<option value="">Chọn kệ</option>';
                
                if (data.shelves && data.shelves.length > 0) {
                    data.shelves.forEach(shelf => {
                        const option = document.createElement('option');
                        option.value = shelf.shelf_id;
                        option.textContent = `${shelf.shelf_code} (${shelf.quantity} SP)`;
                        shelfSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải thông tin sản phẩm:', error);
        });
}

// Lưu sản phẩm thêm thủ công
function saveManualProduct() {
    const form = document.getElementById('addProductForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'addCheckResult');
    formData.append('checkId', currentCheckId);
    
    fetch('api/inventory_checks.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddProductModal();
            loadCheckResults(currentCheckId); // Tải lại kết quả kiểm kê
        } else {
            alert(data.message || 'Không thể thêm sản phẩm vào kết quả kiểm kê');
        }
    })
    .catch(error => {
        console.error('Lỗi khi thêm sản phẩm:', error);
        alert('Đã xảy ra lỗi khi thêm sản phẩm vào kết quả kiểm kê');
    });
}

// Xử lý quét barcode
function processBarcode() {
    const barcode = document.getElementById('barcodeInput').value.trim();
    
    if (!barcode) return;
    
    // Gọi AJAX để tìm sản phẩm theo barcode
    fetch(`api/products.php?action=getProductByBarcode&barcode=${barcode}&warehouseId=${document.getElementById('warehouseId').value}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị thông tin sản phẩm
                document.getElementById('barcodeResult').style.display = 'block';
                document.getElementById('barcodeProductName').textContent = data.product.product_name;
                document.getElementById('barcodeProductCode').textContent = data.product.product_code;
                
                // Lưu thông tin sản phẩm vào biến toàn cục để thêm vào kết quả
                window.currentScannedProduct = data.product;
            } else {
                alert(data.message || 'Không tìm thấy sản phẩm có mã barcode này');
                document.getElementById('barcodeResult').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Lỗi khi quét barcode:', error);
            alert('Đã xảy ra lỗi khi xử lý mã barcode');
        });
}

// Thêm sản phẩm đã quét barcode vào kết quả
function addScannedProduct() {
    if (!window.currentScannedProduct) return;
    
    const quantity = parseInt(document.getElementById('barcodeQuantity').value) || 0;
    
    if (quantity <= 0) {
        alert('Vui lòng nhập số lượng hợp lệ');
        return;
    }
    
    const product = window.currentScannedProduct;
    
    const formData = new FormData();
    formData.append('action', 'addCheckResult');
    formData.append('checkId', currentCheckId);
    formData.append('productId', product.product_id);
    formData.append('expectedQuantity', product.quantity || 0);
    formData.append('actualQuantity', quantity);
    formData.append('shelfId', product.shelf_id || '');
    
    fetch('api/inventory_checks.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reset form barcode
            document.getElementById('barcodeInput').value = '';
            document.getElementById('barcodeResult').style.display = 'none';
            document.getElementById('barcodeQuantity').value = '1';
            
            // Tải lại kết quả kiểm kê
            loadCheckResults(currentCheckId);
        } else {
            alert(data.message || 'Không thể thêm sản phẩm vào kết quả kiểm kê');
        }
    })
    .catch(error => {
        console.error('Lỗi khi thêm sản phẩm:', error);
        alert('Đã xảy ra lỗi khi thêm sản phẩm vào kết quả kiểm kê');
    });
}

// Mô phỏng quét RFID
function simulateRfidScanning() {
    const totalEstimatedProducts = 100; // Số lượng sản phẩm ước tính cần quét
    let progress = 0;
    totalScannedProducts = 0;
    
    // Cập nhật trạng thái
    document.getElementById('rfidScanStatus').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang quét RFID...';
    
    // Quét mô phỏng
    rfidScanInterval = setInterval(() => {
        // Tăng tiến độ
        progress += Math.random() * 5;
        totalScannedProducts += Math.floor(Math.random() * 3); // 0-2 sản phẩm mỗi lần quét
        
        if (progress >= 100) {
            progress = 100;
            stopRfidScanning();
        }
        
        // Cập nhật UI
        document.getElementById('rfidScanProgress').style.width = `${progress}%`;
        document.getElementById('rfidScanProgress').textContent = `${Math.floor(progress)}%`;
        document.getElementById('rfidScannedCount').textContent = totalScannedProducts;
        
        // Mô phỏng thêm sản phẩm vào danh sách kết quả
        if (Math.random() > 0.7) { // 30% cơ hội thêm sản phẩm mới
            simulateAddRfidProduct();
        }
        
    }, 500);
}

// Dừng quét RFID
function stopRfidScanning() {
    if (rfidScanInterval) {
        clearInterval(rfidScanInterval);
        rfidScanInterval = null;
    }
    
    document.getElementById('rfidScanStatus').innerHTML = '<i class="fas fa-check-circle me-2"></i>Đã hoàn thành quét RFID';
    document.getElementById('startRfidScan').style.display = 'inline-block';
    document.getElementById('stopRfidScan').style.display = 'none';
}

// Mô phỏng thêm sản phẩm quét được bằng RFID
function simulateAddRfidProduct() {
    // Trong thực tế, dữ liệu này sẽ được lấy từ thiết bị RFID
    fetch(`api/inventory_checks.php?action=simulateRfidScan&checkId=${currentCheckId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Tải lại kết quả kiểm kê
                loadCheckResults(currentCheckId);
            }
        })
        .catch(error => {
            console.error('Lỗi khi mô phỏng quét RFID:', error);
        });
}

// Chỉnh sửa kết quả kiểm kê
function editCheckResult(resultId) {
    // Trong thực tế, sẽ hiển thị modal để chỉnh sửa
    const newQuantity = prompt('Nhập số lượng thực tế mới:');
    
    if (newQuantity === null) return;
    
    if (isNaN(newQuantity) || newQuantity < 0) {
        alert('Vui lòng nhập số lượng hợp lệ');
        return;
    }
    
    fetch('api/inventory_checks.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'updateCheckResult',
            resultId: resultId,
            actualQuantity: newQuantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCheckResults(currentCheckId); // Tải lại kết quả kiểm kê
        } else {
            alert(data.message || 'Không thể cập nhật kết quả kiểm kê');
        }
    })
    .catch(error => {
        console.error('Lỗi khi cập nhật kết quả:', error);
        alert('Đã xảy ra lỗi khi cập nhật kết quả kiểm kê');
    });
}

// Lọc danh sách phiếu kiểm kê
function filterInventoryChecks() {
    loadInventoryChecks(1);
}

// Lọc lịch sử kiểm kê
function filterCheckHistory() {
    const warehouseId = document.getElementById('historyWarehouseFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    
    // Gọi AJAX để lấy lịch sử kiểm kê
    fetch(`api/inventory_checks.php?action=getCheckHistory&warehouseId=${warehouseId}&dateFrom=${dateFrom}&dateTo=${dateTo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị lịch sử kiểm kê
                let html = '';
                
                if (data.history.length === 0) {
                    html = '<tr><td colspan="8" class="text-center">Không có dữ liệu lịch sử kiểm kê</td></tr>';
                } else {
                    data.history.forEach(item => {
                        html += `
                            <tr>
                                <td>${item.check_code}</td>
                                <td>${item.warehouse_name}</td>
                                <td>${item.zone_name || 'Toàn bộ kho'}</td>
                                <td>${new Date(item.completed_at).toLocaleString('vi-VN')}</td>
                                <td>${item.created_by_name}</td>
                                <td>${item.total_products}</td>
                                <td>${item.diff_products}</td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="viewCheckReport(${item.check_id})">
                                        <i class="fas fa-file-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                
                document.getElementById('checkHistoryList').innerHTML = html;
                
                // Tạo phân trang nếu cần
                if (data.totalPages > 1) {
                    createPagination('historyPagination', data.totalPages, data.currentPage, 'filterCheckHistory');
                } else {
                    document.getElementById('historyPagination').innerHTML = '';
                }
            } else {
                document.getElementById('checkHistoryList').innerHTML = `<tr><td colspan="8" class="text-center text-danger">${data.message || 'Không thể tải lịch sử kiểm kê'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải lịch sử kiểm kê:', error);
            document.getElementById('checkHistoryList').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
        });
}

// Xem báo cáo kiểm kê
function viewCheckReport(checkId) {
    fetch(`api/inventory_checks.php?action=getCheckReport&checkId=${checkId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const report = data.report;
                
                // Hiển thị thông tin báo cáo
                let html = `
                    <div class="mb-4">
                        <h6 class="mb-3">Thông tin phiếu kiểm kê</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Mã phiếu:</strong> ${report.check_code}</p>
                                <p><strong>Kho kiểm kê:</strong> ${report.warehouse_name}</p>
                                <p><strong>Khu vực:</strong> ${report.zone_name || 'Toàn bộ kho'}</p>
                                <p><strong>Loại kiểm kê:</strong> ${getCheckTypeText(report.check_type)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Thời gian bắt đầu:</strong> ${new Date(report.scheduled_date + ' ' + report.scheduled_time).toLocaleString('vi-VN')}</p>
                                <p><strong>Thời gian hoàn thành:</strong> ${new Date(report.completed_at).toLocaleString('vi-VN')}</p>
                                <p><strong>Người thực hiện:</strong> ${report.created_by_name}</p>
                                <p><strong>Ghi chú:</strong> ${report.notes || 'Không có'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Kết quả kiểm kê</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Mã SP</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Kệ/Vị trí</th>
                                    <th>SL hệ thống</th>
                                    <th>SL thực tế</th>
                                    <th>Chênh lệch</th>
                                    <th>Giá trị chênh lệch</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                if (report.results.length === 0) {
                    html += '<tr><td colspan="7" class="text-center">Không có dữ liệu kết quả kiểm kê</td></tr>';
                } else {
                    let totalDiff = 0;
                    let totalDiffValue = 0;
                    
                    report.results.forEach(result => {
                        const difference = result.actual_quantity - result.expected_quantity;
                        const differenceClass = difference === 0 ? 'text-success' : (difference > 0 ? 'text-primary' : 'text-danger');
                        const diffValue = difference * (result.price || 0);
                        
                        totalDiff += difference;
                        totalDiffValue += diffValue;
                        
                        html += `
                            <tr>
                                <td>${result.product_code}</td>
                                <td>${result.product_name}</td>
                                <td>${result.shelf_code || 'N/A'}</td>
                                <td>${result.expected_quantity}</td>
                                <td>${result.actual_quantity}</td>
                                <td class="${differenceClass}">${difference > 0 ? '+' : ''}${difference}</td>
                                <td class="${differenceClass}">${diffValue.toLocaleString('vi-VN')} đ</td>
                            </tr>
                        `;
                    });
                    
                    // Tổng cộng
                    html += `
                        <tr class="table-secondary fw-bold">
                            <td colspan="5" class="text-end">Tổng cộng:</td>
                            <td class="${totalDiff === 0 ? 'text-success' : (totalDiff > 0 ? 'text-primary' : 'text-danger')}">${totalDiff > 0 ? '+' : ''}${totalDiff}</td>
                            <td class="${totalDiffValue === 0 ? 'text-success' : (totalDiffValue > 0 ? 'text-primary' : 'text-danger')}">${totalDiffValue.toLocaleString('vi-VN')} đ</td>
                        </tr>
                    `;
                }
                
                html += `
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="mb-3">Thống kê chênh lệch</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h3 class="text-primary">${report.stats.total_products}</h3>
                                        <p class="mb-0">Tổng số sản phẩm</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h3 class="text-success">${report.stats.matched_products}</h3>
                                        <p class="mb-0">Số SP khớp đúng</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h3 class="text-danger">${report.stats.diff_products}</h3>
                                        <p class="mb-0">Số SP chênh lệch</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('reportDetailContent').innerHTML = html;
                document.getElementById('viewReportModalTitle').textContent = `Chi tiết báo cáo kiểm kê: ${report.check_code}`;
                document.getElementById('viewReportModal').classList.add('show');
            } else {
                alert(data.message || 'Không thể tải báo cáo kiểm kê');
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải báo cáo kiểm kê:', error);
            alert('Đã xảy ra lỗi khi tải báo cáo kiểm kê');
        });
}

// Đóng modal xem chi tiết báo cáo
function closeViewReportModal() {
    document.getElementById('viewReportModal').classList.remove('show');
}

// Chỉnh sửa phiếu kiểm kê
function editInventoryCheck(checkId) {
    // Gọi AJAX để lấy thông tin phiếu kiểm kê
    fetch(`api/inventory_checks.php?action=getCheckDetails&checkId=${checkId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const checkInfo = data.check;
                
                // Cập nhật tiêu đề modal
                document.getElementById('checkModalTitle').innerText = 'Chỉnh sửa phiếu kiểm kê';
                
                // Điền thông tin vào form
                document.getElementById('checkId').value = checkInfo.check_id;
                document.getElementById('checkCode').value = checkInfo.check_code;
                document.getElementById('warehouseId').value = checkInfo.warehouse_id;
                document.getElementById('checkType').value = checkInfo.check_type;
                document.getElementById('scheduledDate').value = checkInfo.scheduled_date;
                document.getElementById('scheduledTime').value = checkInfo.scheduled_time;
                document.getElementById('notes').value = checkInfo.notes || '';
                
                // Load khu vực và chọn khu vực nếu có
                loadZones();
                setTimeout(() => {
                    if (checkInfo.zone_id) {
                        document.getElementById('zoneId').value = checkInfo.zone_id;
                    }
                }, 500);
                
                // Hiển thị modal
                document.getElementById('checkModal').classList.add('show');
            } else {
                alert(data.message || 'Không thể tải thông tin phiếu kiểm kê');
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải thông tin phiếu kiểm kê:', error);
            alert('Đã xảy ra lỗi khi tải thông tin phiếu kiểm kê');
        });
}

// Hủy phiếu kiểm kê
function cancelInventoryCheck(checkId) {
    if (!confirm('Bạn có chắc chắn muốn hủy phiếu kiểm kê này?')) {
        return;
    }
    
    fetch('api/inventory_checks.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'cancelCheck',
            checkId: checkId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã hủy phiếu kiểm kê thành công');
            loadInventoryChecks(); // Tải lại danh sách phiếu kiểm kê
        } else {
            alert(data.message || 'Không thể hủy phiếu kiểm kê');
        }
    })
    .catch(error => {
        console.error('Lỗi khi hủy phiếu kiểm kê:', error);
        alert('Đã xảy ra lỗi khi hủy phiếu kiểm kê');
    });
}

// Tạo báo cáo chênh lệch
function generateDiscrepancyReport() {
    const warehouseId = document.getElementById('reportWarehouseFilter').value;
    const checkId = document.getElementById('reportCheckFilter').value;
    
    if (!checkId) {
        alert('Vui lòng chọn phiếu kiểm kê để tạo báo cáo');
        return;
    }
    
    // Hiển thị thông báo loading
    document.getElementById('reportResults').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Đang tạo báo cáo...</div>';
    
    // Gọi AJAX để lấy dữ liệu báo cáo
    fetch(`api/inventory_checks.php?action=generateDiscrepancyReport&checkId=${checkId}&warehouseId=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị báo cáo
                const report = data.report;
                
                let html = `
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Báo cáo chênh lệch kiểm kê</h5>
                            <div>
                                <button class="btn btn-sm btn-success me-2" onclick="exportReportToExcel(${checkId})">
                                    <i class="fas fa-file-excel me-1"></i>Xuất Excel
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="exportReportToPDF(${checkId})">
                                    <i class="fas fa-file-pdf me-1"></i>Xuất PDF
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="mb-3">Thông tin chung</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Mã phiếu kiểm kê:</strong> ${report.check_code}</p>
                                        <p><strong>Kho:</strong> ${report.warehouse_name}</p>
                                        <p><strong>Khu vực:</strong> ${report.zone_name || 'Toàn bộ kho'}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Thời gian kiểm kê:</strong> ${new Date(report.completed_at).toLocaleString('vi-VN')}</p>
                                        <p><strong>Người thực hiện:</strong> ${report.created_by_name}</p>
                                        <p><strong>Loại kiểm kê:</strong> ${getCheckTypeText(report.check_type)}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="mb-3">Thống kê chênh lệch</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h3 class="text-primary">${report.stats.total_products}</h3>
                                                <p class="mb-0">Tổng số sản phẩm</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h3 class="text-success">${report.stats.matched_products}</h3>
                                                <p class="mb-0">Số SP khớp đúng</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h3 class="text-danger">${report.stats.diff_products}</h3>
                                                <p class="mb-0">Số SP chênh lệch</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h3 class="text-info">${(report.stats.diff_products / report.stats.total_products * 100).toFixed(2)}%</h3>
                                                <p class="mb-0">Tỷ lệ chênh lệch</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h6 class="mb-3">Chi tiết chênh lệch</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Mã SP</th>
                                                <th>Tên sản phẩm</th>
                                                <th>Kệ/Vị trí</th>
                                                <th>SL hệ thống</th>
                                                <th>SL thực tế</th>
                                                <th>Chênh lệch</th>
                                                <th>Giá trị chênh lệch</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;
                
                if (report.discrepancies.length === 0) {
                    html += '<tr><td colspan="7" class="text-center">Không có sản phẩm chênh lệch</td></tr>';
                } else {
                    let totalDiffValue = 0;
                    
                    report.discrepancies.forEach(item => {
                        const difference = item.actual_quantity - item.expected_quantity;
                        const differenceClass = difference > 0 ? 'text-primary' : 'text-danger';
                        const diffValue = difference * (item.price || 0);
                        
                        totalDiffValue += diffValue;
                        
                        html += `
                            <tr>
                                <td>${item.product_code}</td>
                                <td>${item.product_name}</td>
                                <td>${item.shelf_code || 'N/A'}</td>
                                <td>${item.expected_quantity}</td>
                                <td>${item.actual_quantity}</td>
                                <td class="${differenceClass}">${difference > 0 ? '+' : ''}${difference}</td>
                                <td class="${differenceClass}">${diffValue.toLocaleString('vi-VN')} đ</td>
                            </tr>
                        `;
                    });
                    
                    // Tổng cộng
                    html += `
                        <tr class="table-secondary fw-bold">
                            <td colspan="6" class="text-end">Tổng giá trị chênh lệch:</td>
                            <td class="${totalDiffValue === 0 ? 'text-success' : (totalDiffValue > 0 ? 'text-primary' : 'text-danger')}">${totalDiffValue.toLocaleString('vi-VN')} đ</td>
                        </tr>
                    `;
                }
                
                html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('reportResults').innerHTML = html;
            } else {
                document.getElementById('reportResults').innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>${data.message || 'Không thể tạo báo cáo'}</div>`;
            }
        })
        .catch(error => {
            console.error('Lỗi khi tạo báo cáo:', error);
            document.getElementById('reportResults').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Đã xảy ra lỗi khi tạo báo cáo</div>';
        });
}

// Xuất báo cáo ra Excel
function exportReportToExcel(checkId) {
    if (!checkId) {
        checkId = currentCheckId;
    }
    
    if (!checkId) {
        alert('Không có ID phiếu kiểm kê để xuất báo cáo');
        return;
    }
    
    // Chuyển hướng tới API xuất Excel
    window.open(`api/inventory_checks.php?action=exportToExcel&checkId=${checkId}`, '_blank');
}

// Xuất báo cáo ra PDF
function exportReportToPDF(checkId) {
    if (!checkId) {
        checkId = currentCheckId;
    }
    
    if (!checkId) {
        alert('Không có ID phiếu kiểm kê để xuất báo cáo');
        return;
    }
    
    // Chuyển hướng tới API xuất PDF
    window.open(`api/inventory_checks.php?action=exportToPDF&checkId=${checkId}`, '_blank');
}

// Hàm hỗ trợ hiển thị text cho loại kiểm kê
function getCheckTypeText(checkType) {
    switch (checkType) {
        case 'AUTOMATIC_RFID':
            return 'Tự động (RFID)';
        case 'MANUAL_BARCODE':
            return 'Thủ công (Barcode)';
        case 'MIXED':
            return 'Kết hợp';
        default:
            return 'Không xác định';
    }
}
</script>