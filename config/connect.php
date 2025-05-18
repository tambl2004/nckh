<?php
// Thông tin kết nối
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'nckh';

try {
    // Tạo kết nối PDO
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    
    // Cấu hình PDO để báo lỗi
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Đảm bảo tương thích ngược với code sử dụng mysqli
    $conn = new mysqli($host, $username, $password, $database);
    
    // Kiểm tra kết nối mysqli
    if ($conn->connect_error) {
        throw new Exception("Kết nối MySQLi thất bại: " . $conn->connect_error);
    }
    
    // Đặt charset cho kết nối mysqli
    $conn->set_charset("utf8");
    
} catch (PDOException $e) {
    die("Kết nối PDO thất bại: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}
?>