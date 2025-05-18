-- Bảng vai trò người dùng
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng người dùng
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    login_attempts INT DEFAULT 0,
    is_locked BOOLEAN DEFAULT FALSE,
    last_login DATETIME,
    otp VARCHAR(10),
    otp_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Bảng lịch sử hoạt động người dùng
CREATE TABLE user_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Bảng nhật ký hệ thống
CREATE TABLE system_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    log_level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    source VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng danh mục sản phẩm
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- Bảng sản phẩm
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) NOT NULL UNIQUE,
    product_name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT NOT NULL,
    unit VARCHAR(20) NOT NULL,
    price DECIMAL(15,2) NOT NULL CHECK (price >= 0),
    image_url VARCHAR(255),
    volume DECIMAL(10,2) COMMENT 'Thể tích (dm³)',
    dimensions VARCHAR(50) COMMENT 'Kích thước (DxRxC)',
    weight DECIMAL(10,2) COMMENT 'Trọng lượng (kg)',
    barcode VARCHAR(100),
    minimum_stock INT DEFAULT 10,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);


-- Bảng cảnh báo sản phẩm
CREATE TABLE product_alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    alert_type ENUM('LOW_STOCK', 'EXPIRING_SOON', 'OUT_OF_STOCK') NOT NULL,
    alert_message TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id)
);

-- Bảng kho
CREATE TABLE warehouses (
    warehouse_id INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_name VARCHAR(100) NOT NULL,
    address TEXT,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng tồn kho sản phẩm
CREATE TABLE inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0 CHECK (quantity >= 0),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (product_id, warehouse_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id)
);
-- Bảng khu vực kho
CREATE TABLE warehouse_zones (
    zone_id INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT NOT NULL,
    zone_code VARCHAR(10) NOT NULL,
    zone_name VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (warehouse_id, zone_code),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id)
);

-- Bảng kệ kho
CREATE TABLE shelves (
    shelf_id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NOT NULL,
    shelf_code VARCHAR(20) NOT NULL,
    position VARCHAR(50),
    max_capacity DECIMAL(10,2) COMMENT 'Sức chứa tối đa (thể tích hoặc số lượng)',
    capacity_unit ENUM('VOLUME', 'QUANTITY') DEFAULT 'VOLUME',
    description TEXT,
    status ENUM('ACTIVE', 'INACTIVE', 'MAINTENANCE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (zone_id, shelf_code),
    FOREIGN KEY (zone_id) REFERENCES warehouse_zones(zone_id)
);

-- Bảng vị trí sản phẩm trong kho
CREATE TABLE product_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    shelf_id INT NOT NULL,
    batch_number VARCHAR(50),
    expiry_date DATE,
    quantity INT NOT NULL DEFAULT 0,
    entry_date DATETIME NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (product_id, shelf_id, batch_number),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (shelf_id) REFERENCES shelves(shelf_id)
);

-- Bảng nhà cung cấp
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(50) NOT NULL UNIQUE,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    tax_code VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng phiếu nhập kho
CREATE TABLE import_orders (
    import_id INT AUTO_INCREMENT PRIMARY KEY,
    import_code VARCHAR(50) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    total_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('DRAFT', 'PENDING', 'COMPLETED', 'CANCELLED') DEFAULT 'DRAFT',
    notes TEXT,
    created_by INT NOT NULL,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
);

-- Bảng chi tiết phiếu nhập
CREATE TABLE import_order_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    import_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(15,2) NOT NULL CHECK (unit_price >= 0),
    batch_number VARCHAR(50),
    expiry_date DATE,
    shelf_id INT,
    total_price DECIMAL(15,2) AS (quantity * unit_price) STORED,
    notes TEXT,
    FOREIGN KEY (import_id) REFERENCES import_orders(import_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (shelf_id) REFERENCES shelves(shelf_id)
);

-- Bảng phiếu xuất kho
CREATE TABLE export_orders (
    export_id INT AUTO_INCREMENT PRIMARY KEY,
    export_code VARCHAR(50) NOT NULL UNIQUE,
    warehouse_id INT NOT NULL,
    recipient VARCHAR(100),
    recipient_address TEXT,
    total_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('DRAFT', 'PENDING', 'COMPLETED', 'CANCELLED') DEFAULT 'DRAFT',
    order_reference VARCHAR(50) COMMENT 'Mã đơn hàng liên kết (nếu có)',
    notes TEXT,
    created_by INT NOT NULL,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
);

-- Bảng chi tiết phiếu xuất
CREATE TABLE export_order_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    export_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(15,2) NOT NULL CHECK (unit_price >= 0),
    batch_number VARCHAR(50),
    shelf_id INT,
    total_price DECIMAL(15,2) AS (quantity * unit_price) STORED,
    notes TEXT,
    FOREIGN KEY (export_id) REFERENCES export_orders(export_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (shelf_id) REFERENCES shelves(shelf_id)
);

-- Bảng di chuyển hàng giữa các kho
CREATE TABLE inventory_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    movement_code VARCHAR(50) NOT NULL UNIQUE,
    product_id INT NOT NULL,
    source_warehouse_id INT NOT NULL,
    source_shelf_id INT,
    target_warehouse_id INT NOT NULL,
    target_shelf_id INT,
    quantity INT NOT NULL CHECK (quantity > 0),
    batch_number VARCHAR(50),
    status ENUM('PENDING', 'IN_TRANSIT', 'COMPLETED', 'CANCELLED') DEFAULT 'PENDING',
    reason TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (source_warehouse_id) REFERENCES warehouses(warehouse_id),
    FOREIGN KEY (source_shelf_id) REFERENCES shelves(shelf_id),
    FOREIGN KEY (target_warehouse_id) REFERENCES warehouses(warehouse_id),
    FOREIGN KEY (target_shelf_id) REFERENCES shelves(shelf_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Bảng thiết bị IoT
CREATE TABLE iot_devices (
    device_id INT AUTO_INCREMENT PRIMARY KEY,
    device_code VARCHAR(50) NOT NULL UNIQUE,
    device_name VARCHAR(100) NOT NULL,
    device_type ENUM('RFID_SCANNER', 'BARCODE_SCANNER', 'TEMPERATURE_SENSOR', 'OTHER'),
    warehouse_id INT,
    zone_id INT,
    mac_address VARCHAR(50),
    ip_address VARCHAR(50),
    firmware_version VARCHAR(50),
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    status ENUM('ACTIVE', 'INACTIVE', 'MAINTENANCE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    FOREIGN KEY (zone_id) REFERENCES warehouse_zones(zone_id)
);

-- Bảng trạng thái thiết bị IoT
CREATE TABLE iot_device_statuses (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    power_status ENUM('ON', 'OFF', 'SLEEP') DEFAULT 'OFF',
    battery_level INT CHECK (battery_level BETWEEN 0 AND 100),
    is_error BOOLEAN DEFAULT FALSE,
    error_message TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES iot_devices(device_id)
);

-- Bảng lịch kiểm kê tự động
CREATE TABLE inventory_checks (
    check_id INT AUTO_INCREMENT PRIMARY KEY,
    check_code VARCHAR(50) NOT NULL UNIQUE,
    warehouse_id INT NOT NULL,
    zone_id INT,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    status ENUM('SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED') DEFAULT 'SCHEDULED',
    check_type ENUM('AUTOMATIC_RFID', 'MANUAL_BARCODE', 'MIXED') NOT NULL,
    created_by INT NOT NULL,
    completed_at DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    FOREIGN KEY (zone_id) REFERENCES warehouse_zones(zone_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Bảng kết quả kiểm kê
CREATE TABLE inventory_check_results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    check_id INT NOT NULL,
    product_id INT NOT NULL,
    expected_quantity INT NOT NULL DEFAULT 0,
    actual_quantity INT NOT NULL DEFAULT 0,
    difference INT AS (actual_quantity - expected_quantity) STORED,
    shelf_id INT,
    batch_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (check_id) REFERENCES inventory_checks(check_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (shelf_id) REFERENCES shelves(shelf_id)
);

-- Trigger cập nhật tồn kho khi nhập hàng
DELIMITER //
CREATE TRIGGER after_import_detail_insert
AFTER INSERT ON import_order_details
FOR EACH ROW
BEGIN
    DECLARE order_status VARCHAR(20);
    
    -- Kiểm tra trạng thái của phiếu nhập
    SELECT status INTO order_status FROM import_orders WHERE import_id = NEW.import_id;
    
    IF order_status = 'COMPLETED' THEN
        -- Cập nhật hoặc thêm mới vào bảng tồn kho
        INSERT INTO inventory (product_id, warehouse_id, quantity)
        SELECT NEW.product_id, io.warehouse_id, NEW.quantity
        FROM import_orders io
        WHERE io.import_id = NEW.import_id
        ON DUPLICATE KEY UPDATE quantity = quantity + NEW.quantity;
        
        -- Cập nhật vị trí sản phẩm trong kho nếu có shelf_id
        IF NEW.shelf_id IS NOT NULL THEN
            INSERT INTO product_locations (product_id, shelf_id, batch_number, expiry_date, quantity, entry_date)
            VALUES (NEW.product_id, NEW.shelf_id, NEW.batch_number, NEW.expiry_date, NEW.quantity, NOW())
            ON DUPLICATE KEY UPDATE quantity = quantity + NEW.quantity;
        END IF;
    END IF;
END //
DELIMITER ;

-- Trigger kiểm tra tồn kho trước khi xuất hàng
DELIMITER //
CREATE TRIGGER before_export_detail_insert
BEFORE INSERT ON export_order_details
FOR EACH ROW
BEGIN
    DECLARE available_qty INT;
    DECLARE warehouse_id_val INT;
    
    SELECT warehouse_id INTO warehouse_id_val 
    FROM export_orders WHERE export_id = NEW.export_id;
    
    -- Kiểm tra số lượng trong kho
    SELECT IFNULL(SUM(quantity), 0) INTO available_qty 
    FROM inventory 
    WHERE product_id = NEW.product_id AND warehouse_id = warehouse_id_val;
    
    IF available_qty < NEW.quantity THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Lỗi: Số lượng xuất vượt quá số lượng tồn kho';
    END IF;
END //
DELIMITER ;

-- Trigger cập nhật tồn kho khi xuất hàng
DELIMITER //
CREATE TRIGGER after_export_detail_completed
AFTER UPDATE ON export_orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'COMPLETED' AND OLD.status != 'COMPLETED' THEN
        -- Cập nhật giảm số lượng trong inventory
        UPDATE inventory i
        JOIN export_order_details eod ON i.product_id = eod.product_id
        SET i.quantity = i.quantity - eod.quantity
        WHERE i.warehouse_id = NEW.warehouse_id 
        AND eod.export_id = NEW.export_id;
        
        -- Cập nhật giảm số lượng trong product_locations nếu có shelf_id
        UPDATE product_locations pl
        JOIN export_order_details eod ON pl.product_id = eod.product_id AND pl.shelf_id = eod.shelf_id
        SET pl.quantity = pl.quantity - eod.quantity
        WHERE eod.export_id = NEW.export_id AND eod.shelf_id IS NOT NULL;
    END IF;
END //
DELIMITER ;

-- Procedure kiểm tra sản phẩm gần hết hạn
DELIMITER //
CREATE PROCEDURE check_expiring_products(IN days_threshold INT)
BEGIN
    INSERT INTO product_alerts (product_id, warehouse_id, alert_type, alert_message, is_active)
    SELECT pl.product_id, wz.warehouse_id, 'EXPIRING_SOON',
           CONCAT('Sản phẩm sắp hết hạn trong ', days_threshold, ' ngày'), TRUE
    FROM product_locations pl
    JOIN shelves s ON pl.shelf_id = s.shelf_id
    JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
    WHERE pl.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL days_threshold DAY)
    AND NOT EXISTS (
        SELECT 1 FROM product_alerts 
        WHERE product_id = pl.product_id 
        AND warehouse_id = wz.warehouse_id 
        AND alert_type = 'EXPIRING_SOON'
        AND is_active = TRUE
    );
END //
DELIMITER ;

-- Procedure kiểm tra sản phẩm tồn kho thấp
DELIMITER //
CREATE PROCEDURE check_low_stock_products()
BEGIN
    INSERT INTO product_alerts (product_id, warehouse_id, alert_type, alert_message, is_active)
    SELECT i.product_id, i.warehouse_id, 'LOW_STOCK',
           CONCAT('Số lượng tồn kho thấp: ', i.quantity), TRUE
    FROM inventory i
    JOIN products p ON i.product_id = p.product_id
    WHERE i.quantity <= p.minimum_stock
    AND NOT EXISTS (
        SELECT 1 FROM product_alerts 
        WHERE product_id = i.product_id 
        AND warehouse_id = i.warehouse_id 
        AND alert_type = 'LOW_STOCK'
        AND is_active = TRUE
    );
END //
DELIMITER ;

-- Procedure gợi ý vị trí kệ phù hợp khi nhập kho
DELIMITER //
CREATE PROCEDURE suggest_shelf_location(
    IN p_product_id INT,
    IN p_warehouse_id INT,
    IN p_quantity INT
)
BEGIN
    -- Tính tổng thể tích cần thiết
    DECLARE required_volume DECIMAL(10,2);
    
    SELECT volume * p_quantity INTO required_volume
    FROM products WHERE product_id = p_product_id;
    
    -- Tìm kệ phù hợp có đủ sức chứa
    SELECT s.shelf_id, s.shelf_code, s.max_capacity,
           (s.max_capacity - IFNULL(SUM(pl.quantity * p.volume), 0)) AS available_capacity
    FROM shelves s
    JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
    LEFT JOIN product_locations pl ON s.shelf_id = pl.shelf_id
    LEFT JOIN products p ON pl.product_id = p.product_id
    WHERE wz.warehouse_id = p_warehouse_id
    AND s.status = 'ACTIVE'
    GROUP BY s.shelf_id
    HAVING available_capacity >= required_volume
    ORDER BY available_capacity ASC
    LIMIT 5;
END //
DELIMITER ;

-- View báo cáo tồn kho
CREATE VIEW inventory_report AS
SELECT 
    p.product_code,
    p.product_name,
    c.category_name,
    w.warehouse_name,
    i.quantity,
    p.price,
    (i.quantity * p.price) AS total_value,
    p.minimum_stock,
    CASE 
        WHEN i.quantity <= p.minimum_stock THEN 'Thấp'
        WHEN i.quantity <= p.minimum_stock * 1.5 THEN 'Trung bình'
        ELSE 'Cao' 
    END AS stock_level
FROM 
    inventory i
    JOIN products p ON i.product_id = p.product_id
    JOIN categories c ON p.category_id = c.category_id
    JOIN warehouses w ON i.warehouse_id = w.warehouse_id;

-- View thống kê nhập kho theo thời gian
CREATE VIEW import_statistics AS
SELECT 
    DATE(io.created_at) AS import_date,
    w.warehouse_name,
    s.supplier_name,
    COUNT(DISTINCT io.import_id) AS total_imports,
    SUM(iod.quantity) AS total_quantity,
    SUM(iod.total_price) AS total_amount
FROM 
    import_orders io
    JOIN import_order_details iod ON io.import_id = iod.import_id
    JOIN warehouses w ON io.warehouse_id = w.warehouse_id
    JOIN suppliers s ON io.supplier_id = s.supplier_id
WHERE 
    io.status = 'COMPLETED'
GROUP BY 
    DATE(io.created_at), w.warehouse_id, s.supplier_id;

-- View thống kê xuất kho theo thời gian
CREATE VIEW export_statistics AS
SELECT 
    DATE(eo.created_at) AS export_date,
    w.warehouse_name,
    COUNT(DISTINCT eo.export_id) AS total_exports,
    SUM(eod.quantity) AS total_quantity,
    SUM(eod.total_price) AS total_amount
FROM 
    export_orders eo
    JOIN export_order_details eod ON eo.export_id = eod.export_id
    JOIN warehouses w ON eo.warehouse_id = w.warehouse_id
WHERE 
    eo.status = 'COMPLETED'
GROUP BY 
    DATE(eo.created_at), w.warehouse_id;

-- View thống kê mức độ sử dụng kệ
CREATE VIEW shelf_utilization AS
SELECT 
    s.shelf_id,
    s.shelf_code,
    wz.zone_code,
    w.warehouse_name,
    s.max_capacity,
    SUM(IFNULL(pl.quantity * p.volume, 0)) AS used_capacity,
    (SUM(IFNULL(pl.quantity * p.volume, 0)) / s.max_capacity * 100) AS utilization_percentage,
    CASE
        WHEN (SUM(IFNULL(pl.quantity * p.volume, 0)) / s.max_capacity * 100) < 30 THEN 'Thấp'
        WHEN (SUM(IFNULL(pl.quantity * p.volume, 0)) / s.max_capacity * 100) < 70 THEN 'Trung bình'
        ELSE 'Cao'
    END AS utilization_level
FROM 
    shelves s
    JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
    JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
    LEFT JOIN product_locations pl ON s.shelf_id = pl.shelf_id
    LEFT JOIN products p ON pl.product_id = p.product_id
GROUP BY 
    s.shelf_id;

-- View báo cáo sản phẩm gần hết hạn
CREATE VIEW expiring_products AS
SELECT 
    p.product_code,
    p.product_name,
    w.warehouse_name,
    s.shelf_code,
    pl.batch_number,
    pl.expiry_date,
    pl.quantity,
    DATEDIFF(pl.expiry_date, CURDATE()) AS days_until_expiry
FROM 
    product_locations pl
    JOIN products p ON pl.product_id = p.product_id
    JOIN shelves s ON pl.shelf_id = s.shelf_id
    JOIN warehouse_zones wz ON s.zone_id = wz.zone_id
    JOIN warehouses w ON wz.warehouse_id = w.warehouse_id
WHERE 
    pl.expiry_date IS NOT NULL
    AND pl.expiry_date > CURDATE()
    AND DATEDIFF(pl.expiry_date, CURDATE()) <= 30
ORDER BY 
    days_until_expiry;