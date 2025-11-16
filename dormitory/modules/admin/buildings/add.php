<?php
$pageTitle = "เพิ่มอาคาร";
require_once __DIR__ . '/../auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // ตรวจสอบข้อมูล
    if (empty($_POST['building_name'])) {
        $errors[] = "กรุณาระบุชื่ออาคาร";
    } else {
        // ตรวจสอบว่ามีชื่ออาคารซ้ำหรือไม่
        $existing = Database::getInstance()->fetch(
            "SELECT building_id FROM buildings WHERE building_name = :name",
            [':name' => $_POST['building_name']]
        );
        if ($existing) {
            $errors[] = "มีอาคารชื่อนี้อยู่แล้ว";
        }
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        try {
            // เพิ่มข้อมูลลงในฐานข้อมูล
            $result = Database::getInstance()->insert(
                "buildings",
                [
                    'building_name' => $_POST['building_name'],
                    'description' => $_POST['description'] ?? null
                ]
            );

            if ($result) {
                $_SESSION['success'] = "เพิ่มอาคารเรียบร้อยแล้ว";
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
                        <h2 class="card-title mb-4">เพิ่มอาคาร</h2>

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
                            <!-- ชื่ออาคาร -->
                            <div class="col-md-6">
                                <label class="form-label">ชื่ออาคาร <span class="text-danger">*</span></label>
                                <input type="text" name="building_name" class="form-control" required
                                    value="<?php echo isset($_POST['building_name']) ? htmlspecialchars($_POST['building_name']) : ''; ?>">
                            </div>

                            <!-- รายละเอียด -->
                            <div class="col-12">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="description" class="form-control"
                                    rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
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