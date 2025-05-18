-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 18, 2025 lúc 12:13 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `nckh`
--

DELIMITER $$
--
-- Thủ tục
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `check_expiring_products` (IN `days_threshold` INT)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `check_low_stock_products` ()   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `suggest_shelf_location` (IN `p_product_id` INT, IN `p_warehouse_id` INT, IN `p_quantity` INT)   BEGIN
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
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `parent_id`, `created_at`, `updated_at`) VALUES
(1, 'Hàng điện tử', 'Hàng điện tử provip', NULL, '2025-05-18 10:06:30', '2025-05-18 10:06:30');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `expiring_products`
-- (See below for the actual view)
--
CREATE TABLE `expiring_products` (
`product_code` varchar(50)
,`product_name` varchar(200)
,`warehouse_name` varchar(100)
,`shelf_code` varchar(20)
,`batch_number` varchar(50)
,`expiry_date` date
,`quantity` int(11)
,`days_until_expiry` int(7)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `export_orders`
--

CREATE TABLE `export_orders` (
  `export_id` int(11) NOT NULL,
  `export_code` varchar(50) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `recipient` varchar(100) DEFAULT NULL,
  `recipient_address` text DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('DRAFT','PENDING','COMPLETED','CANCELLED') DEFAULT 'DRAFT',
  `order_reference` varchar(50) DEFAULT NULL COMMENT 'Mã đơn hàng liên kết (nếu có)',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Bẫy `export_orders`
--
DELIMITER $$
CREATE TRIGGER `after_export_detail_completed` AFTER UPDATE ON `export_orders` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `export_order_details`
--

CREATE TABLE `export_order_details` (
  `detail_id` int(11) NOT NULL,
  `export_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `unit_price` decimal(15,2) NOT NULL CHECK (`unit_price` >= 0),
  `batch_number` varchar(50) DEFAULT NULL,
  `shelf_id` int(11) DEFAULT NULL,
  `total_price` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Bẫy `export_order_details`
--
DELIMITER $$
CREATE TRIGGER `before_export_detail_insert` BEFORE INSERT ON `export_order_details` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `export_statistics`
-- (See below for the actual view)
--
CREATE TABLE `export_statistics` (
`export_date` date
,`warehouse_name` varchar(100)
,`total_exports` bigint(21)
,`total_quantity` decimal(32,0)
,`total_amount` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `import_orders`
--

CREATE TABLE `import_orders` (
  `import_id` int(11) NOT NULL,
  `import_code` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('DRAFT','PENDING','COMPLETED','CANCELLED') DEFAULT 'DRAFT',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `import_order_details`
--

CREATE TABLE `import_order_details` (
  `detail_id` int(11) NOT NULL,
  `import_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `unit_price` decimal(15,2) NOT NULL CHECK (`unit_price` >= 0),
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `shelf_id` int(11) DEFAULT NULL,
  `total_price` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Bẫy `import_order_details`
--
DELIMITER $$
CREATE TRIGGER `after_import_detail_insert` AFTER INSERT ON `import_order_details` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `import_statistics`
-- (See below for the actual view)
--
CREATE TABLE `import_statistics` (
`import_date` date
,`warehouse_name` varchar(100)
,`supplier_name` varchar(100)
,`total_imports` bigint(21)
,`total_quantity` decimal(32,0)
,`total_amount` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0 CHECK (`quantity` >= 0),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_checks`
--

CREATE TABLE `inventory_checks` (
  `check_id` int(11) NOT NULL,
  `check_code` varchar(50) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `status` enum('SCHEDULED','IN_PROGRESS','COMPLETED','CANCELLED') DEFAULT 'SCHEDULED',
  `check_type` enum('AUTOMATIC_RFID','MANUAL_BARCODE','MIXED') NOT NULL,
  `created_by` int(11) NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_check_results`
--

CREATE TABLE `inventory_check_results` (
  `result_id` int(11) NOT NULL,
  `check_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `expected_quantity` int(11) NOT NULL DEFAULT 0,
  `actual_quantity` int(11) NOT NULL DEFAULT 0,
  `difference` int(11) GENERATED ALWAYS AS (`actual_quantity` - `expected_quantity`) STORED,
  `shelf_id` int(11) DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `movement_id` int(11) NOT NULL,
  `movement_code` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `source_warehouse_id` int(11) NOT NULL,
  `source_shelf_id` int(11) DEFAULT NULL,
  `target_warehouse_id` int(11) NOT NULL,
  `target_shelf_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `batch_number` varchar(50) DEFAULT NULL,
  `status` enum('PENDING','IN_TRANSIT','COMPLETED','CANCELLED') DEFAULT 'PENDING',
  `reason` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `inventory_report`
-- (See below for the actual view)
--
CREATE TABLE `inventory_report` (
`product_code` varchar(50)
,`product_name` varchar(200)
,`category_name` varchar(100)
,`warehouse_name` varchar(100)
,`quantity` int(11)
,`price` decimal(15,2)
,`total_value` decimal(25,2)
,`minimum_stock` int(11)
,`stock_level` varchar(10)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `iot_devices`
--

CREATE TABLE `iot_devices` (
  `device_id` int(11) NOT NULL,
  `device_code` varchar(50) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_type` enum('RFID_SCANNER','BARCODE_SCANNER','TEMPERATURE_SENSOR','OTHER') DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `mac_address` varchar(50) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `firmware_version` varchar(50) DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','MAINTENANCE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `iot_device_statuses`
--

CREATE TABLE `iot_device_statuses` (
  `status_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `power_status` enum('ON','OFF','SLEEP') DEFAULT 'OFF',
  `battery_level` int(11) DEFAULT NULL CHECK (`battery_level` between 0 and 100),
  `is_error` tinyint(1) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `price` decimal(15,2) NOT NULL CHECK (`price` >= 0),
  `image_url` varchar(255) DEFAULT NULL,
  `volume` decimal(10,2) DEFAULT NULL COMMENT 'Thể tích (dm³)',
  `dimensions` varchar(50) DEFAULT NULL COMMENT 'Kích thước (DxRxC)',
  `weight` decimal(10,2) DEFAULT NULL COMMENT 'Trọng lượng (kg)',
  `barcode` varchar(100) DEFAULT NULL,
  `minimum_stock` int(11) DEFAULT 10,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_alerts`
--

CREATE TABLE `product_alerts` (
  `alert_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `alert_type` enum('LOW_STOCK','EXPIRING_SOON','OUT_OF_STOCK') NOT NULL,
  `alert_message` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_locations`
--

CREATE TABLE `product_locations` (
  `location_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `shelf_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `entry_date` datetime NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `created_at`) VALUES
(1, 'Admin', 'Quản trị viên hệ thống', '2025-05-18 07:47:48'),
(2, 'Staff', 'Nhân viên kho', '2025-05-18 07:47:48'),
(3, 'User', 'Người dùng thông thường', '2025-05-18 07:47:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 1, '[\"view_products\", \"add_products\", \"edit_products\", \"delete_products\", \"view_inventory\", \"manage_import\", \"manage_export\", \"inventory_check\", \"view_users\", \"add_users\", \"edit_users\", \"delete_users\", \"view_reports\", \"view_logs\", \"view_system_logs\", \"system_settings\"]', '2025-05-18 08:59:55', '2025-05-18 08:59:55'),
(2, 2, '[\"view_products\", \"add_products\", \"edit_products\", \"view_inventory\", \"manage_import\", \"manage_export\", \"inventory_check\", \"view_users\", \"view_reports\"]', '2025-05-18 08:59:55', '2025-05-18 08:59:55'),
(3, 3, '[\"view_products\", \"view_inventory\"]', '2025-05-18 08:59:55', '2025-05-18 08:59:55');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `shelf_utilization`
-- (See below for the actual view)
--
CREATE TABLE `shelf_utilization` (
`shelf_id` int(11)
,`shelf_code` varchar(20)
,`zone_code` varchar(10)
,`warehouse_name` varchar(100)
,`max_capacity` decimal(10,2)
,`used_capacity` decimal(42,2)
,`utilization_percentage` decimal(51,6)
,`utilization_level` varchar(10)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `shelves`
--

CREATE TABLE `shelves` (
  `shelf_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `shelf_code` varchar(20) NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `max_capacity` decimal(10,2) DEFAULT NULL COMMENT 'Sức chứa tối đa (thể tích hoặc số lượng)',
  `capacity_unit` enum('VOLUME','QUANTITY') DEFAULT 'VOLUME',
  `description` text DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','MAINTENANCE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `shelves`
--

INSERT INTO `shelves` (`shelf_id`, `zone_id`, `shelf_code`, `position`, `max_capacity`, `capacity_unit`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'A1', 'Hàng 1', 20.00, 'QUANTITY', 'Kệ víp promax', 'ACTIVE', '2025-05-18 10:03:54', '2025-05-18 10:03:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_code` varchar(50) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_code` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `log_level` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `avatar` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `login_attempts` int(11) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `otp` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`user_id`, `avatar`, `username`, `password`, `email`, `full_name`, `phone`, `role_id`, `is_active`, `login_attempts`, `is_locked`, `last_login`, `otp`, `otp_expiry`, `created_at`, `updated_at`) VALUES
(5, '', 'admin', '$2y$10$3kUj9luITf9xhgAR21e19uXhLkD4ZPMHRiPyCCsw/982xtlr2O5.O', 'vantamst97@gmail.com', 'Đào Văn Tâm', NULL, 1, 1, 0, 0, '2025-05-18 16:20:01', NULL, NULL, '2025-05-18 07:56:19', '2025-05-18 09:20:01'),
(6, '', 'thanh', '$2y$10$281iv5mxlqJzrbUgHDhd3OqbxyegJt/olcjEuMWfk5aJsf6GEYQxO', 'thanhln.dev@gmail.com', 'Lương Ngọc Thành', '', 2, 1, 0, 0, '2025-05-18 16:19:26', NULL, NULL, '2025-05-18 08:08:19', '2025-05-18 09:39:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_logs`
--

CREATE TABLE `user_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user_logs`
--

INSERT INTO `user_logs` (`log_id`, `user_id`, `action_type`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 5, 'REGISTER', 'Kích hoạt tài khoản thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 07:56:42'),
(2, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 07:56:52'),
(3, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 08:04:45'),
(4, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 08:04:53'),
(5, 5, 'RESET_PASSWORD', 'Đặt lại mật khẩu thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 08:07:21'),
(6, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 08:07:26'),
(7, 6, 'REGISTER', 'Kích hoạt tài khoản thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 08:08:37'),
(8, 6, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 08:08:49'),
(9, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 08:25:27'),
(10, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 08:29:47'),
(11, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:08:38'),
(12, 6, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:08:44'),
(13, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:09:04'),
(14, 5, 'RESET_PASSWORD', 'Reset mật khẩu người dùng ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:18:09'),
(15, 6, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:19:26'),
(16, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:20:01'),
(17, 5, 'UPDATE_USER', 'Cập nhật người dùng ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:29:09'),
(18, 5, 'UPDATE_USER', 'Cập nhật người dùng ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:29:12'),
(19, 5, 'UPDATE_USER', 'Cập nhật người dùng ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:29:42'),
(20, 5, 'UPDATE_USER', 'Cập nhật người dùng ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:29:49'),
(21, 5, 'UPDATE_USER', 'Cập nhật người dùng ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:39:18'),
(22, 5, 'RESET_PASSWORD', 'Reset mật khẩu người dùng ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:39:22'),
(23, 5, 'UPDATE_USER', 'Cập nhật người dùng ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-18 09:39:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouses`
--

CREATE TABLE `warehouses` (
  `warehouse_id` int(11) NOT NULL,
  `warehouse_name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouses`
--

INSERT INTO `warehouses` (`warehouse_id`, `warehouse_name`, `address`, `contact_person`, `contact_phone`, `created_at`, `updated_at`) VALUES
(1, 'Kho Linh Kiện Điện Tử', 'Hà Nội', 'Đào Văn Tâm', '0969859400', '2025-05-18 10:02:56', '2025-05-18 10:02:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_zones`
--

CREATE TABLE `warehouse_zones` (
  `zone_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `zone_code` varchar(10) NOT NULL,
  `zone_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouse_zones`
--

INSERT INTO `warehouse_zones` (`zone_id`, `warehouse_id`, `zone_code`, `zone_name`, `description`, `created_at`) VALUES
(1, 1, 'A', 'Khu hàng điện tử', 'Vip promax', '2025-05-18 10:03:25');

-- --------------------------------------------------------

--
-- Cấu trúc cho view `expiring_products`
--
DROP TABLE IF EXISTS `expiring_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `expiring_products`  AS SELECT `p`.`product_code` AS `product_code`, `p`.`product_name` AS `product_name`, `w`.`warehouse_name` AS `warehouse_name`, `s`.`shelf_code` AS `shelf_code`, `pl`.`batch_number` AS `batch_number`, `pl`.`expiry_date` AS `expiry_date`, `pl`.`quantity` AS `quantity`, to_days(`pl`.`expiry_date`) - to_days(curdate()) AS `days_until_expiry` FROM ((((`product_locations` `pl` join `products` `p` on(`pl`.`product_id` = `p`.`product_id`)) join `shelves` `s` on(`pl`.`shelf_id` = `s`.`shelf_id`)) join `warehouse_zones` `wz` on(`s`.`zone_id` = `wz`.`zone_id`)) join `warehouses` `w` on(`wz`.`warehouse_id` = `w`.`warehouse_id`)) WHERE `pl`.`expiry_date` is not null AND `pl`.`expiry_date` > curdate() AND to_days(`pl`.`expiry_date`) - to_days(curdate()) <= 30 ORDER BY to_days(`pl`.`expiry_date`) - to_days(curdate()) ASC ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `export_statistics`
--
DROP TABLE IF EXISTS `export_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `export_statistics`  AS SELECT cast(`eo`.`created_at` as date) AS `export_date`, `w`.`warehouse_name` AS `warehouse_name`, count(distinct `eo`.`export_id`) AS `total_exports`, sum(`eod`.`quantity`) AS `total_quantity`, sum(`eod`.`total_price`) AS `total_amount` FROM ((`export_orders` `eo` join `export_order_details` `eod` on(`eo`.`export_id` = `eod`.`export_id`)) join `warehouses` `w` on(`eo`.`warehouse_id` = `w`.`warehouse_id`)) WHERE `eo`.`status` = 'COMPLETED' GROUP BY cast(`eo`.`created_at` as date), `w`.`warehouse_id` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `import_statistics`
--
DROP TABLE IF EXISTS `import_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `import_statistics`  AS SELECT cast(`io`.`created_at` as date) AS `import_date`, `w`.`warehouse_name` AS `warehouse_name`, `s`.`supplier_name` AS `supplier_name`, count(distinct `io`.`import_id`) AS `total_imports`, sum(`iod`.`quantity`) AS `total_quantity`, sum(`iod`.`total_price`) AS `total_amount` FROM (((`import_orders` `io` join `import_order_details` `iod` on(`io`.`import_id` = `iod`.`import_id`)) join `warehouses` `w` on(`io`.`warehouse_id` = `w`.`warehouse_id`)) join `suppliers` `s` on(`io`.`supplier_id` = `s`.`supplier_id`)) WHERE `io`.`status` = 'COMPLETED' GROUP BY cast(`io`.`created_at` as date), `w`.`warehouse_id`, `s`.`supplier_id` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `inventory_report`
--
DROP TABLE IF EXISTS `inventory_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inventory_report`  AS SELECT `p`.`product_code` AS `product_code`, `p`.`product_name` AS `product_name`, `c`.`category_name` AS `category_name`, `w`.`warehouse_name` AS `warehouse_name`, `i`.`quantity` AS `quantity`, `p`.`price` AS `price`, `i`.`quantity`* `p`.`price` AS `total_value`, `p`.`minimum_stock` AS `minimum_stock`, CASE WHEN `i`.`quantity` <= `p`.`minimum_stock` THEN 'Thấp' WHEN `i`.`quantity` <= `p`.`minimum_stock` * 1.5 THEN 'Trung bình' ELSE 'Cao' END AS `stock_level` FROM (((`inventory` `i` join `products` `p` on(`i`.`product_id` = `p`.`product_id`)) join `categories` `c` on(`p`.`category_id` = `c`.`category_id`)) join `warehouses` `w` on(`i`.`warehouse_id` = `w`.`warehouse_id`)) ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `shelf_utilization`
--
DROP TABLE IF EXISTS `shelf_utilization`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `shelf_utilization`  AS SELECT `s`.`shelf_id` AS `shelf_id`, `s`.`shelf_code` AS `shelf_code`, `wz`.`zone_code` AS `zone_code`, `w`.`warehouse_name` AS `warehouse_name`, `s`.`max_capacity` AS `max_capacity`, sum(ifnull(`pl`.`quantity` * `p`.`volume`,0)) AS `used_capacity`, sum(ifnull(`pl`.`quantity` * `p`.`volume`,0)) / `s`.`max_capacity` * 100 AS `utilization_percentage`, CASE WHEN sum(ifnull(`pl`.`quantity` * `p`.`volume`,0)) / `s`.`max_capacity` * 100 < 30 THEN 'Thấp' WHEN sum(ifnull(`pl`.`quantity` * `p`.`volume`,0)) / `s`.`max_capacity` * 100 < 70 THEN 'Trung bình' ELSE 'Cao' END AS `utilization_level` FROM ((((`shelves` `s` join `warehouse_zones` `wz` on(`s`.`zone_id` = `wz`.`zone_id`)) join `warehouses` `w` on(`wz`.`warehouse_id` = `w`.`warehouse_id`)) left join `product_locations` `pl` on(`s`.`shelf_id` = `pl`.`shelf_id`)) left join `products` `p` on(`pl`.`product_id` = `p`.`product_id`)) GROUP BY `s`.`shelf_id` ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Chỉ mục cho bảng `export_orders`
--
ALTER TABLE `export_orders`
  ADD PRIMARY KEY (`export_id`),
  ADD UNIQUE KEY `export_code` (`export_code`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Chỉ mục cho bảng `export_order_details`
--
ALTER TABLE `export_order_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `export_id` (`export_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `shelf_id` (`shelf_id`);

--
-- Chỉ mục cho bảng `import_orders`
--
ALTER TABLE `import_orders`
  ADD PRIMARY KEY (`import_id`),
  ADD UNIQUE KEY `import_code` (`import_code`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Chỉ mục cho bảng `import_order_details`
--
ALTER TABLE `import_order_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `import_id` (`import_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `shelf_id` (`shelf_id`);

--
-- Chỉ mục cho bảng `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD UNIQUE KEY `product_id` (`product_id`,`warehouse_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Chỉ mục cho bảng `inventory_checks`
--
ALTER TABLE `inventory_checks`
  ADD PRIMARY KEY (`check_id`),
  ADD UNIQUE KEY `check_code` (`check_code`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `zone_id` (`zone_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `inventory_check_results`
--
ALTER TABLE `inventory_check_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `check_id` (`check_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `shelf_id` (`shelf_id`);

--
-- Chỉ mục cho bảng `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD UNIQUE KEY `movement_code` (`movement_code`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `source_warehouse_id` (`source_warehouse_id`),
  ADD KEY `source_shelf_id` (`source_shelf_id`),
  ADD KEY `target_warehouse_id` (`target_warehouse_id`),
  ADD KEY `target_shelf_id` (`target_shelf_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `iot_devices`
--
ALTER TABLE `iot_devices`
  ADD PRIMARY KEY (`device_id`),
  ADD UNIQUE KEY `device_code` (`device_code`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `zone_id` (`zone_id`);

--
-- Chỉ mục cho bảng `iot_device_statuses`
--
ALTER TABLE `iot_device_statuses`
  ADD PRIMARY KEY (`status_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `product_alerts`
--
ALTER TABLE `product_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Chỉ mục cho bảng `product_locations`
--
ALTER TABLE `product_locations`
  ADD PRIMARY KEY (`location_id`),
  ADD UNIQUE KEY `product_id` (`product_id`,`shelf_id`,`batch_number`),
  ADD KEY `shelf_id` (`shelf_id`);

--
-- Chỉ mục cho bảng `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Chỉ mục cho bảng `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

--
-- Chỉ mục cho bảng `shelves`
--
ALTER TABLE `shelves`
  ADD PRIMARY KEY (`shelf_id`),
  ADD UNIQUE KEY `zone_id` (`zone_id`,`shelf_code`);

--
-- Chỉ mục cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `supplier_code` (`supplier_code`);

--
-- Chỉ mục cho bảng `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Chỉ mục cho bảng `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`warehouse_id`);

--
-- Chỉ mục cho bảng `warehouse_zones`
--
ALTER TABLE `warehouse_zones`
  ADD PRIMARY KEY (`zone_id`),
  ADD UNIQUE KEY `warehouse_id` (`warehouse_id`,`zone_code`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `export_orders`
--
ALTER TABLE `export_orders`
  MODIFY `export_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `export_order_details`
--
ALTER TABLE `export_order_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `import_orders`
--
ALTER TABLE `import_orders`
  MODIFY `import_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `import_order_details`
--
ALTER TABLE `import_order_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `inventory_checks`
--
ALTER TABLE `inventory_checks`
  MODIFY `check_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `inventory_check_results`
--
ALTER TABLE `inventory_check_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `iot_devices`
--
ALTER TABLE `iot_devices`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `iot_device_statuses`
--
ALTER TABLE `iot_device_statuses`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_alerts`
--
ALTER TABLE `product_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_locations`
--
ALTER TABLE `product_locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `shelves`
--
ALTER TABLE `shelves`
  MODIFY `shelf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `warehouse_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `warehouse_zones`
--
ALTER TABLE `warehouse_zones`
  MODIFY `zone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `export_orders`
--
ALTER TABLE `export_orders`
  ADD CONSTRAINT `export_orders_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  ADD CONSTRAINT `export_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `export_orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Các ràng buộc cho bảng `export_order_details`
--
ALTER TABLE `export_order_details`
  ADD CONSTRAINT `export_order_details_ibfk_1` FOREIGN KEY (`export_id`) REFERENCES `export_orders` (`export_id`),
  ADD CONSTRAINT `export_order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `export_order_details_ibfk_3` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`);

--
-- Các ràng buộc cho bảng `import_orders`
--
ALTER TABLE `import_orders`
  ADD CONSTRAINT `import_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `import_orders_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  ADD CONSTRAINT `import_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `import_orders_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Các ràng buộc cho bảng `import_order_details`
--
ALTER TABLE `import_order_details`
  ADD CONSTRAINT `import_order_details_ibfk_1` FOREIGN KEY (`import_id`) REFERENCES `import_orders` (`import_id`),
  ADD CONSTRAINT `import_order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `import_order_details_ibfk_3` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`);

--
-- Các ràng buộc cho bảng `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`);

--
-- Các ràng buộc cho bảng `inventory_checks`
--
ALTER TABLE `inventory_checks`
  ADD CONSTRAINT `inventory_checks_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  ADD CONSTRAINT `inventory_checks_ibfk_2` FOREIGN KEY (`zone_id`) REFERENCES `warehouse_zones` (`zone_id`),
  ADD CONSTRAINT `inventory_checks_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Các ràng buộc cho bảng `inventory_check_results`
--
ALTER TABLE `inventory_check_results`
  ADD CONSTRAINT `inventory_check_results_ibfk_1` FOREIGN KEY (`check_id`) REFERENCES `inventory_checks` (`check_id`),
  ADD CONSTRAINT `inventory_check_results_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `inventory_check_results_ibfk_3` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`);

--
-- Các ràng buộc cho bảng `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `inventory_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `inventory_movements_ibfk_2` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  ADD CONSTRAINT `inventory_movements_ibfk_3` FOREIGN KEY (`source_shelf_id`) REFERENCES `shelves` (`shelf_id`),
  ADD CONSTRAINT `inventory_movements_ibfk_4` FOREIGN KEY (`target_warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  ADD CONSTRAINT `inventory_movements_ibfk_5` FOREIGN KEY (`target_shelf_id`) REFERENCES `shelves` (`shelf_id`),
  ADD CONSTRAINT `inventory_movements_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Các ràng buộc cho bảng `iot_devices`
--
ALTER TABLE `iot_devices`
  ADD CONSTRAINT `iot_devices_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  ADD CONSTRAINT `iot_devices_ibfk_2` FOREIGN KEY (`zone_id`) REFERENCES `warehouse_zones` (`zone_id`);

--
-- Các ràng buộc cho bảng `iot_device_statuses`
--
ALTER TABLE `iot_device_statuses`
  ADD CONSTRAINT `iot_device_statuses_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `iot_devices` (`device_id`);

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Các ràng buộc cho bảng `product_alerts`
--
ALTER TABLE `product_alerts`
  ADD CONSTRAINT `product_alerts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `product_alerts_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`);

--
-- Các ràng buộc cho bảng `product_locations`
--
ALTER TABLE `product_locations`
  ADD CONSTRAINT `product_locations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `product_locations_ibfk_2` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`);

--
-- Các ràng buộc cho bảng `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `shelves`
--
ALTER TABLE `shelves`
  ADD CONSTRAINT `shelves_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `warehouse_zones` (`zone_id`);

--
-- Các ràng buộc cho bảng `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Các ràng buộc cho bảng `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Các ràng buộc cho bảng `warehouse_zones`
--
ALTER TABLE `warehouse_zones`
  ADD CONSTRAINT `warehouse_zones_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
