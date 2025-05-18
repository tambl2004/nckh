<?php
// Kết nối database
include 'config/connect.php';

// Truy vấn lấy thống kê
// Tổng số sản phẩm
$sql_products = "SELECT COUNT(*) as total FROM products";
$result_products = $conn->query($sql_products);
$total_products = $result_products && $result_products->num_rows > 0 ? $result_products->fetch_assoc()['total'] : 0;

// Tổng số kho
$sql_warehouses = "SELECT COUNT(*) as total FROM warehouses";
$result_warehouses = $conn->query($sql_warehouses);
$total_warehouses = $result_warehouses && $result_warehouses->num_rows > 0 ? $result_warehouses->fetch_assoc()['total'] : 0;

// Tổng số nhập kho trong tháng
$sql_imports = "SELECT COUNT(*) as total FROM import_orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND status = 'COMPLETED'";
$result_imports = $conn->query($sql_imports);
$total_imports = $result_imports && $result_imports->num_rows > 0 ? $result_imports->fetch_assoc()['total'] : 0;

// Tổng số xuất kho trong tháng
$sql_exports = "SELECT COUNT(*) as total FROM export_orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND status = 'COMPLETED'";
$result_exports = $conn->query($sql_exports);
$total_exports = $result_exports && $result_exports->num_rows > 0 ? $result_exports->fetch_assoc()['total'] : 0;

// Sản phẩm sắp hết hạn
$sql_expiring = "SELECT COUNT(*) as total FROM product_alerts WHERE alert_type = 'EXPIRING_SOON' AND is_active = 1";
$result_expiring = $conn->query($sql_expiring);
$total_expiring = $result_expiring && $result_expiring->num_rows > 0 ? $result_expiring->fetch_assoc()['total'] : 0;

// Sản phẩm tồn kho thấp
$sql_low_stock = "SELECT COUNT(*) as total FROM product_alerts WHERE alert_type = 'LOW_STOCK' AND is_active = 1";
$result_low_stock = $conn->query($sql_low_stock);
$total_low_stock = $result_low_stock && $result_low_stock->num_rows > 0 ? $result_low_stock->fetch_assoc()['total'] : 0;

// Lấy danh sách các cảnh báo mới nhất
$sql_alerts = "SELECT pa.*, p.product_name, p.product_code, w.warehouse_name 
               FROM product_alerts pa 
               JOIN products p ON pa.product_id = p.product_id
               JOIN warehouses w ON pa.warehouse_id = w.warehouse_id
               WHERE pa.is_active = 1
               ORDER BY pa.created_at DESC
               LIMIT 5";
$result_alerts = $conn->query($sql_alerts);
$alerts = [];
if ($result_alerts && $result_alerts->num_rows > 0) {
    while ($row = $result_alerts->fetch_assoc()) {
        $alerts[] = $row;
    }
}

// Lấy nhật ký hoạt động gần đây
$sql_logs = "SELECT ul.*, u.username, u.full_name 
            FROM user_logs ul
            JOIN users u ON ul.user_id = u.user_id
            ORDER BY ul.created_at DESC
            LIMIT 5";
$result_logs = $conn->query($sql_logs);
$logs = [];
if ($result_logs && $result_logs->num_rows > 0) {
    while ($row = $result_logs->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>

<div class="container-fluid">
    <h1 class="page-title">Tổng quan hệ thống</h1>
    
    <!-- Thẻ thống kê -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="card-title">Tổng sản phẩm</span>
                    <div class="d-flex align-items-center mt-2">
                        <h2 class="card-text mb-0"><?php echo number_format($total_products); ?></h2>
                        <i class="fas fa-box stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="card-title">Tổng số kho</span>
                    <div class="d-flex align-items-center mt-2">
                        <h2 class="card-text mb-0"><?php echo number_format($total_warehouses); ?></h2>
                        <i class="fas fa-warehouse stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="card-title">Nhập kho tháng này</span>
                    <div class="d-flex align-items-center mt-2">
                        <h2 class="card-text mb-0"><?php echo number_format($total_imports); ?></h2>
                        <i class="fas fa-file-import stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="card-title">Xuất kho tháng này</span>
                    <div class="d-flex align-items-center mt-2">
                        <h2 class="card-text mb-0"><?php echo number_format($total_exports); ?></h2>
                        <i class="fas fa-file-export stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Thống kê cảnh báo -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="card-title">Sản phẩm sắp hết hạn</span>
                    <div class="d-flex align-items-center mt-2">
                        <h2 class="card-text mb-0"><?php echo number_format($total_expiring); ?></h2>
                        <i class="fas fa-calendar-times stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="card-title">Sản phẩm tồn kho thấp</span>
                    <div class="d-flex align-items-center mt-2">
                        <h2 class="card-text mb-0"><?php echo number_format($total_low_stock); ?></h2>
                        <i class="fas fa-exclamation-triangle stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Biểu đồ -->
    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="chart-container">
                <h5 class="mb-4">Thống kê nhập/xuất kho 6 tháng gần đây</h5>
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="chart-container">
                <h5 class="mb-4">Top 5 sản phẩm xuất nhiều nhất</h5>
                <canvas id="topProductsChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Cảnh báo và Hoạt động gần đây -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="function-container">
                <h5 class="mb-3">Cảnh báo mới nhất</h5>
                
                <?php if (count($alerts) > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mã SP</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Kho</th>
                                    <th>Loại cảnh báo</th>
                                    <th>Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alerts as $alert): ?>
                                    <tr>
                                        <td><?php echo $alert['product_code']; ?></td>
                                        <td><?php echo $alert['product_name']; ?></td>
                                        <td><?php echo $alert['warehouse_name']; ?></td>
                                        <td>
                                            <?php if ($alert['alert_type'] == 'EXPIRING_SOON'): ?>
                                                <span class="badge bg-warning">Sắp hết hạn</span>
                                            <?php elseif ($alert['alert_type'] == 'LOW_STOCK'): ?>
                                                <span class="badge bg-danger">Tồn kho thấp</span>
                                            <?php elseif ($alert['alert_type'] == 'OUT_OF_STOCK'): ?>
                                                <span class="badge bg-dark">Hết hàng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($alert['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Không có cảnh báo nào</div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-end mt-3">
                    <a href="?option=baocaothongke" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="function-container">
                <h5 class="mb-3">Hoạt động gần đây</h5>
                
                <?php if (count($logs) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($logs as $log): ?>
                            <div class="log-item">
                                <div class="d-flex justify-content-between">
                                    <span class="log-user"><?php echo $log['full_name']; ?></span>
                                    <span class="log-time"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></span>
                                </div>
                                <div class="mt-1">
                                    <span class="log-action <?php echo strtolower($log['action_type']); ?>"><?php echo $log['action_type']; ?></span>
                                    <span><?php echo $log['description']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Không có hoạt động nào gần đây</div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-end mt-3">
                    <a href="?option=nhanvien&tab=logs" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
            </div>
        </div>
    </div>
</div>