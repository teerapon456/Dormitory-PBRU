<?php
$pageTitle = "แก้ไขข้อมูลผู้ใช้";
require_once __DIR__ . '/../auth_check.php';

// ตรวจสอบ ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ไม่พบข้อมูลผู้ใช้ที่ต้องการแก้ไข";
    header("Location: view.php");
    exit;
}

$user_id = (int)$_GET['id'];

// ดึงข้อมูลผู้ใช้
$user = Database::getInstance()->fetch("
    SELECT u.*, r.room_number, b.building_name 
    FROM users u 
    LEFT JOIN rooms r ON u.room_id = r.room_id 
    LEFT JOIN buildings b ON r.building_id = b.building_id 
    WHERE u.user_id = :user_id
", [':user_id' => $user_id]);

if (!$user) {
    $_SESSION['error'] = "ไม่พบข้อมูลผู้ใช้ที่ต้องการแก้ไข";
    header("Location: view.php");
    exit;
}

// ดึงข้อมูลห้องพักที่ว่าง
$available_rooms = Database::getInstance()->fetchAll("
    SELECT r.room_id, r.room_number, b.building_name, r.current_occupancy, r.max_capacity
    FROM rooms r
    JOIN buildings b ON r.building_id = b.building_id
    WHERE r.current_occupancy < r.max_capacity OR r.room_id = :current_room_id
    ORDER BY b.building_name, r.room_number
", [':current_room_id' => $user['room_id'] ?? 0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid security token";
        header("Location: view.php");
        exit;
    }

    $errors = [];
    $old_data = $user; // เก็บข้อมูลเดิมไว้เปรียบเทียบ

    // ตรวจสอบข้อมูล
    if (empty($_POST['username'])) {
        $errors[] = "กรุณาระบุชื่อผู้ใช้";
    } else {
        // ตรวจสอบว่ามีชื่อผู้ใช้นี้ในระบบแล้วหรือไม่ (ยกเว้นผู้ใช้ปัจจุบัน)
        $existing = Database::getInstance()->fetch(
            "SELECT user_id FROM users WHERE username = :username AND user_id != :user_id",
            [
                ':username' => $_POST['username'],
                ':user_id' => $user_id
            ]
        );
        if ($existing) {
            $errors[] = "มีชื่อผู้ใช้นี้ในระบบแล้ว";
        }
    }

    if (empty($_POST['full_name'])) {
        $errors[] = "กรุณาระบุชื่อ-นามสกุล";
    }

    if (empty($_POST['email'])) {
        $errors[] = "กรุณาระบุอีเมล";
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
    } else {
        // ตรวจสอบว่ามีอีเมลนี้ในระบบแล้วหรือไม่ (ยกเว้นผู้ใช้ปัจจุบัน)
        $existing = Database::getInstance()->fetch(
            "SELECT user_id FROM users WHERE email = :email AND user_id != :user_id",
            [
                ':email' => $_POST['email'],
                ':user_id' => $user_id
            ]
        );
        if ($existing) {
            $errors[] = "มีอีเมลนี้ในระบบแล้ว";
        }
    }

    if (empty($_POST['role'])) {
        $errors[] = "กรุณาเลือกระดับผู้ใช้";
    }

    // ตรวจสอบการเปลี่ยนแปลงระดับผู้ใช้
    if ($user['role'] === 'ผู้ดูแลระบบ' && $_POST['role'] !== 'ผู้ดูแลระบบ') {
        // นับจำนวนผู้ดูแลระบบที่เหลือ
        $admin_count = Database::getInstance()->fetch(
            "SELECT COUNT(*) as count FROM users WHERE role = 'ผู้ดูแลระบบ' AND user_id != :user_id",
            [':user_id' => $user_id]
        )['count'];

        if ($admin_count < 1) {
            $errors[] = "ไม่สามารถเปลี่ยนระดับผู้ใช้ได้เนื่องจากเป็นผู้ดูแลระบบคนสุดท้าย";
        }
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        try {
            Database::getInstance()->beginTransaction();

            // สร้างข้อมูลสำหรับอัพเดท
            $data = [
                'username' => $_POST['username'],
                'full_name' => $_POST['full_name'],
                'email' => $_POST['email'],
                'phone_number' => !empty($_POST['phone_number']) ? $_POST['phone_number'] : null,
                'role' => $_POST['role']
            ];

            // ถ้ามีการกรอกรหัสผ่านใหม่
            if (!empty($_POST['password'])) {
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            // จัดการห้องพัก
            $old_room_id = $user['room_id'];
            $new_room_id = !empty($_POST['room_id']) ? $_POST['room_id'] : null;

            // ถ้าเปลี่ยนห้องพัก
            if ($old_room_id != $new_room_id) {
                // ถ้ามีห้องพักเดิม ให้ลดจำนวนผู้พักในห้องเดิม
                if ($old_room_id) {
                    Database::getInstance()->query(
                        "UPDATE rooms SET current_occupancy = current_occupancy - 1 WHERE room_id = :room_id",
                        [':room_id' => $old_room_id]
                    );
                }
                // ถ้ามีห้องพักใหม่ ให้เพิ่มจำนวนผู้พักในห้องใหม่
                if ($new_room_id) {
                    Database::getInstance()->query(
                        "UPDATE rooms SET current_occupancy = current_occupancy + 1 WHERE room_id = :room_id",
                        [':room_id' => $new_room_id]
                    );
                }
                $data['room_id'] = $new_room_id;
            }

            // อัพเดทข้อมูล
            $result = Database::getInstance()->update(
                "users",
                $data,
                "user_id = ?",
                [$user_id]
            );

            if ($result) {
                // สร้างข้อความอธิบายการเปลี่ยนแปลง
                $changes = [];
                foreach ($data as $key => $value) {
                    if ($key !== 'password' && $value !== $old_data[$key]) {
                        $changes[] = "$key: {$old_data[$key]} -> $value";
                    }
                }
                if (!empty($_POST['password'])) {
                    $changes[] = "password: [changed]";
                }

                // บันทึกประวัติการแก้ไข
                if (!empty($changes)) {
                    Database::getInstance()->insert(
                        "activity_logs",
                        [
                            'user_id' => $_SESSION['user_id'],
                            'action' => 'update',
                            'module' => 'users',
                            'description' => "แก้ไขข้อมูลผู้ใช้ {$user['username']} (ID: {$user_id}) - " . implode(", ", $changes),
                            'created_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }

                Database::getInstance()->commit();
                $_SESSION['success'] = "แก้ไขข้อมูลผู้ใช้เรียบร้อยแล้ว";
                header("Location: view.php");
                exit;
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
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
                <!-- การ์ดฟอร์ม -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title mb-4">แก้ไขข้อมูลผู้ใช้</h2>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- สถิติผู้ใช้ -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border-0 bg-primary bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-primary">ชื่อผู้ใช้</h6>
                                        <h3 class="card-title mb-0">
                                            <?php echo htmlspecialchars($user['username']); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-success bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-success">ระดับผู้ใช้</h6>
                                        <h3 class="card-title mb-0"><?php echo htmlspecialchars($user['role']); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-info bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-info">ห้องพัก</h6>
                                        <h3 class="card-title mb-0">
                                            <?php
                                            if ($user['room_id']) {
                                                echo htmlspecialchars($user['building_name'] . ' ห้อง ' . $user['room_number']);
                                            } else {
                                                echo 'ไม่มีห้องพัก';
                                            }
                                            ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <!-- ชื่อผู้ใช้ -->
                            <div class="col-md-6">
                                <label class="form-label">ชื่อผู้ใช้ <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required maxlength="50"
                                    value="<?php echo htmlspecialchars($user['username']); ?>">
                            </div>

                            <!-- รหัสผ่าน -->
                            <div class="col-md-6">
                                <label class="form-label">รหัสผ่าน <small
                                        class="text-muted">(เว้นว่างถ้าไม่ต้องการเปลี่ยน)</small></label>
                                <input type="password" name="password" class="form-control" maxlength="255">
                            </div>

                            <!-- ชื่อ-นามสกุล -->
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required maxlength="100"
                                    value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>

                            <!-- อีเมล -->
                            <div class="col-md-6">
                                <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required maxlength="30"
                                    value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>

                            <!-- เบอร์โทร -->
                            <div class="col-md-6">
                                <label class="form-label">เบอร์โทร</label>
                                <input type="tel" name="phone_number" class="form-control" maxlength="20"
                                    value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                            </div>

                            <!-- ระดับผู้ใช้ -->
                            <div class="col-md-6">
                                <label class="form-label">ระดับผู้ใช้ <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="">เลือกระดับผู้ใช้</option>
                                    <option value="ผู้ดูแลระบบ"
                                        <?php echo $user['role'] == 'ผู้ดูแลระบบ' ? 'selected' : ''; ?>>
                                        ผู้ดูแลระบบ</option>
                                    <option value="นักศึกษา"
                                        <?php echo $user['role'] == 'นักศึกษา' ? 'selected' : ''; ?>>
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
                                        <?php echo ($user['room_id'] == $room['room_id']) ? 'selected' : ''; ?>>
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