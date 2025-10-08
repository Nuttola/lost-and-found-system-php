<?php
// login.php
$page_title = "เข้าสู่ระบบ";
require_once 'includes/header.php';

// หากล็อกอินอยู่แล้ว ให้ redirect ไปที่ dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: user_dashboard.php');
    exit();
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // ดึงข้อมูลผู้ใช้จากอีเมลที่กรอก
    $sql = "SELECT user_id, fullname, password, user_role, email FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // **!!! จุดตรวจสอบรหัสผ่าน !!!**
        if (password_verify($password, $user['password'])) {
            // ล็อกอินสำเร็จ: สร้าง Session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['user_role'] = $user['user_role']; 
            $_SESSION['email'] = $user['email']; // เพิ่มบรรทัดนี้เพื่อความสมบูรณ์
            
            $_SESSION['message'] = ['type' => 'success', 'text' => 'เข้าสู่ระบบสำเร็จ!'];
            
            // Redirect ตามบทบาท
            if ($user['user_role'] >= 2) { 
                header('Location: admin/dashboard.php');
            } else { 
                header('Location: user_dashboard.php');
            }
            exit();
        } else {
            // รหัสผ่านไม่ถูกต้อง
            $message = ['type' => 'danger', 'text' => 'รหัสผ่านไม่ถูกต้อง'];
        }
    } else {
        // ไม่พบอีเมล
        $message = ['type' => 'danger', 'text' => 'ไม่พบอีเมลในระบบ'];
    }

    $stmt->close();
}
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <h2 class="mb-4"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $message['type']; ?>"><?= $message['text']; ?></div>
        <?php endif; ?>

        <div class="card shadow-lg p-4">
            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">อีเมล</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg w-100">เข้าสู่ระบบ</button>
                <p class="text-center mt-3">ยังไม่มีบัญชี? <a href="register.php">ลงทะเบียนที่นี่</a></p>
                </form>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>