<div class="function-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">Quản lý sản phẩm</h4>
        <div>
            <button class="btn btn-add" onclick="showProductForm()">
                <i class="fas fa-plus-circle me-2"></i>Thêm sản phẩm
            </button>
        </div>
    </div>

    <!-- Tabs cho sản phẩm và danh mục -->
    <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="true">
                Danh sách sản phẩm
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab" aria-controls="categories" aria-selected="false">
                Danh mục sản phẩm
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab" aria-controls="alerts" aria-selected="false">
                Cảnh báo sản phẩm
            </button>
        </li>
    </ul>

    <!-- Nội dung các tab -->
    <div class="tab-content" id="productTabsContent">
        <!-- Tab danh sách sản phẩm -->
        <div class="tab-pane fade show active" id="products" role="tabpanel" aria-labelledby="products-tab">
            <!-- Bộ lọc sản phẩm -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <select class="form-select" id="categoryFilter">
                        <option value="">Tất cả danh mục</option>
                        <?php
                        // Lấy danh sách danh mục cha
                        $categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY category_name");
                        while ($category = $categories->fetch_assoc()) {
                            echo "<option value='{$category['category_id']}'>{$category['category_name']}</option>";
                            
                            // Lấy danh sách danh mục con
                            $subcategories = $conn->query("SELECT * FROM categories WHERE parent_id = {$category['category_id']} ORDER BY category_name");
                            while ($subcategory = $subcategories->fetch_assoc()) {
                                echo "<option value='{$subcategory['category_id']}' data-level='1'>-- {$subcategory['category_name']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="stockFilter">
                        <option value="">Tất cả trạng thái tồn kho</option>
                        <option value="low">Tồn kho thấp</option>
                        <option value="normal">Tồn kho bình thường</option>
                        <option value="high">Tồn kho cao</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchProduct" placeholder="Tìm kiếm mã, tên sản phẩm...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100" onclick="filterProducts()">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
            </div>

            <!-- Bảng danh sách sản phẩm -->
            <div class="table-responsive">
                <table class="data-table table">
                    <thead>
                        <tr>
                            <th width="5%">Mã SP</th>
                            <th width="8%">Hình ảnh</th>
                            <th width="20%">Tên sản phẩm</th>
                            <th width="15%">Danh mục</th>
                            <th width="10%">Đơn vị</th>
                            <th width="10%">Giá</th>
                            <th width="10%">Tồn kho</th>
                            <th width="10%">Barcode</th>
                            <th width="12%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="productsList">
                        <?php
                        // Lấy danh sách sản phẩm với thông tin danh mục và tồn kho
                        $sql = "SELECT p.*, c.category_name, 
                                IFNULL(SUM(i.quantity), 0) as total_stock
                                FROM products p
                                LEFT JOIN categories c ON p.category_id = c.category_id
                                LEFT JOIN inventory i ON p.product_id = i.product_id
                                GROUP BY p.product_id
                                ORDER BY p.product_code";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                // Xác định trạng thái tồn kho
                                $stock_status = '';
                                $stock_text = '';
                                
                                if ($row['total_stock'] <= $row['minimum_stock']) {
                                    $stock_status = 'bg-danger';
                                    $stock_text = 'Thấp';
                                } elseif ($row['total_stock'] <= $row['minimum_stock'] * 1.5) {
                                    $stock_status = 'bg-warning';
                                    $stock_text = 'Trung bình';
                                } else {
                                    $stock_status = 'bg-success';
                                    $stock_text = 'Cao';
                                }
                                
                                // Hiển thị hình ảnh sản phẩm
                                $image = !empty($row['image_url']) ? $row['image_url'] : 'image/products/no-image.jpg';
                                
                                echo "<tr>
                                    <td>{$row['product_code']}</td>
                                    <td><img src='{$image}' alt='{$row['product_name']}' width='50' height='50' class='rounded'></td>
                                    <td>{$row['product_name']}</td>
                                    <td>{$row['category_name']}</td>
                                    <td>{$row['unit']}</td>
                                    <td>" . number_format($row['price'], 0, ',', '.') . " đ</td>
                                    <td>
                                        <span class='badge $stock_status'>{$row['total_stock']} ({$stock_text})</span>
                                    </td>
                                    <td>{$row['barcode']}</td>
                                    <td class='action-buttons'>
                                        <button class='btn btn-sm btn-info' onclick='viewProduct({$row['product_id']})'>
                                            <i class='fas fa-eye'></i>
                                        </button>
                                        <button class='btn btn-sm btn-primary ms-1' onclick='editProduct({$row['product_id']})'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <button class='btn btn-sm btn-danger ms-1' onclick='deleteProduct({$row['product_id']})'>
                                            <i class='fas fa-trash'></i>
                                        </button>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center'>Không có sản phẩm nào</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab danh mục sản phẩm -->
        <div class="tab-pane fade" id="categories" role="tabpanel" aria-labelledby="categories-tab">
            <!-- Bộ lọc danh mục -->
            <div class="row mb-4">
                <div class="col-md-4 offset-md-6">
                    <input type="text" class="form-control" id="searchCategory" placeholder="Tìm kiếm danh mục...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-add w-100" onclick="showCategoryForm()">
                        <i class="fas fa-plus-circle me-2"></i>Thêm danh mục
                    </button>
                </div>
            </div>

            <!-- Bảng danh sách danh mục -->
            <div class="table-responsive">
                <table class="data-table table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="25%">Tên danh mục</th>
                            <th width="40%">Mô tả</th>
                            <th width="15%">Danh mục cha</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesList">
                        <?php
                        // Lấy danh sách danh mục
                        $sql = "SELECT c.*, p.category_name as parent_name 
                                FROM categories c 
                                LEFT JOIN categories p ON c.parent_id = p.category_id
                                ORDER BY c.parent_id IS NULL DESC, c.category_name";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $parent_name = $row['parent_name'] ? $row['parent_name'] : 'Không có';
                                
                                echo "<tr>
                                    <td>{$row['category_id']}</td>
                                    <td>{$row['category_name']}</td>
                                    <td>{$row['description']}</td>
                                    <td>{$parent_name}</td>
                                    <td class='action-buttons'>
                                        <button class='btn btn-sm btn-primary' onclick='editCategory({$row['category_id']})'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <button class='btn btn-sm btn-danger ms-1' onclick='deleteCategory({$row['category_id']})'>
                                            <i class='fas fa-trash'></i>
                                        </button>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>Không có danh mục nào</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab cảnh báo sản phẩm -->
        <div class="tab-pane fade" id="alerts" role="tabpanel" aria-labelledby="alerts-tab">
            <div class="row">
                <!-- Cảnh báo tồn kho thấp -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Sản phẩm tồn kho thấp</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mã SP</th>
                                            <th>Tên sản phẩm</th>
                                            <th>Tồn kho</th>
                                            <th>Ngưỡng tối thiểu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Lấy danh sách sản phẩm tồn kho thấp
                                        $sql = "SELECT p.*, c.category_name, 
                                            IFNULL(SUM(i.quantity), 0) as total_stock
                                            FROM products p
                                            LEFT JOIN categories c ON p.category_id = c.category_id
                                            LEFT JOIN inventory i ON p.product_id = i.product_id
                                            GROUP BY p.product_id, p.product_code, p.product_name, p.image_url, p.description, p.category_id, p.unit, p.price, 
                                            p.volume, p.dimensions, p.weight, p.barcode, p.minimum_stock, p.created_by, p.created_at, p.updated_at, c.category_name
                                            HAVING IFNULL(SUM(i.quantity), 0) <= p.minimum_stock
                                            ORDER BY (IFNULL(SUM(i.quantity), 0) / p.minimum_stock) ASC
                                            LIMIT 10";
                                        $result = $conn->query($sql);

                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>
                                                    <td>{$row['product_code']}</td>
                                                    <td>{$row['product_name']}</td>
                                                    <td><span class='badge bg-danger'>{$row['total_stock']}</span></td>
                                                    <td>{$row['minimum_stock']}</td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center'>Không có sản phẩm tồn kho thấp</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cảnh báo sản phẩm gần hết hạn -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Sản phẩm gần hết hạn</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mã SP</th>
                                            <th>Tên sản phẩm</th>
                                            <th>Lô hàng</th>
                                            <th>Hạn sử dụng</th>
                                            <th>Còn lại</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Lấy danh sách sản phẩm gần hết hạn (30 ngày)
                                        $sql = "SELECT p.product_code, p.product_name, pl.batch_number, pl.expiry_date,
                                                DATEDIFF(pl.expiry_date, CURDATE()) as days_remaining
                                                FROM product_locations pl
                                                JOIN products p ON pl.product_id = p.product_id
                                                WHERE pl.expiry_date IS NOT NULL
                                                AND pl.expiry_date > CURDATE()
                                                AND DATEDIFF(pl.expiry_date, CURDATE()) <= 30
                                                ORDER BY days_remaining ASC
                                                LIMIT 10";
                                        $result = $conn->query($sql);

                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $badge_class = $row['days_remaining'] <= 7 ? 'bg-danger' : 'bg-warning';
                                                
                                                echo "<tr>
                                                    <td>{$row['product_code']}</td>
                                                    <td>{$row['product_name']}</td>
                                                    <td>{$row['batch_number']}</td>
                                                    <td>" . date('d/m/Y', strtotime($row['expiry_date'])) . "</td>
                                                    <td><span class='badge {$badge_class}'>{$row['days_remaining']} ngày</span></td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center'>Không có sản phẩm gần hết hạn</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal thêm/sửa sản phẩm -->
<div class="custom-modal" id="productModal">
    <div class="modal-content" style="width: 800px; max-width: 90%;">
        <div class="modal-header">
            <h5 class="modal-title" id="productModalTitle">Thêm sản phẩm mới</h5>
            <button type="button" class="modal-close" onclick="closeProductModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="productId" name="productId" value="0">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="productCode">Mã sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="productCode" name="productCode" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="productName">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="productName" name="productName" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="categoryId">Danh mục <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoryId" name="categoryId" required>
                                <option value="">Chọn danh mục</option>
                                <?php
                                // Lấy danh sách danh mục cha
                                $categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY category_name");
                                while ($category = $categories->fetch_assoc()) {
                                    echo "<option value='{$category['category_id']}'>{$category['category_name']}</option>";
                                    
                                    // Lấy danh sách danh mục con
                                    $subcategories = $conn->query("SELECT * FROM categories WHERE parent_id = {$category['category_id']} ORDER BY category_name");
                                    while ($subcategory = $subcategories->fetch_assoc()) {
                                        echo "<option value='{$subcategory['category_id']}' data-level='1'>-- {$subcategory['category_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="unit">Đơn vị <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="unit" name="unit" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="price">Giá <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="1000" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="minimumStock">Tồn kho tối thiểu</label>
                            <input type="number" class="form-control" id="minimumStock" name="minimumStock" min="0" value="10">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="barcode">Mã barcode/RFID</label>
                            <input type="text" class="form-control" id="barcode" name="barcode">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="dimensions">Kích thước (DxRxC)</label>
                            <input type="text" class="form-control" id="dimensions" name="dimensions" placeholder="Ví dụ: 100x50x20">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="weight">Trọng lượng (kg)</label>
                            <input type="number" class="form-control" id="weight" name="weight" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="volume">Thể tích (dm³)</label>
                            <input type="number" class="form-control" id="volume" name="volume" min="0" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="description">Mô tả sản phẩm</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group mb-3">
                    <label for="productImage">Hình ảnh sản phẩm</label>
                    <div class="image-preview-container" onclick="document.getElementById('productImage').click()">
                        <img id="imagePreview" class="image-preview" src="" alt="Preview">
                        <span id="preview_placeholder">Nhấp để chọn ảnh</span>
                    </div>
                    <input type="file" class="form-control d-none" id="productImage" name="productImage" accept="image/*" onchange="previewImage(this)">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveProduct()">Lưu</button>
        </div>
    </div>
</div>

<!-- Modal thêm/sửa danh mục -->
<div class="custom-modal" id="categoryModal">
    <div class="modal-content" style="width: 500px; max-width: 90%;">
        <div class="modal-header">
            <h5 class="modal-title" id="categoryModalTitle">Thêm danh mục mới</h5>
            <button type="button" class="modal-close" onclick="closeCategoryModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="categoryForm">
                <input type="hidden" id="categoryId" name="categoryId" value="0">
                
                <div class="form-group mb-3">
                    <label for="categoryName">Tên danh mục <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="categoryName" name="categoryName" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="parentId">Danh mục cha</label>
                    <select class="form-select" id="parentId" name="parentId">
                        <option value="">Không có (Danh mục gốc)</option>
                        <?php
                        // Lấy danh sách danh mục cha
                        $categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY category_name");
                        while ($category = $categories->fetch_assoc()) {
                            echo "<option value='{$category['category_id']}'>{$category['category_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label for="categoryDescription">Mô tả</label>
                    <textarea class="form-control" id="categoryDescription" name="categoryDescription" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveCategory()">Lưu</button>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết sản phẩm -->
<div class="custom-modal" id="viewProductModal">
    <div class="modal-content" style="width: 800px; max-width: 90%;">
        <div class="modal-header">
            <h5 class="modal-title">Chi tiết sản phẩm</h5>
            <button type="button" class="modal-close" onclick="closeViewProductModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <img id="viewProductImage" src="" alt="Sản phẩm" class="img-fluid rounded mb-3" style="max-height: 200px;">
                </div>
                <div class="col-md-8">
                    <h4 id="viewProductName"></h4>
                    <p id="viewProductCode" class="text-muted"></p>
                    <p id="viewProductDescription"></p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Danh mục:</strong> <span id="viewProductCategory"></span></p>
                            <p><strong>Đơn vị:</strong> <span id="viewProductUnit"></span></p>
                            <p><strong>Giá:</strong> <span id="viewProductPrice"></span></p>
                            <p><strong>Barcode/RFID:</strong> <span id="viewProductBarcode"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Kích thước:</strong> <span id="viewProductDimensions"></span></p>
                            <p><strong>Trọng lượng:</strong> <span id="viewProductWeight"></span></p>
                            <p><strong>Thể tích:</strong> <span id="viewProductVolume"></span></p>
                            <p><strong>Tồn kho tối thiểu:</strong> <span id="viewProductMinStock"></span></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <!-- Thông tin tồn kho -->
            <h5 class="mb-3">Thông tin tồn kho</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Kho</th>
                            <th>Vị trí</th>
                            <th>Lô hàng</th>
                            <th>Hạn sử dụng</th>
                            <th>Số lượng</th>
                        </tr>
                    </thead>
                    <tbody id="viewProductInventory">
                        <!-- Dữ liệu sẽ được load bằng AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewProductModal()">Đóng</button>
        </div>
    </div>
</div>

<!-- Toast thông báo -->
<div class="toast-container">
    <div id="toast" class="toast">
        <div class="toast-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="toast-message" id="toastMessage"></div>
    </div>
</div>

<script>
// Biến toàn cục
let currentProductId = 0;
let currentCategoryId = 0;

// Hiển thị form thêm sản phẩm
function showProductForm() {
    // Reset form
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '0';
    document.getElementById('productModalTitle').textContent = 'Thêm sản phẩm mới';
    
    // Ẩn hình ảnh preview
    document.getElementById('imagePreview').classList.remove('has-image');
    document.getElementById('imagePreview').src = '';
    
    // Hiện modal
    document.getElementById('productModal').classList.add('show');
}

// Đóng modal sản phẩm
function closeProductModal() {
    document.getElementById('productModal').classList.remove('show');
}

// Hiển thị form thêm danh mục
function showCategoryForm() {
    // Reset form
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '0';
    document.getElementById('categoryModalTitle').textContent = 'Thêm danh mục mới';
    
    // Hiện modal
    document.getElementById('categoryModal').classList.add('show');
}

// Đóng modal danh mục
function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('show');
}

// Đóng modal xem chi tiết sản phẩm
function closeViewProductModal() {
    document.getElementById('viewProductModal').classList.remove('show');
}

// Xem chi tiết sản phẩm
function viewProduct(productId) {
    // Gọi API lấy thông tin sản phẩm
    fetch(`ajax/sanpham/ajax_handler.php?module=products&action=getProductDetails&id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hiển thị thông tin sản phẩm
                const product = data.product;
                
                document.getElementById('viewProductName').textContent = product.product_name;
                document.getElementById('viewProductCode').textContent = `Mã sản phẩm: ${product.product_code}`;
                document.getElementById('viewProductDescription').textContent = product.description || 'Không có mô tả';
                document.getElementById('viewProductCategory').textContent = product.category_name;
                document.getElementById('viewProductUnit').textContent = product.unit;
                document.getElementById('viewProductPrice').textContent = formatCurrency(product.price);
                document.getElementById('viewProductBarcode').textContent = product.barcode || 'Không có';
                document.getElementById('viewProductDimensions').textContent = product.dimensions || 'Không có';
                document.getElementById('viewProductWeight').textContent = product.weight ? `${product.weight} kg` : 'Không có';
                document.getElementById('viewProductVolume').textContent = product.volume ? `${product.volume} dm³` : 'Không có';
                document.getElementById('viewProductMinStock').textContent = product.minimum_stock;
                
                // Hiển thị hình ảnh sản phẩm
                const imageUrl = product.image_url || 'image/products/no-image.jpg';
                document.getElementById('viewProductImage').src = imageUrl;
                
                // Hiển thị thông tin tồn kho
                let inventoryHTML = '';
                
                if (data.inventory && data.inventory.length > 0) {
                    data.inventory.forEach(item => {
                        const expiryDate = item.expiry_date ? formatDate(item.expiry_date) : 'Không có';
                        
                        inventoryHTML += `
                            <tr>
                                <td>${item.warehouse_name}</td>
                                <td>${item.shelf_code} (${item.zone_code})</td>
                                <td>${item.batch_number || 'Không có'}</td>
                                <td>${expiryDate}</td>
                                <td>${item.quantity}</td>
                            </tr>
                        `;
                    });
                } else {
                    inventoryHTML = '<tr><td colspan="5" class="text-center">Không có dữ liệu tồn kho</td></tr>';
                }
                
                document.getElementById('viewProductInventory').innerHTML = inventoryHTML;
                
                // Hiện modal
                document.getElementById('viewProductModal').classList.add('show');
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra khi lấy thông tin sản phẩm');
            }
        })
        .catch(error => {
            console.error('Lỗi khi lấy thông tin sản phẩm:', error);
            showToast('error', 'Có lỗi xảy ra khi lấy thông tin sản phẩm');
        });
}

// Sửa sản phẩm
function editProduct(productId) {
    currentProductId = productId;
    
    // Gọi API lấy thông tin sản phẩm
    fetch(`ajax/sanpham/ajax_handler.php?module=products&action=getProductDetails&id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;
                
                // Điền thông tin vào form
                document.getElementById('productId').value = product.product_id;
                document.getElementById('productCode').value = product.product_code;
                document.getElementById('productName').value = product.product_name;
                document.getElementById('categoryId').value = product.category_id;
                document.getElementById('unit').value = product.unit;
                document.getElementById('price').value = product.price;
                document.getElementById('minimumStock').value = product.minimum_stock;
                document.getElementById('barcode').value = product.barcode || '';
                document.getElementById('dimensions').value = product.dimensions || '';
                document.getElementById('weight').value = product.weight || '';
                document.getElementById('volume').value = product.volume || '';
                document.getElementById('description').value = product.description || '';
                
                // Hiển thị hình ảnh nếu có
                if (product.image_url) {
                    document.getElementById('imagePreview').src = product.image_url;
                    document.getElementById('imagePreview').classList.add('has-image');
                } else {
                    document.getElementById('imagePreview').src = '';
                    document.getElementById('imagePreview').classList.remove('has-image');
                }
                
                // Cập nhật tiêu đề modal
                document.getElementById('productModalTitle').textContent = 'Cập nhật sản phẩm';
                
                // Hiện modal
                document.getElementById('productModal').classList.add('show');
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra khi lấy thông tin sản phẩm');
            }
        })
        .catch(error => {
            console.error('Lỗi khi lấy thông tin sản phẩm:', error);
            showToast('error', 'Có lỗi xảy ra khi lấy thông tin sản phẩm');
        });
}

// Xóa sản phẩm
function deleteProduct(productId) {
    if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
        fetch('ajax/sanpham/ajax_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `module=products&action=deleteProduct&id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Xóa sản phẩm thành công');
                // Tải lại trang sau 1 giây
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra khi xóa sản phẩm');
            }
        })
        .catch(error => {
            console.error('Lỗi khi xóa sản phẩm:', error);
            showToast('error', 'Có lỗi xảy ra khi xóa sản phẩm');
        });
    }
}

// Lưu sản phẩm
function saveProduct() {
    // Kiểm tra form hợp lệ
    const form = document.getElementById('productForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Thu thập dữ liệu form
    const formData = new FormData(form);
    formData.append('module', 'products');
    formData.append('action', currentProductId > 0 ? 'updateProduct' : 'addProduct');
    
    // Gửi request
    fetch('ajax/sanpham/ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', currentProductId > 0 ? 'Cập nhật sản phẩm thành công' : 'Thêm sản phẩm mới thành công');
            closeProductModal();
            // Tải lại trang sau 1 giây
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', data.message || 'Có lỗi xảy ra khi lưu sản phẩm');
        }
    })
    .catch(error => {
        console.error('Lỗi khi lưu sản phẩm:', error);
        showToast('error', 'Có lỗi xảy ra khi lưu sản phẩm');
    });
}

// Sửa danh mục
function editCategory(categoryId) {
    currentCategoryId = categoryId;
    
    fetch(`ajax/sanpham/ajax_handler.php?module=products&action=getCategoryDetails&id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const category = data.category;
                
                // Điền thông tin vào form
                document.getElementById('categoryId').value = category.category_id;
                document.getElementById('categoryName').value = category.category_name;
                document.getElementById('parentId').value = category.parent_id || '';
                document.getElementById('categoryDescription').value = category.description || '';
                
                // Cập nhật tiêu đề modal
                document.getElementById('categoryModalTitle').textContent = 'Cập nhật danh mục';
                
                // Hiện modal
                document.getElementById('categoryModal').classList.add('show');
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra khi lấy thông tin danh mục');
            }
        })
        .catch(error => {
            console.error('Lỗi khi lấy thông tin danh mục:', error);
            showToast('error', 'Có lỗi xảy ra khi lấy thông tin danh mục');
        });
}

// Xóa danh mục
function deleteCategory(categoryId) {
    if (confirm('Bạn có chắc chắn muốn xóa danh mục này? Các sản phẩm trong danh mục này sẽ bị ảnh hưởng.')) {
        fetch('ajax/sanpham/ajax_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `module=products&action=deleteCategory&id=${categoryId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Xóa danh mục thành công');
                // Tải lại trang sau 1 giây
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra khi xóa danh mục');
            }
        })
        .catch(error => {
            console.error('Lỗi khi xóa danh mục:', error);
            showToast('error', 'Có lỗi xảy ra khi xóa danh mục');
        });
    }
}

// Lưu danh mục
function saveCategory() {
    // Kiểm tra form hợp lệ
    const form = document.getElementById('categoryForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Thu thập dữ liệu form
    const formData = new FormData(form);
    formData.append('module', 'products');
    formData.append('action', currentCategoryId > 0 ? 'updateCategory' : 'addCategory');
    
    // Chuyển FormData thành URLSearchParams để gửi dạng form-urlencoded
    const data = new URLSearchParams();
    for (const pair of formData) {
        data.append(pair[0], pair[1]);
    }
    
    // Gửi request
    fetch('ajax/sanpham/ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', currentCategoryId > 0 ? 'Cập nhật danh mục thành công' : 'Thêm danh mục mới thành công');
            closeCategoryModal();
            // Tải lại trang sau 1 giây
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', data.message || 'Có lỗi xảy ra khi lưu danh mục');
        }
    })
    .catch(error => {
        console.error('Lỗi khi lưu danh mục:', error);
        showToast('error', 'Có lỗi xảy ra khi lưu danh mục');
    });
}

// Lọc sản phẩm
function filterProducts() {
    const categoryId = document.getElementById('categoryFilter').value;
    const stockStatus = document.getElementById('stockFilter').value;
    const searchTerm = document.getElementById('searchProduct').value;
    
    fetch('ajax/sanpham/ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `module=products&action=filterProducts&categoryId=${categoryId}&stockStatus=${stockStatus}&searchTerm=${searchTerm}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật bảng sản phẩm
            const productsTable = document.getElementById('productsList');
            
            if (data.products.length === 0) {
                productsTable.innerHTML = '<tr><td colspan="9" class="text-center">Không tìm thấy sản phẩm nào</td></tr>';
                return;
            }
            
            let html = '';
            data.products.forEach(product => {
                // Xác định trạng thái tồn kho
                let stockStatus = '';
                let stockText = '';
                
                if (product.total_stock <= product.minimum_stock) {
                    stockStatus = 'bg-danger';
                    stockText = 'Thấp';
                } else if (product.total_stock <= product.minimum_stock * 1.5) {
                    stockStatus = 'bg-warning';
                    stockText = 'Trung bình';
                } else {
                    stockStatus = 'bg-success';
                    stockText = 'Cao';
                }
                
                // Hiển thị hình ảnh sản phẩm
                const image = product.image_url || 'image/products/no-image.jpg';
                
                html += `
                    <tr>
                        <td>${product.product_code}</td>
                        <td><img src="${image}" alt="${product.product_name}" width="50" height="50" class="rounded"></td>
                        <td>${product.product_name}</td>
                        <td>${product.category_name}</td>
                        <td>${product.unit}</td>
                        <td>${formatCurrency(product.price)}</td>
                        <td>
                            <span class="badge ${stockStatus}">${product.total_stock} (${stockText})</span>
                        </td>
                        <td>${product.barcode || ''}</td>
                        <td class="action-buttons">
                            <button class="btn btn-sm btn-info" onclick="viewProduct(${product.product_id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary ms-1" onclick="editProduct(${product.product_id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger ms-1" onclick="deleteProduct(${product.product_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            productsTable.innerHTML = html;
        } else {
            showToast('error', data.message || 'Có lỗi xảy ra khi lọc sản phẩm');
        }
    })
    .catch(error => {
        console.error('Lỗi khi lọc sản phẩm:', error);
        showToast('error', 'Có lỗi xảy ra khi lọc sản phẩm');
    });
}

// Preview hình ảnh khi chọn file
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const imagePreview = document.getElementById('imagePreview');
            imagePreview.src = e.target.result;
            imagePreview.classList.add('has-image');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Hiển thị toast thông báo
function showToast(type, message) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = toast.querySelector('.toast-icon i');
    
    // Đặt nội dung và kiểu thông báo
    toastMessage.textContent = message;
    
    if (type === 'success') {
        toast.className = 'toast toast-success';
        toastIcon.className = 'fas fa-check-circle';
    } else {
        toast.className = 'toast toast-error';
        toastIcon.className = 'fas fa-exclamation-circle';
    }
    
    // Hiện toast
    toast.classList.add('show');
    
    // Tự động ẩn sau 3 giây
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Format tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Format ngày tháng
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

// Khởi tạo khi trang được load
document.addEventListener('DOMContentLoaded', function() {
    // Bắt sự kiện khi người dùng nhập vào ô tìm kiếm danh mục
    document.getElementById('searchCategory').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#categoriesList tr');
        
        rows.forEach(row => {
            const categoryName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const categoryDesc = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            
            if (categoryName.includes(searchTerm) || categoryDesc.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Bắt sự kiện Enter cho ô tìm kiếm sản phẩm
    document.getElementById('searchProduct').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            filterProducts();
        }
    });
});
</script>