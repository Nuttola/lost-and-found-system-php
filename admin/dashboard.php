<?php
// admin/dashboard.php
$page_title = "Admin Dashboard - ภาพรวมระบบ";
// เนื่องจากไฟล์นี้อยู่ในโฟลเดอร์ย่อย (admin/) จึงต้องกลับไปเรียก includes/header.php
require_once '../includes/header.php';
require_once 'check_role.php'; // ตรวจสอบสิทธิ์

// ดึงข้อมูลภาพรวมสถิติ
$total_items = $conn->query("SELECT COUNT(*) FROM items")->fetch_row()[0];
$pending_items = $conn->query("SELECT COUNT(*) FROM items WHERE item_status = 1")->fetch_row()[0];
$suspicious_items = $conn->query("SELECT COUNT(*) FROM items WHERE ai_check_status = 2 AND admin_verification = 0")->fetch_row()[0];
$returned_items = $conn->query("SELECT COUNT(*) FROM items WHERE item_status = 3")->fetch_row()[0];

// ดึงรายการที่ AI ตรวจจับว่าน่าสงสัย (สูงสุด 10 รายการ)
$suspicious_sql = "SELECT i.*, u.fullname, c.category_name 
                   FROM items i
                   JOIN users u ON i.reporter_id = u.user_id
                   JOIN categories c ON i.category_id = c.category_id
                   WHERE i.ai_check_status = 2 AND i.admin_verification = 0 
                   ORDER BY i.created_at ASC 
                   LIMIT 10";
$suspicious_result = $conn->query($suspicious_sql);

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-5"><i class="fas fa-tools"></i> ระบบจัดการหลังบ้าน</h2>
        <p class="lead">ยินดีต้อนรับ, เจ้าหน้าที่/ผู้ดูแลระบบ ท่านมีหน้าที่ตรวจสอบรายการเพื่อยืนยันความถูกต้อง</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $message['type']; ?> mb-4"><?= $message['text']; ?></div>
<?php endif; ?>

<div class="row mb-5">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white shadow-lg p-3 h-100">
            <div class="card-body text-center">
                <h5 class="card-title mb-3">รายการทั้งหมด</h5>
                <h1 class="display-4 fw-bold mb-0"><?= $total_items; ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-dark shadow-lg p-3 h-100">
            <div class="card-body text-center">
                <h5 class="card-title mb-3">รายการรอตรวจสอบ</h5>
                <h1 class="display-4 fw-bold mb-0"><?= $pending_items; ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white shadow-lg p-3 h-100">
            <div class="card-body text-center">
                <h5 class="card-title mb-3">🚨 ภาพต้องสงสัย (AI)</h5>
                <h1 class="display-4 fw-bold mb-0"><?= $suspicious_items; ?></h1>
                <p class="mt-2 mb-0"><small>ต้องรีบตรวจสอบ!</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white shadow-lg p-3 h-100">
            <div class="card-body text-center">
                <h5 class="card-title mb-3">ส่งคืนสำเร็จแล้ว</h5>
                <h1 class="display-4 fw-bold mb-0"><?= $returned_items; ?></h1>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-lg mb-5">
    <div class="card-header bg-danger text-white p-3">
        <i class="fas fa-exclamation-triangle"></i> รายการที่ต้องตรวจสอบรูปภาพ (AI Suspicious)
    </div>
    <div class="card-body p-4"> <?php if ($suspicious_items > 0): ?>
        <p class="text-danger my-3">รายการเหล่านี้มีรูปภาพที่ AI ตรวจพบว่ามีความน่าสงสัยว่าอาจเป็นภาพตัดต่อ หรือภาพปลอม โปรดตรวจสอบโดยละเอียด</p>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                </table>
        </div>
        <?php else: ?>
            <div class="alert alert-success text-center border-0 py-3 mt-3">
                <h4 class="alert-heading"><i class="fas fa-check-circle"></i> ไม่มีรายการภาพต้องสงสัย</h4>
                <p class="mb-0">ระบบสะอาด! ไม่มีรายการภาพต้องสงสัยที่รอการตรวจสอบในขณะนี้</p>
            </div>
        <?php endif; ?>

        <a href="items_manage.php" class="btn btn-primary mt-4"><i class="fas fa-list"></i> ดูรายการทั้งหมด</a>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>

<?php
require_once '../includes/footer.php';
?>