<?php
require_once __DIR__ . '/includes/header.php';

// ตรวจสอบการล็อกอิน
if (!Auth::isLoggedIn()) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบก่อนใช้งาน';
    header('Location: login.php');
    exit;
}

// ตรวจสอบว่าเป็นนักศึกษา
if (Auth::getRole() !== 'นักศึกษา') {
    $_SESSION['error'] = 'หน้านี้สำหรับนักศึกษาเท่านั้น';
    header('Location: index.php');
    exit;
}

// Get current user ID
$userId = Auth::getUserId();

// Initialize database connection
$db = Database::getInstance();

// Get student information with room details
$student = $db->fetch("
    SELECT u.*, 
           r.room_id,
           r.room_number,
           r.building_id,
           b.building_name
    FROM users u
    LEFT JOIN rooms r ON u.room_id = r.room_id
    LEFT JOIN buildings b ON r.building_id = b.building_id
    WHERE u.user_id = ?
", [$userId]);

// Get repair categories
$categories = $db->fetchAll("
    SELECT * FROM repair_categories 
    ORDER BY category_name
");

// Get repair locations
$locations = $db->fetchAll("
    SELECT * FROM repair_locations 
    WHERE (is_public_area = 1) OR (building_id = ?)
    ORDER BY location_name_th
", [$student['building_id'] ?? 0]);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Get form data
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $locationId = intval($_POST['location_id'] ?? 0);

        // Validate required fields
        if (empty($title)) throw new Exception('กรุณาระบุหัวข้อการแจ้งซ่อม');
        if (empty($description)) throw new Exception('กรุณาระบุรายละเอียดการแจ้งซ่อม');
        if (empty($locationId)) throw new Exception('กรุณาเลือกสถานที่');

        // Insert repair request
        $repairId = $db->insert('repair_requests', [
            'user_id' => $userId,
            'room_id' => $student['room_id'] ?? null,
            'location_id' => $locationId,
            'title' => $title,
            'description' => $description,
            'status' => 'รอดำเนินการ',
            'created_time' => date('Y-m-d H:i:s')
        ]);

        // Handle image uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $uploadDir = 'uploads/repairs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $newFileName = 'repair_' . $repairId . '_' . uniqid() . '.' . $ext;
                    $destination = $uploadDir . $newFileName;

                    if (move_uploaded_file($tmp_name, $destination)) {
                        $db->insert('repair_images', [
                            'request_id' => $repairId,
                            'image_path' => $destination,
                            'created_time' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }

        // Add repair history
        $db->insert('repair_history', [
            'request_id' => $repairId,
            'admin_id' => null,
            'action' => 'รับเรื่องแจ้งซ่อม',
            'notes' => 'แจ้งซ่อมโดยนักศึกษา: ' . $student['full_name'],
            'created_time' => date('Y-m-d H:i:s')
        ]);

        $db->commit();
        $_SESSION['success'] = "ส่งเรื่องแจ้งซ่อมเรียบร้อยแล้ว หมายเลขแจ้งซ่อม: " . $repairId;
        header('Location: repair_status.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>แจ้งซ่อม</h2>
                <a href="repair_status.php" class="btn btn-secondary">
                    <i class="fas fa-history"></i> ประวัติการแจ้งซ่อม
                </a>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <!-- ข้อมูลผู้แจ้งซ่อม -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-user"></i> ข้อมูลผู้แจ้งซ่อม
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>ชื่อ-นามสกุล:</strong>
                                <?php echo htmlspecialchars($student['full_name']); ?>
                            </p>
                            <p class="mb-2"><strong>อีเมล:</strong> <?php echo htmlspecialchars($student['email']); ?>
                            </p>
                            <p class="mb-2"><strong>เบอร์โทร:</strong>
                                <?php echo htmlspecialchars($student['phone_number'] ?? '-'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($student['room_id'])): ?>
                                <p class="mb-2"><strong>อาคาร:</strong>
                                    <?php echo htmlspecialchars($student['building_name'] ?? '-'); ?></p>
                                <p class="mb-2"><strong>ห้อง:</strong>
                                    <?php echo htmlspecialchars($student['room_number'] ?? '-'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- แบบฟอร์มแจ้งซ่อม -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-tools"></i> กรอกข้อมูลแจ้งซ่อม
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" id="repairForm">
                        <div class="mb-3">
                            <label for="title" class="form-label">หัวข้อการแจ้งซ่อม <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title"
                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="location_id" class="form-label">สถานที่ <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="location_id" name="location_id" required>
                                <option value="">-- เลือกสถานที่ --</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['location_id']; ?>"
                                        <?php echo (isset($_POST['location_id']) && $_POST['location_id'] == $location['location_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($location['location_name_th']); ?>
                                        <?php echo $location['is_public_area'] ? ' (พื้นที่ส่วนกลาง)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">รายละเอียด <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <div class="form-text">กรุณาระบุรายละเอียดการแจ้งซ่อมให้ชัดเจน
                                เพื่อให้เจ้าหน้าที่สามารถดำเนินการได้อย่างรวดเร็ว</div>
                        </div>
                        <div class="mb-3">
                            <label for="images" class="form-label">รูปภาพประกอบ (สามารถเลือกได้หลายรูป)</label>
                            <input type="file" class="form-control" id="images" name="images[]" multiple
                                accept="image/*">
                            <div class="form-text">รูปภาพต้องเป็นไฟล์ JPG, PNG หรือ GIF ที่มีขนาดไม่เกิน 5MB</div>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="repair_status.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> ยกเลิก
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> ส่งเรื่องแจ้งซ่อม
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 1rem;">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-info-circle"></i> คำแนะนำการแจ้งซ่อม
                </div>
                <div class="card-body">
                    <ol>
                        <li>กรอกข้อมูลให้ครบถ้วน โดยเฉพาะช่องที่มีเครื่องหมาย <span class="text-danger">*</span></li>
                        <li>ระบุรายละเอียดการแจ้งซ่อมให้ชัดเจน เช่น ลักษณะความเสียหาย ตำแหน่งที่เสียหาย</li>
                        <li>แนบรูปภาพประกอบเพื่อให้เจ้าหน้าที่เข้าใจปัญหาได้ดียิ่งขึ้น</li>
                        <li>ตรวจสอบสถานะการซ่อมได้ที่หน้า "รายการแจ้งซ่อม"</li>
                        <li>หากมีข้อสงสัยเพิ่มเติม สามารถติดต่อเจ้าหน้าที่หอพักได้ที่เบอร์ 02-123-4567</li>
                    </ol>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="fas fa-exclamation-triangle"></i> <strong>หมายเหตุ:</strong>
                        การแจ้งซ่อมที่มีความเร่งด่วนสูง เช่น น้ำรั่ว ไฟฟ้าลัดวงจร
                        ควรแจ้งเจ้าหน้าที่โดยตรงที่สำนักงานหอพักหรือโทร 02-123-4567 ต่อ 0
                        เพื่อความรวดเร็วในการแก้ไขปัญหา
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // แสดงตัวอย่างรูปภาพที่เลือก
        $('#images').on('change', function() {
            // ทำฟังก์ชันแสดงรูปตัวอย่างถ้าต้องการ
        });

        // Validate form before submit
        $('#repairForm').on('submit', function(e) {
            let valid = true;

            // Basic validation
            if ($('#title').val().trim() === '') {
                valid = false;
                $('#title').addClass('is-invalid');
            } else {
                $('#title').removeClass('is-invalid');
            }

            if ($('#description').val().trim() === '') {
                valid = false;
                $('#description').addClass('is-invalid');
            } else {
                $('#description').removeClass('is-invalid');
            }

            if ($('#location_id').val() === '') {
                valid = false;
                $('#location_id').addClass('is-invalid');
            } else {
                $('#location_id').removeClass('is-invalid');
            }

            if (!valid) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
        });

        var dropdownToggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        dropdownToggles.forEach(function(dropdownToggle) {
            new bootstrap.Dropdown(dropdownToggle);
        });

        // Close dropdowns
        if (!e.target.matches('.dropdown-toggle')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(dropdown) {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }

        document.querySelectorAll('.dropdown-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (this.getAttribute('href')) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
ob_end_flush(); // Send the output buffer to the browser
?>