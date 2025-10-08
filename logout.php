<?php
// logout.php
require_once 'config/db_connect.php'; // เพื่อให้มั่นใจว่า session_start() ถูกเรียก

// ล้างตัวแปร Session ทั้งหมด
$_SESSION = array();

// ทำลาย Session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

$_SESSION['message'] = ['type' => 'info', 'text' => 'คุณออกจากระบบแล้ว'];

// นำกลับไปหน้าหลัก
header('Location: index.php');
exit();
?>