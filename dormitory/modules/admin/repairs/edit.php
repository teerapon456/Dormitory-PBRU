<?php
$pageTitle = "แก้ไขรายการแจ้งซ่อม";
require_once __DIR__ . '/../auth_check.php';

// ตรวจสอบ ID ที่ต้องการแก้ไข
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid repair request ID';
    header('Location: view.php');
    exit;
}

$request_id = intval($_GET['id']);

// ดึงข้อมูลการแจ้งซ่อม
try {
    $db = Database::getInstance();

    $repair = $db->fetch(
        "SELECT r.*, 
                u.full_name as user_name, 
                c.full_name as contact_name,
                c.phone_number as contact_phone,
                c.email as contact_email,
                rm.room_number, 
                b.building_name 
         FROM repair_requests r
         LEFT JOIN users u ON r.user_id = u.user_id
         LEFT JOIN public_repair_contacts c ON r.contact_id = c.contact_id
         LEFT JOIN rooms rm ON r.room_id = rm.room_id
         LEFT JOIN buildings b ON rm.building_id = b.building_id
         WHERE r.request_id = ?",
        [$request_id]
    );

    if (!$repair) {
        $_SESSION['error'] = 'ไม่พบข้อมูลรายการแจ้งซ่อม';
        header('Location: view.php');
        exit;
    }

    // ดึงรูปภาพที่เกี่ยวข้อง
    $images = $db->fetchAll(
        "SELECT * FROM repair_images WHERE request_id = ?",
        [$request_id]
    );
} catch (Exception $e) {
    $_SESSION['error'] = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
    header('Location: view.php');
    exit;
}

// สถานะการซ่อม
$statuses = [
    'รอดำเนินการ' => 'รอดำเนินการ',
    'กำลังดำเนินการ' => 'กำลังดำเนินการ',
    'เสร็จสิ้น' => 'เสร็จสิ้น',
    'ยกเลิก' => 'ยกเลิก'
];

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($_POST['title'])) {
            throw new Exception('กรุณาระบุหัวข้อการแจ้งซ่อม');
        }

        if (empty($_POST['status']) || !array_key_exists($_POST['status'], $statuses)) {
            throw new Exception('กรุณาเลือกสถานะการซ่อม');
        }

        // เริ่ม transaction
        $db->beginTransaction();

        try {
            // อัพเดทข้อมูลการแจ้งซ่อม
            $result = $db->update(
                "repair_requests",
                [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'] ?? null,
                    'status' => $_POST['status'],
                    'updated_time' => date('Y-m-d H:i:s')
                ],
                "request_id = ?",
                [$request_id]
            );

            if (!$result) {
                throw new Exception('ไม่สามารถอัพเดทข้อมูลได้');
            }

            // จัดการรูปภาพที่ต้องการลบ
            if (!empty($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $image_id) {
                    // ดึงข้อมูลรูปภาพ
                    $image = $db->fetch(
                        "SELECT image_path FROM repair_images WHERE image_id = ? AND request_id = ?",
                        [intval($image_id), $request_id]
                    );

                    if ($image) {
                        // ลบไฟล์รูปภาพ
                        $image_path = __DIR__ . '/../../../' . $image['image_path'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }

                        // ลบข้อมูลจากฐานข้อมูล
                        $db->delete(
                            "repair_images",
                            "image_id = ? AND request_id = ?",
                            [intval($image_id), $request_id]
                        );
                    }
                }
            }

            // อัพโหลดรูปภาพใหม่
            if (!empty($_FILES['images']['name'][0])) {
                $upload_path = __DIR__ . '/../../../uploads/repairs/';
                if (!file_exists($upload_path)) {
                    mkdir($upload_path, 0777, true);
                }

                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $filename = uniqid() . '_' . $_FILES['images']['name'][$key];
                        $filepath = 'uploads/repairs/' . $filename;

                        if (move_uploaded_file($tmp_name, $upload_path . $filename)) {
                            $db->insert(
                                "repair_images",
                                [
                                    'request_id' => $request_id,
                                    'image_path' => $filepath
                                ]
                            );
                        }
                    }
                }
            }

            // บันทึก transaction
            $db->commit();
            $_SESSION['success'] = 'อัพเดทข้อมูลเรียบร้อยแล้ว';
            header('Location: view.php');
            exit;
        } catch (Exception $e) {
            // ถ้าเกิดข้อผิดพลาด ให้ rollback transaction
            $db->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}
?>

<!-- ส่วนแสดงผล HTML -->
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main content -->
        <div class="col-lg-10">
            <div class="px-4 py-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="card-title mb-0">แก้ไขรายการแจ้งซ่อม</h2>
                            <a href="view.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>กลับ
                            </a>
                        </div>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- แสดงข้อมูลผู้แจ้ง -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h5 class="card-title">ข้อมูลผู้แจ้ง</h5>
                                <?php if ($repair['user_id']): ?>
                                    <p class="mb-1">
                                        <strong>ชื่อผู้แจ้ง:</strong> <?php echo htmlspecialchars($repair['user_name']); ?>
                                        <span class="badge bg-primary">นักศึกษา</span>
                                    </p>
                                <?php elseif ($repair['contact_id']): ?>
                                    <p class="mb-1">
                                        <strong>ชื่อผู้แจ้ง:</strong> <?php echo htmlspecialchars($repair['contact_name']); ?>
                                        <span class="badge bg-info">ผู้แจ้งภายนอก</span>
                                    </p>
                                    <p class="mb-1">
                                        <strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($repair['contact_phone']); ?>
                                    </p>
                                    <?php if ($repair['contact_email']): ?>
                                        <p class="mb-0">
                                            <strong>อีเมล:</strong> <?php echo htmlspecialchars($repair['contact_email']); ?>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">หัวข้อ <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" required
                                        value="<?php echo htmlspecialchars($repair['title']); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select" required>
                                        <?php foreach ($statuses as $value => $label): ?>
                                            <option value="<?php echo $value; ?>"
                                                <?php echo $repair['status'] === $value ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">รายละเอียด</label>
                                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($repair['description'] ?? ''); ?></textarea>
                                </div>

                                <?php if (!empty($images)): ?>
                                    <div class="col-12">
                                        <label class="form-label d-block">รูปภาพปัจจุบัน</label>
                                        <div class="row g-3">
                                            <?php foreach ($images as $image): ?>
                                                <div class="col-md-3">
                                                    <div class="position-relative">
                                                        <img src="/dormitory/<?php echo $image['image_path']; ?>"
                                                            class="img-thumbnail" alt="Repair image">
                                                        <div class="form-check position-absolute top-0 end-0 m-2">
                                                            <input type="checkbox" name="delete_images[]"
                                                                value="<?php echo $image['image_id']; ?>"
                                                                class="form-check-input bg-danger border-danger"
                                                                id="delete_image_<?php echo $image['image_id']; ?>">
                                                            <label class="form-check-label visually-hidden"
                                                                for="delete_image_<?php echo $image['image_id']; ?>">
                                                                ลบรูปภาพนี้
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            เลือกที่รูปภาพที่ต้องการลบ
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="col-12">
                                    <label class="form-label">เพิ่มรูปภาพใหม่</label>
                                    <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                                    <small class="text-muted">สามารถเลือกได้หลายรูป</small>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>บันทึก
                                    </button>
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