<?php
$pageTitle = "แก้ไขข้อมูลห้องพัก";
require_once __DIR__ . '/../auth_check.php';

// ตรวจสอบ ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ไม่พบข้อมูลห้องที่ต้องการแก้ไข";
    header("Location: view.php");
    exit;
}

$room_id = (int)$_GET['id'];

// ดึงข้อมูลห้องพัก
$room = Database::getInstance()->fetch("
    SELECT r.*, b.building_name,
           (SELECT COUNT(*) FROM users u WHERE u.room_id = r.room_id) as current_residents
    FROM rooms r
    LEFT JOIN buildings b ON b.building_id = r.building_id
    WHERE r.room_id = :room_id
", [':room_id' => $room_id]);

if (!$room) {
    $_SESSION['error'] = "ไม่พบข้อมูลห้องที่ต้องการแก้ไข";
    header("Location: view.php");
    exit;
}

// ดึงข้อมูลอาคารทั้งหมดเพื่อแสดงในตัวเลือก
$buildings = Database::getInstance()->fetchAll("SELECT building_id, building_name FROM buildings ORDER BY building_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // ตรวจสอบข้อมูล
    if (empty($_POST['building_id'])) {
        $errors[] = "กรุณาเลือกอาคาร";
    }

    if (empty($_POST['room_number'])) {
        $errors[] = "กรุณาระบุเลขห้อง";
    } else {
        // ตรวจสอบว่ามีเลขห้องซ้ำในอาคารเดียวกันหรือไม่ (ยกเว้นห้องปัจจุบัน)
        $existing = Database::getInstance()->fetch(
            "SELECT room_id FROM rooms WHERE building_id = :building_id AND room_number = :room_number AND room_id != :room_id",
            [
                ':building_id' => $_POST['building_id'],
                ':room_number' => $_POST['room_number'],
                ':room_id' => $room_id
            ]
        );
        if ($existing) {
            $errors[] = "มีห้องนี้อยู่แล้วในอาคารที่เลือก";
        }
    }

    if (empty($_POST['floor_number']) || !is_numeric($_POST['floor_number'])) {
        $errors[] = "กรุณาระบุชั้นเป็นตัวเลข";
    }

    if (empty($_POST['max_capacity']) || !is_numeric($_POST['max_capacity']) || $_POST['max_capacity'] < 1) {
        $errors[] = "กรุณาระบุความจุห้องเป็นตัวเลขที่มากกว่า 0";
    }

    // ตรวจสอบว่าความจุใหม่ต้องไม่น้อยกว่าจำนวนผู้พักปัจจุบัน
    if ($_POST['max_capacity'] < $room['current_occupancy']) {
        $errors[] = "ไม่สามารถกำหนดความจุน้อยกว่าจำนวนผู้พักปัจจุบัน";
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        try {
            // อัพเดทข้อมูล
            $result = Database::getInstance()->update(
                "rooms",
                [
                    'building_id' => $_POST['building_id'],
                    'room_number' => $_POST['room_number'],
                    'floor_number' => $_POST['floor_number'],
                    'max_capacity' => $_POST['max_capacity']
                ],
                "room_id = ?",
                [$room_id]
            );

            if ($result) {
                $_SESSION['success'] = "แก้ไขข้อมูลห้องเรียบร้อยแล้ว";
                header("Location: view.php");
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
                    <div class="view-group view-group-flush">
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
                        <h2 class="card-title mb-4">แก้ไขข้อมูลห้องพัก</h2>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- สถิติห้อง -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border-0 bg-primary bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-primary">อาคาร</h6>
                                        <h3 class="card-title mb-0">
                                            <?php echo htmlspecialchars($room['building_name']); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-success bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-success">ผู้พักปัจจุบัน</h6>
                                        <h3 class="card-title mb-0"><?php echo $room['current_occupancy']; ?> คน</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-info bg-opacity-10">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-info">ความจุสูงสุด</h6>
                                        <h3 class="card-title mb-0"><?php echo $room['max_capacity']; ?> คน</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="row g-3">
                            <!-- อาคาร -->
                            <div class="col-md-6">
                                <label class="form-label">อาคาร <span class="text-danger">*</span></label>
                                <select name="building_id" class="form-select" required>
                                    <option value="">เลือกอาคาร</option>
                                    <?php foreach ($buildings as $building): ?>
                                    <option value="<?php echo $building['building_id']; ?>"
                                        <?php echo ($room['building_id'] == $building['building_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($building['building_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- เลขห้อง -->
                            <div class="col-md-3">
                                <label class="form-label">เลขห้อง <span class="text-danger">*</span></label>
                                <input type="text" name="room_number" class="form-control" required maxlength="10"
                                    value="<?php echo htmlspecialchars($room['room_number']); ?>">
                            </div>

                            <!-- ชั้น -->
                            <div class="col-md-3">
                                <label class="form-label">ชั้น <span class="text-danger">*</span></label>
                                <input type="number" name="floor_number" class="form-control" required min="1"
                                    value="<?php echo htmlspecialchars($room['floor_number']); ?>">
                            </div>

                            <!-- ความจุสูงสุด -->
                            <div class="col-md-3">
                                <label class="form-label">ความจุสูงสุด (คน) <span class="text-danger">*</span></label>
                                <input type="number" name="max_capacity" class="form-control" required min="1"
                                    value="<?php echo htmlspecialchars($room['max_capacity']); ?>">
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