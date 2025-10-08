<?php
// user_dashboard.php
$page_title = "แดชบอร์ดส่วนตัวของฉัน";
require_once 'includes/header.php';

// ตรวจสอบสถานะการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = [
        'type' => 'danger', 
        'text' => 'กรุณาเข้าสู่ระบบเพื่อดูแดชบอร์ด'
    ];
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// ดึงรายการสิ่งของที่ผู้ใช้คนนี้แจ้งทั้งหมด (Lost และ Found)
$sql = "SELECT i.*, c.category_name 
        FROM items i
        JOIN categories c ON i.category_id = c.category_id
        WHERE i.reporter_id = ?
        ORDER BY i.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// ฟังก์ชันช่วยเหลือสำหรับแสดงสถานะ (Status Helper)
function getItemStatus($status_id) {
    switch ($status_id) {
        case 1: return ['text' => 'รอการตรวจสอบ', 'class' => 'warning'];
        case 2: return ['text' => 'ยืนยันและประกาศแล้ว', 'class' => 'info'];
        case 3: return ['text' => 'ส่งคืนเจ้าของสำเร็จ', 'class' => 'success'];
        case 4: return ['text' => 'รายการถูกปฏิเสธ/ลบ', 'class' => 'danger'];
        default: return ['text' => 'ไม่ทราบสถานะ', 'class' => 'secondary'];
    }
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-chart-line"></i> แดชบอร์ดของฉัน | สวัสดี, <?= htmlspecialchars($_SESSION['fullname']); ?></h2>
        <p class="lead">รายการทั้งหมดที่คุณได้แจ้งไว้ในระบบ</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $message['type']; ?>"><?= $message['text']; ?></div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button" role="tab" aria-controls="items" aria-selected="true">
            <i class="fas fa-clipboard-list"></i> รายการทั้งหมดที่แจ้งไว้
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">
             <i class="fas fa-user-cog"></i> จัดการบัญชี
        </button>
    </li>
</ul>

<div class="tab-content" id="myTabContent">
    
    <div class="tab-pane fade show active" id="items" role="tabpanel" aria-labelledby="items-tab">
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $type = ($row['item_type'] == 1) ? 'หาย' : 'พบ';
                    $type_class = ($row['item_type'] == 1) ? 'danger' : 'success';
                    $status_info = getItemStatus($row['item_status']);
                    $ai_check_text = ($row['ai_check_status'] == 2) ? ' (ภาพต้องสงสัย)' : '';
                    $ai_check_badge_class = ($row['ai_check_status'] == 2) ? 'warning' : 'secondary';
                    $ai_check_badge_icon = ($row['ai_check_status'] == 2) ? 'fas fa-eye' : 'fas fa-check';
                ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-<?= $type_class; ?>">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="<?= $row['item_image'] ? BASE_URL . $row['item_image'] : 'https://picsum.photos/400/300?grayscale&random=' . $row['item_id']; ?>" 
                                        class="img-fluid rounded-start" alt="<?= htmlspecialchars($row['item_name']); ?>" style="height: 100%; object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title text-<?= $type_class; ?>"><?= htmlspecialchars($row['item_name']); ?></h5>
                                        <p class="card-text"><small class="text-muted">วันที่<?= $type; ?>: <?= date('d/m/Y', strtotime($row['date_found_lost'])); ?></small></p>
                                        <p class="card-text">สถานที่: <?= htmlspecialchars($row['location_found_lost']); ?></p>

                                        <div class="mt-2">
                                            <span class="badge bg-primary me-2"><?= htmlspecialchars($row['category_name']); ?></span>
                                            <span class="badge bg-<?= $status_info['class']; ?>"><?= $status_info['text']; ?></span>
                                            <?php if ($row['ai_check_status'] > 0): ?>
                                            <span class="badge bg-<?= $ai_check_badge_class; ?> text-dark">
                                                <i class="<?= $ai_check_badge_icon; ?>"></i> AI Check<?= $ai_check_text; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>

                                        <a href="item_detail.php?id=<?= $row['item_id']; ?>" class="btn btn-outline-secondary btn-sm mt-3 float-end">
                                            <i class="fas fa-eye"></i> ดูรายละเอียด
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center" role="alert">
                        <i class="fas fa-info-circle"></i> คุณยังไม่ได้แจ้งรายการใดๆ ในระบบ
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
        <div class="card p-4">
            <h4>ข้อมูลส่วนตัว</h4>
            <p><strong>ชื่อ:</strong> <?= htmlspecialchars($_SESSION['fullname']); ?></p>
            <p><strong>อีเมล:</strong> <?= htmlspecialchars($_SESSION['email'] ?? 'ไม่พบข้อมูล'); ?></p>
            <p><strong>บทบาท:</strong> ผู้ใช้งานทั่วไป (User)</p>
            
            <a href="edit_profile.php" class="btn btn-outline-primary mt-3 w-50 disabled" title="ฟังก์ชันนี้จะถูกพัฒนาในภายหลัง">
                <i class="fas fa-edit"></i> แก้ไขข้อมูลส่วนตัว
            </a>
            <a href="change_password.php" class="btn btn-outline-warning mt-3 w-50 disabled" title="ฟังก์ชันนี้จะถูกพัฒนาในภายหลัง">
                <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
            </a>
        </div>
    </div>

</div>


<?php
$stmt->close();
require_once 'includes/footer.php';
?>