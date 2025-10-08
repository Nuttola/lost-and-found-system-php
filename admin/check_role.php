<?php
// admin/check_role.php
// ไฟล์นี้ใช้ตรวจสอบสิทธิ์ผู้ใช้งาน

// ตรวจสอบว่ามีการเข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = [
        'type' => 'danger', 
        'text' => 'กรุณาเข้าสู่ระบบก่อนเข้าสู่ระบบจัดการหลังบ้าน'
    ];
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// ตรวจสอบบทบาท: ต้องเป็น Staff (2) หรือ Admin (3) เท่านั้น
if ($_SESSION['user_role'] < 2) {
    $_SESSION['message'] = [
        'type' => 'danger', 
        'text' => 'คุณไม่มีสิทธิ์เข้าถึงระบบจัดการหลังบ้าน'
    ];
    header('Location: ' . BASE_URL . 'user_dashboard.php');
    exit();
}
// หากผ่านการตรวจสอบสิทธิ์, สคริปต์จะดำเนินต่อไป
?>