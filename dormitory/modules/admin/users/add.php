<?php
$pageTitle = "เพิ่มบัญชีผู้ใช้";
require_once __DIR__ . '/../auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // ตรวจสอบข้อมูล
    if (empty($_POST['username'])) {
        $errors[] = "กรุณาระบุชื่อผู้ใช้";
    } else {
        // ตรวจสอบว่ามีชื่อผู้ใช้นี้ในระบบแล้วหรือไม่
        $existing = Database::getInstance()->fetch(
            "SELECT user_id FROM users WHERE username = :username",
            [':username' => $_POST['username']]
        );
        if ($existing) {
            $errors[] = "มีชื่อผู้ใช้นี้ในระบบแล้ว";
        }
    }

    if (empty($_POST['password'])) {
        $errors[] = "กรุณาระบุรหัสผ่าน";
    }

    if (empty($_POST['full_name'])) {
        $errors[] = "กรุณาระบุชื่อ-นามสกุล";
    }

    if (empty($_POST['email'])) {
        $errors[] = "กรุณาระบุอีเมล";
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
    } else {
        // ตรวจสอบว่ามีอีเมลนี้ในระบบแล้วหรือไม่
        $existing = Database::getInstance()->fetch(
            "SELECT user_id FROM users WHERE email = :email",
            [':email' => $_POST['email']]
        );
        if ($existing) {
            $errors[] = "มีอีเมลนี้ในระบบแล้ว";
        }
    }

    if (empty($_POST['role'])) {
        $errors[] = "กรุณาเลือกระดับผู้ใช้";
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        try {
            // เริ่ม transaction
            Database::getInstance()->beginTransaction();

            // เพิ่มข้อมูลลงในฐานข้อมูล
            $result = Database::getInstance()->insert(
                "users",
                [
                    'username' => $_POST['username'],
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'full_name' => $_POST['full_name'],
                    'email' => $_POST['email'],
                    'phone_number' => !empty($_POST['phone_number']) ? $_POST['phone_number'] : null,
                    'role' => $_POST['role'],
                    'room_id' => !empty($_POST['room_id']) ? $_POST['room_id'] : null,
                    'created_time' => date('Y-m-d H:i:s')
                ]
            );

            if ($result) {
                // Commit transaction
                Database::getInstance()->commit();
                $_SESSION['success'] = "เพิ่มบัญชีผู้ใช้เรียบร้อยแล้ว";
                header("Location: view.php");
                exit;
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
            }
        } catch (Exception $e) {
            // Rollback transaction
            Database::getInstance()->rollBack();
            $errors[] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// ดึงข้อมูลห้องพักที่ว่าง
$available_rooms = Database::getInstance()->fetchAll("
    SELECT r.room_id, r.room_number, b.building_name, r.current_occupancy, r.max_capacity
    FROM rooms r
    JOIN buildings b ON r.building_id = b.building_id
    WHERE r.current_occupancy < r.max_capacity
    ORDER BY b.building_name, r.room_number
");
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2">
            <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 1rem;">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            เมนูหลัก
                        </div>

                        <a href="../dashboard.php"
                            class="list-group-item list-group-item-action border-0 <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line me-2"></i>แดชบอร์ด
                        </a>

                        <a href="../buildings/list.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/buildings/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-building me-2"></i>จัดการอาคาร
                        </a>

                        <a href="../rooms/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/rooms/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-door-open me-2"></i>จัดการห้องพัก
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            การจัดการผู้ใช้
                        </div>

                        <a href="../users/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-users me-2"></i>จัดการผู้ใช้
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            การแจ้งซ่อม
                        </div>

                        <a href="../repairs/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo basename($_SERVER['PHP_SELF']) === 'repairs.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tools me-2"></i>จัดการการแจ้งซ่อม
                        </a>

                        <a href="../repairs/categories.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/repairs/categories.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-list me-2"></i>หมวดหมู่การแจ้งซ่อม
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            การเงิน
                        </div>

                        <a href="../bills/list.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/bills/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-file-invoice-dollar me-2"></i>จัดการค่าใช้จ่าย
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            รายงาน
                        </div>

                        <a href="../reports/occupancy.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/reports/occupancy.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-bed me-2"></i>รายงานการเข้าพัก
                        </a>

                        <a href="../reports/repairs.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/reports/repairs.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-wrench me-2"></i>รายงานการแจ้งซ่อม
                        </a>

                        <a href="../reports/finance.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/reports/finance.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie me-2"></i>รายงานการเงิน
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            ระบบ
                        </div>

                        <a href="../settings/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/settings/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-cog me-2"></i>ตั้งค่าระบบ
                        </a>

                        <a href="../logs/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/logs/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-history me-2"></i>ประวัติการใช้งาน
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="px-4 py-3">
                <!-- การ์ดฟอร์ม -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title mb-4">เพิ่มบัญชีผู้ใช้</h2>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="row g-3">
                            <!-- ชื่อผู้ใช้ -->
                            <div class="col-md-6">
                                <label class="form-label">ชื่อผู้ใช้ <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required maxlength="50"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    placeholder="ระบุชื่อผู้ใช้">
                            </div>

                            <!-- รหัสผ่าน -->
                            <div class="col-md-6">
                                <label class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required maxlength="255"
                                    placeholder="ระบุรหัสผ่าน">
                            </div>

                            <!-- ชื่อ-นามสกุล -->
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required maxlength="100"
                                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                    placeholder="ระบุชื่อ-นามสกุล">
                            </div>

                            <!-- อีเมล -->
                            <div class="col-md-6">
                                <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required maxlength="30"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    placeholder="example@domain.com">
                            </div>

                            <!-- เบอร์โทร -->
                            <div class="col-md-6">
                                <label class="form-label">เบอร์โทร</label>
                                <input type="tel" name="phone_number" class="form-control" maxlength="20"
                                    value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                                    placeholder="0812345678">
                            </div>

                            <!-- ระดับผู้ใช้ -->
                            <div class="col-md-6">
                                <label class="form-label">ระดับผู้ใช้ <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="">เลือกระดับผู้ใช้</option>
                                    <option value="ผู้ดูแลระบบ"
                                        <?php echo (isset($_POST['role']) && $_POST['role'] == 'ผู้ดูแลระบบ') ? 'selected' : ''; ?>>
                                        ผู้ดูแลระบบ</option>
                                    <option value="นักศึกษา"
                                        <?php echo (isset($_POST['role']) && $_POST['role'] == 'นักศึกษา') ? 'selected' : ''; ?>>
                                        นักศึกษา</option>
                                </select>
                            </div>

                            <!-- ห้องพัก -->
                            <div class="col-12">
                                <label class="form-label">ห้องพัก</label>
                                <select name="room_id" class="form-select">
                                    <option value="">ไม่ระบุห้องพัก</option>
                                    <?php foreach ($available_rooms as $room): ?>
                                    <option value="<?php echo $room['room_id']; ?>"
                                        <?php echo (isset($_POST['room_id']) && $_POST['room_id'] == $room['room_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($room['building_name'] . ' ห้อง ' . $room['room_number']); ?>
                                        (<?php echo $room['current_occupancy']; ?>/<?php echo $room['max_capacity']; ?>
                                        คน)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">เลือกห้องพักเฉพาะกรณีที่เป็นนักศึกษา</div>
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

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>