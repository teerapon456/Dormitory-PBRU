<?php
$pageTitle = "เพิ่มห้องพัก";
require_once __DIR__ . '/../auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // ตรวจสอบข้อมูล
    if (empty($_POST['building_id'])) {
        $errors[] = "กรุณาเลือกอาคาร";
    }

    if (empty($_POST['room_number'])) {
        $errors[] = "กรุณาระบุเลขห้อง";
    } else {
        // ตรวจสอบว่ามีเลขห้องซ้ำในอาคารเดียวกันหรือไม่
        $existing = Database::getInstance()->fetch(
            "SELECT room_id FROM rooms WHERE building_id = :building_id AND room_number = :room_number",
            [
                ':building_id' => $_POST['building_id'],
                ':room_number' => $_POST['room_number']
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

    // ถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        try {
            // เพิ่มข้อมูลลงในฐานข้อมูล
            $result = Database::getInstance()->insert(
                "rooms",
                [
                    'building_id' => $_POST['building_id'],
                    'room_number' => $_POST['room_number'],
                    'floor_number' => $_POST['floor_number'],
                    'max_capacity' => $_POST['max_capacity'],
                    'current_occupancy' => 0
                ]
            );

            if ($result) {
                $_SESSION['success'] = "เพิ่มห้องพักเรียบร้อยแล้ว";
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

// ดึงข้อมูลอาคารทั้งหมดเพื่อแสดงในตัวเลือก
$buildings = Database::getInstance()->fetchAll("SELECT building_id, building_name FROM buildings ORDER BY building_name");
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
                        <h2 class="card-title mb-4">เพิ่มห้องพัก</h2>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="row g-3">
                            <!-- อาคาร -->
                            <div class="col-md-6">
                                <label class="form-label">อาคาร <span class="text-danger">*</span></label>
                                <select name="building_id" class="form-select" required>
                                    <option value="">เลือกอาคาร</option>
                                    <?php foreach ($buildings as $building): ?>
                                    <option value="<?php echo $building['building_id']; ?>"
                                        <?php echo (isset($_POST['building_id']) && $_POST['building_id'] == $building['building_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($building['building_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- เลขห้อง -->
                            <div class="col-md-3">
                                <label class="form-label">เลขห้อง <span class="text-danger">*</span></label>
                                <input type="text" name="room_number" class="form-control" required maxlength="10"
                                    value="<?php echo isset($_POST['room_number']) ? htmlspecialchars($_POST['room_number']) : ''; ?>">
                            </div>

                            <!-- ชั้น -->
                            <div class="col-md-3">
                                <label class="form-label">ชั้น <span class="text-danger">*</span></label>
                                <input type="number" name="floor_number" class="form-control" required min="1"
                                    value="<?php echo isset($_POST['floor_number']) ? htmlspecialchars($_POST['floor_number']) : '1'; ?>">
                            </div>

                            <!-- ความจุสูงสุด -->
                            <div class="col-md-3">
                                <label class="form-label">ความจุสูงสุด (คน) <span class="text-danger">*</span></label>
                                <input type="number" name="max_capacity" class="form-control" required min="1"
                                    value="<?php echo isset($_POST['max_capacity']) ? htmlspecialchars($_POST['max_capacity']) : '4'; ?>">
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