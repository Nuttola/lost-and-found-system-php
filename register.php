<?php
// register.php
$page_title = "ลงทะเบียนผู้ใช้งานใหม่";
require_once 'includes/header.php'; // header.php มีการเรียก db_connect.php และ session_start() แล้ว

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'user'; // กำหนดบทบาทเริ่มต้นเป็นผู้ใช้งานทั่วไป

    // 1. ตรวจสอบรหัสผ่าน
    if ($password !== $confirm_password) {
        $message = ['type' => 'danger', 'text' => 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน'];
    } 
    
    if (!$message) {
        // 2. ตรวจสอบอีเมลซ้ำ
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = ['type' => 'warning', 'text' => 'อีเมลนี้ถูกใช้ลงทะเบียนแล้ว'];
        }
        $check_stmt->close();
    }

   // 3. บันทึกข้อมูล
    if (!$message) {
        $hashed_password = hash_password($password); 
        
        // ***********************************************
        // !!! การแก้ไขชั่วคราว: กำหนด Role ด้วยมือเพื่อสร้าง Staff/Admin !!!
        $user_role_int = 1; // ค่าเริ่มต้นคือ User
        
        // ***********************************************
        
        $sql = "INSERT INTO users (fullname, email, password, user_role, phone) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // เนื่องจากฟอร์มไม่มีช่อง phone ให้กำหนดเป็นค่า NULL หรือเลขมั่วๆ ไปก่อน
        $dummy_phone = '0810000000'; 

        $stmt->bind_param("sssis", $fullname, $email, $hashed_password, $user_role_int, $dummy_phone); 
        
        if ($stmt->execute()) {
            $_SESSION['message'] = [
                'type' => 'success', 
                'text' => 'ลงทะเบียนสำเร็จแล้ว! กรุณาเข้าสู่ระบบ'
            ];
            header('Location: login.php');
            exit();
        } else {
            $message = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการลงทะเบียน: ' . $stmt->error];
        }
        $stmt->close();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <h2 class="mb-4"><i class="fas fa-user-plus"></i> สร้างบัญชีใหม่</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $message['type']; ?>"><?= $message['text']; ?></div>
        <?php endif; ?>

        <div class="card shadow-lg p-4">
            <form method="POST">
                <div class="mb-3">
                    <label for="fullname" class="form-label">ชื่อ-นามสกุล</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">อีเมล (ใช้เป็นชื่อผู้ใช้)</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg w-100">ลงทะเบียน</button>
                <p class="text-center mt-3">มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a></p>
            </form>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>