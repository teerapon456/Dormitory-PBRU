<?php
require_once __DIR__ . '/includes/header.php';

// ถ้าล็อกอินแล้วให้ redirect ไปตามบทบาท
if (Auth::isLoggedIn()) {
    header('Location: ' . Auth::getRedirectUrl());
    exit;
}

// ดึงข้อมูลห้องว่าง
$db = Database::getInstance();
$available_rooms = $db->fetchAll("
    SELECT r.room_id, r.room_number, r.floor_number, r.max_capacity, r.current_occupancy,
           b.building_id, b.building_name
    FROM rooms r
    JOIN buildings b ON r.building_id = b.building_id
    WHERE r.current_occupancy < r.max_capacity
    ORDER BY b.building_name, r.floor_number, r.room_number
");

// จัดการการสมัครสมาชิก
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();

        // ตรวจสอบ username ซ้ำ
        $existingUser = $db->fetch("SELECT username FROM users WHERE username = ?", [$_POST['username']]);
        if ($existingUser) {
            throw new Exception('ชื่อผู้ใช้นี้มีผู้ใช้งานแล้ว');
        }

        // ตรวจสอบ email ซ้ำ
        $existingEmail = $db->fetch("SELECT email FROM users WHERE email = ?", [$_POST['email']]);
        if ($existingEmail) {
            throw new Exception('อีเมลนี้มีผู้ใช้งานแล้ว');
        }

        // ตรวจสอบรหัสผ่าน
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception('รหัสผ่านไม่ตรงกัน');
        }

        if (strlen($_POST['password']) < 6) {
            throw new Exception('รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
        }

        // ตรวจสอบห้องพัก (ถ้ามีการเลือก)
        $room_id = null;
        if (!empty($_POST['room_id'])) {
            $room = $db->fetch("
                SELECT room_id, max_capacity, current_occupancy
                FROM rooms 
                WHERE room_id = ?
            ", [$_POST['room_id']]);

            if (!$room) {
                throw new Exception('ห้องพักที่เลือกไม่ถูกต้อง');
            }

            if ($room['current_occupancy'] >= $room['max_capacity']) {
                throw new Exception('ห้องพักที่เลือกเต็มแล้ว');
            }

            $room_id = $room['room_id'];
        }

        $db->beginTransaction();

        try {
            // เตรียมข้อมูลสำหรับบันทึก
            $userData = [
                'username' => trim($_POST['username']),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'email' => trim($_POST['email']),
                'full_name' => trim($_POST['full_name']),
                'phone_number' => trim($_POST['phone_number']),
                'role' => 'นักศึกษา',
                'room_id' => $room_id
            ];

            // บันทึกข้อมูลผู้ใช้
            $userId = $db->insert('users', $userData);

            // อัพเดทจำนวนผู้พักในห้อง
            if ($room_id) {
                $db->query("
                    UPDATE rooms 
                    SET current_occupancy = current_occupancy + 1 
                    WHERE room_id = ?
                ", [$room_id]);
            }

            $db->commit();

            $_SESSION['success'] = 'สมัครสมาชิกเรียบร้อยแล้ว กรุณาเข้าสู่ระบบ';
            header('Location: login.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception('ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ดึงข้อความแจ้งเตือนจาก session
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : ($error ?? '');

// ล้าง session
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">สมัครสมาชิก</h2>
                        <p class="text-muted">กรอกข้อมูลเพื่อสมัครสมาชิก</p>
                    </div>

                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">ชื่อผู้ใช้ <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" required
                                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                </div>
                                <div class="form-text">ชื่อผู้ใช้ต้องไม่ซ้ำกับผู้อื่น</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" required
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                <div class="form-text">อีเมลต้องไม่ซ้ำกับผู้อื่น</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">ชื่อ-นามสกุล <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user-circle"></i>
                                </span>
                                <input type="text" class="form-control" id="full_name" name="full_name" required
                                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label">เบอร์โทรศัพท์</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number"
                                    pattern="[0-9]{10}"
                                    value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                            </div>
                            <div class="form-text">กรอกเฉพาะตัวเลข 10 หลัก</div>
                        </div>

                        <?php if (!empty($available_rooms)): ?>
                        <div class="mb-3">
                            <label for="room_id" class="form-label">
                                <i class="fas fa-bed me-1"></i>เลือกห้องพัก
                                <small class="text-muted">(แสดงเฉพาะห้องที่ยังรับนักศึกษาได้)</small>
                            </label>
                            <select class="form-select mb-2" id="room_id" name="room_id">
                                <option value="">-- เลือกห้องพัก --</option>
                                <?php foreach ($available_rooms as $room): ?>
                                <?php
                                        $occupancy_percentage = ($room['current_occupancy'] / $room['max_capacity']) * 100;
                                        $progress_class = 'bg-success';
                                        if ($occupancy_percentage >= 75) {
                                            $progress_class = 'bg-danger';
                                        } elseif ($occupancy_percentage >= 50) {
                                            $progress_class = 'bg-warning';
                                        }
                                        ?>
                                <option value="<?php echo $room['room_id']; ?>"
                                    data-occupancy="<?php echo $room['current_occupancy']; ?>"
                                    data-capacity="<?php echo $room['max_capacity']; ?>"
                                    data-percentage="<?php echo $occupancy_percentage; ?>"
                                    data-progress-class="<?php echo $progress_class; ?>"
                                    <?php echo (isset($_POST['room_id']) && $_POST['room_id'] == $room['room_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($room['building_name'] . ' ชั้น ' . $room['floor_number'] . ' ห้อง ' . $room['room_number']); ?>
                                    (<?php echo $room['current_occupancy']; ?>/<?php echo $room['max_capacity']; ?> คน)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="room-occupancy-info" class="d-none">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped" role="progressbar" style="width: 0%">
                                        <span class="progress-text fw-bold">0/0 คน</span>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="available-beds">มีเตียงว่าง 0 เตียง</span>
                                </small>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">รหัสผ่าน <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required
                                        minlength="6">
                                </div>
                                <div class="form-text">รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>สมัครสมาชิก
                            </button>
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>กลับไปหน้าเข้าสู่ระบบ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');

    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// เพิ่ม function สำหรับอัพเดทแถบแสดงจำนวนผู้พัก
function updateRoomOccupancy() {
    const roomSelect = document.getElementById('room_id');
    const occupancyInfo = document.getElementById('room-occupancy-info');
    const progressBar = occupancyInfo.querySelector('.progress-bar');
    const progressText = occupancyInfo.querySelector('.progress-text');
    const availableBeds = occupancyInfo.querySelector('.available-beds');

    if (roomSelect.value) {
        const selectedOption = roomSelect.options[roomSelect.selectedIndex];
        const occupancy = selectedOption.dataset.occupancy;
        const capacity = selectedOption.dataset.capacity;
        const percentage = selectedOption.dataset.percentage;
        const progressClass = selectedOption.dataset.progressClass;

        // อัพเดทแถบแสดงจำนวน
        progressBar.style.width = percentage + '%';
        progressBar.className = `progress-bar progress-bar-striped ${progressClass}`;
        progressText.textContent = `${occupancy}/${capacity} คน`;

        // คำนวณจำนวนเตียงว่าง
        const availableCount = capacity - occupancy;
        availableBeds.textContent = `มีเตียงว่าง ${availableCount} เตียง`;

        // แสดง progress bar
        occupancyInfo.classList.remove('d-none');
    } else {
        // ซ่อน progress bar ถ้าไม่ได้เลือกห้อง
        occupancyInfo.classList.add('d-none');
    }
}

// เพิ่ม event listener สำหรับการเลือกห้อง
document.getElementById('room_id')?.addEventListener('change', function() {
    updateRoomOccupancy();

    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value && selectedOption.textContent.includes('/')) {
        const occupancy = selectedOption.dataset.occupancy;
        const capacity = selectedOption.dataset.capacity;
        if (parseInt(occupancy) >= parseInt(capacity)) {
            alert('ห้องนี้เต็มแล้ว กรุณาเลือกห้องอื่น');
            this.value = '';
            updateRoomOccupancy();
        }
    }
});

// เรียกใช้ฟังก์ชันครั้งแรกเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    updateRoomOccupancy();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>