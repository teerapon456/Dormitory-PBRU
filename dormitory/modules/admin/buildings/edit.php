<?php
$pageTitle = "แก้ไขข้อมูลอาคาร";
require_once __DIR__ . '/../auth_check.php';

// ตรวจสอบ ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ไม่พบข้อมูลอาคารที่ต้องการแก้ไข";
    header("Location: list.php");
    exit;
}

$building_id = (int)$_GET['id'];

// ดึงข้อมูลอาคาร
$building = Database::getInstance()->fetch("
    SELECT b.*,
           (SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.building_id) as total_rooms,
           (SELECT COUNT(*) FROM rooms r INNER JOIN users u ON r.room_id = u.room_id WHERE r.building_id = b.building_id) as occupied_rooms,
           (SELECT COUNT(*) FROM rooms r INNER JOIN users u ON r.room_id = u.room_id WHERE r.building_id = b.building_id) as total_residents,
           (SELECT SUM(max_capacity) FROM rooms r WHERE r.building_id = b.building_id) as max_capacity
    FROM buildings b
    WHERE b.building_id = :building_id
", [':building_id' => $building_id]);

if (!$building) {
    $_SESSION['error'] = "ไม่พบข้อมูลอาคารที่ต้องการแก้ไข";
    header("Location: list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // ตรวจสอบข้อมูล
    if (empty($_POST['building_name'])) {
        $errors[] = "กรุณาระบุชื่ออาคาร";
    } else {
        // ตรวจสอบว่ามีชื่ออาคารซ้ำหรือไม่ (ยกเว้นชื่อเดิมของตัวเอง)
        $existing = Database::getInstance()->fetch(
            "SELECT building_id FROM buildings WHERE building_name = :name AND building_id != :id",
            [':name' => $_POST['building_name'], ':id' => $building_id]
        );
        if ($existing) {
            $errors[] = "มีอาคารชื่อนี้อยู่แล้ว";
        }
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        try {
            // อัพเดทข้อมูล
            $result = Database::getInstance()->update(
                "buildings",
                [
                    'building_name' => $_POST['building_name'],
                    'description' => $_POST['description'] ?? null
                ],
                "building_id = :id",
                [':id' => $building_id]
            );

            if ($result) {
                $_SESSION['success'] = "แก้ไขข้อมูลอาคารเรียบร้อยแล้ว";
                header("Location: list.php");
                exit;
            } else {
                $errors[] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            }
        } catch (Exception $e) {
            $errors[] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2">
            <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 1rem;">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- ... existing sidebar code ... -->
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
                        <h2 class="card-title mb-4">แก้ไขข้อมูลอาคาร</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- สถิติอาคาร -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border-0 bg-primary bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-primary">จำนวนห้องทั้งหมด</h6>
                                        <h3 class="card-title mb-0"><?php echo $building['total_rooms']; ?> ห้อง</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 bg-success bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-success">ห้องที่มีผู้พัก</h6>
                                        <h3 class="card-title mb-0"><?php echo $building['occupied_rooms']; ?> ห้อง</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 bg-info bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-info">จำนวนผู้พักปัจจุบัน</h6>
                                        <h3 class="card-title mb-0"><?php echo $building['total_residents'] ?? 0; ?> คน</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 bg-warning bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-warning">ความจุสูงสุด</h6>
                                        <h3 class="card-title mb-0"><?php echo $building['max_capacity'] ?? 0; ?> คน</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="row g-3">
                            <!-- ชื่ออาคาร -->
                            <div class="col-md-6">
                                <label class="form-label">ชื่ออาคาร <span class="text-danger">*</span></label>
                                <input type="text" name="building_name" class="form-control" required
                                    value="<?php echo htmlspecialchars($building['building_name']); ?>">
                            </div>

                            <!-- รายละเอียด -->
                            <div class="col-12">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($building['description'] ?? ''); ?></textarea>
                            </div>

                            <!-- ปุ่มดำเนินการ -->
                            <div class="col-12">
                                <hr class="my-4">
                                <div class="d-flex justify-content-end">
                                    <a href="list.php" class="btn btn-secondary me-2">ยกเลิก</a>
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