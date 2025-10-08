<?php
// admin/items_manage.php
$page_title = "Admin - จัดการรายการทั้งหมด";
require_once '../includes/header.php';
require_once 'check_role.php';

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// ดึงรายการทั้งหมด พร้อมข้อมูลผู้แจ้งและหมวดหมู่
$sql = "SELECT i.item_id, i.item_name, i.item_type, i.item_status, i.ai_check_status, i.created_at, u.fullname, c.category_name 
        FROM items i
        JOIN users u ON i.reporter_id = u.user_id
        JOIN categories c ON i.category_id = c.category_id
        ORDER BY i.created_at DESC";
$result = $conn->query($sql);

// ฟังก์ชันช่วยเหลือสำหรับแสดงสถานะ (Status Helper)
function getStatusBadge($status_id) {
    switch ($status_id) {
        case 1: return '<span class="badge bg-warning text-dark">รอตรวจสอบ</span>';
        case 2: return '<span class="badge bg-info">ยืนยันแล้ว</span>';
        case 3: return '<span class="badge bg-success">ส่งคืนสำเร็จ</span>';
        case 4: return '<span class="badge bg-danger">ถูกปฏิเสธ</span>';
        default: return '<span class="badge bg-secondary">ไม่ทราบ</span>';
    }
}

// ฟังก์ชันแสดงสถานะ AI
function getAIBadge($ai_status) {
    switch ($ai_status) {
        case 0: return '<span class="badge bg-light text-muted">N/A</span>';
        case 1: return '<span class="badge bg-success"><i class="fas fa-check"></i> ผ่าน</span>';
        case 2: return '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> สงสัย</span>';
        default: return '<span class="badge bg-secondary">?</span>';
    }
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-list-ul"></i> จัดการรายการทั้งหมดในระบบ</h2>
        <p class="lead">ดูและจัดการรายการของหายและของที่เก็บได้ทั้งหมด</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $message['type']; ?>"><?= $message['text']; ?></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-header bg-dark text-white">
        รายการสิ่งของ (ทั้งหมด <?= $result->num_rows; ?> รายการ)
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="itemsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ประเภท</th>
                        <th>ชื่อสิ่งของ</th>
                        <th>หมวดหมู่</th>
                        <th>ผู้แจ้ง</th>
                        <th>วันที่แจ้ง</th>
                        <th>สถานะ</th>
                        <th>AI Check</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $type_text = ($row['item_type'] == 1) ? 'หาย' : 'พบ';
                            $type_class = ($row['item_type'] == 1) ? 'danger' : 'success';
                        ?>
                        <tr>
                            <td><?= $row['item_id']; ?></td>
                            <td><span class="badge bg-<?= $type_class; ?>"><?= $type_text; ?></span></td>
                            <td><?= htmlspecialchars($row['item_name']); ?></td>
                            <td><?= htmlspecialchars($row['category_name']); ?></td>
                            <td><?= htmlspecialchars($row['fullname']); ?></td>
                            <td><?= date('d/m/Y', strtotime($row['created_at'])); ?></td>
                            <td><?= getStatusBadge($row['item_status']); ?></td>
                            <td><?= getAIBadge($row['ai_check_status']); ?></td>
                            <td>
                                <a href="item_verify.php?id=<?= $row['item_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> ตรวจสอบ
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">ไม่พบรายการสิ่งของในระบบ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>