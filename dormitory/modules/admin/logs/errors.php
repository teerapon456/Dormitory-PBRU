<?php
$pageTitle = "บันทึกข้อผิดพลาด";
require_once __DIR__ . '/../auth_check.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="px-4 py-3">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle fa-4x text-muted"></i>
                    </div>
                    <h2>อยู่ระหว่างการพัฒนา</h2>
                    <p class="text-muted">ขออภัย ระบบบันทึกข้อผิดพลาดกำลังอยู่ในระหว่างการพัฒนา</p>
                    <div class="mt-4">
                        <a href="../dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>กลับสู่หน้าหลัก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>