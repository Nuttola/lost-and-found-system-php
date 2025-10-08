<?php
// report_lost.php
$page_title = "แจ้งของหาย - Lost Item Report";
require_once 'includes/header.php';

// ตรวจสอบว่ามีการเข้าสู่ระบบแล้วหรือไม่ (ตาม 3.2.1.3: ต้องเข้าสู่ระบบก่อนแจ้ง)
if (!isset($_SESSION['user_id'])) {
    // ใช้ Bootstrap Alert แจ้งเตือน
    $_SESSION['message'] = [
        'type' => 'danger', 
        'text' => 'กรุณาเข้าสู่ระบบก่อนแจ้งรายการของหาย'
    ];
    header('Location: login.php');
    exit();
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// ดึงรายการหมวดหมู่สำหรับ Dropdown
$categories_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");

// กำหนดค่าเริ่มต้นของสถานะสำหรับรายการ LOST
$item_type = 1; // 1 = Lost

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $category_id = (int)$_POST['category_id'];
    $description = $conn->real_escape_string($_POST['description']);
    $location_lost = $conn->real_escape_string($_POST['location_lost']);
    $date_lost = $conn->real_escape_string($_POST['date_lost']);
    $reporter_id = (int)$_SESSION['user_id'];
    
    $uploaded_path = null;
    $exif_date = null;
    $exif_location = null;
    
    // สถานะ AI: 0=ไม่ตรวจสอบ, 1=ผ่าน/น่าเชื่อถือ, 2=น่าสงสัย
    $ai_status = 0; 
    
    // --- 1. จัดการไฟล์อัปโหลด ---
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid('lost_') . '.' . $file_extension;
        $target_file = $upload_dir . $new_file_name;
        $file_path = 'uploads/' . $new_file_name;
        
        // ตรวจสอบขนาดและประเภทไฟล์ (โค้ดเดิมที่คุณเคยทำ)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
               if (in_array(strtolower($file_extension), $allowed_types) && $_FILES['item_image']['size'] <= 5000000) { 
            if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target_file)) {
                $uploaded_path = $file_path;
                
                // --- 2. ดึงข้อมูล EXIF จากไฟล์ที่อัปโหลด (ทำครั้งเดียว) ---
                $exif_data = get_exif_data($target_file);
                $exif_date = $exif_data['date'];
                $exif_location = $exif_data['location'];
                
                // --- 3. เรียกใช้ฟังก์ชัน AI Check โดยใช้ข้อมูล EXIF ที่ดึงมาแล้ว ---
                $ai_status = get_ai_status_from_api($target_file, $exif_data);  
                
            } else {
                $message = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์.'];
            }
        } else {
            $message = ['type' => 'danger', 'text' => 'ไฟล์ภาพมีขนาดใหญ่เกิน 5MB หรือไม่ใช่ไฟล์ภาพที่อนุญาต.'];
        }
    }

    // --- 4. บันทึกข้อมูลลงฐานข้อมูล ---
    if (!$message) {
        // เพิ่มคอลัมน์ exif_date, exif_location, และ is_ai_generated
        $sql = "INSERT INTO items (reporter_id, item_name, category_id, item_type, description, date_found_lost, location_found_lost, item_image, exif_date, exif_location, item_status, is_ai_generated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
        
        $stmt = $conn->prepare($sql);
        // "isissssssssi" : i=int, s=string. (เพิ่ม s s i สำหรับ exif_date, exif_location, is_ai_generated)
        $stmt->bind_param("isisssssssi", $reporter_id, $item_name, $category_id, $item_type, $description, $date_lost, $location_lost, $uploaded_path, $exif_date, $exif_location, $ai_status);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'แจ้งรายการของหายสำเร็จแล้ว! รอเจ้าหน้าที่ตรวจสอบและยืนยัน.'];
            header('Location: user_dashboard.php');
            exit();
        } else {
            $message = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $conn->error];
        }

        $stmt->close();
    }
}
?>
<h2 class="mb-4">แจ้งรายการของหาย</h2>
<?php if ($message): ?>
    <div class="alert alert-<?= $message['type']; ?>"><?= htmlspecialchars($message['text']); ?></div>
<?php endif; ?>

<div class="card shadow-lg">
    <div class="card-body p-4">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="item_name" class="form-label">ชื่อสิ่งของที่หาย <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="item_name" name="item_name" placeholder="เช่น กระเป๋าสตางค์หนังสีดำ, MacBook Pro" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="category_id" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php while($category = $categories_result->fetch_assoc()): ?>
                            <option value="<?= $category['category_id']; ?>">
                                <?= htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_lost" class="form-label">วันที่คาดว่าหาย <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="date_lost" name="date_lost" max="<?= date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="location_lost" class="form-label">สถานที่ที่คาดว่าหาย <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location_lost" name="location_lost" placeholder="เช่น อาคาร 9 ชั้น 3, โรงอาหารคณะวิทย์" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">คำอธิบายเพิ่มเติม (สี, ลักษณะเด่น, ยี่ห้อ) <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>

            <div class="mb-4">
                <label for="item_image" class="form-label">รูปภาพประกอบ (ถ้ามี)</label>
                <input class="form-control" type="file" id="item_image" name="item_image" accept="image/*">
                <div class="form-text">ระบบจะใช้ AI ตรวจสอบรูปภาพของคุณเพื่อป้องกันภาพตัดต่อ/ภาพปลอม ก่อนส่งให้เจ้าหน้าที่ยืนยัน</div>
            </div>
            
            <button type="submit" class="btn btn-danger btn-lg w-100"><i class="fas fa-bullhorn"></i> ยืนยันการแจ้งของหาย</button>
        </form>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>