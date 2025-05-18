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
$avatar = 'image/avata.jpg'; // Avatar mặc định
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT avatar FROM users WHERE user_id = '$user_id'";
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
    <title>Admin - Kho hàng thông minh</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/font.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/chucnang.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h2 class="mb-0">Kho hàng thông minh</h2>
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
            <li class="nav-item <?php echo ($option == 'nhanvien') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=nhanvien">
                    <i class="fas fa-user"></i>
                    <span>Quản lý người dùng</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'kho') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=kho">
                    <i class="fas fa-warehouse"></i>
                    <span>Quản lý kho</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'nhapkho') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=nhapkho">
                    <i class="fas fa-arrow-down"></i>
                    <span>Nhập kho</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'xuatkho') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=xuatkho">
                    <i class="fas fa-arrow-up"></i>
                    <span>Xuất kho</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'kiemke') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=kiemke">
                    <i class="fas fa-check-square"></i>
                    <span>Kiểm kê kho</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'hethongIoT') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=hethongIoT">
                    <i class="fas fa-network-wired"></i>
                    <span>Hệ thống IoT</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'tichhop') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=tichhop">
                    <i class="fas fa-plug"></i>
                    <span>Tích hợp và tự động hóa</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($option == 'baocaothongke') ? 'active' : ''; ?>">
                <a class="nav-link" href="?option=baocaothongke">
                    <i class="fas fa-chart-pie"></i>
                    <span>Báo cáo thống kê</span>
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
                include 'views/sanpham.php';
                break;
       
            case 'taikhoan':
                include 'views/taikhoancuatoi.php';
                break;
            case 'nhanvien':
                include 'views/nguoidung.php';
                break;
            case 'kho':
                include 'views/kho.php';
                break;
            case 'nhapkho':
                include 'views/nhapkho.php';
                break;
            case 'xuatkho':
                include 'views/xuatkho.php';
                break;
            case 'kiemke':
                include 'views/kiemke.php';
                break;
            case 'hethongIoT':
                include 'views/hethongIoT.php';
                break;
            case 'baocaothongke':
                include 'views/baocaothongke.php';
                break;
            case 'tichhop':
                include 'views/tudonghoa.php';
                break;
            case 'caidat':
                include 'views/setting.php';
                break;
            default:
                include '404.php';
        }
        ?>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
    <?php if ($option == 'home' || $option == 'thongke') { ?>
        <script src="js/chart.js"></script>
        <script src="js/tongquan.js"></script>
    <?php } ?>
</body>
</html>