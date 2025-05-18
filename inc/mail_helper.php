<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendMail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // Cấu hình máy chủ
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Thay bằng SMTP server của bạn
        $mail->SMTPAuth = true;
        $mail->Username = 'thanhhtb2312@gmail.com'; // Thay bằng email của bạn
        $mail->Password = 'ejlsqkkocrokychg'; // Mật khẩu ứng dụng
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Người gửi và người nhận
        $mail->setFrom('thanhhtb2312@gmail.com', 'Hệ thống Quản lý Kho');
        $mail->addAddress($to);
        
        // Nội dung
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Ghi log lỗi
        error_log("Lỗi gửi mail: {$mail->ErrorInfo}");
        return false;
    }
}

// Tạo OTP ngẫu nhiên (6 chữ số)
function generateOTP() {
    return rand(100000, 999999);
}

// Kiểm tra OTP có hợp lệ không (chưa hết hạn)
function isValidOTP($expiry_time) {
    if (!$expiry_time) return false;
    return strtotime($expiry_time) > time();
}