<?php
// item_detail.php
$page_title = "รายละเอียดรายการสิ่งของ";
require_once 'includes/header.php';

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($item_id === 0) {
    echo '<div class="alert alert-danger text-center">ไม่พบ ID รายการที่ระบุ</div>';
    require_once 'includes/footer.php';
    exit();
}

// ดึงข้อมูลรายการ รวมถึงผู้แจ้งและหมวดหมู่
$sql = "SELECT i.*, u.fullname, c.category_name 
        FROM items i
        JOIN users u ON i.reporter_id = u.user_id
        JOIN categories c ON i.category_id = c.category_id
        WHERE i.item_id = ? AND i.item_status != 4 -- ไม่แสดงรายการที่ถูกปฏิเสธ (4)
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger text-center">ไม่พบรายการสิ่งของ หรือรายการถูกลบออกจากระบบแล้ว</div>';
    require_once 'includes/footer.php';
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();

// กำหนดตัวแปรสำหรับแสดงผล
$is_lost = ($item['item_type'] == 1);
$type_text = $is_lost ? 'ของหาย' : 'ของที่เก็บได้';
$type_class = $is_lost ? 'danger' : 'success';
$date_label = $is_lost ? 'วันที่หาย' : 'วันที่พบ';
$location_label = $is_lost ? 'สถานที่คาดว่าหาย' : 'สถานที่ที่พบ';

// ฟังก์ชันแสดงสถานะ
function getStatusLabel($status_id) {
    switch ($status_id) {
        case 1: return ['text' => 'รอการตรวจสอบ', 'class' => 'warning'];
        case 2: return ['text' => 'ยืนยันและประกาศแล้ว', 'class' => 'info'];
        case 3: return ['text' => 'ส่งคืนเจ้าของสำเร็จ', 'class' => 'success'];
        default: return ['text' => 'ไม่ทราบสถานะ', 'class' => 'secondary'];
    }
}
$status_info = getStatusLabel($item['item_status']);
$is_verified = ($item['item_status'] >= 2);
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>">หน้าหลัก</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($item['item_name']); ?></li>
            </ol>
        </nav>
        
        <h2 class="mb-4 text-<?= $type_class; ?>"><i class="fas fa-box"></i> รายละเอียด: <?= htmlspecialchars($item['item_name']); ?></h2>
        
        <div class="alert alert-<?= $status_info['class']; ?> text-center">
            <strong><i class="fas fa-info-circle"></i> สถานะ:</strong> <?= $status_info['text']; ?>
            <?php if (!$is_verified): ?>
                <br><small>รายการนี้อยู่ระหว่างการตรวจสอบโดยเจ้าหน้าที่มหาวิทยาลัย</small>
            <?php endif; ?>
        </div>
        
        <div class="card shadow-lg mb-5">
            <div class="row g-0">
                <div class="col-md-5">
                    <?php if ($item['item_image']): ?>
                        <img src="<?= BASE_URL . $item['item_image']; ?>" 
                             class="img-fluid rounded-start w-100 h-100" 
                             alt="<?= htmlspecialchars($item['item_name']); ?>" 
                             style="object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light p-5 text-center h-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-image fa-3x text-muted"></i>
                            <p class="mt-3">ไม่มีรูปภาพประกอบ</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-7">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-3"><?= htmlspecialchars($item['item_name']); ?></h3>
                        
                        <p><strong>หมวดหมู่:</strong> <span class="badge bg-primary"><?= htmlspecialchars($item['category_name']); ?></span></p>
                        <p><strong>ประเภทการแจ้ง:</strong> <span class="badge bg-<?= $type_class; ?>"><?= $type_text; ?></span></p>
                        <p><strong><?= $date_label; ?>:</strong> <?= date('d/m/Y', strtotime($item['date_found_lost'])); ?></p>
                        <p><strong><?= $location_label; ?>:</strong> <?= htmlspecialchars($item['location_found_lost']); ?></p>
                        
                        <hr>
                        
                        <h5>คำอธิบายเพิ่มเติม (ลักษณะเด่น, ยี่ห้อ, สี)</h5>
                        <p><?= nl2br(htmlspecialchars($item['description'])); ?></p>
                        
                        <hr>

                        <?php if ($is_verified): ?>
                            <div class="alert alert-success mt-3">
                                <h5><i class="fas fa-phone-alt"></i> ข้อมูลติดต่อ (ติดต่อเจ้าหน้าที่)</h5>
                                <p>รายการนี้ได้รับการยืนยันแล้ว หากคุณเป็นเจ้าของ โปรดนำหลักฐานความเป็นเจ้าของมาติดต่อที่ **ฝ่ายบริการนักศึกษา อาคาร 10 ชั้น 1** หรือติดต่อเจ้าหน้าที่</p>
                                <a href="mailto:staff@umt.ac.th" class="btn btn-warning"><i class="fas fa-envelope"></i> ส่งอีเมลถึงเจ้าหน้าที่</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-clock"></i> ข้อมูลติดต่อจะปรากฏเมื่อเจ้าหน้าที่ยืนยันรายการแล้ว
                            </div>
                        <?php endif; ?>

                        <p class="mt-4"><small class="text-muted">แจ้งโดย: <?= htmlspecialchars($item['fullname']); ?> เมื่อวันที่ <?= date('d/m/Y H:i', strtotime($item['created_at'])); ?> น.</small></p>
                    </div>
                </div>
            </div>
        </div>
        
        <p class="text-center"><a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> กลับสู่หน้าหลัก</a></p>

    </div>
</div>

<?php
require_once 'includes/footer.php';
?>