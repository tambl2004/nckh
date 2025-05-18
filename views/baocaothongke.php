<?php
// Kết nối đến cơ sở dữ liệu
include_once 'config/connect.php';
?>

<div class="function-container">
    <h4 class="page-title">Báo cáo và thống kê</h4>
    
    <!-- Tabs cho các loại báo cáo -->
    <ul class="nav nav-tabs mb-4" id="reportsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-tab-pane" type="button" role="tab" aria-controls="inventory-tab-pane" aria-selected="true">
                <i class="fas fa-boxes me-2"></i>Báo cáo tồn kho
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="imports-exports-tab" data-bs-toggle="tab" data-bs-target="#imports-exports-tab-pane" type="button" role="tab" aria-controls="imports-exports-tab-pane" aria-selected="false">
                <i class="fas fa-exchange-alt me-2"></i>Thống kê nhập/xuất
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="warehouse-analysis-tab" data-bs-toggle="tab" data-bs-target="#warehouse-analysis-tab-pane" type="button" role="tab" aria-controls="warehouse-analysis-tab-pane" aria-selected="false">
                <i class="fas fa-warehouse me-2"></i>Phân tích kho
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="expiry-tracking-tab" data-bs-toggle="tab" data-bs-target="#expiry-tracking-tab-pane" type="button" role="tab" aria-controls="expiry-tracking-tab-pane" aria-selected="false">
                <i class="fas fa-calendar-times me-2"></i>Theo dõi hạn sử dụng
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="reportsTabsContent">
        <!-- Tab Báo cáo tồn kho -->
        <div class="tab-pane fade show active" id="inventory-tab-pane" role="tabpanel" aria-labelledby="inventory-tab" tabindex="0">
            <!-- Bộ lọc báo cáo tồn kho -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <select class="form-select" id="inventoryWarehouseFilter">
                        <option value="">Tất cả kho</option>
                        <?php
                        $sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row["warehouse_id"] . "'>" . $row["warehouse_name"] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="inventoryCategoryFilter">
                        <option value="">Tất cả danh mục</option>
                        <?php
                        $sql = "SELECT category_id, category_name FROM categories ORDER BY category_name";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row["category_id"] . "'>" . $row["category_name"] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="inventoryStockLevelFilter">
                        <option value="">Tất cả mức tồn kho</option>
                        <option value="Thấp">Thấp</option>
                        <option value="Trung bình">Trung bình</option>
                        <option value="Cao">Cao</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" id="generateInventoryReport">
                        <i class="fas fa-sync-alt me-2"></i>Tạo báo cáo
                    </button>
                </div>
            </div>
            
            <!-- Thống kê tổng quan tồn kho -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="totalProducts">0</h2>
                            <p class="mb-0">Tổng sản phẩm</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="totalQuantity">0</h2>
                            <p class="mb-0">Tổng số lượng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="totalValue">0 ₫</h2>
                            <p class="mb-0">Tổng giá trị</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="lowStockProducts">0</h2>
                            <p class="mb-0">Sản phẩm tồn thấp</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Biểu đồ phân bổ tồn kho -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Phân bổ giá trị tồn kho theo danh mục</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="inventoryCategoryChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Phân bổ tồn kho theo kho</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="inventoryWarehouseChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bảng chi tiết tồn kho -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Chi tiết tồn kho</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-success me-2" id="exportInventoryExcel">
                            <i class="fas fa-file-excel me-1"></i>Xuất Excel
                        </button>
                        <button class="btn btn-sm btn-outline-danger" id="exportInventoryPDF">
                            <i class="fas fa-file-pdf me-1"></i>Xuất PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>Mã SP</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Kho</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Tổng giá trị</th>
                                    <th>Mức tồn kho</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                                <!-- Dữ liệu sẽ được load bằng AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Thống kê nhập/xuất -->
        <div class="tab-pane fade" id="imports-exports-tab-pane" role="tabpanel" aria-labelledby="imports-exports-tab" tabindex="0">
            <!-- Bộ lọc thống kê nhập/xuất -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <select class="form-select" id="ioWarehouseFilter">
                        <option value="">Tất cả kho</option>
                        <?php
                        $sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row["warehouse_id"] . "'>" . $row["warehouse_name"] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="ioDateRangeFilter">
                        <option value="7">7 ngày qua</option>
                        <option value="30">30 ngày qua</option>
                        <option value="90">90 ngày qua</option>
                        <option value="365">1 năm qua</option>
                        <option value="custom">Tùy chỉnh</option>
                    </select>
                </div>
                <div class="col-md-3 date-range-container" style="display: none;">
                    <div class="input-group">
                        <input type="date" class="form-control" id="ioStartDate">
                        <span class="input-group-text">đến</span>
                        <input type="date" class="form-control" id="ioEndDate">
                    </div>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" id="generateIOReport">
                        <i class="fas fa-sync-alt me-2"></i>Tạo báo cáo
                    </button>
                </div>
            </div>
            
            <!-- Thống kê tổng quan nhập/xuất -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="totalImports">0</h2>
                            <p class="mb-0">Tổng phiếu nhập</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="totalImportValue">0 ₫</h2>
                            <p class="mb-0">Tổng giá trị nhập</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="totalExports">0</h2>
                            <p class="mb-0">Tổng phiếu xuất</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="totalExportValue">0 ₫</h2>
                            <p class="mb-0">Tổng giá trị xuất</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Biểu đồ nhập/xuất theo thời gian -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Biểu đồ nhập/xuất theo thời gian</h5>
                </div>
                <div class="card-body">
                    <canvas id="ioTimeChart" height="300"></canvas>
                </div>
            </div>
            
            <!-- Tabs nhập/xuất chi tiết -->
            <ul class="nav nav-tabs mb-3" id="ioDetailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="imports-tab" data-bs-toggle="tab" data-bs-target="#imports-tab-pane" type="button" role="tab" aria-controls="imports-tab-pane" aria-selected="true">Chi tiết nhập kho</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="exports-tab" data-bs-toggle="tab" data-bs-target="#exports-tab-pane" type="button" role="tab" aria-controls="exports-tab-pane" aria-selected="false">Chi tiết xuất kho</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="suppliers-analysis-tab" data-bs-toggle="tab" data-bs-target="#suppliers-analysis-tab-pane" type="button" role="tab" aria-controls="suppliers-analysis-tab-pane" aria-selected="false">Phân tích nhà cung cấp</button>
                </li>
            </ul>
            
            <div class="tab-content" id="ioDetailTabsContent">
                <!-- Tab chi tiết nhập kho -->
                <div class="tab-pane fade show active" id="imports-tab-pane" role="tabpanel" aria-labelledby="imports-tab" tabindex="0">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Chi tiết nhập kho</h5>
                            <div>
                                <button class="btn btn-sm btn-outline-success me-2" id="exportImportsExcel">
                                    <i class="fas fa-file-excel me-1"></i>Xuất Excel
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="importsTable">
                                    <thead>
                                        <tr>
                                            <th>Ngày nhập</th>
                                            <th>Kho</th>
                                            <th>Nhà cung cấp</th>
                                            <th>Số phiếu nhập</th>
                                            <th>Tổng số lượng</th>
                                            <th>Tổng giá trị</th>
                                        </tr>
                                    </thead>
                                    <tbody id="importsTableBody">
                                        <!-- Dữ liệu sẽ được load bằng AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab chi tiết xuất kho -->
                <div class="tab-pane fade" id="exports-tab-pane" role="tabpanel" aria-labelledby="exports-tab" tabindex="0">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Chi tiết xuất kho</h5>
                            <div>
                                <button class="btn btn-sm btn-outline-success me-2" id="exportExportsExcel">
                                    <i class="fas fa-file-excel me-1"></i>Xuất Excel
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="exportsTable">
                                    <thead>
                                        <tr>
                                            <th>Ngày xuất</th>
                                            <th>Kho</th>
                                            <th>Số phiếu xuất</th>
                                            <th>Tổng số lượng</th>
                                            <th>Tổng giá trị</th>
                                        </tr>
                                    </thead>
                                    <tbody id="exportsTableBody">
                                        <!-- Dữ liệu sẽ được load bằng AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab phân tích nhà cung cấp -->
                <div class="tab-pane fade" id="suppliers-analysis-tab-pane" role="tabpanel" aria-labelledby="suppliers-analysis-tab" tabindex="0">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Top 5 nhà cung cấp theo giá trị</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="supplierValueChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Top 5 nhà cung cấp theo số lượng</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="supplierQuantityChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Chi tiết nhà cung cấp</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="suppliersTable">
                                    <thead>
                                        <tr>
                                            <th>Nhà cung cấp</th>
                                            <th>Số phiếu nhập</th>
                                            <th>Tổng số lượng</th>
                                            <th>Tổng giá trị</th>
                                            <th>% Tỷ trọng</th>
                                        </tr>
                                    </thead>
                                    <tbody id="suppliersTableBody">
                                        <!-- Dữ liệu sẽ được load bằng AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Phân tích kho -->
        <div class="tab-pane fade" id="warehouse-analysis-tab-pane" role="tabpanel" aria-labelledby="warehouse-analysis-tab" tabindex="0">
            <!-- Bộ lọc phân tích kho -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <select class="form-select" id="warehouseFilter">
                        <option value="">Tất cả kho</option>
                        <?php
                        $sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row["warehouse_id"] . "'>" . $row["warehouse_name"] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-primary w-100" id="generateWarehouseReport">
                        <i class="fas fa-sync-alt me-2"></i>Tạo báo cáo
                    </button>
                </div>
            </div>
            
            <!-- Thống kê tổng quan kho -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="totalShelves">0</h2>
                            <p class="mb-0">Tổng số kệ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="usedCapacity">0%</h2>
                            <p class="mb-0">Công suất sử dụng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="highUtilizationShelves">0</h2>
                            <p class="mb-0">Kệ sử dụng cao</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="lowUtilizationShelves">0</h2>
                            <p class="mb-0">Kệ sử dụng thấp</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Biểu đồ sử dụng kho -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Mức độ sử dụng kệ</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="shelfUtilizationChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Dự báo nhu cầu không gian kho</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="warehouseCapacityForecastChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bảng chi tiết kệ -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Chi tiết sử dụng kệ</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-success me-2" id="exportShelvesExcel">
                            <i class="fas fa-file-excel me-1"></i>Xuất Excel
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="shelvesTable">
                            <thead>
                                <tr>
                                    <th>Mã kệ</th>
                                    <th>Khu vực</th>
                                    <th>Kho</th>
                                    <th>Công suất tối đa</th>
                                    <th>Công suất đã dùng</th>
                                    <th>% Sử dụng</th>
                                    <th>Mức độ sử dụng</th>
                                </tr>
                            </thead>
                            <tbody id="shelvesTableBody">
                                <!-- Dữ liệu sẽ được load bằng AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Theo dõi hạn sử dụng -->
        <div class="tab-pane fade" id="expiry-tracking-tab-pane" role="tabpanel" aria-labelledby="expiry-tracking-tab" tabindex="0">
            <!-- Bộ lọc theo dõi hạn sử dụng -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <select class="form-select" id="expiryWarehouseFilter">
                        <option value="">Tất cả kho</option>
                        <?php
                        $sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row["warehouse_id"] . "'>" . $row["warehouse_name"] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="expiryDaysFilter">
                        <option value="30">30 ngày tới</option>
                        <option value="60">60 ngày tới</option>
                        <option value="90">90 ngày tới</option>
                        <option value="180">180 ngày tới</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" id="generateExpiryReport">
                        <i class="fas fa-sync-alt me-2"></i>Tạo báo cáo
                    </button>
                </div>
            </div>
            
            <!-- Thống kê tổng quan hạn sử dụng -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 id="totalExpiringProducts">0</h2>
                            <p class="mb-0">Tổng SP gần hết hạn</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h2 id="urgent30DaysProducts">0</h2>
                            <p class="mb-0">Sắp hết hạn (dưới 30 ngày)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h2 id="urgent7DaysProducts">0</h2>
                            <p class="mb-0">Sắp hết hạn (dưới 7 ngày)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Biểu đồ phân bổ sản phẩm gần hết hạn -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Phân bổ sản phẩm sắp hết hạn</h5>
                </div>
                <div class="card-body">
                    <canvas id="expiryDistributionChart" height="300"></canvas>
                </div>
            </div>
            
            <!-- Bảng chi tiết sản phẩm gần hết hạn -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Chi tiết sản phẩm gần hết hạn</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-success me-2" id="exportExpiryExcel">
                            <i class="fas fa-file-excel me-1"></i>Xuất Excel
                        </button>
                        <button class="btn btn-sm btn-outline-danger" id="exportExpiryPDF">
                            <i class="fas fa-file-pdf me-1"></i>Xuất PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="expiryTable">
                            <thead>
                                <tr>
                                    <th>Mã SP</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Kho</th>
                                    <th>Vị trí kệ</th>
                                    <th>Lô hàng</th>
                                    <th>Ngày hết hạn</th>
                                    <th>Còn lại (ngày)</th>
                                    <th>Số lượng</th>
                                </tr>
                            </thead>
                            <tbody id="expiryTableBody">
                                <!-- Dữ liệu sẽ được load bằng AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo thư viện Chart.js
    let inventoryCategoryChart = null;
    let inventoryWarehouseChart = null;
    let ioTimeChart = null;
    let supplierValueChart = null;
    let supplierQuantityChart = null;
    let shelfUtilizationChart = null;
    let warehouseCapacityForecastChart = null;
    let expiryDistributionChart = null;
    
    // Báo cáo tồn kho
    document.getElementById('generateInventoryReport').addEventListener('click', function() {
        generateInventoryReport();
    });
    
    // Hiển thị input tùy chỉnh ngày khi chọn tùy chỉnh
    document.getElementById('ioDateRangeFilter').addEventListener('change', function() {
        if (this.value === 'custom') {
            document.querySelector('.date-range-container').style.display = 'block';
        } else {
            document.querySelector('.date-range-container').style.display = 'none';
        }
    });
    
    // Thống kê nhập/xuất
    document.getElementById('generateIOReport').addEventListener('click', function() {
        generateIOReport();
    });
    
    // Phân tích kho
    document.getElementById('generateWarehouseReport').addEventListener('click', function() {
        generateWarehouseAnalysis();
    });
    
    // Theo dõi hạn sử dụng
    document.getElementById('generateExpiryReport').addEventListener('click', function() {
        generateExpiryReport();
    });
    
    // Xuất Excel báo cáo tồn kho
    document.getElementById('exportInventoryExcel').addEventListener('click', function() {
        exportToExcel('inventory');
    });
    
    // Xuất PDF báo cáo tồn kho
    document.getElementById('exportInventoryPDF').addEventListener('click', function() {
        exportToPDF('inventory');
    });
    
    // Xuất Excel báo cáo nhập kho
    document.getElementById('exportImportsExcel').addEventListener('click', function() {
        exportToExcel('imports');
    });
    
    // Xuất Excel báo cáo xuất kho
    document.getElementById('exportExportsExcel').addEventListener('click', function() {
        exportToExcel('exports');
    });
    
    // Xuất Excel báo cáo kệ
    document.getElementById('exportShelvesExcel').addEventListener('click', function() {
        exportToExcel('shelves');
    });
    
    // Xuất Excel báo cáo hạn sử dụng
    document.getElementById('exportExpiryExcel').addEventListener('click', function() {
        exportToExcel('expiry');
    });
    
    // Xuất PDF báo cáo hạn sử dụng
    document.getElementById('exportExpiryPDF').addEventListener('click', function() {
        exportToPDF('expiry');
    });
    
    // Tải báo cáo tồn kho mặc định khi trang được tải
    generateInventoryReport();
    
    // Hàm tạo báo cáo tồn kho
    function generateInventoryReport() {
        const warehouseId = document.getElementById('inventoryWarehouseFilter').value;
        const categoryId = document.getElementById('inventoryCategoryFilter').value;
        const stockLevel = document.getElementById('inventoryStockLevelFilter').value;
        
        // Hiển thị loading
        document.getElementById('inventoryTableBody').innerHTML = '<tr><td colspan="8" class="text-center">Đang tải dữ liệu...</td></tr>';
        
        // Gọi API để lấy dữ liệu
        fetch(`ajax/inventory_report.php?warehouseId=${warehouseId}&categoryId=${categoryId}&stockLevel=${stockLevel}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật thống kê tổng quan
                    document.getElementById('totalProducts').textContent = data.stats.totalProducts;
                    document.getElementById('totalQuantity').textContent = data.stats.totalQuantity;
                    document.getElementById('totalValue').textContent = formatCurrency(data.stats.totalValue);
                    document.getElementById('lowStockProducts').textContent = data.stats.lowStockProducts;
                    
                    // Hiển thị dữ liệu trong bảng
                    let tableHtml = '';
                    data.inventory.forEach(item => {
                        let stockLevelClass = '';
                        if (item.stock_level === 'Thấp') {
                            stockLevelClass = 'text-danger';
                        } else if (item.stock_level === 'Trung bình') {
                            stockLevelClass = 'text-warning';
                        } else {
                            stockLevelClass = 'text-success';
                        }
                        
                        tableHtml += `
                            <tr>
                                <td>${item.product_code}</td>
                                <td>${item.product_name}</td>
                                <td>${item.category_name}</td>
                                <td>${item.warehouse_name}</td>
                                <td>${item.quantity}</td>
                                <td>${formatCurrency(item.price)}</td>
                                <td>${formatCurrency(item.total_value)}</td>
                                <td><span class="${stockLevelClass}">${item.stock_level}</span></td>
                            </tr>
                        `;
                    });
                    
                    if (tableHtml === '') {
                        tableHtml = '<tr><td colspan="8" class="text-center">Không có dữ liệu</td></tr>';
                    }
                    document.getElementById('inventoryTableBody').innerHTML = tableHtml;
                    
                    // Vẽ biểu đồ phân bổ theo danh mục
                    if (inventoryCategoryChart) {
                        inventoryCategoryChart.destroy();
                    }
                    
                    const categoryLabels = [];
                    const categoryValues = [];
                    const categoryColors = [];
                    
                    // Tạo dữ liệu nhóm theo danh mục
                    const categoryData = {};
                    data.inventory.forEach(item => {
                        if (!categoryData[item.category_name]) {
                            categoryData[item.category_name] = 0;
                        }
                        categoryData[item.category_name] += parseFloat(item.total_value);
                    });
                    
                    // Tạo mảng dữ liệu cho biểu đồ
                    for (const category in categoryData) {
                        categoryLabels.push(category);
                        categoryValues.push(categoryData[category]);
                        categoryColors.push(getRandomColor());
                    }
                    
                    // Vẽ biểu đồ phân bổ theo danh mục
                    const ctxCategory = document.getElementById('inventoryCategoryChart').getContext('2d');
                    inventoryCategoryChart = new Chart(ctxCategory, {
                        type: 'pie',
                        data: {
                            labels: categoryLabels,
                            datasets: [{
                                data: categoryValues,
                                backgroundColor: categoryColors
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'right'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.raw;
                                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(2);
                                            return `${context.label}: ${formatCurrency(value)} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    
                    // Vẽ biểu đồ phân bổ theo kho
                    if (inventoryWarehouseChart) {
                        inventoryWarehouseChart.destroy();
                    }
                    
                    const warehouseLabels = [];
                    const warehouseValues = [];
                    const warehouseColors = [];
                    
                    // Tạo dữ liệu nhóm theo kho
                    const warehouseData = {};
                    data.inventory.forEach(item => {
                        if (!warehouseData[item.warehouse_name]) {
                            warehouseData[item.warehouse_name] = 0;
                        }
                        warehouseData[item.warehouse_name] += parseFloat(item.total_value);
                    });
                    
                    // Tạo mảng dữ liệu cho biểu đồ
                    for (const warehouse in warehouseData) {
                        warehouseLabels.push(warehouse);
                        warehouseValues.push(warehouseData[warehouse]);
                        warehouseColors.push(getRandomColor());
                    }
                    
                    // Vẽ biểu đồ phân bổ theo kho
                    const ctxWarehouse = document.getElementById('inventoryWarehouseChart').getContext('2d');
                    inventoryWarehouseChart = new Chart(ctxWarehouse, {
                        type: 'doughnut',
                        data: {
                            labels: warehouseLabels,
                            datasets: [{
                                data: warehouseValues,
                                backgroundColor: warehouseColors
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'right'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.raw;
                                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(2);
                                            return `${context.label}: ${formatCurrency(value)} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    document.getElementById('inventoryTableBody').innerHTML = '<tr><td colspan="8" class="text-center">Không có dữ liệu</td></tr>';
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải báo cáo tồn kho:', error);
                document.getElementById('inventoryTableBody').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
            });
    }
    
    // Hàm tạo báo cáo nhập/xuất
    function generateIOReport() {
        const warehouseId = document.getElementById('ioWarehouseFilter').value;
        const dateRange = document.getElementById('ioDateRangeFilter').value;
        let startDate = '';
        let endDate = '';
        
        if (dateRange === 'custom') {
            startDate = document.getElementById('ioStartDate').value;
            endDate = document.getElementById('ioEndDate').value;
            
            if (!startDate || !endDate) {
                alert('Vui lòng chọn ngày bắt đầu và ngày kết thúc');
                return;
            }
        }
        
        // Hiển thị loading
        document.getElementById('importsTableBody').innerHTML = '<tr><td colspan="6" class="text-center">Đang tải dữ liệu...</td></tr>';
        document.getElementById('exportsTableBody').innerHTML = '<tr><td colspan="5" class="text-center">Đang tải dữ liệu...</td></tr>';
        
        // Gọi API để lấy dữ liệu
        fetch(`ajax/io_report.php?warehouseId=${warehouseId}&dateRange=${dateRange}&startDate=${startDate}&endDate=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật thống kê tổng quan
                    document.getElementById('totalImports').textContent = data.stats.totalImports;
                    document.getElementById('totalImportValue').textContent = formatCurrency(data.stats.totalImportValue);
                    document.getElementById('totalExports').textContent = data.stats.totalExports;
                    document.getElementById('totalExportValue').textContent = formatCurrency(data.stats.totalExportValue);
                    
                    // Hiển thị dữ liệu nhập kho
                    let importsTableHtml = '';
                    data.imports.forEach(item => {
                        importsTableHtml += `
                            <tr>
                                <td>${formatDate(item.import_date)}</td>
                                <td>${item.warehouse_name}</td>
                                <td>${item.supplier_name}</td>
                                <td>${item.total_imports}</td>
                                <td>${item.total_quantity}</td>
                                <td>${formatCurrency(item.total_amount)}</td>
                            </tr>
                        `;
                    });
                    
                    if (importsTableHtml === '') {
                        importsTableHtml = '<tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>';
                    }
                    document.getElementById('importsTableBody').innerHTML = importsTableHtml;
                    
                    // Hiển thị dữ liệu xuất kho
                    let exportsTableHtml = '';
                    data.exports.forEach(item => {
                        exportsTableHtml += `
                            <tr>
                                <td>${formatDate(item.export_date)}</td>
                                <td>${item.warehouse_name}</td>
                                <td>${item.total_exports}</td>
                                <td>${item.total_quantity}</td>
                                <td>${formatCurrency(item.total_amount)}</td>
                            </tr>
                        `;
                    });
                    
                    if (exportsTableHtml === '') {
                        exportsTableHtml = '<tr><td colspan="5" class="text-center">Không có dữ liệu</td></tr>';
                    }
                    document.getElementById('exportsTableBody').innerHTML = exportsTableHtml;
                    
                    // Phân tích nhà cung cấp
                    let suppliersTableHtml = '';
                    
                    data.suppliers.forEach(item => {
                        suppliersTableHtml += `
                            <tr>
                                <td>${item.supplier_name}</td>
                                <td>${item.total_imports}</td>
                                <td>${item.total_quantity}</td>
                                <td>${formatCurrency(item.total_amount)}</td>
                                <td>${item.percentage}%</td>
                            </tr>
                        `;
                    });
                    
                    if (suppliersTableHtml === '') {
                        suppliersTableHtml = '<tr><td colspan="5" class="text-center">Không có dữ liệu</td></tr>';
                    }
                    document.getElementById('suppliersTableBody').innerHTML = suppliersTableHtml;
                    
                    // Vẽ biểu đồ nhập/xuất theo thời gian
                    if (ioTimeChart) {
                        ioTimeChart.destroy();
                    }
                    
                    const ioTimeLabels = [];
                    const importValues = [];
                    const exportValues = [];
                    
                    // Tạo nhãn và dữ liệu cho biểu đồ
                    data.timeSeriesData.forEach(item => {
                        ioTimeLabels.push(formatDate(item.date));
                        importValues.push(item.import_value);
                        exportValues.push(item.export_value);
                    });
                    
                    // Vẽ biểu đồ nhập/xuất theo thời gian
                    const ctxIOTime = document.getElementById('ioTimeChart').getContext('2d');
                    ioTimeChart = new Chart(ctxIOTime, {
                        type: 'line',
                        data: {
                            labels: ioTimeLabels,
                            datasets: [
                                {
                                    label: 'Giá trị nhập kho',
                                    data: importValues,
                                    borderColor: '#4e73df',
                                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Giá trị xuất kho',
                                    data: exportValues,
                                    borderColor: '#e74a3b',
                                    backgroundColor: 'rgba(231, 74, 59, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    ticks: {
                                        callback: function(value) {
                                            return formatCurrency(value);
                                        }
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.dataset.label || '';
                                            const value = context.raw;
                                            return `${label}: ${formatCurrency(value)}`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    
                    // Vẽ biểu đồ top 5 nhà cung cấp theo giá trị
                    if (supplierValueChart) {
                        supplierValueChart.destroy();
                    }
                    
                    const top5Suppliers = data.suppliers.slice(0, 5);
                    const supplierLabels = top5Suppliers.map(item => item.supplier_name);
                    const supplierValues = top5Suppliers.map(item => parseFloat(item.total_amount));
                    const supplierValueColors = getColorArray(5);
                    
                    const ctxSupplierValue = document.getElementById('supplierValueChart').getContext('2d');
                    supplierValueChart = new Chart(ctxSupplierValue, {
                        type: 'bar',
                        data: {
                            labels: supplierLabels,
                            datasets: [{
                                label: 'Giá trị nhập kho',
                                data: supplierValues,
                                backgroundColor: supplierValueColors
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    ticks: {
                                        callback: function(value) {
                                            return formatCurrency(value);
                                        }
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.raw;
                                            return `Giá trị: ${formatCurrency(value)}`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    
                    // Vẽ biểu đồ top 5 nhà cung cấp theo số lượng
                    if (supplierQuantityChart) {
                        supplierQuantityChart.destroy();
                    }
                    
                    const supplierQuantities = top5Suppliers.map(item => parseInt(item.total_quantity));
                    const supplierQuantityColors = getColorArray(5, true);
                    
                    const ctxSupplierQuantity = document.getElementById('supplierQuantityChart').getContext('2d');
                    supplierQuantityChart = new Chart(ctxSupplierQuantity, {
                        type: 'bar',
                        data: {
                            labels: supplierLabels,
                            datasets: [{
                                label: 'Số lượng nhập kho',
                                data: supplierQuantities,
                                backgroundColor: supplierQuantityColors
                            }]
                        },
                        options: {
                            responsive: true
                        }
                    });
                } else {
                    document.getElementById('importsTableBody').innerHTML = '<tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>';
                    document.getElementById('exportsTableBody').innerHTML = '<tr><td colspan="5" class="text-center">Không có dữ liệu</td></tr>';
                    document.getElementById('suppliersTableBody').innerHTML = '<tr><td colspan="5" class="text-center">Không có dữ liệu</td></tr>';
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải báo cáo nhập/xuất:', error);
                document.getElementById('importsTableBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
                document.getElementById('exportsTableBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
                document.getElementById('suppliersTableBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
            });
    }
    
    // Hàm phân tích kho
    function generateWarehouseAnalysis() {
        const warehouseId = document.getElementById('warehouseFilter').value;
        
        // Hiển thị loading
        document.getElementById('shelvesTableBody').innerHTML = '<tr><td colspan="7" class="text-center">Đang tải dữ liệu...</td></tr>';
        
        // Gọi API để lấy dữ liệu
        fetch(`ajax/shelf_report.php?warehouse_id=${warehouseId}`)
            .then(response => response.json())
            .then(data => {
                // Tính toán thống kê tổng quan
                let totalShelves = data.length;
                let usedCapacity = 0;
                let totalCapacity = 0;
                let highUtilizationShelves = 0;
                let lowUtilizationShelves = 0;
                
                data.forEach(item => {
                    let usedCap = parseFloat(item.used_capacity);
                    let maxCap = parseFloat(item.max_capacity);
                    totalCapacity += maxCap;
                    usedCapacity += usedCap;
                    
                    if (item.utilization_level === 'Cao') {
                        highUtilizationShelves++;
                    }
                    if (item.utilization_level === 'Thấp') {
                        lowUtilizationShelves++;
                    }
                });
                
                let utilizationPercentage = totalCapacity > 0 ? (usedCapacity / totalCapacity * 100).toFixed(2) : 0;
                
                // Cập nhật thống kê tổng quan
                document.getElementById('totalShelves').textContent = totalShelves;
                document.getElementById('usedCapacity').textContent = utilizationPercentage + '%';
                document.getElementById('highUtilizationShelves').textContent = highUtilizationShelves;
                document.getElementById('lowUtilizationShelves').textContent = lowUtilizationShelves;
                
                // Hiển thị dữ liệu trong bảng
                let tableHtml = '';
                data.forEach(item => {
                    let utilizationClass = '';
                    if (item.utilization_level === 'Thấp') {
                        utilizationClass = 'text-success';
                    } else if (item.utilization_level === 'Trung bình') {
                        utilizationClass = 'text-warning';
                    } else {
                        utilizationClass = 'text-danger';
                    }
                    
                    tableHtml += `
                        <tr>
                            <td>${item.shelf_code}</td>
                            <td>${item.zone_code}</td>
                            <td>${item.warehouse_name}</td>
                            <td>${parseFloat(item.max_capacity).toFixed(2)}</td>
                            <td>${parseFloat(item.used_capacity).toFixed(2)}</td>
                            <td>${parseFloat(item.utilization_percentage).toFixed(2)}%</td>
                            <td class="${utilizationClass}">${item.utilization_level}</td>
                        </tr>
                    `;
                });
                
                if (tableHtml === '') {
                    tableHtml = '<tr><td colspan="7" class="text-center">Không có dữ liệu</td></tr>';
                }
                document.getElementById('shelvesTableBody').innerHTML = tableHtml;
                
                // Vẽ biểu đồ mức độ sử dụng kệ
                if (shelfUtilizationChart) {
                    shelfUtilizationChart.destroy();
                }
                
                const utilizationLevels = ['Thấp', 'Trung bình', 'Cao'];
                const utilizationCounts = [lowUtilizationShelves, totalShelves - lowUtilizationShelves - highUtilizationShelves, highUtilizationShelves];
                const utilizationColors = ['#1cc88a', '#f6c23e', '#e74a3b'];
                
                const ctxShelfUtilization = document.getElementById('shelfUtilizationChart').getContext('2d');
                shelfUtilizationChart = new Chart(ctxShelfUtilization, {
                    type: 'pie',
                    data: {
                        labels: utilizationLevels,
                        datasets: [{
                            data: utilizationCounts,
                            backgroundColor: utilizationColors
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(2);
                                        return `${context.label}: ${value} kệ (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Vẽ biểu đồ dự báo nhu cầu không gian kho
                if (warehouseCapacityForecastChart) {
                    warehouseCapacityForecastChart.destroy();
                }
                
                // Tạo dữ liệu giả định cho biểu đồ dự báo
                // Trong thực tế, cần phải tính toán dự báo dựa trên dữ liệu lịch sử
                const forecastMonths = ['Hiện tại', '1 tháng', '2 tháng', '3 tháng', '4 tháng', '5 tháng', '6 tháng'];
                
                // Giả định tốc độ tăng trưởng 5% mỗi tháng
                let forecastData = [utilizationPercentage];
                let currentUtil = parseFloat(utilizationPercentage);
                
                for (let i = 1; i < 7; i++) {
                    currentUtil = Math.min(currentUtil * 1.05, 100); // Tăng 5% mỗi tháng, tối đa 100%
                    forecastData.push(currentUtil.toFixed(2));
                }
                
                const ctxForecast = document.getElementById('warehouseCapacityForecastChart').getContext('2d');
                warehouseCapacityForecastChart = new Chart(ctxForecast, {
                    type: 'line',
                    data: {
                        labels: forecastMonths,
                        datasets: [{
                            label: 'Dự báo công suất sử dụng (%)',
                            data: forecastData,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                min: 0,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Lỗi khi tải phân tích kho:', error);
                document.getElementById('shelvesTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
            });
    }
    
    // Hàm tạo báo cáo hạn sử dụng
    function generateExpiryReport() {
        const warehouseId = document.getElementById('expiryWarehouseFilter').value;
        const daysThreshold = document.getElementById('expiryDaysFilter').value;
        
        // Hiển thị loading
        document.getElementById('expiryTableBody').innerHTML = '<tr><td colspan="8" class="text-center">Đang tải dữ liệu...</td></tr>';
        
        // Gọi API để lấy dữ liệu
        fetch(`ajax/expiry_report.php?warehouse_id=${warehouseId}&days_threshold=${daysThreshold}`)
            .then(response => response.json())
            .then(data => {
                // Tính toán thống kê
                let totalProducts = data.length;
                let urgent30Days = 0;
                let urgent7Days = 0;
                
                data.forEach(item => {
                    if (parseInt(item.days_until_expiry) <= 30) {
                        urgent30Days++;
                    }
                    if (parseInt(item.days_until_expiry) <= 7) {
                        urgent7Days++;
                    }
                });
                
                // Cập nhật thống kê tổng quan
                document.getElementById('totalExpiringProducts').textContent = totalProducts;
                document.getElementById('urgent30DaysProducts').textContent = urgent30Days;
                document.getElementById('urgent7DaysProducts').textContent = urgent7Days;
                
                // Hiển thị dữ liệu trong bảng
                let tableHtml = '';
                data.forEach(item => {
                    let daysClass = '';
                    let daysRemaining = parseInt(item.days_until_expiry);
                    
                    if (daysRemaining <= 7) {
                        daysClass = 'text-danger fw-bold';
                    } else if (daysRemaining <= 30) {
                        daysClass = 'text-warning fw-bold';
                    } else {
                        daysClass = 'text-info';
                    }
                    
                    tableHtml += `
                        <tr>
                            <td>${item.product_code}</td>
                            <td>${item.product_name}</td>
                            <td>${item.warehouse_name}</td>
                            <td>${item.shelf_code}</td>
                            <td>${item.batch_number || 'N/A'}</td>
                            <td>${formatDate(item.expiry_date)}</td>
                            <td class="${daysClass}">${item.days_until_expiry}</td>
                            <td>${item.quantity}</td>
                        </tr>
                    `;
                });
                
                if (tableHtml === '') {
                    tableHtml = '<tr><td colspan="8" class="text-center">Không có sản phẩm gần hết hạn trong khoảng thời gian đã chọn</td></tr>';
                }
                document.getElementById('expiryTableBody').innerHTML = tableHtml;
                
                // Vẽ biểu đồ phân bổ sản phẩm gần hết hạn
                if (expiryDistributionChart) {
                    expiryDistributionChart.destroy();
                }
                
                // Nhóm sản phẩm theo ngày còn lại
                const expiryGroups = {
                    '<= 7 ngày': 0,
                    '8-15 ngày': 0,
                    '16-30 ngày': 0,
                    '31-60 ngày': 0,
                    '61-90 ngày': 0,
                    '> 90 ngày': 0
                };
                
                data.forEach(item => {
                    const days = parseInt(item.days_until_expiry);
                    const quantity = parseInt(item.quantity);
                    
                    if (days <= 7) {
                        expiryGroups['<= 7 ngày'] += quantity;
                    } else if (days <= 15) {
                        expiryGroups['8-15 ngày'] += quantity;
                    } else if (days <= 30) {
                        expiryGroups['16-30 ngày'] += quantity;
                    } else if (days <= 60) {
                        expiryGroups['31-60 ngày'] += quantity;
                    } else if (days <= 90) {
                        expiryGroups['61-90 ngày'] += quantity;
                    } else {
                        expiryGroups['> 90 ngày'] += quantity;
                    }
                });
                
                const expiryLabels = Object.keys(expiryGroups);
                const expiryValues = Object.values(expiryGroups);
                const expiryColors = ['#e74a3b', '#f6c23e', '#4e73df', '#1cc88a', '#36b9cc', '#6f42c1'];
                
                const ctxExpiry = document.getElementById('expiryDistributionChart').getContext('2d');
                expiryDistributionChart = new Chart(ctxExpiry, {
                    type: 'bar',
                    data: {
                        labels: expiryLabels,
                        datasets: [{
                            label: 'Số lượng sản phẩm',
                            data: expiryValues,
                            backgroundColor: expiryColors
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Lỗi khi tải báo cáo hạn sử dụng:', error);
                document.getElementById('expiryTableBody').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Đã xảy ra lỗi khi tải dữ liệu</td></tr>';
            });
    }
    
    // Hàm hỗ trợ xuất Excel
    function exportToExcel(reportType) {
        let queryParams = '';
        
        switch (reportType) {
            case 'inventory':
                queryParams = `?warehouseId=${document.getElementById('inventoryWarehouseFilter').value}&categoryId=${document.getElementById('inventoryCategoryFilter').value}&stockLevel=${document.getElementById('inventoryStockLevelFilter').value}`;
                window.open(`ajax/export_excel.php${queryParams}&reportType=inventory`, '_blank');
                break;
            case 'imports':
                queryParams = `?warehouseId=${document.getElementById('ioWarehouseFilter').value}&dateRange=${document.getElementById('ioDateRangeFilter').value}`;
                if (document.getElementById('ioDateRangeFilter').value === 'custom') {
                    queryParams += `&startDate=${document.getElementById('ioStartDate').value}&endDate=${document.getElementById('ioEndDate').value}`;
                }
                window.open(`ajax/export_excel.php${queryParams}&reportType=imports`, '_blank');
                break;
            case 'exports':
                queryParams = `?warehouseId=${document.getElementById('ioWarehouseFilter').value}&dateRange=${document.getElementById('ioDateRangeFilter').value}`;
                if (document.getElementById('ioDateRangeFilter').value === 'custom') {
                    queryParams += `&startDate=${document.getElementById('ioStartDate').value}&endDate=${document.getElementById('ioEndDate').value}`;
                }
                window.open(`ajax/export_excel.php${queryParams}&reportType=exports`, '_blank');
                break;
            case 'shelves':
                queryParams = `?warehouseId=${document.getElementById('warehouseFilter').value}`;
                window.open(`ajax/export_excel.php${queryParams}&reportType=shelves`, '_blank');
                break;
            case 'expiry':
                queryParams = `?warehouseId=${document.getElementById('expiryWarehouseFilter').value}&daysThreshold=${document.getElementById('expiryDaysFilter').value}`;
                window.open(`ajax/export_excel.php${queryParams}&reportType=expiry`, '_blank');
                break;
        }
    }
    
    // Hàm hỗ trợ xuất PDF
    function exportToPDF(reportType) {
        let queryParams = '';
        
        switch (reportType) {
            case 'inventory':
                queryParams = `?warehouseId=${document.getElementById('inventoryWarehouseFilter').value}&categoryId=${document.getElementById('inventoryCategoryFilter').value}&stockLevel=${document.getElementById('inventoryStockLevelFilter').value}`;
                window.open(`ajax/export_pdf.php${queryParams}&reportType=inventory`, '_blank');
                break;
            case 'expiry':
                queryParams = `?warehouseId=${document.getElementById('expiryWarehouseFilter').value}&daysThreshold=${document.getElementById('expiryDaysFilter').value}`;
                window.open(`ajax/export_pdf.php${queryParams}&reportType=expiry`, '_blank');
                break;
        }
    }
    
    // Hàm tiện ích định dạng tiền tệ
    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
            minimumFractionDigits: 0
        }).format(value);
    }
    
    // Hàm tiện ích định dạng ngày
    function formatDate(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('vi-VN').format(date);
    }
    
    // Hàm tạo màu ngẫu nhiên
    function getRandomColor() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }
    
    // Hàm tạo mảng màu
    function getColorArray(count, usePastel = false) {
        const colors = [];
        if (usePastel) {
            // Màu pastel
            const baseColors = ['#f94144', '#f3722c', '#f8961e', '#f9c74f', '#90be6d', '#43aa8b', '#577590'];
            for (let i = 0; i < count; i++) {
                colors.push(baseColors[i % baseColors.length]);
            }
        } else {
            // Màu cơ bản
            const baseColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1', '#5a5c69'];
            for (let i = 0; i < count; i++) {
                colors.push(baseColors[i % baseColors.length]);
            }
        }
        return colors;
    }
});
</script>