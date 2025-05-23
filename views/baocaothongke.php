<?php
// Kết nối đến cơ sở dữ liệu
include_once 'config/connect.php';

// Lấy các tham số từ URL (nếu có)
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'inventory';
$warehouse_filter = isset($_GET['warehouse']) ? $_GET['warehouse'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$stock_level_filter = isset($_GET['stock_level']) ? $_GET['stock_level'] : '';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '30';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$days_threshold = isset($_GET['days_threshold']) ? $_GET['days_threshold'] : '30';

// Truy vấn dữ liệu báo cáo tồn kho
$inventory_sql = "SELECT p.product_code, p.product_name, c.category_name, w.warehouse_name, 
                  i.quantity, p.price, i.quantity * p.price as total_value, p.minimum_stock,
                  CASE 
                     WHEN i.quantity <= p.minimum_stock THEN 'Thấp' 
                     WHEN i.quantity <= p.minimum_stock * 1.5 THEN 'Trung bình' 
                     ELSE 'Cao' 
                  END AS stock_level 
                  FROM inventory i
                  JOIN products p ON i.product_id = p.product_id
                  JOIN categories c ON p.category_id = c.category_id
                  JOIN warehouses w ON i.warehouse_id = w.warehouse_id
                  WHERE 1=1";

if (!empty($warehouse_filter)) {
    $inventory_sql .= " AND i.warehouse_id = $warehouse_filter";
}

if (!empty($category_filter)) {
    $inventory_sql .= " AND p.category_id = $category_filter";
}

if (!empty($stock_level_filter)) {
    $inventory_sql .= " AND (CASE 
                              WHEN i.quantity <= p.minimum_stock THEN 'Thấp' 
                              WHEN i.quantity <= p.minimum_stock * 1.5 THEN 'Trung bình' 
                              ELSE 'Cao' 
                           END) = '$stock_level_filter'";
}

$inventory_result = $conn->query($inventory_sql);
$inventory_data = [];
$total_products = 0;
$total_quantity = 0;
$total_value = 0;
$low_stock_products = 0;

// Dữ liệu cho biểu đồ phân bổ theo danh mục và kho
$category_data = [];
$warehouse_data = [];

if ($inventory_result && $inventory_result->num_rows > 0) {
    while ($row = $inventory_result->fetch_assoc()) {
        $inventory_data[] = $row;
        $total_products++;
        $total_quantity += $row['quantity'];
        $total_value += $row['total_value'];
        
        if ($row['stock_level'] == 'Thấp') {
            $low_stock_products++;
        }
        
        // Thống kê theo danh mục
        if (!isset($category_data[$row['category_name']])) {
            $category_data[$row['category_name']] = 0;
        }
        $category_data[$row['category_name']] += $row['total_value'];
        
        // Thống kê theo kho
        if (!isset($warehouse_data[$row['warehouse_name']])) {
            $warehouse_data[$row['warehouse_name']] = 0;
        }
        $warehouse_data[$row['warehouse_name']] += $row['total_value'];
    }
}

// Truy vấn thống kê nhập kho
$import_sql = "SELECT DATE(io.created_at) as import_date, w.warehouse_name, s.supplier_name,
               COUNT(DISTINCT io.import_id) as total_imports, 
               SUM(iod.quantity) as total_quantity,
               SUM(iod.quantity * iod.unit_price) as total_amount
               FROM import_orders io
               JOIN import_order_details iod ON io.import_id = iod.import_id
               JOIN warehouses w ON io.warehouse_id = w.warehouse_id
               JOIN suppliers s ON io.supplier_id = s.supplier_id
               WHERE io.status = 'COMPLETED'";

if (!empty($warehouse_filter)) {
    $import_sql .= " AND io.warehouse_id = $warehouse_filter";
}

// Thiết lập điều kiện ngày
if ($date_range === 'custom' && !empty($start_date) && !empty($end_date)) {
    $import_sql .= " AND DATE(io.created_at) BETWEEN '$start_date' AND '$end_date'";
} else {
    $days = intval($date_range);
    $import_sql .= " AND io.created_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
}

$import_sql .= " GROUP BY DATE(io.created_at), w.warehouse_id, s.supplier_id
                 ORDER BY import_date DESC";

$import_result = $conn->query($import_sql);
$import_data = [];
$total_imports = 0;
$total_import_value = 0;

if ($import_result && $import_result->num_rows > 0) {
    while ($row = $import_result->fetch_assoc()) {
        $import_data[] = $row;
        $total_imports += $row['total_imports'];
        $total_import_value += $row['total_amount'];
    }
}

// Truy vấn thống kê xuất kho
$export_sql = "SELECT DATE(eo.created_at) as export_date, w.warehouse_name,
               COUNT(DISTINCT eo.export_id) as total_exports, 
               SUM(eod.quantity) as total_quantity,
               SUM(eod.quantity * eod.unit_price) as total_amount
               FROM export_orders eo
               JOIN export_order_details eod ON eo.export_id = eod.export_id
               JOIN warehouses w ON eo.warehouse_id = w.warehouse_id
               WHERE eo.status = 'COMPLETED'";

if (!empty($warehouse_filter)) {
    $export_sql .= " AND eo.warehouse_id = $warehouse_filter";
}

// Thiết lập điều kiện ngày
if ($date_range === 'custom' && !empty($start_date) && !empty($end_date)) {
    $export_sql .= " AND DATE(eo.created_at) BETWEEN '$start_date' AND '$end_date'";
} else {
    $days = intval($date_range);
    $export_sql .= " AND eo.created_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
}

$export_sql .= " GROUP BY DATE(eo.created_at), w.warehouse_id
                 ORDER BY export_date DESC";

$export_result = $conn->query($export_sql);
$export_data = [];
$total_exports = 0;
$total_export_value = 0;

if ($export_result && $export_result->num_rows > 0) {
    while ($row = $export_result->fetch_assoc()) {
        $export_data[] = $row;
        $total_exports += $row['total_exports'];
        $total_export_value += $row['total_amount'];
    }
}

// Truy vấn thống kê nhà cung cấp
$supplier_sql = "SELECT s.supplier_name,
                 COUNT(DISTINCT io.import_id) as total_imports,
                 SUM(iod.quantity) as total_quantity,
                 SUM(iod.quantity * iod.unit_price) as total_amount
                 FROM import_orders io
                 JOIN import_order_details iod ON io.import_id = iod.import_id
                 JOIN suppliers s ON io.supplier_id = s.supplier_id
                 WHERE io.status = 'COMPLETED'";

if (!empty($warehouse_filter)) {
    $supplier_sql .= " AND io.warehouse_id = $warehouse_filter";
}

// Thiết lập điều kiện ngày
if ($date_range === 'custom' && !empty($start_date) && !empty($end_date)) {
    $supplier_sql .= " AND DATE(io.created_at) BETWEEN '$start_date' AND '$end_date'";
} else {
    $days = intval($date_range);
    $supplier_sql .= " AND io.created_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
}

$supplier_sql .= " GROUP BY s.supplier_id
                   ORDER BY total_amount DESC";

$supplier_result = $conn->query($supplier_sql);
$supplier_data = [];
$total_supplier_amount = 0;

if ($supplier_result && $supplier_result->num_rows > 0) {
    // Tính tổng giá trị nhập hàng từ tất cả nhà cung cấp
    $total_query = "SELECT SUM(iod.quantity * iod.unit_price) as total FROM import_orders io JOIN import_order_details iod ON io.import_id = iod.import_id WHERE io.status = 'COMPLETED'";
    $total_result = $conn->query($total_query);
    $total_row = $total_result->fetch_assoc();
    $total_supplier_amount = $total_row['total'];
    
    while ($row = $supplier_result->fetch_assoc()) {
        $row['percentage'] = $total_supplier_amount > 0 ? round(($row['total_amount'] / $total_supplier_amount) * 100, 2) : 0;
        $supplier_data[] = $row;
    }
}

// Truy vấn phân tích kho
$shelf_sql = "SELECT s.shelf_id, s.shelf_code, wz.zone_code, w.warehouse_name,
              s.max_capacity,
              SUM(IFNULL(pl.quantity * p.volume, 0)) as used_capacity,
              (SUM(IFNULL(pl.quantity * p.volume, 0)) / s.max_capacity * 100) as utilization_percentage,
              CASE 
                 WHEN SUM(IFNULL(pl.quantity * p.volume, 0)) / s.max_capacity * 100 < 30 THEN 'Thấp' 
                 WHEN SUM(IFNULL(pl.quantity * p.volume, 0)) / s.max_capacity * 100 < 70 THEN 'Trung bình' 
                 ELSE 'Cao' 
              END AS utilization_level
              FROM shelves s
              JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
              JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
              LEFT JOIN product_locations pl ON s.shelf_id = pl.shelf_id
              LEFT JOIN products p ON pl.product_id = p.product_id";

if (!empty($warehouse_filter)) {
    $shelf_sql .= " WHERE wz.warehouse_id = $warehouse_filter";
}

$shelf_sql .= " GROUP BY s.shelf_id";

$shelf_result = $conn->query($shelf_sql);
$shelf_data = [];
$total_shelves = 0;
$used_capacity = 0;
$total_capacity = 0;
$high_utilization_shelves = 0;
$low_utilization_shelves = 0;

if ($shelf_result && $shelf_result->num_rows > 0) {
    while ($row = $shelf_result->fetch_assoc()) {
        $shelf_data[] = $row;
        $total_shelves++;
        $used_capacity += floatval($row['used_capacity']);
        $total_capacity += floatval($row['max_capacity']);
        
        if ($row['utilization_level'] == 'Cao') {
            $high_utilization_shelves++;
        } else if ($row['utilization_level'] == 'Thấp') {
            $low_utilization_shelves++;
        }
    }
}

$utilization_percentage = $total_capacity > 0 ? round(($used_capacity / $total_capacity) * 100, 2) : 0;

// Truy vấn sản phẩm gần hết hạn
$expiry_sql = "SELECT p.product_code, p.product_name, w.warehouse_name, s.shelf_code,
               pl.batch_number, pl.expiry_date, pl.quantity,
               DATEDIFF(pl.expiry_date, CURDATE()) as days_until_expiry
               FROM product_locations pl
               JOIN products p ON pl.product_id = p.product_id
               JOIN shelves s ON pl.shelf_id = s.shelf_id
               JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
               JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
               WHERE pl.expiry_date IS NOT NULL 
               AND pl.expiry_date > CURDATE()
               AND DATEDIFF(pl.expiry_date, CURDATE()) <= $days_threshold";

if (!empty($warehouse_filter)) {
    $expiry_sql .= " AND wz.warehouse_id = $warehouse_filter";
}

$expiry_sql .= " ORDER BY days_until_expiry ASC";

$expiry_result = $conn->query($expiry_sql);
$expiry_data = [];
$total_expiring_products = 0;
$urgent_30_days = 0;
$urgent_7_days = 0;

if ($expiry_result && $expiry_result->num_rows > 0) {
    while ($row = $expiry_result->fetch_assoc()) {
        $expiry_data[] = $row;
        $total_expiring_products++;
        
        if (intval($row['days_until_expiry']) <= 30) {
            $urgent_30_days++;
        }
        if (intval($row['days_until_expiry']) <= 7) {
            $urgent_7_days++;
        }
    }
}

// Hàm định dạng tiền tệ
function formatCurrency($value) {
    return number_format($value, 0, ',', '.') . ' ₫';
}

// Hàm định dạng ngày
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d/m/Y');
}

// Tạo dữ liệu cho biểu đồ
$category_labels = json_encode(array_keys($category_data));
$category_values = json_encode(array_values($category_data));

$warehouse_labels = json_encode(array_keys($warehouse_data));
$warehouse_values = json_encode(array_values($warehouse_data));

// Dữ liệu cho biểu đồ kệ
$utilization_labels = json_encode(['Thấp', 'Trung bình', 'Cao']);
$medium_shelves = $total_shelves - $high_utilization_shelves - $low_utilization_shelves;
$utilization_values = json_encode([$low_utilization_shelves, $medium_shelves, $high_utilization_shelves]);

// Dữ liệu cho biểu đồ hạn sử dụng
$expiry_groups = [
    '<= 7 ngày' => 0,
    '8-15 ngày' => 0,
    '16-30 ngày' => 0,
    '31-60 ngày' => 0,
    '61-90 ngày' => 0
];

foreach ($expiry_data as $item) {
    $days = intval($item['days_until_expiry']);
    $quantity = intval($item['quantity']);
    
    if ($days <= 7) {
        $expiry_groups['<= 7 ngày'] += $quantity;
    } else if ($days <= 15) {
        $expiry_groups['8-15 ngày'] += $quantity;
    } else if ($days <= 30) {
        $expiry_groups['16-30 ngày'] += $quantity;
    } else if ($days <= 60) {
        $expiry_groups['31-60 ngày'] += $quantity;
    } else if ($days <= 90) {
        $expiry_groups['61-90 ngày'] += $quantity;
    }
}

$expiry_labels = json_encode(array_keys($expiry_groups));
$expiry_values = json_encode(array_values($expiry_groups));

// Dữ liệu cho biểu đồ nhập/xuất theo thời gian
$time_series_data = [];
$now = new DateTime();
$interval = new DateInterval('P1D');
$period = new DatePeriod(
    (new DateTime())->sub(new DateInterval('P' . $date_range . 'D')),
    $interval,
    $now
);

foreach ($period as $date) {
    $date_str = $date->format('Y-m-d');
    $time_series_data[$date_str] = [
        'date' => $date_str,
        'import_value' => 0,
        'export_value' => 0
    ];
}

foreach ($import_data as $item) {
    if (isset($time_series_data[$item['import_date']])) {
        $time_series_data[$item['import_date']]['import_value'] += $item['total_amount'];
    }
}

foreach ($export_data as $item) {
    if (isset($time_series_data[$item['export_date']])) {
        $time_series_data[$item['export_date']]['export_value'] += $item['total_amount'];
    }
}

$time_labels = [];
$import_values = [];
$export_values = [];

foreach ($time_series_data as $data) {
    $time_labels[] = formatDate($data['date']);
    $import_values[] = $data['import_value'];
    $export_values[] = $data['export_value'];
}

$time_labels_json = json_encode($time_labels);
$import_values_json = json_encode($import_values);
$export_values_json = json_encode($export_values);

// Dữ liệu cho biểu đồ nhà cung cấp
$top5_suppliers = array_slice($supplier_data, 0, 5);
$supplier_labels = [];
$supplier_values = [];
$supplier_quantities = [];

foreach ($top5_suppliers as $supplier) {
    $supplier_labels[] = $supplier['supplier_name'];
    $supplier_values[] = $supplier['total_amount'];
    $supplier_quantities[] = $supplier['total_quantity'];
}

$supplier_labels_json = json_encode($supplier_labels);
$supplier_values_json = json_encode($supplier_values);
$supplier_quantities_json = json_encode($supplier_quantities);

// Dữ liệu dự báo kho
$forecast_months = ['Hiện tại', '1 tháng', '2 tháng', '3 tháng', '4 tháng', '5 tháng', '6 tháng'];
$forecast_data = [$utilization_percentage];
$current_util = floatval($utilization_percentage);

for ($i = 1; $i < 7; $i++) {
    $current_util = min($current_util * 1.05, 100); // Tăng 5% mỗi tháng, tối đa 100%
    $forecast_data[] = round($current_util, 2);
}

$forecast_labels_json = json_encode($forecast_months);
$forecast_data_json = json_encode($forecast_data);
?>

<div class="function-container">
    <h4 class="page-title">Báo cáo và thống kê</h4>
    
    <!-- Tabs cho các loại báo cáo -->
    <ul class="nav nav-tabs mb-4" id="reportsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($current_tab == 'inventory') ? 'active' : '' ?>" id="inventory-tab" href="?option=baocaothongke&tab=inventory" role="tab">
                <i class="fas fa-boxes me-2"></i>Báo cáo tồn kho
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($current_tab == 'io') ? 'active' : '' ?>" id="imports-exports-tab" href="?option=baocaothongke&tab=io" role="tab">
                <i class="fas fa-exchange-alt me-2"></i>Thống kê nhập/xuất
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($current_tab == 'warehouse') ? 'active' : '' ?>" id="warehouse-analysis-tab" href="?option=baocaothongke&tab=warehouse" role="tab">
                <i class="fas fa-warehouse me-2"></i>Phân tích kho
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($current_tab == 'expiry') ? 'active' : '' ?>" id="expiry-tracking-tab" href="?option=baocaothongke&tab=expiry" role="tab">
                <i class="fas fa-calendar-times me-2"></i>Theo dõi hạn sử dụng
            </a>
        </li>
    </ul>
    
    <div class="tab-content" id="reportsTabsContent">
        <!-- Tab Báo cáo tồn kho -->
        <div class="tab-pane fade <?= ($current_tab == 'inventory') ? 'show active' : '' ?>" id="inventory-tab-pane" role="tabpanel" aria-labelledby="inventory-tab" tabindex="0">
            <!-- Bộ lọc báo cáo tồn kho -->
            <div class="row mb-4">
                <form method="GET">
                    <input type="hidden" name="option" value="baocaothongke">
                    <input type="hidden" name="tab" value="inventory">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-select" name="warehouse" id="inventoryWarehouseFilter">
                                <option value="">Tất cả kho</option>
                                <?php
                                $sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $selected = ($warehouse_filter == $row["warehouse_id"]) ? "selected" : "";
                                        echo "<option value='" . $row["warehouse_id"] . "' $selected>" . $row["warehouse_name"] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="category" id="inventoryCategoryFilter">
                                <option value="">Tất cả danh mục</option>
                                <?php
                                $sql = "SELECT category_id, category_name FROM categories ORDER BY category_name";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $selected = ($category_filter == $row["category_id"]) ? "selected" : "";
                                        echo "<option value='" . $row["category_id"] . "' $selected>" . $row["category_name"] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="stock_level" id="inventoryStockLevelFilter">
                                <option value="">Tất cả mức tồn kho</option>
                                <option value="Thấp" <?= ($stock_level_filter == 'Thấp') ? 'selected' : '' ?>>Thấp</option>
                                <option value="Trung bình" <?= ($stock_level_filter == 'Trung bình') ? 'selected' : '' ?>>Trung bình</option>
                                <option value="Cao" <?= ($stock_level_filter == 'Cao') ? 'selected' : '' ?>>Cao</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sync-alt me-2"></i>Tạo báo cáo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Thống kê tổng quan tồn kho -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $total_products ?></h2>
                            <p class="mb-0">Tổng sản phẩm</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $total_quantity ?></h2>
                            <p class="mb-0">Tổng số lượng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= formatCurrency($total_value) ?></h2>
                            <p class="mb-0">Tổng giá trị</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $low_stock_products ?></h2>
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
                        <a href="export/inventory_excel.php?warehouse=<?= $warehouse_filter ?>&category=<?= $category_filter ?>&stock_level=<?= $stock_level_filter ?>" target="_blank" class="btn btn-sm btn-outline-success me-2">
                            <i class="fas fa-file-excel me-1"></i>Xuất Excel
                        </a>
                        <a href="export/inventory_pdf.php?warehouse=<?= $warehouse_filter ?>&category=<?= $category_filter ?>&stock_level=<?= $stock_level_filter ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-file-pdf me-1"></i>Xuất PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
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
                            <tbody>
                                <?php if (count($inventory_data) > 0): ?>
                                    <?php foreach ($inventory_data as $item): ?>
                                        <tr>
                                            <td><?= $item['product_code'] ?></td>
                                            <td><?= $item['product_name'] ?></td>
                                            <td><?= $item['category_name'] ?></td>
                                            <td><?= $item['warehouse_name'] ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td><?= formatCurrency($item['price']) ?></td>
                                            <td><?= formatCurrency($item['total_value']) ?></td>
                                            <td>
                                                <span class="<?= $item['stock_level'] == 'Thấp' ? 'text-danger' : ($item['stock_level'] == 'Trung bình' ? 'text-warning' : 'text-success') ?>">
                                                    <?= $item['stock_level'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Thống kê nhập/xuất -->
        <div class="tab-pane fade <?= ($current_tab == 'io') ? 'show active' : '' ?>" id="imports-exports-tab-pane" role="tabpanel" aria-labelledby="imports-exports-tab" tabindex="0">
            <!-- Bộ lọc thống kê nhập/xuất -->
            <div class="row mb-4">
                <form method="GET">
                    <input type="hidden" name="option" value="baocaothongke">
                    <input type="hidden" name="tab" value="io">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-select" name="warehouse" id="ioWarehouseFilter">
                                <option value="">Tất cả kho</option>
                                <?php
                                $sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $selected = ($warehouse_filter == $row["warehouse_id"]) ? "selected" : "";
                                        echo "<option value='" . $row["warehouse_id"] . "' $selected>" . $row["warehouse_name"] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="date_range" id="ioDateRangeFilter">
                                <option value="7" <?= ($date_range == '7') ? 'selected' : '' ?>>7 ngày qua</option>
                                <option value="30" <?= ($date_range == '30') ? 'selected' : '' ?>>30 ngày qua</option>
                                <option value="90" <?= ($date_range == '90') ? 'selected' : '' ?>>90 ngày qua</option>
                                <option value="365" <?= ($date_range == '365') ? 'selected' : '' ?>>1 năm qua</option>
                                <option value="custom" <?= ($date_range == 'custom') ? 'selected' : '' ?>>Tùy chỉnh</option>
                            </select>
                        </div>
                        <div class="col-md-3 <?= ($date_range == 'custom') ? '' : 'd-none' ?>" id="date-range-container">
                            <div class="input-group">
                                <input type="date" class="form-control" name="start_date" id="ioStartDate" value="<?= $start_date ?>">
                                <span class="input-group-text">đến</span>
                                <input type="date" class="form-control" name="end_date" id="ioEndDate" value="<?= $end_date ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sync-alt me-2"></i>Tạo báo cáo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Thống kê tổng quan nhập/xuất -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $total_imports ?></h2>
                            <p class="mb-0">Tổng phiếu nhập</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= formatCurrency($total_import_value) ?></h2>
                            <p class="mb-0">Tổng giá trị nhập</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $total_exports ?></h2>
                            <p class="mb-0">Tổng phiếu xuất</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= formatCurrency($total_export_value) ?></h2>
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
                                <a href="export/imports_excel.php?warehouse=<?= $warehouse_filter ?>&date_range=<?= $date_range ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" target="_blank" class="btn btn-sm btn-outline-success me-2">
                                    <i class="fas fa-file-excel me-1"></i>Xuất Excel
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
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
                                    <tbody>
                                        <?php if (count($import_data) > 0): ?>
                                            <?php foreach ($import_data as $item): ?>
                                                <tr>
                                                    <td><?= formatDate($item['import_date']) ?></td>
                                                    <td><?= $item['warehouse_name'] ?></td>
                                                    <td><?= $item['supplier_name'] ?></td>
                                                    <td><?= $item['total_imports'] ?></td>
                                                    <td><?= $item['total_quantity'] ?></td>
                                                    <td><?= formatCurrency($item['total_amount']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Không có dữ liệu</td>
                                            </tr>
                                        <?php endif; ?>
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
                                <a href="export/exports_excel.php?warehouse=<?= $warehouse_filter ?>&date_range=<?= $date_range ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" target="_blank" class="btn btn-sm btn-outline-success me-2">
                                    <i class="fas fa-file-excel me-1"></i>Xuất Excel
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ngày xuất</th>
                                            <th>Kho</th>
                                            <th>Số phiếu xuất</th>
                                            <th>Tổng số lượng</th>
                                            <th>Tổng giá trị</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($export_data) > 0): ?>
                                            <?php foreach ($export_data as $item): ?>
                                                <tr>
                                                    <td><?= formatDate($item['export_date']) ?></td>
                                                    <td><?= $item['warehouse_name'] ?></td>
                                                    <td><?= $item['total_exports'] ?></td>
                                                    <td><?= $item['total_quantity'] ?></td>
                                                    <td><?= formatCurrency($item['total_amount']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Không có dữ liệu</td>
                                            </tr>
                                        <?php endif; ?>
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
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nhà cung cấp</th>
                                            <th>Số phiếu nhập</th>
                                            <th>Tổng số lượng</th>
                                            <th>Tổng giá trị</th>
                                            <th>% Tỷ trọng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($supplier_data) > 0): ?>
                                            <?php foreach ($supplier_data as $item): ?>
                                                <tr>
                                                    <td><?= $item['supplier_name'] ?></td>
                                                    <td><?= $item['total_imports'] ?></td>
                                                    <td><?= $item['total_quantity'] ?></td>
                                                    <td><?= formatCurrency($item['total_amount']) ?></td>
                                                    <td><?= $item['percentage'] ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Không có dữ liệu</td>
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
        
        <!-- Tab Phân tích kho -->
        <div class="tab-pane fade <?= ($current_tab == 'warehouse') ? 'show active' : '' ?>" id="warehouse-analysis-tab-pane" role="tabpanel" aria-labelledby="warehouse-analysis-tab" tabindex="0">
            <!-- Bộ lọc phân tích kho -->
            <div class="row mb-4">
                <form method="GET">
                    <input type="hidden" name="option" value="baocaothongke">
                    <input type="hidden" name="tab" value="warehouse">
                    <div class="row">
                        <div class="col-md-6">
                            <select class="form-select" name="warehouse" id="warehouseFilter">
                                <option value="">Tất cả kho</option>
                                <?php
                                $sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $selected = ($warehouse_filter == $row["warehouse_id"]) ? "selected" : "";
                                        echo "<option value='" . $row["warehouse_id"] . "' $selected>" . $row["warehouse_name"] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sync-alt me-2"></i>Tạo báo cáo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Thống kê tổng quan kho -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $total_shelves ?></h2>
                            <p class="mb-0">Tổng số kệ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $utilization_percentage ?>%</h2>
                            <p class="mb-0">Công suất sử dụng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $high_utilization_shelves ?></h2>
                            <p class="mb-0">Kệ sử dụng cao</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $low_utilization_shelves ?></h2>
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
                        <a href="export/shelves_excel.php?warehouse=<?= $warehouse_filter ?>" target="_blank" class="btn btn-sm btn-outline-success me-2">
                            <i class="fas fa-file-excel me-1"></i>Xuất Excel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
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
                            <tbody>
                                <?php if (count($shelf_data) > 0): ?>
                                    <?php foreach ($shelf_data as $item): ?>
                                        <?php 
                                            $utilization_class = '';
                                            if ($item['utilization_level'] == 'Thấp') {
                                                $utilization_class = 'text-success';
                                            } else if ($item['utilization_level'] == 'Trung bình') {
                                                $utilization_class = 'text-warning';
                                            } else {
                                                $utilization_class = 'text-danger';
                                            }
                                        ?>
                                        <tr>
                                            <td><?= $item['shelf_code'] ?></td>
                                            <td><?= $item['zone_code'] ?></td>
                                            <td><?= $item['warehouse_name'] ?></td>
                                            <td><?= number_format($item['max_capacity'], 2) ?></td>
                                            <td><?= number_format($item['used_capacity'], 2) ?></td>
                                            <td><?= number_format($item['utilization_percentage'], 2) ?>%</td>
                                            <td class="<?= $utilization_class ?>"><?= $item['utilization_level'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Theo dõi hạn sử dụng -->
        <div class="tab-pane fade <?= ($current_tab == 'expiry') ? 'show active' : '' ?>" id="expiry-tracking-tab-pane" role="tabpanel" aria-labelledby="expiry-tracking-tab" tabindex="0">
            <!-- Bộ lọc theo dõi hạn sử dụng -->
            <div class="row mb-4">
                <form method="GET">
                    <input type="hidden" name="option" value="baocaothongke">
                    <input type="hidden" name="tab" value="expiry">
                    <div class="row">
                        <div class="col-md-4">
                            <select class="form-select" name="warehouse" id="expiryWarehouseFilter">
                                <option value="">Tất cả kho</option>
                                <?php
                                $sql = "SELECT warehouse_id, warehouse_name FROM warehouses ORDER BY warehouse_name";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $selected = ($warehouse_filter == $row["warehouse_id"]) ? "selected" : "";
                                        echo "<option value='" . $row["warehouse_id"] . "' $selected>" . $row["warehouse_name"] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="days_threshold" id="expiryDaysFilter">
                                <option value="30" <?= ($days_threshold == '30') ? 'selected' : '' ?>>30 ngày tới</option>
                                <option value="60" <?= ($days_threshold == '60') ? 'selected' : '' ?>>60 ngày tới</option>
                                <option value="90" <?= ($days_threshold == '90') ? 'selected' : '' ?>>90 ngày tới</option>
                                <option value="180" <?= ($days_threshold == '180') ? 'selected' : '' ?>>180 ngày tới</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sync-alt me-2"></i>Tạo báo cáo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Thống kê tổng quan hạn sử dụng -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2><?= $total_expiring_products ?></h2>
                            <p class="mb-0">Tổng SP gần hết hạn</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h2><?= $urgent_30_days ?></h2>
                            <p class="mb-0">Sắp hết hạn (dưới 30 ngày)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h2><?= $urgent_7_days ?></h2>
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
                        <a href="export/expiry_excel.php?warehouse=<?= $warehouse_filter ?>&days_threshold=<?= $days_threshold ?>" target="_blank" class="btn btn-sm btn-outline-success me-2">
                            <i class="fas fa-file-excel me-1"></i>Xuất Excel
                        </a>
                        <a href="export/expiry_pdf.php?warehouse=<?= $warehouse_filter ?>&days_threshold=<?= $days_threshold ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-file-pdf me-1"></i>Xuất PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
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
                            <tbody>
                                <?php if (count($expiry_data) > 0): ?>
                                    <?php foreach ($expiry_data as $item): ?>
                                        <?php 
                                            $days_class = '';
                                            $days_remaining = intval($item['days_until_expiry']);
                                            
                                            if ($days_remaining <= 7) {
                                                $days_class = 'text-danger fw-bold';
                                            } else if ($days_remaining <= 30) {
                                                $days_class = 'text-warning fw-bold';
                                            } else {
                                                $days_class = 'text-info';
                                            }
                                        ?>
                                        <tr>
                                            <td><?= $item['product_code'] ?></td>
                                            <td><?= $item['product_name'] ?></td>
                                            <td><?= $item['warehouse_name'] ?></td>
                                            <td><?= $item['shelf_code'] ?></td>
                                            <td><?= $item['batch_number'] ?: 'N/A' ?></td>
                                            <td><?= formatDate($item['expiry_date']) ?></td>
                                            <td class="<?= $days_class ?>"><?= $days_remaining ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Không có sản phẩm gần hết hạn trong khoảng thời gian đã chọn</td>
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

<script>
// Xử lý hiển thị/ẩn date range khi thay đổi lựa chọn
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo các biểu đồ khi tài liệu đã tải xong
    
    // Hiển thị input tùy chỉnh ngày khi chọn tùy chỉnh
    const dateRangeFilter = document.getElementById('ioDateRangeFilter');
    if (dateRangeFilter) {
        dateRangeFilter.addEventListener('change', function() {
            const container = document.getElementById('date-range-container');
            if (this.value === 'custom') {
                container.classList.remove('d-none');
            } else {
                container.classList.add('d-none');
            }
        });
    }
    
    // Biểu đồ phân bổ tồn kho theo danh mục
    const ctxCategory = document.getElementById('inventoryCategoryChart');
    if (ctxCategory) {
        new Chart(ctxCategory, {
            type: 'pie',
            data: {
                labels: <?= $category_labels ?>,
                datasets: [{
                    data: <?= $category_values ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1',
                        '#fd7e14', '#20c997', '#6c757d', '#17a2b8', '#dc3545', '#28a745'
                    ]
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
    }
    
    // Biểu đồ phân bổ tồn kho theo kho
    const ctxWarehouse = document.getElementById('inventoryWarehouseChart');
    if (ctxWarehouse) {
        new Chart(ctxWarehouse, {
            type: 'doughnut',
            data: {
                labels: <?= $warehouse_labels ?>,
                datasets: [{
                    data: <?= $warehouse_values ?>,
                    backgroundColor: [
                        '#1cc88a', '#4e73df', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1',
                        '#fd7e14', '#20c997', '#6c757d', '#17a2b8', '#dc3545', '#28a745'
                    ]
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
    }
    
    // Biểu đồ nhập/xuất theo thời gian
    const ctxIOTime = document.getElementById('ioTimeChart');
    if (ctxIOTime) {
        new Chart(ctxIOTime, {
            type: 'line',
            data: {
                labels: <?= $time_labels_json ?>,
                datasets: [
                    {
                        label: 'Giá trị nhập kho',
                        data: <?= $import_values_json ?>,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Giá trị xuất kho',
                        data: <?= $export_values_json ?>,
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
    }
    
    // Biểu đồ top 5 nhà cung cấp theo giá trị
    const ctxSupplierValue = document.getElementById('supplierValueChart');
    if (ctxSupplierValue) {
        new Chart(ctxSupplierValue, {
            type: 'bar',
            data: {
                labels: <?= $supplier_labels_json ?>,
                datasets: [{
                    label: 'Giá trị nhập kho',
                    data: <?= $supplier_values_json ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                    ]
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
    }
    
    // Biểu đồ top 5 nhà cung cấp theo số lượng
    const ctxSupplierQuantity = document.getElementById('supplierQuantityChart');
    if (ctxSupplierQuantity) {
        new Chart(ctxSupplierQuantity, {
            type: 'bar',
            data: {
                labels: <?= $supplier_labels_json ?>,
                datasets: [{
                    label: 'Số lượng nhập kho',
                    data: <?= $supplier_quantities_json ?>,
                    backgroundColor: [
                        '#f94144', '#f3722c', '#f8961e', '#f9c74f', '#90be6d'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    }
    
    // Biểu đồ mức độ sử dụng kệ
    const ctxShelfUtilization = document.getElementById('shelfUtilizationChart');
    if (ctxShelfUtilization) {
        new Chart(ctxShelfUtilization, {
            type: 'pie',
            data: {
                labels: <?= $utilization_labels ?>,
                datasets: [{
                    data: <?= $utilization_values ?>,
                    backgroundColor: [
                        '#1cc88a', '#f6c23e', '#e74a3b'
                    ]
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
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                return `${context.label}: ${value} kệ (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Biểu đồ dự báo nhu cầu không gian kho
    const ctxForecast = document.getElementById('warehouseCapacityForecastChart');
    if (ctxForecast) {
        new Chart(ctxForecast, {
            type: 'line',
            data: {
                labels: <?= $forecast_labels_json ?>,
                datasets: [{
                    label: 'Dự báo công suất sử dụng (%)',
                    data: <?= $forecast_data_json ?>,
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
    }
    
    // Biểu đồ phân bổ sản phẩm gần hết hạn
    const ctxExpiry = document.getElementById('expiryDistributionChart');
    if (ctxExpiry) {
        new Chart(ctxExpiry, {
            type: 'bar',
            data: {
                labels: <?= $expiry_labels ?>,
                datasets: [{
                    label: 'Số lượng sản phẩm',
                    data: <?= $expiry_values ?>,
                    backgroundColor: [
                        '#e74a3b', '#f6c23e', '#4e73df', '#1cc88a', '#36b9cc'
                    ]
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
    }
    
    // Hàm định dạng tiền tệ
    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
            minimumFractionDigits: 0
        }).format(value);
    }
});
</script>     