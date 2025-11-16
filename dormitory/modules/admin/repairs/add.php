<?php
$pageTitle = "เพิ่มรายการแจ้งซ่อม";
require_once __DIR__ . '/../auth_check.php';

// ดึงข้อมูลผู้ใช้
$users = Database::getInstance()->fetchAll("
    SELECT user_id, full_name, room_id
    FROM users 
    WHERE role = 'นักศึกษา'
    ORDER BY full_name
");

// ดึงข้อมูลห้องพัก
$rooms = Database::getInstance()->fetchAll("
    SELECT r.room_id, r.room_number, b.building_name
    FROM rooms r
    JOIN buildings b ON r.building_id = b.building_id
    ORDER BY b.building_name, r.room_number
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid security token";
        header("Location: view.php");
        exit;
    }

    $errors = [];

    // ตรวจสอบข้อมูล
    if (empty($_POST['title'])) {
        $errors[] = "กรุณาระบุรายการแจ้งซ่อม";
    }

    // ตรวจสอบว่ามีการเลือกผู้แจ้งซ่อมหรือกรอกข้อมูลผู้ติดต่อ
    if (empty($_POST['user_id']) && empty($_POST['contact_name'])) {
        $errors[] = "กรุณาเลือกผู้แจ้งซ่อมหรือกรอกชื่อผู้ติดต่อ";
    }

    if (empty($_POST['room_id'])) {
        $errors[] = "กรุณาเลือกห้องพัก";
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        try {
            Database::getInstance()->beginTransaction();

            // เพิ่มข้อมูล
            $data = [
                'room_id' => $_POST['room_id'],
                'title' => $_POST['title'],
                'description' => $_POST['description'] ?? null,
                'status' => 'รอดำเนินการ',
                'created_time' => date('Y-m-d H:i:s')
            ];

            // ถ้ามีการเลือกผู้ใช้
            if (!empty($_POST['user_id'])) {
                $data['user_id'] = $_POST['user_id'];
            }
            // ถ้ามีการกรอกชื่อผู้ติดต่อ
            elseif (!empty($_POST['contact_name'])) {
                $data['contact_name'] = $_POST['contact_name'];
            }

            $result = Database::getInstance()->insert("repair_requests", $data);

            if ($result) {
                Database::getInstance()->commit();
                $_SESSION['success'] = "เพิ่มรายการแจ้งซ่อมเรียบร้อยแล้ว";
                header("Location: view.php");
                exit;
            } else {
                throw new Exception("ไม่สามารถเพิ่มข้อมูลได้");
            }
        } catch (Exception $e) {
            Database::getInstance()->rollBack();
            $errors[] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// สร้าง CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="px-4 py-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="card-title mb-0">เพิ่มรายการแจ้งซ่อม</h2>
                        </div>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <!-- ประเภทผู้แจ้ง -->
                            <div class="col-12">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reporter_type" id="user"
                                        value="user" checked onclick="toggleReporterFields()">
                                    <label class="form-check-label" for="user">ผู้ใช้ระบบ</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reporter_type" id="contact"
                                        value="contact" onclick="toggleReporterFields()">
                                    <label class="form-check-label" for="contact">ผู้ติดต่อภายนอก</label>
                                </div>
                            </div>

                            <!-- ผู้แจ้งซ่อม (ผู้ใช้ระบบ) -->
                            <div class="col-md-6" id="userField">
                                <label class="form-label">ผู้แจ้งซ่อม</label>
                                <select name="user_id" class="form-select">
                                    <option value="">เลือกผู้แจ้งซ่อม</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>"
                                        <?php echo (isset($_POST['user_id']) && $_POST['user_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- ผู้แจ้งซ่อม (ผู้ติดต่อภายนอก) -->
                            <div class="col-md-6" id="contactField" style="display: none;">
                                <label class="form-label">ชื่อผู้ติดต่อ</label>
                                <input type="text" name="contact_name" class="form-control" maxlength="100"
                                    value="<?php echo htmlspecialchars($_POST['contact_name'] ?? ''); ?>"
                                    placeholder="ระบุชื่อผู้ติดต่อ">
                            </div>

                            <!-- ห้องพัก -->
                            <div class="col-md-6">
                                <label class="form-label">ห้องพัก <span class="text-danger">*</span></label>
                                <select name="room_id" class="form-select" required>
                                    <option value="">เลือกห้องพัก</option>
                                    <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['room_id']; ?>"
                                        <?php echo (isset($_POST['room_id']) && $_POST['room_id'] == $room['room_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($room['building_name'] . ' ห้อง ' . $room['room_number']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- รายการแจ้งซ่อม -->
                            <div class="col-12">
                                <label class="form-label">รายการแจ้งซ่อม <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required maxlength="200"
                                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                    placeholder="ระบุรายการแจ้งซ่อม เช่น ก๊อกน้ำรั่ว, หลอดไฟเสีย">
                            </div>

                            <!-- รายละเอียดเพิ่มเติม -->
                            <div class="col-12">
                                <label class="form-label">รายละเอียดเพิ่มเติม</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <!-- ปุ่มดำเนินการ -->
                            <div class="col-12">
                                <hr class="my-4">
                                <div class="d-flex justify-content-end">
                                    <a href="view.php" class="btn btn-secondary me-2">ยกเลิก</a>
                                    <button type="submit" class="btn btn-primary">บันทึก</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleReporterFields() {
    const userField = document.getElementById('userField');
    const contactField = document.getElementById('contactField');
    const reporterType = document.querySelector('input[name="reporter_type"]:checked').value;

    if (reporterType === 'user') {
        userField.style.display = 'block';
        contactField.style.display = 'none';
        document.querySelector('select[name="user_id"]').required = true;
        document.querySelector('input[name="contact_name"]').required = false;
    } else {
        userField.style.display = 'none';
        contactField.style.display = 'block';
        document.querySelector('select[name="user_id"]').required = false;
        document.querySelector('input[name="contact_name"]').required = true;
    }
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>