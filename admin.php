<?php
session_start();
include_once 'inc/auth.php';
include_once 'config/connect.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Lấy thông tin avatar từ database
$avatar = 'assets/img/default-avatar.png'; // Avatar mặc định
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT avatar FROM nhanvien WHERE id = '$user_id'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        if(!empty($user_data['avatar'])) {
            $avatar = $user_data['avatar'];
        }
    }
}

$option = isset($_GET['option']) ? $_GET['option'] : 'home';    
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Cửa hàng máy tính</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/font.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/chucnang.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h2 class="mb-0">Cửa hàng máy tính</h2>
        <div class="d-flex align-items-center">
            <span class="me-3 fw-medium">Xin chào, Admin</span>
            <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="window.location.href='logout.php'">Đăng xuất</button>
        </div>
    </div>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo" onclick="window.location.href='?option=taikhoan';" style="cursor: pointer;">
            <img src="<?php echo $avatar; ?>" alt="Profile" class="profile-pic">
        </div>
        <ul class="nav flex-column mt-1">
            <li class="nav-item <?php echo ($option == 'home') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=home">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'sanpham') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=sanpham">
                    <i class="fas fa-laptop"></i>
                    <span>Quản lý sản phẩm</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'danhmuc') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=danhmuc">
                    <i class="fas fa-tags"></i>
                    <span>Danh mục</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'donhang') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=donhang">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Quản lý đơn hàng</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'khachhang') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=khachhang">
                    <i class="fas fa-users"></i>
                    <span>Khách hàng</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'tintuc') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=tintuc">
                    <i class="fas fa-newspaper"></i>
                    <span>Tin tức</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'khuyenmai') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=khuyenmai">
                    <i class="fas fa-gift"></i>
                    <span>Khuyến mãi</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'voucher') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=voucher">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Voucher</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'slideshow') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=slideshow">
                    <i class="fas fa-images"></i>
                    <span>Slideshow</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'lienhe') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=lienhe">
                    <i class="fas fa-envelope"></i>
                    <span>Liên hệ</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'nhanvien') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=nhanvien">
                    <i class="fas fa-user"></i>
                    <span>Quản lý nhân viên</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'thongke') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=thongke">
                    <i class="fas fa-chart-line"></i>
                    <span>Thống kê</span>
                </a>
            </li>

            <li class="nav-item <?php echo ($option == 'caidat') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=caidat">
                    <i class="fas fa-cog"></i>
                    <span>Cài đặt</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main content -->
    <main>
    <?php
          
        switch ($option) {
            case 'home':
                include 'views/tongquan.php';
                break;
            case 'sanpham':
                include 'views/qly_sanpham.php';
                break;
            case 'danhmuc':
                include 'views/qly_danhmuc.php';
                break;
            case 'khachhang':
                include 'views/qly_khachhang.php';
                break;
            case 'tintuc':
                include 'views/qly_tintuc.php';
                break;
            case 'khuyenmai':
                include 'views/qly_khuyenmai.php';
                break;
            case 'voucher':
                include 'views/qly_voucher.php';
                break;
            case 'slideshow':
                include 'views/qly_slideshow.php';
                break;
            case 'lienhe':
                include 'views/qly_lienhe.php';
                break;
            case 'caidat':
                include 'views/qly_caidat.php';
                break;
            case 'taikhoan':
                include 'views/taikhoancuatoi.php';
                break;
            case 'nhanvien':
                include 'views/qly_nhanvien.php';
                break;
            case 'donhang':
                include 'views/qly_donhang.php';
                break;
            case 'hoadon':
                include 'views/qly_hoadon.php';
                break;
            case 'thongke':
                include 'views/baocaothongke.php';
                break;
            default:
                include '404.php';
        }
        ?>
    </main>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <?php if ($option == 'home' || $option == 'thongke') { ?>
        <script src="assets/js/chart.js"></script>
        <script src="assets/js/tongquan.js"></script>
    <?php } ?>
</body>
</html>