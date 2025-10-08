<?php
// admin/item_verify.php
$page_title = "ตรวจสอบและยืนยันรายการ";
require_once '../includes/header.php';
require_once 'check_role.php';

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = null;

// 1. จัดการการอัปเดตสถานะ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $item_id_post = (int)$_POST['item_id'];
    $action = $_POST['action'];
    $new_status = 0;

    // กำหนดสถานะใหม่ตาม Action
    if ($action == 'verify') {
        $new_status = 2; // Confirmed
        $admin_verification = 1; // Verified by Admin
        $log_message = 'รายการถูกยืนยันและประกาศแล้ว';
    } elseif ($action == 'return') {
        $new_status = 3; // Returned
        $admin_verification = 1; 
        $log_message = 'รายการถูกส่งคืนเจ้าของแล้ว';
    } elseif ($action == 'reject') {
        $new_status = 4; // Rejected/Deleted (ตั้งค่า 4 หรือลบไปเลย)
        $log_message = 'รายการถูกปฏิเสธและซ่อนจากหน้าหลัก';
    } else {
        $new_status = 1; // Pending
        $admin_verification = 0;
    }

    // อัปเดตฐานข้อมูล
    $update_sql = "UPDATE items SET item_status = ?, admin_verification = ? WHERE item_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    
    // Admin Verification จะถูกตั้งค่าเป็น 1 (ยืนยัน) เมื่อมีการดำเนินการใดๆ ที่ถือเป็นการตรวจสอบ
    $update_stmt->bind_param("iii", $new_status, $admin_verification, $item_id_post); 
    
    if ($update_stmt->execute()) {
        $_SESSION['message'] = ['type' => 'success', 'text' => $log_message];
        header('Location: dashboard.php');
        exit();
    } else {
        $message = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ: ' . $update_stmt->error];
    }
    $update_stmt->close();
}


// 2. ดึงข้อมูลรายการ
$sql_item = "SELECT i.*, c.category_name, u.fullname AS reporter_name, u.phone AS reporter_phone, u.email AS reporter_email
             FROM items i 
             JOIN categories c ON i.category_id = c.category_id 
             JOIN users u ON i.reporter_id = u.user_id 
             WHERE i.item_id = ?";

// *** โค้ดที่แก้ไขและเพิ่มการตรวจสอบข้อผิดพลาด ***
$stmt_item = $conn->prepare($sql_item);

if ($stmt_item === false) {
    // กรณีที่ prepare ล้มเหลว (มักเกิดจาก Query ผิด)
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'SQL Prepare Error: ' . $conn->error . ' (Query: ' . $sql_item . ')'];
    header('Location: dashboard.php');
    exit();
}

// ถ้า prepare สำเร็จ:
$stmt_item->bind_param("i", $item_id);
$stmt_item->execute();
    
$result_item = $stmt_item->get_result(); 
    
if ($result_item->num_rows === 0) {
    // หากไม่พบรายการ
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'ไม่พบรายการที่ต้องการตรวจสอบ'];
    header('Location: dashboard.php');
    exit();
}

// กำหนดตัวแปร $item โดยตรงจากผลลัพธ์
$item = $result_item->fetch_assoc();
$stmt_item->close();

// ฟังก์ชันช่วยเหลือสำหรับแสดงสถานะ (Status Helper)
function getStatusInfo($status_id, $ai_check) {
    $info = ['text' => 'ไม่ทราบสถานะ', 'class' => 'secondary'];
    if ($status_id == 1) $info = ['text' => 'รอการตรวจสอบ', 'class' => 'warning'];
    if ($status_id == 2) $info = ['text' => 'ยืนยันและประกาศแล้ว', 'class' => 'info'];
    if ($status_id == 3) $info = ['text' => 'ส่งคืนเจ้าของสำเร็จ', 'class' => 'success'];
    if ($status_id == 4) $info = ['text' => 'ถูกปฏิเสธ/ถูกลบ', 'class' => 'danger'];
    
    $ai_badge = '';
    if ($ai_check == 2) {
        $ai_badge = '<span class="badge bg-danger me-2"><i class="fas fa-robot"></i> AI SUSPICIOUS</span>';
    } elseif ($ai_check == 1) {
        $ai_badge = '<span class="badge bg-success me-2"><i class="fas fa-check"></i> AI PASSED</span>';
    }
    
    $info['badge'] = $ai_badge . '<span class="badge bg-' . $info['class'] . '">' . $info['text'] . '</span>';
    return $info;
}

$status_info = getStatusInfo($item['item_status'], $item['ai_check_status']);
$type_text = ($item['item_type'] == 1) ? 'รายการของหาย' : 'รายการของที่เก็บได้';
$type_icon = ($item['item_type'] == 1) ? 'fas fa-search-minus' : 'fas fa-hand-holding-heart';

?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="<?= $type_icon; ?>"></i> ตรวจสอบ: <?= htmlspecialchars($item['item_name']); ?></h2>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $message['type']; ?>"><?= $message['text']; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-clipboard-list"></i> ข้อมูลรายการ</h4>
            </div>
            <div class="card-body">
                <p><strong>ชื่อสิ่งของ:</strong> <?= htmlspecialchars($item['item_name']); ?></p>
                <p><strong>ประเภท:</strong> <?= htmlspecialchars($item['category_name']); ?></p>
                <p><strong>ประเภทการแจ้ง:</strong> <span class="badge bg-info"><?= $type_text; ?></span></p>
                <p><strong>วันที่ <?= ($item['item_type'] == 1) ? 'หาย' : 'พบ'; ?>:</strong> <?= date('d/m/Y', strtotime($item['date_found_lost'])); ?></p>
                <p><strong>สถานที่:</strong> <?= htmlspecialchars($item['location_found_lost']); ?></p>
                <hr>
                <h5>คำอธิบายเพิ่มเติม</h5>
                <p><?= nl2br(htmlspecialchars($item['description'])); ?></p>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-user-tag"></i> ข้อมูลผู้รายงาน</h5>
            </div>
            <div class="card-body">
                <p><strong>ชื่อ-นามสกุล:</strong> 
                    <?= htmlspecialchars($item['reporter_name']); ?></p>
                <p><strong>อีเมล:</strong> 
                    <?= htmlspecialchars($item['reporter_email']); ?></p>
                <p><strong>เบอร์โทรศัพท์:</strong> 
                    <?= htmlspecialchars($item['reporter_phone']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white">
                <h4><i class="fas fa-camera"></i> รูปภาพหลักฐาน</h4>
            </div>
            <div class="card-body text-center">
                <?php if ($item['item_image']): ?>
                    <img src="<?= BASE_URL . $item['item_image']; ?>" class="img-fluid rounded shadow-sm" alt="หลักฐาน" style="max-height: 250px; object-fit: cover;">
                <?php else: ?>
                    <p class="text-muted">ไม่มีรูปภาพหลักฐาน</p>
                <?php endif; ?>
                <div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-microscope"></i> ข้อมูลหลักฐานดิจิทัล (Digital Evidence)</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-camera"></i> ข้อมูล EXIF (ภาพถ่าย)</h6>
                <p><strong>วันที่ถ่ายภาพ:</strong> 
                    <?php if ($item['exif_date']): ?>
                        <span class="text-success"><?= date('d/m/Y H:i:s', strtotime($item['exif_date'])); ?></span>
                    <?php else: ?>
                        <span class="text-muted">ไม่พบข้อมูลวันที่/เวลา</span>
                    <?php endif; ?>
                </p>
                <p><strong>ตำแหน่งที่ถ่าย:</strong> 
                    <?= $item['exif_location'] ? htmlspecialchars($item['exif_location']) : '<span class="text-muted">ไม่พบข้อมูลตำแหน่ง (GPS)</span>'; ?></p>
            </div>
            <div class="col-md-6 border-start ps-4">
                <h6><i class="fas fa-robot"></i> สถานะการตรวจสอบ AI</h6>
                <?php
                $ai_status = $item['is_ai_generated'];
                switch ($ai_status) {
                    case 1: $ai_badge = '<span class="badge bg-success py-2"><i class="fas fa-check-circle"></i> ผ่านการตรวจสอบ AI (น่าเชื่อถือ)</span>'; break;
                    case 2: $ai_badge = '<span class="badge bg-danger py-2"><i class="fas fa-exclamation-triangle"></i> น่าสงสัยว่า AI สร้าง/ตัดต่อ</span>'; break;
                    default: $ai_badge = '<span class="badge bg-secondary py-2">ไม่มีภาพ/ไม่ได้ตรวจสอบ AI</span>'; break;
                }
                ?>
                <p class="mt-3"><?= $ai_badge; ?></p>
                <?php if ($ai_status == 2): ?>
                    <div class="alert alert-danger mt-3 py-2">
                        <small>ระบบตรวจพบความผิดปกติของภาพถ่าย โปรดใช้ความระมัดระวังในการยืนยันรายการนี้</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-lg">
            <div class="card-header bg-info text-white">
                <h4><i class="fas fa-cogs"></i> การดำเนินการและสถานะ</h4>
            </div>
            <div class="card-body">
                <p><strong>สถานะปัจจุบัน:</strong> <?= $status_info['badge']; ?></p>
                <hr>
                <form method="POST">
                    <input type="hidden" name="item_id" value="<?= $item['item_id']; ?>">
                    
                    <button type="submit" name="action" value="verify" class="btn btn-info w-100 mb-2" 
                        <?= ($item['item_status'] == 2) ? 'disabled' : ''; ?>>
                        <i class="fas fa-check-circle"></i> ยืนยันและประกาศ
                    </button>
                    
                    <button type="submit" name="action" value="return" class="btn btn-success w-100 mb-2"
                        <?= ($item['item_status'] == 3) ? 'disabled' : ''; ?>>
                        <i class="fas fa-handshake"></i> ส่งคืนสำเร็จแล้ว
                    </button>
                    
                    <button type="submit" name="action" value="reject" class="btn btn-danger w-100" 
                        onclick="return confirm('คุณแน่ใจหรือไม่ที่จะปฏิเสธรายการนี้? รายการจะถูกซ่อนจากผู้ใช้');">
                        <i class="fas fa-times-circle"></i> ปฏิเสธรายการ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>