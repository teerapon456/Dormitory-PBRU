<?php
$pageTitle = "แก้ไขค่าใช้จ่าย";
require_once __DIR__ . '/../auth_check.php';

// ตรวจสอบ ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ไม่พบรายการที่ต้องการแก้ไข";
    header("Location: list.php");
    exit;
}

$bill_id = (int)$_GET['id'];

// ดึงข้อมูลค่าใช้จ่าย
$bill = Database::getInstance()->fetch("
    SELECT b.*, r.room_number, bd.building_name, u.full_name
    FROM utility_bills b
    LEFT JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN buildings bd ON r.building_id = bd.building_id
    LEFT JOIN users u ON r.room_id = u.room_id
    WHERE b.bill_id = :bill_id
", [':bill_id' => $bill_id]);

if (!$bill) {
    $_SESSION['error'] = "ไม่พบรายการที่ต้องการแก้ไข";
    header("Location: list.php");
    exit;
}

// ดึงข้อมูลห้องพักและผู้พัก
$rooms = Database::getInstance()->fetchAll("
    SELECT 
        r.room_id,
        r.room_number,
        b.building_name,
        GROUP_CONCAT(DISTINCT u.full_name SEPARATOR ', ') as residents
    FROM rooms r
    LEFT JOIN buildings b ON r.building_id = b.building_id
    LEFT JOIN users u ON r.room_id = u.room_id
    GROUP BY r.room_id, r.room_number, b.building_name
    ORDER BY b.building_name, r.room_number
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // ตรวจสอบข้อมูล
    if (empty($_POST['room_id'])) {
        $errors[] = "กรุณาเลือกห้องพัก";
    }

    if (empty($_POST['bill_type'])) {
        $errors[] = "กรุณาระบุประเภทค่าใช้จ่าย";
    }

    if (!is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
        $errors[] = "กรุณาระบุจำนวนเงินให้ถูกต้อง";
    }

    if (empty($_POST['reading_time'])) {
        $errors[] = "กรุณาระบุวันที่";
    }

    if (empty($_POST['due_date'])) {
        $errors[] = "กรุณาระบุกำหนดชำระ";
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        try {
            // อัพเดทข้อมูล
            $result = Database::getInstance()->update(
                "utility_bills",
                [
                    'room_id' => $_POST['room_id'],
                    'bill_type' => $_POST['bill_type'],
                    'amount' => $_POST['amount'],
                    'reading_time' => $_POST['reading_time'],
                    'due_date' => $_POST['due_date'],
                    'status' => $_POST['status']
                ],
                "bill_id = ?",
                [$bill_id]
            );

            if ($result) {
                $_SESSION['success'] = "แก้ไขค่าใช้จ่ายเรียบร้อยแล้ว";
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

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- การ์ดฟอร์ม -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-4">แก้ไขค่าใช้จ่าย</h2>

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
                        <!-- เลือกห้อง -->
                        <div class="col-md-6">
                            <label class="form-label">ห้องพัก <span class="text-danger">*</span></label>
                            <select name="room_id" class="form-select" required>
                                <option value="">เลือกห้องพัก</option>
                                <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['room_id']; ?>"
                                    <?php echo $bill['room_id'] == $room['room_id'] ? 'selected' : ''; ?>>
                                    <?php
                                        echo htmlspecialchars($room['building_name'] . ' ห้อง ' . $room['room_number']);
                                        if ($room['residents']) {
                                            echo ' (' . htmlspecialchars($room['residents']) . ')';
                                        }
                                        ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- ประเภทค่าใช้จ่าย -->
                        <div class="col-md-6">
                            <label class="form-label">ประเภทค่าใช้จ่าย <span class="text-danger">*</span></label>
                            <select name="bill_type" class="form-select" required>
                                <option value="">เลือกประเภทค่าใช้จ่าย</option>
                                <option value="ค่าน้ำ" <?php echo $bill['bill_type'] === 'ค่าน้ำ' ? 'selected' : ''; ?>>
                                    ค่าน้ำ</option>
                                <option value="ค่าไฟ" <?php echo $bill['bill_type'] === 'ค่าไฟ' ? 'selected' : ''; ?>>
                                    ค่าไฟ</option>
                                <option value="ค่าห้อง"
                                    <?php echo $bill['bill_type'] === 'ค่าห้อง' ? 'selected' : ''; ?>>ค่าห้อง</option>
                                <option value="อื่นๆ" <?php echo $bill['bill_type'] === 'อื่นๆ' ? 'selected' : ''; ?>>
                                    อื่นๆ</option>
                            </select>
                        </div>

                        <!-- จำนวนเงิน -->
                        <div class="col-md-3">
                            <label class="form-label">จำนวนเงิน (บาท) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0" required
                                value="<?php echo htmlspecialchars($bill['amount']); ?>">
                        </div>

                        <!-- วันที่ -->
                        <div class="col-md-3">
                            <label class="form-label">วันที่ <span class="text-danger">*</span></label>
                            <input type="date" name="reading_time" class="form-control" required
                                value="<?php echo htmlspecialchars($bill['reading_time']); ?>">
                        </div>

                        <!-- กำหนดชำระ -->
                        <div class="col-md-3">
                            <label class="form-label">กำหนดชำระ <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" class="form-control" required
                                value="<?php echo htmlspecialchars($bill['due_date']); ?>">
                        </div>

                        <!-- สถานะ -->
                        <div class="col-md-3">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="รอดำเนินการ"
                                    <?php echo $bill['status'] === 'รอดำเนินการ' ? 'selected' : ''; ?>>รอดำเนินการ
                                </option>
                                <option value="ชำระแล้ว"
                                    <?php echo $bill['status'] === 'ชำระแล้ว' ? 'selected' : ''; ?>>ชำระแล้ว</option>
                                <option value="เลยกำหนด"
                                    <?php echo $bill['status'] === 'เลยกำหนด' ? 'selected' : ''; ?>>เลยกำหนด</option>
                            </select>
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

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>