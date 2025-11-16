<?php
$pageTitle = "ข้อมูลส่วนตัว";
require_once __DIR__ . '/../../includes/header.php';

// ตรวจสอบการล็อกอิน
if (!Auth::isLoggedIn()) {
    header('Location: ' . Config::$baseUrl . '/modules/users/login.php');
    exit;
}

// ดึงข้อมูลผู้ใช้
$user = Auth::getCurrentUser();

// ดึงข้อมูลนักศึกษา (ถ้ามี)
$student = null;
if ($user['role'] === 'student') {
    $student = Database::getInstance()->fetch("
        SELECT * FROM students WHERE user_id = ?
    ", [$_SESSION['user_id']]);
}

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;

    // ตรวจสอบข้อมูล
    if (empty($_POST['full_name'])) {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    }

    if (empty($_POST['email'])) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    if (empty($_POST['phone_number'])) {
        $errors[] = 'กรุณากรอกเบอร์โทรศัพท์';
    }

    // ถ้ามีการเปลี่ยนรหัสผ่าน
    if (!empty($_POST['current_password'])) {
        if (!password_verify($_POST['current_password'], $user['password'])) {
            $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } elseif (empty($_POST['new_password'])) {
            $errors[] = 'กรุณากรอกรหัสผ่านใหม่';
        } elseif (strlen($_POST['new_password']) < 6) {
            $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $errors[] = 'รหัสผ่านใหม่ไม่ตรงกัน';
        }
    }

    if (empty($errors)) {
        try {
            Database::getInstance()->beginTransaction();

            // ตรวจสอบว่าอีเมลซ้ำหรือไม่
            $existing_user = Database::getInstance()->fetch(
                "SELECT user_id FROM users WHERE email = ? AND user_id != ?",
                [$_POST['email'], $user['user_id']]
            );

            if ($existing_user) {
                $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
            } else {
                // อัพเดทข้อมูลผู้ใช้
                $update_query = "
                    UPDATE users SET 
                        full_name = ?,
                        email = ?,
                        phone_number = ?
                ";
                $update_params = [
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['phone_number']
                ];

                // ถ้ามีการเปลี่ยนรหัสผ่าน
                if (!empty($_POST['new_password'])) {
                    $update_query .= ", password = ?";
                    $update_params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                }

                $update_query .= " WHERE user_id = ?";
                $update_params[] = $_SESSION['user_id'];

                Database::getInstance()->query($update_query, $update_params);

                // อัพเดทข้อมูลนักศึกษา (ถ้ามี)
                if ($student) {
                    Database::getInstance()->query("
                        UPDATE students SET 
                            student_id = ?,
                            faculty = ?,
                            year = ?
                        WHERE user_id = ?
                    ", [
                        $_POST['student_id'],
                        $_POST['faculty'],
                        $_POST['year'],
                        $_SESSION['user_id']
                    ]);
                }

                // บันทึก log
                Functions::logActivity(
                    $user['user_id'],
                    'update_profile',
                    'อัพเดทข้อมูลส่วนตัว'
                );

                Database::getInstance()->commit();

                $_SESSION['success'] = 'อัพเดทข้อมูลเรียบร้อยแล้ว';
                header('Location: ' . Config::$baseUrl . '/modules/users/profile.php');
                exit;
            }
        } catch (Exception $e) {
            Database::getInstance()->rollBack();
            $errors[] = 'เกิดข้อผิดพลาดในการอัพเดทข้อมูล';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-circle"></i> ข้อมูลส่วนตัว
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            <div class="form-text">ไม่สามารถแก้ไขชื่อผู้ใช้ได้</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">บทบาท</label>
                            <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars($user['role']); ?>" readonly>
                            <div class="form-text">ไม่สามารถแก้ไขบทบาทได้</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="full_name" name="full_name"
                            value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        <div class="invalid-feedback">
                            กรุณากรอกชื่อ-นามสกุล
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <div class="invalid-feedback">
                                กรุณากรอกอีเมลให้ถูกต้อง
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone_number" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number"
                                value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                            <div class="invalid-feedback">
                                กรุณากรอกเบอร์โทรศัพท์
                            </div>
                        </div>
                    </div>

                    <?php if ($student): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="student_id" class="form-label">รหัสนักศึกษา</label>
                                <input type="text" class="form-control" id="student_id" name="student_id"
                                    value="<?php echo htmlspecialchars($student['student_id']); ?>" required>
                                <div class="invalid-feedback">
                                    กรุณากรอกรหัสนักศึกษา
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="faculty" class="form-label">คณะ</label>
                                <input type="text" class="form-control" id="faculty" name="faculty"
                                    value="<?php echo htmlspecialchars($student['faculty']); ?>" required>
                                <div class="invalid-feedback">
                                    กรุณากรอกคณะ
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="year" class="form-label">ชั้นปี</label>
                            <select class="form-select" id="year" name="year" required>
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?php echo $i; ?>"
                                        <?php echo $student['year'] == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">
                                กรุณาเลือกชั้นปี
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <h5 class="mb-3">เปลี่ยนรหัสผ่าน</h5>

                    <div class="mb-3">
                        <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                        <a href="<?php echo Config::$baseUrl; ?>/modules/users/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> ยกเลิก
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>