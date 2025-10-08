<?php
// report_found.php
$page_title = "แจ้งของที่เก็บได้ - Found Item Report";
require_once 'includes/header.php';

// ตรวจสอบว่ามีการเข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = [
        'type' => 'danger', 
        'text' => 'กรุณาเข้าสู่ระบบก่อนแจ้งรายการของที่เก็บได้'
    ];
    header('Location: login.php');
    exit();
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// ดึงรายการหมวดหมู่สำหรับ Dropdown
$categories_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");

// กำหนดค่าเริ่มต้นของสถานะสำหรับรายการ FOUND
$item_type = 2; // 2 = Found

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $category_id = (int)$_POST['category_id'];
    $description = $conn->real_escape_string($_POST['description']);
    $location_found = $conn->real_escape_string($_POST['location_found']);
    $date_found = $conn->real_escape_string($_POST['date_found']);
    $reporter_id = (int)$_SESSION['user_id'];
    
    $uploaded_path = null;
    $exif_date = null;
    $exif_location = null;
    
    // สถานะ AI: 0=ไม่ตรวจสอบ, 1=ผ่าน/น่าเชื่อถือ, 2=น่าสงสัย
    $ai_status = 0; 
    
    // --- 1. จัดการไฟล์อัปโหลด (กำหนดให้ต้องมีรูปภาพสำหรับ Found Item) ---
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid('found_') . '.' . $file_extension;
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
    } else {
         $message = ['type' => 'danger', 'text' => 'กรุณาแนบรูปภาพประกอบรายการที่เก็บได้'];
    }

    // --- 4. บันทึกข้อมูลลงฐานข้อมูล ---
    if (!$message) {
        // เพิ่มคอลัมน์ exif_date, exif_location, และ is_ai_generated
        $sql = "INSERT INTO items (reporter_id, item_name, category_id, item_type, description, date_found_lost, location_found_lost, item_image, exif_date, exif_location, item_status, is_ai_generated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
        
        $stmt = $conn->prepare($sql);
        // "isissssssssi" : i=int, s=string. (เพิ่ม s s i สำหรับ exif_date, exif_location, is_ai_generated)
        $stmt->bind_param("isisssssssi", $reporter_id, $item_name, $category_id, $item_type, $description, $date_found, $location_found, $uploaded_path, $exif_date, $exif_location, $ai_status);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'แจ้งรายการของที่เก็บได้สำเร็จแล้ว! รอเจ้าหน้าที่ตรวจสอบและยืนยัน.'];
            header('Location: user_dashboard.php');
            exit();
        } else {
            $message = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $conn->error];
        }

        $stmt->close();
    }
}
?>
<h2 class="mb-4">แจ้งรายการของที่เก็บได้</h2>
<?php if ($message): ?>
    <div class="alert alert-<?= $message['type']; ?>"><?= htmlspecialchars($message['text']); ?></div>
<?php endif; ?>

<div class="card shadow-lg">
    <div class="card-body p-4">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="item_name" class="form-label">ชื่อสิ่งของที่เก็บได้ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="item_name" name="item_name" placeholder="เช่น โทรศัพท์ iPhone 15, กุญแจรถยนต์ Honda" required>
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
                    <label for="date_found" class="form-label">วันที่พบ <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="date_found" name="date_found" max="<?= date('Y-m-d'); ?>"" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="location_found" class="form-label">สถานที่ที่พบ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location_found" name="location_found" placeholder="เช่น บริเวณลานจอดรถ, โต๊ะอ่านหนังสือในห้องสมุด" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">คำอธิบายเพิ่มเติม (สี, ลักษณะเด่น, ยี่ห้อ) <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>

            <div class="mb-4">
                <label for="item_image" class="form-label">รูปภาพประกอบ <span class="text-danger">*</span></label>
                <input class="form-control" type="file" id="item_image" name="item_image" accept="image/*" required>
                <div class="form-text">รูปภาพจะถูกตรวจสอบโดย AI เพื่อยืนยันความถูกต้องของรายการ</div>
            </div>
            
            <button type="submit" class="btn btn-success btn-lg w-100"><i class="fas fa-hands-helping"></i> ยืนยันการแจ้งของที่เก็บได้</button>
        </form>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>