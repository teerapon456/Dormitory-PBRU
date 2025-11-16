<?php
require_once __DIR__ . '/../../../includes/header.php';

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // ตรวจสอบข้อมูล
    if (empty($_POST['full_name'])) {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    }
    if (empty($_POST['phone_number'])) {
        $errors[] = 'กรุณากรอกเบอร์โทรศัพท์';
    }
    if (empty($_POST['email'])) {
        $errors[] = 'กรุณากรอกอีเมล';
    }
    if (empty($_POST['address'])) {
        $errors[] = 'กรุณากรอกที่อยู่';
    }
    if (empty($_POST['repair_item_name'])) {
        $errors[] = 'กรุณากรอกรายการที่ต้องการซ่อม';
    }
    if (empty($_POST['repair_description'])) {
        $errors[] = 'กรุณากรอกรายละเอียดการซ่อม';
    }
    if (empty($_POST['repair_location'])) {
        $errors[] = 'กรุณากรอกสถานที่ที่ต้องการซ่อม';
    }

    if (empty($errors)) {
        try {
            // บันทึกข้อมูลการแจ้งซ่อม
            $repairId = Database::getInstance()->insert('public_repair_contacts', [
                'full_name' => $_POST['full_name'],
                'phone_number' => $_POST['phone_number'],
                'email' => $_POST['email'],
                'address' => $_POST['address'],
                'repair_item_name' => $_POST['repair_item_name'],
                'repair_description' => $_POST['repair_description'],
                'repair_location' => $_POST['repair_location'],
                'repair_priority' => $_POST['repair_priority'] ?? 'ปานกลาง',
                'preferred_contact_time' => $_POST['preferred_contact_time'] ?? null,
                'additional_notes' => $_POST['additional_notes'] ?? null
            ]);

            // บันทึก log
            Functions::log("New public repair request created: ID {$repairId}", 'INFO');

            // แสดงข้อความสำเร็จ
            $_SESSION['success'] = 'บันทึกการแจ้งซ่อมเรียบร้อยแล้ว';
            header('Location: ' . Config::$baseUrl . '/modules/public/repairs/view.php?id=' . $repairId);
            exit;
        } catch (Exception $e) {
            $errors[] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
            Functions::log($e->getMessage(), 'ERROR');
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-4">แจ้งซ่อมสำหรับประชาชนทั่วไป</h2>

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
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">ชื่อ-นามสกุล *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                    required>
                                <div class="invalid-feedback">
                                    กรุณากรอกชื่อ-นามสกุล
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label">เบอร์โทรศัพท์ *</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number"
                                    value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                                    required>
                                <div class="invalid-feedback">
                                    กรุณากรอกเบอร์โทรศัพท์
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล *</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required>
                            <div class="invalid-feedback">
                                กรุณากรอกอีเมล
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">ที่อยู่ *</label>
                            <textarea class="form-control" id="address" name="address" rows="2"
                                required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            <div class="invalid-feedback">
                                กรุณากรอกที่อยู่
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="repair_item_name" class="form-label">รายการที่ต้องการซ่อม *</label>
                            <input type="text" class="form-control" id="repair_item_name" name="repair_item_name"
                                value="<?php echo isset($_POST['repair_item_name']) ? htmlspecialchars($_POST['repair_item_name']) : ''; ?>"
                                required>
                            <div class="invalid-feedback">
                                กรุณากรอกรายการที่ต้องการซ่อม
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="repair_description" class="form-label">รายละเอียดการซ่อม *</label>
                            <textarea class="form-control" id="repair_description" name="repair_description" rows="3"
                                required><?php echo isset($_POST['repair_description']) ? htmlspecialchars($_POST['repair_description']) : ''; ?></textarea>
                            <div class="invalid-feedback">
                                กรุณากรอกรายละเอียดการซ่อม
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="repair_location" class="form-label">สถานที่ที่ต้องการซ่อม *</label>
                            <input type="text" class="form-control" id="repair_location" name="repair_location"
                                value="<?php echo isset($_POST['repair_location']) ? htmlspecialchars($_POST['repair_location']) : ''; ?>"
                                required>
                            <div class="invalid-feedback">
                                กรุณากรอกสถานที่ที่ต้องการซ่อม
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="repair_priority" class="form-label">ความเร่งด่วน</label>
                                <select class="form-select" id="repair_priority" name="repair_priority">
                                    <option value="ต่ำ"
                                        <?php echo (isset($_POST['repair_priority']) && $_POST['repair_priority'] === 'ต่ำ') ? 'selected' : ''; ?>>
                                        ต่ำ</option>
                                    <option value="ปานกลาง"
                                        <?php echo (isset($_POST['repair_priority']) && $_POST['repair_priority'] === 'ปานกลาง') ? 'selected' : ''; ?>>
                                        ปานกลาง</option>
                                    <option value="สูง"
                                        <?php echo (isset($_POST['repair_priority']) && $_POST['repair_priority'] === 'สูง') ? 'selected' : ''; ?>>
                                        สูง</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="preferred_contact_time" class="form-label">เวลาที่สะดวกให้ติดต่อ</label>
                                <input type="text" class="form-control" id="preferred_contact_time"
                                    name="preferred_contact_time"
                                    value="<?php echo isset($_POST['preferred_contact_time']) ? htmlspecialchars($_POST['preferred_contact_time']) : ''; ?>"
                                    placeholder="เช่น 9:00-17:00">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="additional_notes" class="form-label">หมายเหตุเพิ่มเติม</label>
                            <textarea class="form-control" id="additional_notes" name="additional_notes"
                                rows="2"><?php echo isset($_POST['additional_notes']) ? htmlspecialchars($_POST['additional_notes']) : ''; ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> ส่งคำขอแจ้งซ่อม
                            </button>
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

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>