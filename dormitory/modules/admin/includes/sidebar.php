<?php
// ตรวจสอบว่ามีการกำหนด $current_page หรือไม่
if (!isset($current_page)) {
    $current_page = '';
}
?>

<!-- Sidebar -->
<div class="col-lg-2">
    <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 1rem;">
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                    เมนูหลัก
                </div>
                <!-- แดชบอร์ด -->
                <a href="/dormitory/modules/admin/dashboard.php"
                    class="list-group-item list-group-item-action border-0 <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-2"></i>แดชบอร์ด
                </a>
                <!-- จัดการข้อมูลผู้ใช้ -->
                <a href="/dormitory/modules/admin/users/view.php"
                    class="list-group-item list-group-item-action border-0 <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i>จัดการข้อมูลผู้ใช้
                </a>
                <!-- จัดการข้อมูลอาคาร -->
                <a href="/dormitory/modules/admin/buildings/view.php"
                    class="list-group-item list-group-item-action border-0 <?php echo $current_page === 'buildings' ? 'active' : ''; ?>">
                    <i class="fas fa-building me-2"></i>จัดการข้อมูลอาคาร
                </a>
                <!-- จัดการข้อมูลห้องพัก -->
                <a href="/dormitory/modules/admin/rooms/view.php"
                    class="list-group-item list-group-item-action border-0 <?php echo $current_page === 'rooms' ? 'active' : ''; ?>">
                    <i class="fas fa-door-open me-2"></i>จัดการข้อมูลห้องพัก
                </a>
                <!-- จัดการข้อมูลแจ้งซ่อม -->
                <a href="/dormitory/modules/admin/repairs/view.php"
                    class="list-group-item list-group-item-action border-0 <?php echo $current_page === 'repairs' ? 'active' : ''; ?>">
                    <i class="fas fa-tools me-2"></i>จัดการข้อมูลแจ้งซ่อม
                </a>
                <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                    การเงิน
                </div>
                <!-- จัดการข้อมูลบิล -->
                <a href="/dormitory/modules/admin/bills/view.php"
                    class="list-group-item list-group-item-action border-0 <?php echo $current_page === 'bills' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar me-2"></i>จัดการข้อมูลบิล
                </a>

                <div class="list-group-item border-0 font-weight-bold text-uppercase px-3 py-2">
                    รายงาน
                </div>
                <!-- ตั้งค่าระบบ -->
                <a href="/dormitory/modules/admin/settings/view.php"
                    class="list-group-item list-group-item-action border-0 <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog me-2"></i>ตั้งค่าระบบ
                </a>

                <!-- ออกจากระบบ -->
                <a href="/dormitory/logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* เพิ่ม CSS สำหรับ Sidebar */
.list-group-item {
    border: none;
    padding: 0.875rem 1.25rem;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    color: #2d3338;
    font-weight: 500;
}

.list-group-item:hover {
    background-color: #f0f2f5;
    color: #0d6efd;
}

.list-group-item.active {
    background-color: #e7f0ff;
    border-color: #e7f0ff;
    color: #0d6efd;
    font-weight: 600;
}

.list-group-item i {
    width: 24px;
    text-align: center;
    font-size: 1.1rem;
}

.card {
    border-radius: 12px;
    margin-bottom: 1rem;
    background-color: #ffffff;
}

/* ปรับแต่งสีไอคอน */
.list-group-item i {
    color: #6c757d;
    transition: color 0.2s ease;
}

.list-group-item:hover i {
    color: #0d6efd;
}

.list-group-item.active i {
    color: #0d6efd;
}

/* ปรับแต่งสีข้อความ */
.list-group-item.text-danger {
    color: #dc3545 !important;
    font-weight: 500;
}

.list-group-item.text-danger:hover {
    background-color: #ffebee;
    color: #dc3545 !important;
}

.list-group-item.text-danger:hover i {
    color: #dc3545;
}

/* ปรับแต่งหัวข้อหมวดหมู่ */
.list-group-item.font-weight-bold {
    background-color: #ffffff;
    font-size: 0.75rem;
    color: #6c757d;
    padding: 1rem 1.25rem 0.5rem;
    font-weight: 700;
    letter-spacing: 0.5px;
}

/* ปรับแต่ง sticky top */
.sticky-top {
    z-index: 1020;
    top: 1rem;
}

/* เพิ่มเงา */
.shadow-sm {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
}
</style>