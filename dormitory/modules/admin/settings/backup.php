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
    WHERE category = 'backup'
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
            'backup_enabled' => isset($_POST['backup_enabled']) ? '1' : '0',
            'backup_frequency' => $_POST['backup_frequency'] ?? 'daily',
            'backup_time' => $_POST['backup_time'] ?? '00:00',
            'backup_retention_days' => $_POST['backup_retention_days'] ?? '30',
            'backup_path' => $_POST['backup_path'] ?? 'backups',
            'backup_compress' => isset($_POST['backup_compress']) ? '1' : '0',
            'backup_notify' => isset($_POST['backup_notify']) ? '1' : '0',
            'backup_notify_email' => $_POST['backup_notify_email'] ?? '',
            'backup_notify_success' => isset($_POST['backup_notify_success']) ? '1' : '0',
            'backup_notify_error' => isset($_POST['backup_notify_error']) ? '1' : '0'
        ];

        foreach ($settings_to_update as $key => $value) {
            Database::getInstance()->update('settings', 
                ['value' => $value],
                ['category' => 'backup', 'key' => $key]
            );
        }

        // บันทึกประวัติการเปลี่ยนแปลง
        Database::getInstance()->insert('system_logs', [
            'action' => 'update_backup_settings',
            'details' => 'อัปเดตการตั้งค่าการสำรองข้อมูล',
            'created_by' => Auth::getUserId(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        Database::getInstance()->commit();
        $success = true;
        $_SESSION['success'] = 'บันทึกการตั้งค่าการสำรองข้อมูลเรียบร้อยแล้ว';

        // รีเฟรชข้อมูลการตั้งค่า
        $settings = Database::getInstance()->fetchAll("
            SELECT * FROM settings 
            WHERE category = 'backup'
        ");
        foreach ($settings as $setting) {
            $settings_map[$setting['key']] = $setting['value'];
        }
    } catch (Exception $e) {
        Database::getInstance()->rollBack();
        $errors[] = 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า: ' . $e->getMessage();
    }
}

// ดึงรายการไฟล์สำรองข้อมูล
$backup_files = [];
$backup_path = __DIR__ . '/../../../' . ($settings_map['backup_path'] ?? 'backups');
if (is_dir($backup_path)) {
    $files = scandir($backup_path);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($backup_path . '/' . $file),
                'date' => date('Y-m-d H:i:s', filemtime($backup_path . '/' . $file))
            ];
        }
    }
    usort($backup_files, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>ตั้งค่าการสำรองข้อมูล</h2>
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

<div class="row">
    <!-- ฟอร์มตั้งค่า -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <!-- การตั้งค่าการสำรองข้อมูล -->
                        <div class="col-12">
                            <h5 class="mb-3">การตั้งค่าการสำรองข้อมูล</h5>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="backup_enabled" name="backup_enabled"
                                    <?php echo ($settings_map['backup_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="backup_enabled">เปิดใช้งานการสำรองข้อมูลอัตโนมัติ</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="backup_frequency" class="form-label">ความถี่ในการสำรองข้อมูล</label>
                            <select class="form-select" id="backup_frequency" name="backup_frequency" required>
                                <option value="daily" <?php echo ($settings_map['backup_frequency'] ?? '') == 'daily' ? 'selected' : ''; ?>>
                                    ทุกวัน
                                </option>
                                <option value="weekly" <?php echo ($settings_map['backup_frequency'] ?? '') == 'weekly' ? 'selected' : ''; ?>>
                                    ทุกสัปดาห์
                                </option>
                                <option value="monthly" <?php echo ($settings_map['backup_frequency'] ?? '') == 'monthly' ? 'selected' : ''; ?>>
                                    ทุกเดือน
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="backup_time" class="form-label">เวลาสำรองข้อมูล</label>
                            <input type="time" class="form-control" id="backup_time" name="backup_time"
                                value="<?php echo htmlspecialchars($settings_map['backup_time'] ?? '00:00'); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="backup_retention_days" class="form-label">เก็บไฟล์สำรองข้อมูล (วัน)</label>
                            <input type="number" class="form-control" id="backup_retention_days" name="backup_retention_days"
                                value="<?php echo htmlspecialchars($settings_map['backup_retention_days'] ?? '30'); ?>" min="1" max="365" required>
                        </div>

                        <div class="col-md-6">
                            <label for="backup_path" class="form-label">ที่เก็บไฟล์สำรองข้อมูล</label>
                            <input type="text" class="form-control" id="backup_path" name="backup_path"
                                value="<?php echo htmlspecialchars($settings_map['backup_path'] ?? 'backups'); ?>" required>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="backup_compress" name="backup_compress"
                                    <?php echo ($settings_map['backup_compress'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="backup_compress">บีบอัดไฟล์สำรองข้อมูล</label>
                            </div>
                        </div>

                        <!-- การแจ้งเตือน -->
                        <div class="col-12">
                            <h5 class="mb-3">การแจ้งเตือน</h5>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="backup_notify" name="backup_notify"
                                    <?php echo ($settings_map['backup_notify'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="backup_notify">แจ้งเตือนทางอีเมล</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="backup_notify_email" class="form-label">อีเมลสำหรับแจ้งเตือน</label>
                            <input type="email" class="form-control" id="backup_notify_email" name="backup_notify_email"
                                value="<?php echo htmlspecialchars($settings_map['backup_notify_email'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="backup_notify_success" name="backup_notify_success"
                                    <?php echo ($settings_map['backup_notify_success'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="backup_notify_success">แจ้งเตือนเมื่อสำรองข้อมูลสำเร็จ</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="backup_notify_error" name="backup_notify_error"
                                    <?php echo ($settings_map['backup_notify_error'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="backup_notify_error">แจ้งเตือนเมื่อสำรองข้อมูลล้มเหลว</label>
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
    </div>

    <!-- รายการไฟล์สำรองข้อมูล -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">ไฟล์สำรองข้อมูล</h5>
            </div>
            <div class="card-body">
                <?php if (empty($backup_files)): ?>
                    <div class="alert alert-info mb-0">
                        ไม่พบไฟล์สำรองข้อมูล
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($backup_files as $file): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($file['name']); ?></h6>
                                    <small><?php echo date('d/m/Y H:i', strtotime($file['date'])); ?></small>
                                </div>
                                <small class="text-muted">
                                    <?php echo number_format($file['size'] / 1024, 2); ?> KB
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
