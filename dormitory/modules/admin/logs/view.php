<?php
$pageTitle = "ประวัติการใช้งานระบบ";
require_once __DIR__ . '/../auth_check.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2">
            <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 1rem;">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            เมนูหลัก
                        </div>

                        <a href="../dashboard.php"
                            class="list-group-item list-group-item-action border-0 <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line me-2"></i>แดชบอร์ด
                        </a>

                        <a href="../buildings/list.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/buildings/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-building me-2"></i>จัดการอาคาร
                        </a>

                        <a href="../rooms/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/rooms/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-door-open me-2"></i>จัดการห้องพัก
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            การจัดการผู้ใช้
                        </div>

                        <a href="../users/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-users me-2"></i>จัดการผู้ใช้
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            การแจ้งซ่อม
                        </div>

                        <a href="../repairs/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo basename($_SERVER['PHP_SELF']) === 'repairs.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tools me-2"></i>จัดการการแจ้งซ่อม
                        </a>

                        <a href="../repairs/categories.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/repairs/categories.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-list me-2"></i>หมวดหมู่การแจ้งซ่อม
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            การเงิน
                        </div>

                        <a href="../bills/list.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/bills/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-file-invoice-dollar me-2"></i>จัดการค่าใช้จ่าย
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            รายงาน
                        </div>

                        <a href="../reports/occupancy.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/reports/occupancy.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-bed me-2"></i>รายงานการเข้าพัก
                        </a>

                        <a href="../reports/repairs.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/reports/repairs.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-wrench me-2"></i>รายงานการแจ้งซ่อม
                        </a>

                        <a href="../reports/finance.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/reports/finance.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie me-2"></i>รายงานการเงิน
                        </a>

                        <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                            ระบบ
                        </div>

                        <a href="../settings/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/settings/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-cog me-2"></i>ตั้งค่าระบบ
                        </a>

                        <a href="../logs/view.php"
                            class="list-group-item list-group-item-action border-0 <?php echo strpos($_SERVER['PHP_SELF'], '/logs/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-history me-2"></i>ประวัติการใช้งาน
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="px-4 py-3">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-tools fa-4x text-muted"></i>
                    </div>
                    <h2>อยู่ระหว่างการพัฒนา</h2>
                    <p class="text-muted">ขออภัย ฟีเจอร์นี้กำลังอยู่ในระหว่างการพัฒนา</p>
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