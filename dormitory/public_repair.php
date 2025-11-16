<?php
require_once __DIR__ . '/includes/header.php';

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $db->beginTransaction();

        // Validate and sanitize input
        $title = filter_input(INPUT_POST, 'repair_type', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        // Validate required fields
        if (!$title || !$description || !$location || !$name || !$phone) {
            throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
        }

        // Create contact
        $contact_id = $db->insert('public_repair_contacts', [
            'full_name' => $name,
            'phone_number' => $phone,
            'email' => $email,
            'repair_item_name' => $title,
            'repair_description' => $description,
            'repair_location' => $location,
            'repair_priority' => 'ปานกลาง',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // สร้างรหัสติดตามการซ่อม: R + ปี(2หลัก) + เดือน(2หลัก) + วัน(2หลัก) + เลขสุ่ม(4หลัก)
        $request_id = 'R' . date('ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // หา location_id จากพื้นที่ส่วนกลาง (is_public_area = 1)
        $public_location = $db->fetch("
            SELECT location_id 
            FROM repair_locations 
            WHERE is_public_area = 1 
            LIMIT 1
        ");
        $location_id = $public_location ? $public_location['location_id'] : null;

        // Create repair request
        $repair_id = $db->insert('repair_requests', [
            'contact_id' => $contact_id,
            'location_id' => $location_id,
            'title' => $title,
            'description' => $description,
            'status' => 'รอดำเนินการ',
            'created_time' => date('Y-m-d H:i:s'),
            'updated_time' => date('Y-m-d H:i:s')
        ]);

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/repairs/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception('ไม่สามารถสร้างโฟลเดอร์สำหรับอัพโหลดรูปภาพได้');
                }
            }

            $file_info = pathinfo($_FILES['image']['name']);
            $ext = strtolower($file_info['extension']);

            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowed_types)) {
                throw new Exception('ไฟล์รูปภาพต้องเป็นนามสกุล JPG, PNG หรือ GIF เท่านั้น');
            }

            // Validate file size (5MB limit)
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                throw new Exception('ไฟล์รูปภาพต้องมีขนาดไม่เกิน 5MB');
            }

            $new_filename = 'repair_' . $repair_id . '_' . uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                throw new Exception('ไม่สามารถอัพโหลดรูปภาพได้');
            }

            // Save image record
            $db->insert('repair_images', [
                'request_id' => $repair_id,
                'image_path' => 'uploads/repairs/' . $new_filename,
                'created_time' => date('Y-m-d H:i:s')
            ]);
        }

        // บันทึกประวัติการแจ้งซ่อม
        $db->insert('repair_history', [
            'request_id' => $repair_id,
            'action' => '',
            'notes' => "แจ้งซ่อมโดยบุคคลภายนอก: {$name}",
            'created_time' => date('Y-m-d H:i:s')
        ]);

        $db->commit();

        // Set success message
        $_SESSION['success'] = true;
        $_SESSION['success_message'] = 'บันทึกการแจ้งซ่อมเรียบร้อยแล้ว';
        $_SESSION['tracking_info'] = $request_id;

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// ดึงข้อมูล success message จาก session
$success = isset($_SESSION['success']) ? $_SESSION['success'] : false;
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$tracking_info = isset($_SESSION['tracking_info']) ? $_SESSION['tracking_info'] : '';

// Clear session
unset($_SESSION['success'], $_SESSION['success_message'], $_SESSION['tracking_info']);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tools me-2 text-primary"></i>แจ้งซ่อมสำหรับพื้นที่ทั่วไป</h2>
            <a href="<?php echo Config::$baseUrl; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
            </a>
        </div>
    </div>
</div>

<?php if ($success): ?>
<!-- แสดงผลเมื่อบันทึกสำเร็จ -->
<div class="card border-success mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>บันทึกข้อมูลสำเร็จ</h5>
    </div>
    <div class="card-body">
        <div class="text-center py-4">
            <div class="display-1 text-success mb-3">
                <i class="fas fa-check-circle"></i>
            </div>
            <h4 class="mb-3"><?php echo $success_message; ?></h4>
            <div class="alert alert-info py-3">
                <p class="mb-1">รหัสติดตามการซ่อมของคุณคือ:</p>
                <h3 class="mb-0"><?php echo $tracking_info; ?></h3>
                <p class="small mt-2">กรุณาเก็บรหัสนี้ไว้เพื่อติดตามสถานะการซ่อม</p>
            </div>
            <p class="mb-4">เจ้าหน้าที่จะดำเนินการตรวจสอบและซ่อมแซมตามที่ท่านแจ้งโดยเร็วที่สุด</p>
            <div class="d-flex justify-content-center gap-2">
                <a href="<?php echo Config::$baseUrl; ?>/repair_status.php" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>ติดตามสถานะ
                </a>
                <a href="<?php echo Config::$baseUrl; ?>/public_repair.php" class="btn btn-outline-secondary">
                    <i class="fas fa-plus me-2"></i>แจ้งซ่อมเพิ่มเติม
                </a>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- คำอธิบายบริการ -->
<div class="card bg-light mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="text-primary mb-3">บริการแจ้งซ่อมออนไลน์สำหรับพื้นที่ส่วนกลางและพื้นที่ทั่วไป</h5>
                <p>บริการนี้สำหรับการแจ้งซ่อมในพื้นที่ส่วนกลางหรือพื้นที่สาธารณะ
                    ไม่จำเป็นต้องเป็นผู้พักอาศัยในหอพักก็สามารถแจ้งซ่อมได้</p>
                <ul class="mb-0">
                    <li>ซ่อมแซมสิ่งอำนวยความสะดวกในพื้นที่ส่วนกลาง</li>
                    <li>แจ้งปัญหาไฟฟ้า ประปา หรือโครงสร้างทั่วไป</li>
                    <li>ติดตามสถานะการซ่อมได้ด้วยรหัสติดตาม</li>
                </ul>
            </div>
            <div class="col-md-4 text-center">
                <img src="<?php echo Config::$baseUrl; ?>/assets/images/repair-service.svg" alt="บริการแจ้งซ่อม"
                    class="img-fluid" style="max-height: 150px;">
            </div>
        </div>
    </div>
</div>

<!-- ฟอร์มแจ้งซ่อม -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white py-3">
        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>กรอกข้อมูลการแจ้งซ่อม</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <!-- ข้อมูลการซ่อม -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">รายละเอียดการซ่อม</h6>

                    <div class="mb-3">
                        <label for="repair_type" class="form-label">ประเภทการซ่อม <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="repair_type" name="repair_type" required>
                            <option value="">เลือกประเภทการซ่อม</option>
                            <option value="ประปา"
                                <?php echo isset($_POST['repair_type']) && $_POST['repair_type'] == 'ประปา' ? 'selected' : ''; ?>>
                                ประปา</option>
                            <option value="ไฟฟ้า"
                                <?php echo isset($_POST['repair_type']) && $_POST['repair_type'] == 'ไฟฟ้า' ? 'selected' : ''; ?>>
                                ไฟฟ้า</option>
                            <option value="เฟอร์นิเจอร์"
                                <?php echo isset($_POST['repair_type']) && $_POST['repair_type'] == 'เฟอร์นิเจอร์' ? 'selected' : ''; ?>>
                                เฟอร์นิเจอร์</option>
                            <option value="โครงสร้าง"
                                <?php echo isset($_POST['repair_type']) && $_POST['repair_type'] == 'โครงสร้าง' ? 'selected' : ''; ?>>
                                โครงสร้าง</option>
                            <option value="พื้นที่ส่วนกลาง"
                                <?php echo isset($_POST['repair_type']) && $_POST['repair_type'] == 'พื้นที่ส่วนกลาง' ? 'selected' : ''; ?>>
                                พื้นที่ส่วนกลาง</option>
                            <option value="อื่นๆ"
                                <?php echo isset($_POST['repair_type']) && $_POST['repair_type'] == 'อื่นๆ' ? 'selected' : ''; ?>>
                                อื่นๆ</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">สถานที่ที่ต้องการซ่อม <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="location" name="location"
                            value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                            placeholder="เช่น ลานจอดรถ บริเวณทางเดิน ชั้น 1 อาคาร A" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">รายละเอียดการซ่อม <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4"
                            placeholder="อธิบายปัญหาที่ต้องการให้ซ่อมโดยละเอียด"
                            required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">รูปภาพประกอบ (ถ้ามี)</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i> รองรับไฟล์ JPG, PNG, GIF ขนาดไม่เกิน 5MB
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <!-- ข้อมูลผู้แจ้ง -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">ข้อมูลผู้แจ้ง</h6>

                    <div class="mb-3">
                        <label for="name" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                            placeholder="0XX-XXX-XXXX" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">อีเมล (ถ้ามี)</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            placeholder="example@email.com">
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        ข้อมูลส่วนตัวของท่านจะถูกใช้เพื่อติดต่อกลับเกี่ยวกับการซ่อมเท่านั้น
                    </div>
                </div>
            </div>

            <div class="border-top pt-3 mt-3 text-end">
                <button type="reset" class="btn btn-light me-2">
                    <i class="fas fa-redo me-1"></i> ล้างข้อมูล
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i> ส่งข้อมูลแจ้งซ่อม
                </button>
            </div>
        </form>
    </div>
</div>

<!-- วิธีการติดตาม -->
<div class="card mt-4 bg-light border-0">
    <div class="card-body">
        <h6 class="text-primary"><i class="fas fa-search me-2"></i>วิธีการติดตามสถานะการซ่อม</h6>
        <p class="small mb-0">หลังจากที่ท่านแจ้งซ่อมแล้ว ท่านจะได้รับรหัสติดตามการซ่อม สามารถนำรหัสดังกล่าวไปกรอกที่หน้า
            <a href="<?php echo Config::$baseUrl; ?>/repair_status.php">ตรวจสอบสถานะการซ่อม</a>
            เพื่อติดตามความคืบหน้าได้
        </p>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>