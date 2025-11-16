<?php
// เริ่มใช้ output buffering เพื่อป้องกันปัญหา headers already sent
ob_start();

require_once __DIR__ . '/../../../includes/header.php';

// ตรวจสอบการล็อกอิน
if (!Auth::isLoggedIn()) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบก่อนแจ้งซ่อม';
    header('Location: ' . Config::$baseUrl . '/login.php?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$userId = Auth::getUserId();
$db = Database::getInstance();

// ดึงข้อมูลนักศึกษาพร้อมข้อมูลห้องพัก
$student = $db->fetch("
    SELECT u.*, 
           r.room_id, 
           r.room_number,
           b.building_id,
           b.building_name
    FROM users u
    LEFT JOIN rooms r ON u.room_id = r.room_id
    LEFT JOIN buildings b ON r.building_id = b.building_id
    WHERE u.user_id = ?
", [$userId]);

// ดึงข้อมูลหมวดหมู่การซ่อม
$categories = $db->fetchAll("
    SELECT * FROM repair_categories
    ORDER BY category_name ASC
");

// ดึงข้อมูลสถานที่ซ่อม
$locations = $db->fetchAll("
    SELECT * FROM repair_locations
    WHERE (is_public = 1) OR (building_id = ?)
    ORDER BY name ASC
", [$student['building_id'] ?? 0]);

// ดึงข้อมูลรายการอุปกรณ์
$items = $db->fetchAll("
    SELECT i.*, c.category_name
    FROM repair_items i
    JOIN repair_categories c ON i.category_id = c.category_id
    ORDER BY c.category_name, i.item_name ASC
");

// หมายเลขติดตามการแจ้งซ่อม
$trackingCode = 'SR' . date('ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

// ดำเนินการเมื่อมีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($_POST['repair_type'])) {
        $errors[] = 'กรุณาระบุประเภทการซ่อม';
    }
    if (empty($_POST['repair_description'])) {
        $errors[] = 'กรุณาระบุรายละเอียดการซ่อม';
    }
    if (empty($_POST['repair_location'])) {
        $errors[] = 'กรุณาระบุตำแหน่งที่ต้องการซ่อม';
    }

    // ถ้าไม่มีข้อผิดพลาด ดำเนินการบันทึกข้อมูล
    if (empty($errors)) {
        try {
            // เริ่ม transaction
            $db->beginTransaction();

            // บันทึกข้อมูลการแจ้งซ่อม
            $repairData = [
                'user_id' => $userId,
                'room_id' => $student['room_id'] ?? null,
                'title' => 'แจ้งซ่อม: ' . $_POST['repair_type'],
                'description' => $_POST['repair_description'],
                'status' => 'รอดำเนินการ',
                'created_time' => date('Y-m-d H:i:s')
            ];

            // เพิ่มข้อมูลหมวดหมู่ถ้ามี
            if (!empty($_POST['category_id'])) {
                $repairData['item_id'] = $_POST['item_id'] ?? null;
            }

            // บันทึกข้อมูลลงตาราง repair_requests
            $repairId = $db->insert('repair_requests', $repairData);

            // อัพโหลดรูปภาพ (ถ้ามี)
            if (!empty($_FILES['repair_images']['name'][0])) {
                $uploadDir = __DIR__ . '/../../../uploads/repairs/';

                // สร้างโฟลเดอร์ถ้ายังไม่มี
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($_FILES['repair_images']['name'] as $key => $name) {
                    if ($_FILES['repair_images']['error'][$key] === 0) {
                        $tmpName = $_FILES['repair_images']['tmp_name'][$key];
                        $fileName = uniqid('repair_') . '_' . $name;
                        $uploadPath = $uploadDir . $fileName;

                        // ตรวจสอบประเภทไฟล์
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);

                        if (in_array(strtolower($ext), $allowed)) {
                            if (move_uploaded_file($tmpName, $uploadPath)) {
                                // บันทึกข้อมูลรูปภาพลงตาราง repair_images
                                $db->insert('repair_images', [
                                    'request_id' => $repairId,
                                    'image_path' => 'uploads/repairs/' . $fileName,
                                    'created_time' => date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                    }
                }
            }

            // Commit transaction
            $db->commit();

            // บันทึก log
            Functions::log("นักศึกษา ID: {$userId} แจ้งซ่อมใหม่ ID: {$repairId}", 'INFO');

            // แสดงข้อความสำเร็จ
            $_SESSION['success'] = "บันทึกการแจ้งซ่อมเรียบร้อยแล้ว หมายเลขติดตาม: " . $trackingCode;
            header('Location: ' . Config::$baseUrl . '/modules/public/repairs/student_repair.php?success=1&id=' . $repairId);
            exit;
        } catch (Exception $e) {
            // Rollback ในกรณีที่เกิดข้อผิดพลาด
            $db->rollback();
            $errors[] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
            Functions::log($e->getMessage(), 'ERROR');
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">แจ้งซ่อมหอพัก (สำหรับนักศึกษา)</h3>
                </div>

                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success'] ?? 'บันทึกการแจ้งซ่อมเรียบร้อยแล้ว'; ?>
                            <div class="mt-2">
                                <a href="<?php echo Config::$baseUrl; ?>/modules/public/repairs/list.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-list"></i> ดูรายการแจ้งซ่อม
                                </a>
                                <a href="<?php echo Config::$baseUrl; ?>/modules/public/repairs/student_repair.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus"></i> แจ้งซ่อมใหม่
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- แสดงข้อผิดพลาด (ถ้ามี) -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- ข้อมูลนักศึกษา -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h5 class="alert-heading"><i class="fas fa-user me-2"></i>ข้อมูลผู้แจ้งซ่อม</h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                                            <p class="mb-1"><strong>รหัสนักศึกษา:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
                                            <p class="mb-1"><strong>อีเมล:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>เบอร์โทรศัพท์:</strong> <?php echo htmlspecialchars($student['phone_number'] ?? 'ไม่ระบุ'); ?></p>
                                            <?php if (!empty($student['building_name']) && !empty($student['room_number'])): ?>
                                                <p class="mb-1"><strong>อาคาร:</strong> <?php echo htmlspecialchars($student['building_name']); ?></p>
                                                <p class="mb-1"><strong>ห้อง:</strong> <?php echo htmlspecialchars($student['room_number']); ?></p>
                                            <?php else: ?>
                                                <div class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> คุณยังไม่ได้ลงทะเบียนห้องพัก
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ฟอร์มแจ้งซ่อม -->
                        <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <!-- ประเภทการซ่อม -->
                            <div class="mb-3">
                                <label for="repair_type" class="form-label">ประเภทการซ่อม <span class="text-danger">*</span></label>
                                <select class="form-select" id="repair_type" name="repair_type" required>
                                    <option value="">-- เลือกประเภทการซ่อม --</option>
                                    <option value="ระบบประปา" <?php echo (isset($_POST['repair_type']) && $_POST['repair_type'] === 'ระบบประปา') ? 'selected' : ''; ?>>ระบบประปา (ก๊อกน้ำ, ท่อน้ำ, ฝักบัว)</option>
                                    <option value="ระบบไฟฟ้า" <?php echo (isset($_POST['repair_type']) && $_POST['repair_type'] === 'ระบบไฟฟ้า') ? 'selected' : ''; ?>>ระบบไฟฟ้า (หลอดไฟ, ปลั๊กไฟ, สวิตช์)</option>
                                    <option value="เฟอร์นิเจอร์" <?php echo (isset($_POST['repair_type']) && $_POST['repair_type'] === 'เฟอร์นิเจอร์') ? 'selected' : ''; ?>>เฟอร์นิเจอร์ (เตียง, ตู้, โต๊ะ, เก้าอี้)</option>
                                    <option value="เครื่องปรับอากาศ" <?php echo (isset($_POST['repair_type']) && $_POST['repair_type'] === 'เครื่องปรับอากาศ') ? 'selected' : ''; ?>>เครื่องปรับอากาศ</option>
                                    <option value="อินเทอร์เน็ต" <?php echo (isset($_POST['repair_type']) && $_POST['repair_type'] === 'อินเทอร์เน็ต') ? 'selected' : ''; ?>>อินเทอร์เน็ต</option>
                                    <option value="อื่นๆ" <?php echo (isset($_POST['repair_type']) && $_POST['repair_type'] === 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ (ระบุในรายละเอียด)</option>
                                </select>
                                <div class="invalid-feedback">
                                    กรุณาเลือกประเภทการซ่อม
                                </div>
                            </div>

                            <!-- ตำแหน่งที่ต้องการซ่อม -->
                            <div class="mb-3">
                                <label for="repair_location" class="form-label">ตำแหน่งที่ต้องการซ่อม <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="repair_location" name="repair_location"
                                    placeholder="เช่น ห้องน้ำ, ห้องนอน"
                                    value="<?php
                                            if (isset($_POST['repair_location'])) {
                                                echo htmlspecialchars($_POST['repair_location']);
                                            } elseif (!empty($student['building_name']) && !empty($student['room_number'])) {
                                                echo htmlspecialchars("อาคาร {$student['building_name']} ห้อง {$student['room_number']}");
                                            }
                                            ?>"
                                    required>
                                <div class="invalid-feedback">
                                    กรุณาระบุตำแหน่งที่ต้องการซ่อม
                                </div>
                            </div>

                            <!-- รายละเอียดการซ่อม -->
                            <div class="mb-3">
                                <label for="repair_description" class="form-label">รายละเอียดการซ่อม <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="repair_description" name="repair_description" rows="4"
                                    placeholder="กรุณาระบุรายละเอียดปัญหาที่พบให้ชัดเจน เพื่อความรวดเร็วในการซ่อมแซม" required><?php echo isset($_POST['repair_description']) ? htmlspecialchars($_POST['repair_description']) : ''; ?></textarea>
                                <div class="invalid-feedback">
                                    กรุณาระบุรายละเอียดการซ่อม
                                </div>
                            </div>

                            <!-- ความเร่งด่วน -->
                            <div class="mb-3">
                                <label for="repair_priority" class="form-label">ความเร่งด่วน</label>
                                <select class="form-select" id="repair_priority" name="repair_priority">
                                    <option value="ปานกลาง" <?php echo (isset($_POST['repair_priority']) && $_POST['repair_priority'] === 'ปานกลาง') ? 'selected' : 'selected'; ?>>ปานกลาง</option>
                                    <option value="ต่ำ" <?php echo (isset($_POST['repair_priority']) && $_POST['repair_priority'] === 'ต่ำ') ? 'selected' : ''; ?>>ต่ำ</option>
                                    <option value="สูง" <?php echo (isset($_POST['repair_priority']) && $_POST['repair_priority'] === 'สูง') ? 'selected' : ''; ?>>สูง</option>
                                </select>
                            </div>

                            <!-- อัพโหลดรูปภาพ -->
                            <div class="mb-3">
                                <label for="repair_images" class="form-label">รูปภาพประกอบ (ไม่เกิน 3 รูป)</label>
                                <input type="file" class="form-control" id="repair_images" name="repair_images[]" accept="image/*" multiple>
                                <div class="form-text">รองรับไฟล์ JPG, PNG, GIF ขนาดไม่เกิน 2MB ต่อรูป</div>
                            </div>

                            <!-- ปุ่มส่งข้อมูล -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>ส่งคำขอแจ้งซ่อม
                                </button>
                            </div>
                        </form>

                        <!-- ข้อมูลเพิ่มเติม -->
                        <div class="alert alert-warning mt-4 mb-0">
                            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>ข้อมูลสำคัญ</h5>
                            <hr>
                            <ul class="mb-0">
                                <li>เจ้าหน้าที่จะดำเนินการตามลำดับก่อนหลังและความเร่งด่วน</li>
                                <li>คุณสามารถติดตามสถานะการซ่อมได้ที่เมนู "รายการแจ้งซ่อม"</li>
                                <li>กรณีเร่งด่วนมาก โปรดติดต่อเจ้าหน้าที่หอพักโดยตรงที่ 099-123-4567</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ตรวจสอบจำนวนไฟล์รูปภาพไม่เกิน 3 รูป
    document.getElementById('repair_images').addEventListener('change', function() {
        if (this.files.length > 3) {
            alert('คุณสามารถอัพโหลดรูปภาพได้ไม่เกิน 3 รูป');
            this.value = '';
        }

        // ตรวจสอบขนาดไฟล์ไม่เกิน 2MB ต่อรูป
        for (let i = 0; i < this.files.length; i++) {
            if (this.files[i].size > 2 * 1024 * 1024) {
                alert('ไฟล์ ' + this.files[i].name + ' มีขนาดเกิน 2MB กรุณาเลือกไฟล์ใหม่');
                this.value = '';
                break;
            }
        }
    });

    // Form validation
    (function() {
        'use strict'

        var forms = document.querySelectorAll('.needs-validation')

        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
// ล้างและปิด output buffer
ob_end_flush();
?>