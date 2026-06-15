<?php
$conn = new mysqli('localhost', 'root', '', 'feedback_dhv');
    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }

// Cấu hình site
define('SITE_NAME', 'Hệ thống Phản hồi Ý kiến - ĐH Vinh');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', $protocol . $host . '/student-feedback');
define('SITE_EMAIL', 'feedback@vinhuni.edu.vn');

// Cấu hình email (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('SMTP_FROM', 'feedback@vinhuni.edu.vn');
define('SMTP_FROM_NAME', 'ĐH Vinh - Hệ thống Phản hồi');

// Cấu hình upload
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Session
session_name('feedback_dhv_session');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
?>