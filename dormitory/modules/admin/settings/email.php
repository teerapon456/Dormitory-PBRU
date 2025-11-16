<?php
require_once __DIR__ . '/../../../includes/header.php';

// ตรวจสอบสิทธิ์การเข้าถึง
if (!Auth::hasPermission('admin')) {
    header('Location: ' . Config::$baseUrl . '/modules/users/login.php');
    exit;
}

// ดึงการตั้งค่าปัจจุบัน
$settings = Database::getInstance()->fetchAll("
    SELECT * FROM settings 
    WHERE category = 'email'
");

$settings_map = [];
foreach ($settings as $setting) {
    $settings_map[$setting['key']] = $setting['value'];
}

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;

    try {
        Database::getInstance()->beginTransaction();

        // อัปเดตการตั้งค่า
        $settings_to_update = [
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '587',
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            'mail_from_address' => $_POST['mail_from_address'] ?? '',
            'mail_from_name' => $_POST['mail_from_name'] ?? '',
            'mail_reply_to' => $_POST['mail_reply_to'] ?? '',
            'mail_bcc' => $_POST['mail_bcc'] ?? '',
            'email_notifications_enabled' => isset($_POST['email_notifications_enabled']) ? '1' : '0',
            'notify_new_registration' => isset($_POST['notify_new_registration']) ? '1' : '0',
            'notify_payment_received' => isset($_POST['notify_payment_received']) ? '1' : '0',
            'notify_payment_due' => isset($_POST['notify_payment_due']) ? '1' : '0',
            'notify_maintenance_request' => isset($_POST['notify_maintenance_request']) ? '1' : '0',
            'notify_complaint' => isset($_POST['notify_complaint']) ? '1' : '0'
        ];

        foreach ($settings_to_update as $key => $value) {
            Database::getInstance()->update(
                'settings',
                ['value' => $value],
                ['category' => 'email', 'key' => $key]
            );
        }

        // บันทึกประวัติการเปลี่ยนแปลง
        Database::getInstance()->insert('system_logs', [
            'action' => 'update_email_settings',
            'details' => 'อัปเดตการตั้งค่าอีเมล',
            'created_by' => Auth::getUserId(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        Database::getInstance()->commit();
        $success = true;
        $_SESSION['success'] = 'บันทึกการตั้งค่าอีเมลเรียบร้อยแล้ว';

        // รีเฟรชข้อมูลการตั้งค่า
        $settings = Database::getInstance()->fetchAll("
            SELECT * FROM settings 
            WHERE category = 'email'
        ");
        foreach ($settings as $setting) {
            $settings_map[$setting['key']] = $setting['value'];
        }
    } catch (Exception $e) {
        Database::getInstance()->rollBack();
        $errors[] = 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า: ' . $e->getMessage();
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>ตั้งค่าอีเมล</h2>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" class="needs-validation" novalidate>
            <div class="row g-3">
                <!-- การตั้งค่า SMTP -->
                <div class="col-12">
                    <h5 class="mb-3">การตั้งค่า SMTP</h5>
                </div>

                <div class="col-md-6">
                    <label for="smtp_host" class="form-label">SMTP Host</label>
                    <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                        value="<?php echo htmlspecialchars($settings_map['smtp_host'] ?? ''); ?>" required>
                    <div class="invalid-feedback">
                        กรุณากรอก SMTP Host
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="smtp_port" class="form-label">SMTP Port</label>
                    <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                        value="<?php echo htmlspecialchars($settings_map['smtp_port'] ?? '587'); ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="smtp_encryption" class="form-label">การเข้ารหัส</label>
                    <select class="form-select" id="smtp_encryption" name="smtp_encryption" required>
                        <option value="tls" <?php echo ($settings_map['smtp_encryption'] ?? '') == 'tls' ? 'selected' : ''; ?>>
                            TLS
                        </option>
                        <option value="ssl" <?php echo ($settings_map['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>
                            SSL
                        </option>
                        <option value="" <?php echo ($settings_map['smtp_encryption'] ?? '') == '' ? 'selected' : ''; ?>>
                            ไม่มี
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="smtp_username" class="form-label">SMTP Username</label>
                    <input type="text" class="form-control" id="smtp_username" name="smtp_username"
                        value="<?php echo htmlspecialchars($settings_map['smtp_username'] ?? ''); ?>" required>
                    <div class="invalid-feedback">
                        กรุณากรอก SMTP Username
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="smtp_password" class="form-label">SMTP Password</label>
                    <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                        value="<?php echo htmlspecialchars($settings_map['smtp_password'] ?? ''); ?>" required>
                    <div class="invalid-feedback">
                        กรุณากรอก SMTP Password
                    </div>
                </div>

                <!-- การตั้งค่าอีเมล -->
                <div class="col-12">
                    <h5 class="mb-3">การตั้งค่าอีเมล</h5>
                </div>

                <div class="col-md-6">
                    <label for="mail_from_address" class="form-label">อีเมลผู้ส่ง</label>
                    <input type="email" class="form-control" id="mail_from_address" name="mail_from_address"
                        value="<?php echo htmlspecialchars($settings_map['mail_from_address'] ?? ''); ?>" required>
                    <div class="invalid-feedback">
                        กรุณากรอกอีเมลผู้ส่ง
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="mail_from_name" class="form-label">ชื่อผู้ส่ง</label>
                    <input type="text" class="form-control" id="mail_from_name" name="mail_from_name"
                        value="<?php echo htmlspecialchars($settings_map['mail_from_name'] ?? ''); ?>" required>
                    <div class="invalid-feedback">
                        กรุณากรอกชื่อผู้ส่ง
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="mail_reply_to" class="form-label">อีเมลสำหรับตอบกลับ</label>
                    <input type="email" class="form-control" id="mail_reply_to" name="mail_reply_to"
                        value="<?php echo htmlspecialchars($settings_map['mail_reply_to'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label for="mail_bcc" class="form-label">อีเมลสำเนาลับ</label>
                    <input type="email" class="form-control" id="mail_bcc" name="mail_bcc"
                        value="<?php echo htmlspecialchars($settings_map['mail_bcc'] ?? ''); ?>">
                </div>

                <!-- การแจ้งเตือน -->
                <div class="col-12">
                    <h5 class="mb-3">การแจ้งเตือน</h5>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="email_notifications_enabled" name="email_notifications_enabled"
                            <?php echo ($settings_map['email_notifications_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="email_notifications_enabled">เปิดใช้งานการแจ้งเตือนทางอีเมล</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_new_registration" name="notify_new_registration"
                            <?php echo ($settings_map['notify_new_registration'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="notify_new_registration">แจ้งเตือนเมื่อมีการลงทะเบียนใหม่</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_payment_received" name="notify_payment_received"
                            <?php echo ($settings_map['notify_payment_received'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="notify_payment_received">แจ้งเตือนเมื่อได้รับชำระเงิน</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_payment_due" name="notify_payment_due"
                            <?php echo ($settings_map['notify_payment_due'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="notify_payment_due">แจ้งเตือนเมื่อถึงกำหนดชำระเงิน</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_maintenance_request" name="notify_maintenance_request"
                            <?php echo ($settings_map['notify_maintenance_request'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="notify_maintenance_request">แจ้งเตือนเมื่อมีการแจ้งซ่อมบำรุง</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_complaint" name="notify_complaint"
                            <?php echo ($settings_map['notify_complaint'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="notify_complaint">แจ้งเตือนเมื่อมีการร้องเรียน</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่า
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // ตรวจสอบความถูกต้องของฟอร์ม
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
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>