<?php
// Kiểm tra kết nối
if (!isset($conn)) {
    include_once 'config/connect.php';
}

// Lấy tab hiện tại từ URL hoặc mặc định là 'kho'
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'kho';

// Kiểm tra hành động (thêm, sửa, xóa)
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Hàm hiển thị thông báo
function showAlert($message, $type = 'success') {
    echo "<div class='alert alert-$type alert-dismissible fade show mt-3' role='alert'>
            $message
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
}

// Xử lý thêm mới kho
if (isset($_POST['add_warehouse'])) {
    $warehouse_name = $conn->real_escape_string($_POST['warehouse_name']);
    $address = $conn->real_escape_string($_POST['address']);
    $contact_person = $conn->real_escape_string($_POST['contact_person']);
    $contact_phone = $conn->real_escape_string($_POST['contact_phone']);

    $sql = "INSERT INTO warehouses (warehouse_name, address, contact_person, contact_phone) 
            VALUES ('$warehouse_name', '$address', '$contact_person', '$contact_phone')";
    
    if ($conn->query($sql)) {
        showAlert("Thêm kho mới thành công!");
    } else {
        showAlert("Lỗi: " . $conn->error, 'danger');
    }
}

// Xử lý cập nhật kho
if (isset($_POST['update_warehouse'])) {
    $warehouse_id = $conn->real_escape_string($_POST['warehouse_id']);
    $warehouse_name = $conn->real_escape_string($_POST['warehouse_name']);
    $address = $conn->real_escape_string($_POST['address']);
    $contact_person = $conn->real_escape_string($_POST['contact_person']);
    $contact_phone = $conn->real_escape_string($_POST['contact_phone']);

    $sql = "UPDATE warehouses SET 
            warehouse_name = '$warehouse_name', 
            address = '$address', 
            contact_person = '$contact_person', 
            contact_phone = '$contact_phone' 
            WHERE warehouse_id = $warehouse_id";
    
    if ($conn->query($sql)) {
        showAlert("Cập nhật kho thành công!");
    } else {
        showAlert("Lỗi: " . $conn->error, 'danger');
    }
}

// Xử lý xóa kho
if ($action == 'delete_warehouse' && !empty($id)) {
    // Kiểm tra xem kho có dữ liệu liên quan không
    $check_sql = "SELECT COUNT(*) AS count FROM warehouse_zones WHERE warehouse_id = $id";
    $check_result = $conn->query($check_sql);
    $check_data = $check_result->fetch_assoc();
    
    if ($check_data['count'] > 0) {
        showAlert("Không thể xóa kho này vì có dữ liệu khu vực liên quan!", 'danger');
    } else {
        $sql = "DELETE FROM warehouses WHERE warehouse_id = $id";
        if ($conn->query($sql)) {
            showAlert("Xóa kho thành công!");
        } else {
            showAlert("Lỗi: " . $conn->error, 'danger');
        }
    }
}

// Xử lý thêm mới khu vực
if (isset($_POST['add_zone'])) {
    $warehouse_id = $conn->real_escape_string($_POST['warehouse_id']);
    $zone_code = $conn->real_escape_string($_POST['zone_code']);
    $zone_name = $conn->real_escape_string($_POST['zone_name']);
    $description = $conn->real_escape_string($_POST['description']);

    // Kiểm tra mã khu vực đã tồn tại chưa
    $check_sql = "SELECT COUNT(*) AS count FROM warehouse_zones WHERE warehouse_id = $warehouse_id AND zone_code = '$zone_code'";
    $check_result = $conn->query($check_sql);
    $check_data = $check_result->fetch_assoc();
    
    if ($check_data['count'] > 0) {
        showAlert("Mã khu vực đã tồn tại trong kho này!", 'danger');
    } else {
        $sql = "INSERT INTO warehouse_zones (warehouse_id, zone_code, zone_name, description) 
                VALUES ($warehouse_id, '$zone_code', '$zone_name', '$description')";
        
        if ($conn->query($sql)) {
            showAlert("Thêm khu vực mới thành công!");
        } else {
            showAlert("Lỗi: " . $conn->error, 'danger');
        }
    }
}

// Xử lý cập nhật khu vực
if (isset($_POST['update_zone'])) {
    $zone_id = $conn->real_escape_string($_POST['zone_id']);
    $zone_code = $conn->real_escape_string($_POST['zone_code']);
    $zone_name = $conn->real_escape_string($_POST['zone_name']);
    $description = $conn->real_escape_string($_POST['description']);

    $sql = "UPDATE warehouse_zones SET 
            zone_code = '$zone_code', 
            zone_name = '$zone_name', 
            description = '$description' 
            WHERE zone_id = $zone_id";
    
    if ($conn->query($sql)) {
        showAlert("Cập nhật khu vực thành công!");
    } else {
        showAlert("Lỗi: " . $conn->error, 'danger');
    }
}

// Xử lý xóa khu vực
if ($action == 'delete_zone' && !empty($id)) {
    // Kiểm tra xem khu vực có dữ liệu liên quan không
    $check_sql = "SELECT COUNT(*) AS count FROM shelves WHERE zone_id = $id";
    $check_result = $conn->query($check_sql);
    $check_data = $check_result->fetch_assoc();
    
    if ($check_data['count'] > 0) {
        showAlert("Không thể xóa khu vực này vì có dữ liệu kệ liên quan!", 'danger');
    } else {
        $sql = "DELETE FROM warehouse_zones WHERE zone_id = $id";
        if ($conn->query($sql)) {
            showAlert("Xóa khu vực thành công!");
        } else {
            showAlert("Lỗi: " . $conn->error, 'danger');
        }
    }
}

// Xử lý thêm mới kệ
if (isset($_POST['add_shelf'])) {
    $zone_id = $conn->real_escape_string($_POST['zone_id']);
    $shelf_code = $conn->real_escape_string($_POST['shelf_code']);
    $position = $conn->real_escape_string($_POST['position']);
    $max_capacity = $conn->real_escape_string($_POST['max_capacity']);
    $capacity_unit = $conn->real_escape_string($_POST['capacity_unit']);
    $description = $conn->real_escape_string($_POST['description']);
    $status = $conn->real_escape_string($_POST['status']);

    // Kiểm tra mã kệ đã tồn tại chưa
    $check_sql = "SELECT COUNT(*) AS count FROM shelves WHERE zone_id = $zone_id AND shelf_code = '$shelf_code'";
    $check_result = $conn->query($check_sql);
    $check_data = $check_result->fetch_assoc();
    
    if ($check_data['count'] > 0) {
        showAlert("Mã kệ đã tồn tại trong khu vực này!", 'danger');
    } else {
        $sql = "INSERT INTO shelves (zone_id, shelf_code, position, max_capacity, capacity_unit, description, status) 
                VALUES ($zone_id, '$shelf_code', '$position', $max_capacity, '$capacity_unit', '$description', '$status')";
        
        if ($conn->query($sql)) {
            showAlert("Thêm kệ mới thành công!");
        } else {
            showAlert("Lỗi: " . $conn->error, 'danger');
        }
    }
}

// Xử lý cập nhật kệ
if (isset($_POST['update_shelf'])) {
    $shelf_id = $conn->real_escape_string($_POST['shelf_id']);
    $shelf_code = $conn->real_escape_string($_POST['shelf_code']);
    $position = $conn->real_escape_string($_POST['position']);
    $max_capacity = $conn->real_escape_string($_POST['max_capacity']);
    $capacity_unit = $conn->real_escape_string($_POST['capacity_unit']);
    $description = $conn->real_escape_string($_POST['description']);
    $status = $conn->real_escape_string($_POST['status']);

    $sql = "UPDATE shelves SET 
            shelf_code = '$shelf_code', 
            position = '$position', 
            max_capacity = $max_capacity, 
            capacity_unit = '$capacity_unit', 
            description = '$description', 
            status = '$status' 
            WHERE shelf_id = $shelf_id";
    
    if ($conn->query($sql)) {
        showAlert("Cập nhật kệ thành công!");
    } else {
        showAlert("Lỗi: " . $conn->error, 'danger');
    }
}

// Xử lý xóa kệ
if ($action == 'delete_shelf' && !empty($id)) {
    // Kiểm tra xem kệ có dữ liệu liên quan không
    $check_sql = "SELECT COUNT(*) AS count FROM product_locations WHERE shelf_id = $id";
    $check_result = $conn->query($check_sql);
    $check_data = $check_result->fetch_assoc();
    
    if ($check_data['count'] > 0) {
        showAlert("Không thể xóa kệ này vì có dữ liệu vị trí sản phẩm liên quan!", 'danger');
    } else {
        $sql = "DELETE FROM shelves WHERE shelf_id = $id";
        if ($conn->query($sql)) {
            showAlert("Xóa kệ thành công!");
        } else {
            showAlert("Lỗi: " . $conn->error, 'danger');
        }
    }
}

// Xử lý thêm mới vị trí sản phẩm
if (isset($_POST['add_location'])) {
    $product_id = $conn->real_escape_string($_POST['product_id']);
    $shelf_id = $conn->real_escape_string($_POST['shelf_id']);
    $batch_number = $conn->real_escape_string($_POST['batch_number']);
    $expiry_date = $conn->real_escape_string($_POST['expiry_date']);
    $quantity = $conn->real_escape_string($_POST['quantity']);

    // Kiểm tra sản phẩm và lô đã tồn tại chưa
    $check_sql = "SELECT COUNT(*) AS count FROM product_locations 
                  WHERE product_id = $product_id AND shelf_id = $shelf_id AND batch_number = '$batch_number'";
    $check_result = $conn->query($check_sql);
    $check_data = $check_result->fetch_assoc();
    
    if ($check_data['count'] > 0) {
        showAlert("Vị trí sản phẩm với lô này đã tồn tại!", 'danger');
    } else {
        $sql = "INSERT INTO product_locations (product_id, shelf_id, batch_number, expiry_date, quantity, entry_date) 
                VALUES ($product_id, $shelf_id, '$batch_number', '$expiry_date', $quantity, NOW())";
        
        if ($conn->query($sql)) {
            // Cập nhật tồn kho
            $update_sql = "SELECT zone_id FROM shelves WHERE shelf_id = $shelf_id";
            $result = $conn->query($update_sql);
            $shelf_data = $result->fetch_assoc();
            $zone_id = $shelf_data['zone_id'];
            
            $update_sql = "SELECT warehouse_id FROM warehouse_zones WHERE zone_id = $zone_id";
            $result = $conn->query($update_sql);
            $zone_data = $result->fetch_assoc();
            $warehouse_id = $zone_data['warehouse_id'];
            
            $update_sql = "INSERT INTO inventory (product_id, warehouse_id, quantity) 
                          VALUES ($product_id, $warehouse_id, $quantity)
                          ON DUPLICATE KEY UPDATE quantity = quantity + $quantity";
            
            if ($conn->query($update_sql)) {
                showAlert("Thêm vị trí sản phẩm mới thành công và cập nhật tồn kho!");
            } else {
                showAlert("Thêm vị trí thành công nhưng cập nhật tồn kho thất bại: " . $conn->error, 'warning');
            }
        } else {
            showAlert("Lỗi: " . $conn->error, 'danger');
        }
    }
}

// Xử lý cập nhật vị trí sản phẩm
if (isset($_POST['update_location'])) {
    $location_id = $conn->real_escape_string($_POST['location_id']);
    $batch_number = $conn->real_escape_string($_POST['batch_number']);
    $expiry_date = $conn->real_escape_string($_POST['expiry_date']);
    $quantity = $conn->real_escape_string($_POST['quantity']);
    
    // Lấy số lượng hiện tại để tính chênh lệch
    $check_sql = "SELECT quantity, product_id, shelf_id FROM product_locations WHERE location_id = $location_id";
    $check_result = $conn->query($check_sql);
    $location_data = $check_result->fetch_assoc();
    $old_quantity = $location_data['quantity'];
    $product_id = $location_data['product_id'];
    $shelf_id = $location_data['shelf_id'];
    $quantity_diff = $quantity - $old_quantity;

    $sql = "UPDATE product_locations SET 
            batch_number = '$batch_number', 
            expiry_date = '$expiry_date', 
            quantity = $quantity
            WHERE location_id = $location_id";
    
    if ($conn->query($sql)) {
        // Cập nhật tồn kho nếu có thay đổi số lượng
        if ($quantity_diff != 0) {
            $update_sql = "SELECT zone_id FROM shelves WHERE shelf_id = $shelf_id";
            $result = $conn->query($update_sql);
            $shelf_data = $result->fetch_assoc();
            $zone_id = $shelf_data['zone_id'];
            
            $update_sql = "SELECT warehouse_id FROM warehouse_zones WHERE zone_id = $zone_id";
            $result = $conn->query($update_sql);
            $zone_data = $result->fetch_assoc();
            $warehouse_id = $zone_data['warehouse_id'];
            
            $update_sql = "UPDATE inventory SET quantity = quantity + $quantity_diff 
                           WHERE product_id = $product_id AND warehouse_id = $warehouse_id";
            
            if ($conn->query($update_sql)) {
                showAlert("Cập nhật vị trí sản phẩm và tồn kho thành công!");
            } else {
                showAlert("Cập nhật vị trí thành công nhưng cập nhật tồn kho thất bại: " . $conn->error, 'warning');
            }
        } else {
            showAlert("Cập nhật vị trí sản phẩm thành công!");
        }
    } else {
        showAlert("Lỗi: " . $conn->error, 'danger');
    }
}

// Xử lý xóa vị trí sản phẩm
if ($action == 'delete_location' && !empty($id)) {
    // Lấy thông tin vị trí để cập nhật tồn kho
    $check_sql = "SELECT product_id, shelf_id, quantity FROM product_locations WHERE location_id = $id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $location_data = $check_result->fetch_assoc();
        $product_id = $location_data['product_id'];
        $shelf_id = $location_data['shelf_id'];
        $quantity = $location_data['quantity'];
        
        $delete_sql = "DELETE FROM product_locations WHERE location_id = $id";
        
        if ($conn->query($delete_sql)) {
            // Cập nhật giảm tồn kho
            $update_sql = "SELECT zone_id FROM shelves WHERE shelf_id = $shelf_id";
            $result = $conn->query($update_sql);
            $shelf_data = $result->fetch_assoc();
            $zone_id = $shelf_data['zone_id'];
            
            $update_sql = "SELECT warehouse_id FROM warehouse_zones WHERE zone_id = $zone_id";
            $result = $conn->query($update_sql);
            $zone_data = $result->fetch_assoc();
            $warehouse_id = $zone_data['warehouse_id'];
            
            $update_sql = "UPDATE inventory SET quantity = quantity - $quantity 
                          WHERE product_id = $product_id AND warehouse_id = $warehouse_id";
            
            if ($conn->query($update_sql)) {
                showAlert("Xóa vị trí sản phẩm và cập nhật tồn kho thành công!");
            } else {
                showAlert("Xóa vị trí thành công nhưng cập nhật tồn kho thất bại: " . $conn->error, 'warning');
            }
        } else {
            showAlert("Lỗi: " . $conn->error, 'danger');
        }
    } else {
        showAlert("Không tìm thấy vị trí sản phẩm!", 'danger');
    }
}

// Hàm gợi ý vị trí kệ phù hợp
function suggestShelfLocation($product_id, $warehouse_id, $quantity) {
    global $conn;
    
    $result = [];
    
    // Gọi procedure suggest_shelf_location
    $sql = "CALL suggest_shelf_location($product_id, $warehouse_id, $quantity)";
    $query = $conn->query($sql);
    
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $result[] = $row;
        }
        $query->free();
    }
    
    return $result;
}
?>

<div class="function-container">
    <h4 class="page-title">Quản lý kho hàng</h4>
    
    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="warehouseTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo ($tab == 'kho') ? 'active' : ''; ?>" id="warehouse-tab" data-bs-toggle="tab" data-bs-target="#kho" type="button" role="tab" aria-controls="kho" aria-selected="<?php echo $tab == 'kho' ? 'true' : 'false'; ?>">
                Kho hàng
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo ($tab == 'khu_vuc') ? 'active' : ''; ?>" id="zone-tab" data-bs-toggle="tab" data-bs-target="#khu_vuc" type="button" role="tab" aria-controls="khu_vuc" aria-selected="<?php echo $tab == 'khu_vuc' ? 'true' : 'false'; ?>">
                Khu vực kho
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo ($tab == 'ke') ? 'active' : ''; ?>" id="shelf-tab" data-bs-toggle="tab" data-bs-target="#ke" type="button" role="tab" aria-controls="ke" aria-selected="<?php echo $tab == 'ke' ? 'true' : 'false'; ?>">
                Kệ kho
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo ($tab == 'vi_tri') ? 'active' : ''; ?>" id="location-tab" data-bs-toggle="tab" data-bs-target="#vi_tri" type="button" role="tab" aria-controls="vi_tri" aria-selected="<?php echo $tab == 'vi_tri' ? 'true' : 'false'; ?>">
                Vị trí sản phẩm
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo ($tab == 'bao_cao') ? 'active' : ''; ?>" id="report-tab" data-bs-toggle="tab" data-bs-target="#bao_cao" type="button" role="tab" aria-controls="bao_cao" aria-selected="<?php echo $tab == 'bao_cao' ? 'true' : 'false'; ?>">
                Báo cáo kho
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="warehouseTabsContent">
        <!-- Tab Kho hàng -->
        <div class="tab-pane fade <?php echo ($tab == 'kho') ? 'show active' : ''; ?>" id="kho" role="tabpanel" aria-labelledby="warehouse-tab">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5>Danh sách kho hàng</h5>
                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addWarehouseModal">
                    <i class="fas fa-plus-circle me-2"></i>Thêm kho mới
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="20%">Tên kho</th>
                            <th width="35%">Địa chỉ</th>
                            <th width="15%">Người liên hệ</th>
                            <th width="15%">Số điện thoại</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM warehouses ORDER BY warehouse_id DESC";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['warehouse_id']}</td>
                                        <td>{$row['warehouse_name']}</td>
                                        <td>{$row['address']}</td>
                                        <td>{$row['contact_person']}</td>
                                        <td>{$row['contact_phone']}</td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='btn btn-edit' onclick='editWarehouse({$row['warehouse_id']})' 
                                                    data-id='{$row['warehouse_id']}' 
                                                    data-name='{$row['warehouse_name']}'
                                                    data-address='{$row['address']}'
                                                    data-contact='{$row['contact_person']}'
                                                    data-phone='{$row['contact_phone']}'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <button class='btn btn-delete' onclick='confirmDelete({$row['warehouse_id']}, \"warehouse\")'>
                                                    <i class='fas fa-trash-alt'></i>
                                                </button>
                                            </div>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>Không có dữ liệu kho hàng</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab Khu vực kho -->
        <div class="tab-pane fade <?php echo ($tab == 'khu_vuc') ? 'show active' : ''; ?>" id="khu_vuc" role="tabpanel" aria-labelledby="zone-tab">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5>Danh sách khu vực kho</h5>
                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addZoneModal">
                    <i class="fas fa-plus-circle me-2"></i>Thêm khu vực mới
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Mã khu vực</th>
                            <th width="20%">Tên khu vực</th>
                            <th width="25%">Kho</th>
                            <th width="25%">Mô tả</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT wz.*, w.warehouse_name 
                                FROM warehouse_zones wz
                                JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
                                ORDER BY wz.zone_id DESC";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['zone_id']}</td>
                                        <td>{$row['zone_code']}</td>
                                        <td>{$row['zone_name']}</td>
                                        <td>{$row['warehouse_name']}</td>
                                        <td>{$row['description']}</td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='btn btn-edit' onclick='editZone({$row['zone_id']})' 
                                                    data-id='{$row['zone_id']}' 
                                                    data-code='{$row['zone_code']}'
                                                    data-name='{$row['zone_name']}'
                                                    data-description='{$row['description']}'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <button class='btn btn-delete' onclick='confirmDelete({$row['zone_id']}, \"zone\")'>
                                                    <i class='fas fa-trash-alt'></i>
                                                </button>
                                            </div>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>Không có dữ liệu khu vực kho</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab Kệ kho -->
        <div class="tab-pane fade <?php echo ($tab == 'ke') ? 'show active' : ''; ?>" id="ke" role="tabpanel" aria-labelledby="shelf-tab">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5>Danh sách kệ kho</h5>
                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addShelfModal">
                    <i class="fas fa-plus-circle me-2"></i>Thêm kệ mới
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="10%">Mã kệ</th>
                            <th width="15%">Vị trí</th>
                            <th width="10%">Sức chứa tối đa</th>
                            <th width="10%">Đơn vị</th>
                            <th width="20%">Khu vực</th>
                            <th width="15%">Trạng thái</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT s.*, wz.zone_code, wz.zone_name, w.warehouse_name
                                FROM shelves s
                                JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                                JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
                                ORDER BY s.shelf_id DESC";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status_class = '';
                                switch ($row['status']) {
                                    case 'ACTIVE':
                                        $status_class = 'status-active';
                                        $status_text = 'Hoạt động';
                                        break;
                                    case 'INACTIVE':
                                        $status_class = 'status-inactive';
                                        $status_text = 'Không hoạt động';
                                        break;
                                    case 'MAINTENANCE':
                                        $status_class = 'status-maintenance';
                                        $status_text = 'Bảo trì';
                                        break;
                                }
                                
                                echo "<tr>
                                        <td>{$row['shelf_id']}</td>
                                        <td>{$row['shelf_code']}</td>
                                        <td>{$row['position']}</td>
                                        <td>{$row['max_capacity']}</td>
                                        <td>" . ($row['capacity_unit'] == 'VOLUME' ? 'Thể tích' : 'Số lượng') . "</td>
                                        <td>{$row['zone_code']} - {$row['zone_name']} ({$row['warehouse_name']})</td>
                                        <td><span class='status-badge $status_class'>$status_text</span></td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='btn btn-edit' onclick='editShelf({$row['shelf_id']})' 
                                                    data-id='{$row['shelf_id']}'
                                                    data-code='{$row['shelf_code']}'
                                                    data-position='{$row['position']}'
                                                    data-max-capacity='{$row['max_capacity']}'
                                                    data-capacity-unit='{$row['capacity_unit']}'
                                                    data-description='{$row['description']}'
                                                    data-status='{$row['status']}'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <button class='btn btn-delete' onclick='confirmDelete({$row['shelf_id']}, \"shelf\")'>
                                                    <i class='fas fa-trash-alt'></i>
                                                </button>
                                            </div>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center'>Không có dữ liệu kệ kho</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab Vị trí sản phẩm -->
        <div class="tab-pane fade <?php echo ($tab == 'vi_tri') ? 'show active' : ''; ?>" id="vi_tri" role="tabpanel" aria-labelledby="location-tab">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5>Danh sách vị trí sản phẩm</h5>
                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                    <i class="fas fa-plus-circle me-2"></i>Thêm vị trí mới
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Sản phẩm</th>
                            <th width="10%">Mã lô</th>
                            <th width="10%">Hạn sử dụng</th>
                            <th width="10%">Số lượng</th>
                            <th width="15%">Kệ</th>
                            <th width="15%">Khu vực - Kho</th>
                            <th width="10%">Ngày nhập</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT pl.*, p.product_name, p.product_code, s.shelf_code, 
                                wz.zone_code, wz.zone_name, w.warehouse_name
                                FROM product_locations pl
                                JOIN products p ON pl.product_id = p.product_id
                                JOIN shelves s ON pl.shelf_id = s.shelf_id
                                JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
                                JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
                                ORDER BY pl.entry_date DESC";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['location_id']}</td>
                                        <td>{$row['product_code']} - {$row['product_name']}</td>
                                        <td>{$row['batch_number']}</td>
                                        <td>" . ($row['expiry_date'] ? date('d/m/Y', strtotime($row['expiry_date'])) : 'N/A') . "</td>
                                        <td>{$row['quantity']}</td>
                                        <td>{$row['shelf_code']}</td>
                                        <td>{$row['zone_code']} - {$row['warehouse_name']}</td>
                                        <td>" . date('d/m/Y', strtotime($row['entry_date'])) . "</td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='btn btn-edit' onclick='editLocation({$row['location_id']})' 
                                                    data-id='{$row['location_id']}'
                                                    data-batch='{$row['batch_number']}'
                                                    data-expiry='" . ($row['expiry_date'] ? $row['expiry_date'] : '') . "'
                                                    data-quantity='{$row['quantity']}'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <button class='btn btn-delete' onclick='confirmDelete({$row['location_id']}, \"location\")'>
                                                    <i class='fas fa-trash-alt'></i>
                                                </button>
                                            </div>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center'>Không có dữ liệu vị trí sản phẩm</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab Báo cáo kho -->
        <div class="tab-pane fade <?php echo ($tab == 'bao_cao') ? 'show active' : ''; ?>" id="bao_cao" role="tabpanel" aria-labelledby="report-tab">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Báo cáo tồn kho theo kho</h5>
                        </div>
                        <div class="card-body">
                            <form id="inventoryReportForm" class="mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="report_warehouse">Chọn kho</label>
                                            <select class="form-select" id="report_warehouse">
                                                <option value="">Tất cả các kho</option>
                                                <?php
                                                $warehouse_sql = "SELECT * FROM warehouses ORDER BY warehouse_name";
                                                $warehouse_result = $conn->query($warehouse_sql);
                                                
                                                if ($warehouse_result->num_rows > 0) {
                                                    while ($warehouse = $warehouse_result->fetch_assoc()) {
                                                        echo "<option value='{$warehouse['warehouse_id']}'>{$warehouse['warehouse_name']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="stock_level">Mức tồn kho</label>
                                            <select class="form-select" id="stock_level">
                                                <option value="">Tất cả</option>
                                                <option value="Thấp">Thấp</option>
                                                <option value="Trung bình">Trung bình</option>
                                                <option value="Cao">Cao</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="generateInventoryReport" class="btn btn-primary mt-3">
                                    <i class="fas fa-chart-bar me-2"></i>Tạo báo cáo
                                </button>
                            </form>
                            <div id="inventoryReportResult"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Báo cáo kệ và mức độ sử dụng</h5>
                        </div>
                        <div class="card-body">
                            <form id="shelfUtilizationForm" class="mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="report_shelf_warehouse">Chọn kho</label>
                                            <select class="form-select" id="report_shelf_warehouse">
                                                <option value="">Tất cả các kho</option>
                                                <?php
                                                $warehouse_sql = "SELECT * FROM warehouses ORDER BY warehouse_name";
                                                $warehouse_result = $conn->query($warehouse_sql);
                                                
                                                if ($warehouse_result->num_rows > 0) {
                                                    while ($warehouse = $warehouse_result->fetch_assoc()) {
                                                        echo "<option value='{$warehouse['warehouse_id']}'>{$warehouse['warehouse_name']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="utilization_level">Mức sử dụng</label>
                                            <select class="form-select" id="utilization_level">
                                                <option value="">Tất cả</option>
                                                <option value="Thấp">Thấp (< 30%)</option>
                                                <option value="Trung bình">Trung bình (30-70%)</option>
                                                <option value="Cao">Cao (> 70%)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="generateShelfReport" class="btn btn-primary mt-3">
                                    <i class="fas fa-chart-bar me-2"></i>Tạo báo cáo
                                </button>
                            </form>
                            <div id="shelfReportResult"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Sản phẩm sắp hết hạn</h5>
                        </div>
                        <div class="card-body">
                            <form id="expiryReportForm" class="mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="days_threshold">Ngưỡng ngày sắp hết hạn</label>
                                            <select class="form-select" id="days_threshold">
                                                <option value="7">7 ngày</option>
                                                <option value="15">15 ngày</option>
                                                <option value="30" selected>30 ngày</option>
                                                <option value="60">60 ngày</option>
                                                <option value="90">90 ngày</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expiry_warehouse">Chọn kho</label>
                                            <select class="form-select" id="expiry_warehouse">
                                                <option value="">Tất cả các kho</option>
                                                <?php
                                                $warehouse_sql = "SELECT * FROM warehouses ORDER BY warehouse_name";
                                                $warehouse_result = $conn->query($warehouse_sql);
                                                
                                                if ($warehouse_result->num_rows > 0) {
                                                    while ($warehouse = $warehouse_result->fetch_assoc()) {
                                                        echo "<option value='{$warehouse['warehouse_id']}'>{$warehouse['warehouse_name']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="generateExpiryReport" class="btn btn-primary mt-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Tạo báo cáo
                                </button>
                            </form>
                            <div id="expiryReportResult"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="delete_message">Bạn có chắc chắn muốn xóa mục này không?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <a href="#" id="delete_confirm_btn" class="btn btn-danger">Xác nhận xóa</a>
            </div>
        </div>
    </div>
</div>

<script>
// Xử lý sửa kho
function editWarehouse(id) {
    document.getElementById('edit_warehouse_id').value = id;
    document.getElementById('edit_warehouse_name').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-name');
    document.getElementById('edit_address').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-address');
    document.getElementById('edit_contact_person').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-contact');
    document.getElementById('edit_contact_phone').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-phone');
    
    var editModal = new bootstrap.Modal(document.getElementById('editWarehouseModal'));
    editModal.show();
}

// Xử lý sửa khu vực
function editZone(id) {
    document.getElementById('edit_zone_id').value = id;
    document.getElementById('edit_zone_code').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-code');
    document.getElementById('edit_zone_name').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-name');
    document.getElementById('edit_description').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-description');
    
    var editModal = new bootstrap.Modal(document.getElementById('editZoneModal'));
    editModal.show();
}

// Xử lý sửa kệ
function editShelf(id) {
    document.getElementById('edit_shelf_id').value = id;
    document.getElementById('edit_shelf_code').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-code');
    document.getElementById('edit_position').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-position');
    document.getElementById('edit_max_capacity').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-max-capacity');
    document.getElementById('edit_capacity_unit').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-capacity-unit');
    document.getElementById('edit_description').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-description');
    document.getElementById('edit_status').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-status');
    
    var editModal = new bootstrap.Modal(document.getElementById('editShelfModal'));
    editModal.show();
}

// Xử lý sửa vị trí sản phẩm
function editLocation(id) {
    document.getElementById('edit_location_id').value = id;
    document.getElementById('edit_batch_number').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-batch');
    document.getElementById('edit_expiry_date').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-expiry');
    document.getElementById('edit_quantity').value = document.querySelector(`button[data-id="${id}"]`).getAttribute('data-quantity');
    
    var editModal = new bootstrap.Modal(document.getElementById('editLocationModal'));
    editModal.show();
}

// Xử lý xác nhận xóa
function confirmDelete(id, type) {
    let message = 'Bạn có chắc chắn muốn xóa mục này không?';
    let url = '';
    
    switch(type) {
        case 'warehouse':
            message = 'Bạn có chắc chắn muốn xóa kho này? Điều này sẽ xóa tất cả dữ liệu liên quan.';
            url = `?option=kho&tab=kho&action=delete_warehouse&id=${id}`;
            break;
        case 'zone':
            message = 'Bạn có chắc chắn muốn xóa khu vực này? Điều này sẽ xóa tất cả dữ liệu liên quan.';
            url = `?option=kho&tab=khu_vuc&action=delete_zone&id=${id}`;
            break;
        case 'shelf':
            message = 'Bạn có chắc chắn muốn xóa kệ này? Điều này sẽ xóa tất cả dữ liệu liên quan.';
            url = `?option=kho&tab=ke&action=delete_shelf&id=${id}`;
            break;
        case 'location':
            message = 'Bạn có chắc chắn muốn xóa vị trí sản phẩm này? Điều này sẽ cập nhật lại số lượng tồn kho.';
            url = `?option=kho&tab=vi_tri&action=delete_location&id=${id}`;
            break;
    }
    
    document.getElementById('delete_message').textContent = message;
    document.getElementById('delete_confirm_btn').setAttribute('href', url);
    
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.show();
}

// Load khu vực khi chọn kho
document.getElementById('warehouse_selector')?.addEventListener('change', function() {
    const warehouseId = this.value;
    const zoneSelector = document.getElementById('zone_selector');
    
    zoneSelector.innerHTML = '<option value="">-- Chọn khu vực --</option>';
    zoneSelector.disabled = true;
    
    document.getElementById('shelf_id').innerHTML = '<option value="">-- Chọn kệ --</option>';
    document.getElementById('shelf_id').disabled = true;
    
    if (warehouseId) {
        // Gọi AJAX để lấy danh sách khu vực
        fetch(`ajax/thongtin/layKhuVuc.php?warehouse_id=${warehouseId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    data.forEach(zone => {
                        const option = document.createElement('option');
                        option.value = zone.zone_id;
                        option.textContent = `${zone.zone_code} - ${zone.zone_name}`;
                        zoneSelector.appendChild(option);
                    });
                    zoneSelector.disabled = false;
                }
            })
            .catch(error => console.error('Error:', error));
    }
});

// Load kệ khi chọn khu vực
document.getElementById('zone_selector')?.addEventListener('change', function() {
    const zoneId = this.value;
    const shelfSelector = document.getElementById('shelf_id');
    
    shelfSelector.innerHTML = '<option value="">-- Chọn kệ --</option>';
    shelfSelector.disabled = true;
    
    if (zoneId) {
        // Gọi AJAX để lấy danh sách kệ
        fetch(`ajax/thongtin/layKe.php?zone_id=${zoneId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    data.forEach(shelf => {
                        const option = document.createElement('option');
                        option.value = shelf.shelf_id;
                        option.textContent = `${shelf.shelf_code} - ${shelf.position}`;
                        shelfSelector.appendChild(option);
                    });
                    shelfSelector.disabled = false;
                }
            })
            .catch(error => console.error('Error:', error));
    }
});

// Xử lý gợi ý vị trí kệ
document.getElementById('suggestButton')?.addEventListener('click', function() {
    const productId = document.getElementById('product_id').value;
    const warehouseId = document.getElementById('warehouse_selector').value;
    const quantity = document.getElementById('quantity').value;
    
    if (!productId || !warehouseId || !quantity) {
        alert('Vui lòng chọn sản phẩm, kho và nhập số lượng để gợi ý vị trí');
        return;
    }
    
    // Gọi AJAX để lấy gợi ý vị trí
    fetch(`ajax/thongtin/deXuatViTri.php?product_id=${productId}&warehouse_id=${warehouseId}&quantity=${quantity}`)
        .then(response => response.json())
        .then(data => {
            const suggestionContainer = document.getElementById('suggestion_container');
            const suggestionContent = document.getElementById('suggestion_content');
            
            if (data.length > 0) {
                let html = '<ul class="list-group">';
                data.forEach(shelf => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        Kệ: ${shelf.shelf_code}
                        <span class="badge bg-primary rounded-pill">Sức chứa khả dụng: ${shelf.available_capacity} ${shelf.capacity_unit === 'VOLUME' ? 'dm³' : 'cái'}</span>
                        <button type="button" class="btn btn-sm btn-success select-shelf" data-shelf-id="${shelf.shelf_id}">Chọn</button>
                    </li>`;
                });
                html += '</ul>';
                
                suggestionContent.innerHTML = html;
                suggestionContainer.classList.remove('d-none');
                
                // Xử lý khi chọn kệ từ gợi ý
                document.querySelectorAll('.select-shelf').forEach(button => {
                    button.addEventListener('click', function() {
                        const shelfId = this.getAttribute('data-shelf-id');
                        document.getElementById('shelf_id').value = shelfId;
                        suggestionContainer.classList.add('d-none');
                    });
                });
            } else {
                suggestionContent.innerHTML = '<p class="text-danger">Không tìm thấy kệ phù hợp với số lượng sản phẩm này.</p>';
                suggestionContainer.classList.remove('d-none');
            }
        })
        .catch(error => console.error('Error:', error));
});

// Xử lý báo cáo tồn kho
document.getElementById('generateInventoryReport')?.addEventListener('click', function() {
    const warehouseId = document.getElementById('report_warehouse').value;
    const stockLevel = document.getElementById('stock_level').value;
    
    // Gọi AJAX để lấy báo cáo tồn kho
    fetch(`ajax/baocao/baoCaoTonKho.php?warehouse_id=${warehouseId}&stock_level=${stockLevel}`)
        .then(response => response.json())
        .then(data => {
            const reportContainer = document.getElementById('inventoryReportResult');
            
            if (data.length > 0) {
                let html = `<h6 class="mt-4">Kết quả (${data.length} sản phẩm)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Mã SP</th>
                                <th>Tên sản phẩm</th>
                                <th>Kho</th>
                                <th>Số lượng</th>
                                <th>Ngưỡng tối thiểu</th>
                                <th>Mức tồn kho</th>
                                <th>Giá trị (VNĐ)</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                data.forEach(item => {
                    let statusClass = '';
                    switch(item.stock_level) {
                        case 'Thấp':
                            statusClass = 'text-danger';
                            break;
                        case 'Trung bình':
                            statusClass = 'text-warning';
                            break;
                        case 'Cao':
                            statusClass = 'text-success';
                            break;
                    }
                    
                    html += `<tr>
                        <td>${item.product_code}</td>
                        <td>${item.product_name}</td>
                        <td>${item.warehouse_name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.minimum_stock}</td>
                        <td class="${statusClass}">${item.stock_level}</td>
                        <td>${new Intl.NumberFormat('vi-VN').format(item.total_value)}</td>
                    </tr>`;
                });
                
                html += `</tbody></table></div>
                <button type="button" class="btn btn-secondary mt-3" onclick="exportTableToExcel('inventoryReport')">
                    <i class="fas fa-file-excel me-2"></i>Xuất Excel
                </button>`;
                
                reportContainer.innerHTML = html;
            } else {
                reportContainer.innerHTML = '<div class="alert alert-info mt-4">Không có dữ liệu phù hợp với điều kiện tìm kiếm.</div>';
            }
        })
        .catch(error => console.error('Error:', error));
});

// Xử lý báo cáo kệ
document.getElementById('generateShelfReport')?.addEventListener('click', function() {
    const warehouseId = document.getElementById('report_shelf_warehouse').value;
    const utilizationLevel = document.getElementById('utilization_level').value;
    
    // Gọi AJAX để lấy báo cáo kệ
    fetch(`ajax/baocao/baoCaoKe.php?warehouse_id=${warehouseId}&utilization_level=${utilizationLevel}`)
        .then(response => response.json())
        .then(data => {
            const reportContainer = document.getElementById('shelfReportResult');
            
            if (data.length > 0) {
                let html = `<h6 class="mt-4">Kết quả (${data.length} kệ)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Mã kệ</th>
                                <th>Khu vực</th>
                                <th>Kho</th>
                                <th>Sức chứa tối đa</th>
                                <th>Đã sử dụng</th>
                                <th>Tỷ lệ sử dụng</th>
                                <th>Mức độ</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                data.forEach(item => {
                    let utilizationClass = '';
                    switch(item.utilization_level) {
                        case 'Thấp':
                            utilizationClass = 'text-success';
                            break;
                        case 'Trung bình':
                            utilizationClass = 'text-warning';
                            break;
                        case 'Cao':
                            utilizationClass = 'text-danger';
                            break;
                    }
                    
                    html += `<tr>
                        <td>${item.shelf_code}</td>
                        <td>${item.zone_code}</td>
                        <td>${item.warehouse_name}</td>
                        <td>${item.max_capacity}</td>
                        <td>${parseFloat(item.used_capacity).toFixed(2)}</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar ${utilizationClass === 'text-success' ? 'bg-success' : (utilizationClass === 'text-warning' ? 'bg-warning' : 'bg-danger')}" 
                                    role="progressbar" style="width: ${item.utilization_percentage}%;" 
                                    aria-valuenow="${item.utilization_percentage}" aria-valuemin="0" aria-valuemax="100">
                                    ${parseFloat(item.utilization_percentage).toFixed(1)}%
                                </div>
                            </div>
                        </td>
                        <td class="${utilizationClass}">${item.utilization_level}</td>
                    </tr>`;
                });
                
                html += `</tbody></table></div>
                <button type="button" class="btn btn-secondary mt-3" onclick="exportTableToExcel('shelfReport')">
                    <i class="fas fa-file-excel me-2"></i>Xuất Excel
                </button>`;
                
                reportContainer.innerHTML = html;
            } else {
                reportContainer.innerHTML = '<div class="alert alert-info mt-4">Không có dữ liệu phù hợp với điều kiện tìm kiếm.</div>';
            }
        })
        .catch(error => console.error('Error:', error));
});

// Xử lý báo cáo sản phẩm sắp hết hạn
document.getElementById('generateExpiryReport')?.addEventListener('click', function() {
    const daysThreshold = document.getElementById('days_threshold').value;
    const warehouseId = document.getElementById('expiry_warehouse').value;
    
    // Gọi AJAX để lấy báo cáo sản phẩm sắp hết hạn
    fetch(`ajax/baocao/baoCaoHetHan.php?days_threshold=${daysThreshold}&warehouse_id=${warehouseId}`)
        .then(response => response.json())
        .then(data => {
            const reportContainer = document.getElementById('expiryReportResult');
            
            if (data.length > 0) {
                let html = `<h6 class="mt-4">Kết quả (${data.length} sản phẩm)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Mã SP</th>
                                <th>Tên sản phẩm</th>
                                <th>Kho</th>
                                <th>Kệ</th>
                                <th>Mã lô</th>
                                <th>Hạn sử dụng</th>
                                <th>Số lượng</th>
                                <th>Còn lại</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                data.forEach(item => {
                    let daysClass = '';
                    if (item.days_until_expiry <= 7) {
                        daysClass = 'text-danger fw-bold';
                    } else if (item.days_until_expiry <= 15) {
                        daysClass = 'text-warning fw-bold';
                    } else {
                        daysClass = 'text-info';
                    }
                    
                    html += `<tr>
                        <td>${item.product_code}</td>
                        <td>${item.product_name}</td>
                        <td>${item.warehouse_name}</td>
                        <td>${item.shelf_code}</td>
                        <td>${item.batch_number}</td>
                        <td>${new Date(item.expiry_date).toLocaleDateString('vi-VN')}</td>
                        <td>${item.quantity}</td>
                        <td class="${daysClass}">${item.days_until_expiry} ngày</td>
                    </tr>`;
                });
                
                html += `</tbody></table></div>
                <button type="button" class="btn btn-secondary mt-3" onclick="exportTableToExcel('expiryReport')">
                    <i class="fas fa-file-excel me-2"></i>Xuất Excel
                </button>`;
                
                reportContainer.innerHTML = html;
            } else {
                reportContainer.innerHTML = '<div class="alert alert-info mt-4">Không có sản phẩm nào sắp hết hạn trong khoảng thời gian đã chọn.</div>';
            }
        })
        .catch(error => console.error('Error:', error));
});

// Hàm xuất báo cáo ra Excel
function exportTableToExcel(tableId, filename = '') {
    const table = document.querySelector(`#${tableId}Result table`);
    if (!table) return;
    
    const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
    
    const reportType = {
        'inventoryReport': 'Bao_cao_ton_kho',
        'shelfReport': 'Bao_cao_ke_kho',
        'expiryReport': 'Bao_cao_san_pham_sap_het_han'
    };
    
    const defaultFilename = reportType[tableId] ? reportType[tableId] : 'Bao_cao';
    filename = filename || `${defaultFilename}_${new Date().toISOString().slice(0, 10)}.xlsx`;
    
    XLSX.writeFile(wb, filename);
}

// JavaScript xử lý chuyển đổi tab
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý chuyển tab
    const warehouseTabs = document.querySelectorAll('#warehouseTabs .nav-link');
    warehouseTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            // Lấy tab được chọn
            const tabId = this.getAttribute('data-bs-target').substring(1);
            
            // Cập nhật URL với history API mà không reload trang
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('tab', tabId);
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            history.pushState({ tab: tabId }, '', newUrl);
        });
    });
    
    // Xử lý sự kiện popstate khi người dùng sử dụng nút back/forward trên trình duyệt
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.tab) {
            // Kích hoạt tab tương ứng
            const tabToActivate = document.querySelector(`#warehouseTabs .nav-link[data-bs-target="#${event.state.tab}"]`);
            if (tabToActivate) {
                const tab = new bootstrap.Tab(tabToActivate);
                tab.show();
            }
        }
    });
});
</script>

<!-- Thêm thư viện SheetJS để xuất Excel -->
<script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>