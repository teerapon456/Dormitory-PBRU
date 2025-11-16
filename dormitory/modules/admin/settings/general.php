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
    WHERE category = 'general'
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
            'site_name' => $_POST['site_name'] ?? '',
            'site_description' => $_POST['site_description'] ?? '',
            'contact_email' => $_POST['contact_email'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'timezone' => $_POST['timezone'] ?? 'Asia/Bangkok',
            'date_format' => $_POST['date_format'] ?? 'd/m/Y',
            'currency' => $_POST['currency'] ?? 'THB',
            'currency_symbol' => $_POST['currency_symbol'] ?? '฿',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'registration_enabled' => isset($_POST['registration_enabled']) ? '1' : '0',
            'max_login_attempts' => $_POST['max_login_attempts'] ?? '5',
            'session_timeout' => $_POST['session_timeout'] ?? '30'
        ];

        foreach ($settings_to_update as $key => $value) {
            Database::getInstance()->update(
                'settings',
                ['value' => $value],
                ['category' => 'general', 'key' => $key]
            );
        }

        // บันทึกประวัติการเปลี่ยนแปลง
        Database::getInstance()->insert('system_logs', [
            'action' => 'update_settings',
            'details' => 'อัปเดตการตั้งค่าระบบ',
            'created_by' => Auth::getUserId(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        Database::getInstance()->commit();
        $success = true;
        $_SESSION['success'] = 'บันทึกการตั้งค่าเรียบร้อยแล้ว';

        // รีเฟรชข้อมูลการตั้งค่า
        $settings = Database::getInstance()->fetchAll("
            SELECT * FROM settings 
            WHERE category = 'general'
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
            <h2>ตั้งค่าระบบ</h2>
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
                <!-- ข้อมูลทั่วไป -->
                <div class="col-md-6">
                    <label for="site_name" class="form-label">ชื่อระบบ</label>
                    <input type="text" class="form-control" id="site_name" name="site_name"
                        value="<?php echo htmlspecialchars($settings_map['site_name'] ?? ''); ?>" required>
                    <div class="invalid-feedback">
                        กรุณากรอกชื่อระบบ
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="site_description" class="form-label">คำอธิบายระบบ</label>
                    <input type="text" class="form-control" id="site_description" name="site_description"
                        value="<?php echo htmlspecialchars($settings_map['site_description'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label for="contact_email" class="form-label">อีเมลติดต่อ</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email"
                        value="<?php echo htmlspecialchars($settings_map['contact_email'] ?? ''); ?>" required>
                    <div class="invalid-feedback">
                        กรุณากรอกอีเมลที่ถูกต้อง
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="contact_phone" class="form-label">เบอร์โทรติดต่อ</label>
                    <input type="text" class="form-control" id="contact_phone" name="contact_phone"
                        value="<?php echo htmlspecialchars($settings_map['contact_phone'] ?? ''); ?>">
                </div>

                <div class="col-12">
                    <label for="address" class="form-label">ที่อยู่</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($settings_map['address'] ?? ''); ?></textarea>
                </div>

                <!-- การตั้งค่าระบบ -->
                <div class="col-md-6">
                    <label for="timezone" class="form-label">เขตเวลา</label>
                    <select class="form-select" id="timezone" name="timezone" required>
                        <option value="Asia/Bangkok" <?php echo ($settings_map['timezone'] ?? '') == 'Asia/Bangkok' ? 'selected' : ''; ?>>
                            กรุงเทพฯ (UTC+7)
                        </option>
                        <option value="UTC" <?php echo ($settings_map['timezone'] ?? '') == 'UTC' ? 'selected' : ''; ?>>
                            UTC
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="date_format" class="form-label">รูปแบบวันที่</label>
                    <select class="form-select" id="date_format" name="date_format" required>
                        <option value="d/m/Y" <?php echo ($settings_map['date_format'] ?? '') == 'd/m/Y' ? 'selected' : ''; ?>>
                            DD/MM/YYYY
                        </option>
                        <option value="Y-m-d" <?php echo ($settings_map['date_format'] ?? '') == 'Y-m-d' ? 'selected' : ''; ?>>
                            YYYY-MM-DD
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="currency" class="form-label">สกุลเงิน</label>
                    <select class="form-select" id="currency" name="currency" required>
                        <option value="THB" <?php echo ($settings_map['currency'] ?? '') == 'THB' ? 'selected' : ''; ?>>
                            บาทไทย (THB)
                        </option>
                        <option value="USD" <?php echo ($settings_map['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>
                            ดอลลาร์สหรัฐ (USD)
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="currency_symbol" class="form-label">สัญลักษณ์สกุลเงิน</label>
                    <input type="text" class="form-control" id="currency_symbol" name="currency_symbol"
                        value="<?php echo htmlspecialchars($settings_map['currency_symbol'] ?? '฿'); ?>" required>
                </div>

                <!-- การตั้งค่าความปลอดภัย -->
                <div class="col-md-6">
                    <label for="max_login_attempts" class="form-label">จำนวนครั้งสูงสุดในการล็อกอิน</label>
                    <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts"
                        value="<?php echo htmlspecialchars($settings_map['max_login_attempts'] ?? '5'); ?>" min="1" max="10" required>
                </div>

                <div class="col-md-6">
                    <label for="session_timeout" class="form-label">หมดเวลาการล็อกอิน (นาที)</label>
                    <input type="number" class="form-control" id="session_timeout" name="session_timeout"
                        value="<?php echo htmlspecialchars($settings_map['session_timeout'] ?? '30'); ?>" min="5" max="120" required>
                </div>

                <!-- ตัวเลือกเพิ่มเติม -->
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode"
                            <?php echo ($settings_map['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="maintenance_mode">โหมดบำรุงรักษา</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="registration_enabled" name="registration_enabled"
                            <?php echo ($settings_map['registration_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="registration_enabled">เปิดใช้งานการลงทะเบียน</label>
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