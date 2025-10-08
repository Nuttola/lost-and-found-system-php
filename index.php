<?php
// index.php
$page_title = "หน้าหลัก - รายการของหายและของพบ";
require_once 'includes/header.php';

// ดึงรายการสิ่งของทั้งหมดจากตาราง items โดยเรียงตามวันที่ล่าสุด
$sql = "SELECT i.*, c.category_name, u.fullname 
        FROM items i
        JOIN categories c ON i.category_id = c.category_id
        JOIN users u ON i.reporter_id = u.user_id
        WHERE i.item_status != 3 -- ไม่แสดงรายการที่ถูกส่งคืนแล้ว
        ORDER BY i.created_at DESC";
$result = $conn->query($sql);

?>

<div class="hero-section text-center">
    <h1 class="display-4">ศูนย์กลางแจ้งของหาย-ตามหาของ</h1>
    <p class="lead">ในมหาวิทยาลัยราชภัฏชัยภูมิ</p>
    <a href="report_lost.php" class="btn btn-danger btn-lg me-2">แจ้งของหาย!</a>
    <a href="report_found.php" class="btn btn-success btn-lg">แจ้งของที่เก็บได้!</a>
</div>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="fas fa-list-alt"></i> รายการที่ถูกแจ้งล่าสุด</h2>
        <p class="text-muted">ข้อมูลที่แสดงจะถูกตรวจสอบโดยเจ้าหน้าที่ก่อนการยืนยัน (Confirmed)</p>
    </div>
</div>

<div class="row">
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <img src="<?= $row['item_image'] ? BASE_URL . $row['item_image'] : 'https://picsum.photos/400/300?grayscale&random=' . $row['item_id']; ?>" 
                         class="card-img-top" alt="<?= htmlspecialchars($row['item_name']); ?>" style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($row['item_name']); ?></h5>
                        <p class="card-text text-muted">ประเภท: <?= htmlspecialchars($row['category_name']); ?></p>
                        
                        <div class="mb-2">
                            <?php 
                                $type_class = ($row['item_type'] == 1) ? 'danger' : 'success';
                                $type_text = ($row['item_type'] == 1) ? 'แจ้งของหาย' : 'แจ้งของที่เก็บได้';
                            ?>
                            <span class="badge bg-<?= $type_class; ?>"><?= $type_text; ?></span>
                            
                            <?php 
                                $status_class = 'secondary';
                                $status_text = 'รอตรวจสอบ';
                                if ($row['item_status'] == 2) {
                                    $status_class = 'info';
                                    $status_text = 'ยืนยันแล้ว';
                                }
                            ?>
                            <span class="badge bg-<?= $status_class; ?>"><?= $status_text; ?></span>

                            <?php if ($row['ai_check_status'] == 2): ?>
                                <span class="badge bg-warning text-dark" data-bs-toggle="tooltip" title="รูปภาพอยู่ระหว่างการตรวจสอบโดยละเอียด"><i class="fas fa-eye"></i> ตรวจสอบภาพ</span>
                            <?php endif; ?>
                        </div>

                        <p class="card-text mt-auto"><small>สถานที่: <?= htmlspecialchars($row['location_found_lost']); ?></small></p>
                        <a href="item_detail.php?id=<?= $row['item_id']; ?>" class="btn btn-outline-primary btn-sm mt-2">ดูรายละเอียดเพิ่มเติม</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle"></i> ยังไม่มีรายการแจ้งของหายหรือของที่เก็บได้
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>