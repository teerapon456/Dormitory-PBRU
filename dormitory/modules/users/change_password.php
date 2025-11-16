<?php
$pageTitle = "เปลี่ยนรหัสผ่าน";
require_once __DIR__ . '/../../includes/header.php';

// ตรวจสอบการล็อกอิน
if (!Auth::isLoggedIn()) {
    header('Location: ' . Config::$baseUrl . '/modules/users/login.php');
    exit;
}

// ดึงข้อมูลผู้ใช้
$user = Auth::getCurrentUser();

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;

    // ตรวจสอบข้อมูล
    if (empty($_POST['current_password'])) {
        $errors[] = 'กรุณากรอกรหัสผ่านปัจจุบัน';
    }

    if (empty($_POST['new_password'])) {
        $errors[] = 'กรุณากรอกรหัสผ่านใหม่';
    } elseif (strlen($_POST['new_password']) < 6) {
        $errors[] = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    }

    if (empty($_POST['confirm_password'])) {
        $errors[] = 'กรุณายืนยันรหัสผ่านใหม่';
    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
        $errors[] = 'รหัสผ่านใหม่ไม่ตรงกัน';
    }

    if (empty($errors)) {
        try {
            // ตรวจสอบรหัสผ่านปัจจุบัน
            if (!Functions::verifyPassword($_POST['current_password'], $user['password'])) {
                $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            } else {
                // อัพเดทรหัสผ่านใหม่
                $new_password = Functions::hashPassword($_POST['new_password']);
                Database::getInstance()->query(
                    "UPDATE users SET password = ? WHERE user_id = ?",
                    [$new_password, $user['user_id']]
                );

                // บันทึก log
                Functions::logActivity(
                    $user['user_id'],
                    'change_password',
                    'เปลี่ยนรหัสผ่าน'
                );

                $_SESSION['success'] = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
                header('Location: ' . Config::$baseUrl . '/modules/users/dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
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

                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <div class="invalid-feedback">
                            กรุณากรอกรหัสผ่านปัจจุบัน
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="invalid-feedback">
                            กรุณากรอกรหัสผ่านใหม่
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">
                            กรุณายืนยันรหัสผ่านใหม่
                        </div>
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