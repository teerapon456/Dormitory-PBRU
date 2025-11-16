<?php
$pageTitle = "จัดการข้อมูลอาคาร";
require_once __DIR__ . '/../auth_check.php';

// การค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// สร้าง SQL query สำหรับการค้นหา
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE building_name LIKE :search";
    $params[':search'] = "%{$search}%";
}

// การแบ่งหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// ดึงจำนวนรายการทั้งหมด
$count_sql = "SELECT COUNT(*) as total FROM buildings {$where_clause}";
$total_items = Database::getInstance()->fetch($count_sql, $params)['total'];
$total_pages = ceil($total_items / $per_page);

// ดึงข้อมูลอาคาร
$sql = "
    SELECT 
        b.*,
        (SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.building_id) as total_rooms,
        (SELECT COUNT(DISTINCT u.user_id) FROM rooms r 
         LEFT JOIN users u ON r.room_id = u.room_id 
         WHERE r.building_id = b.building_id) as total_residents,
        (SELECT COUNT(*) FROM rooms r 
         WHERE r.building_id = b.building_id 
         AND EXISTS (SELECT 1 FROM users u WHERE u.room_id = r.room_id)) as occupied_rooms,
        (SELECT SUM(max_capacity) FROM rooms r WHERE r.building_id = b.building_id) as max_capacity
    FROM buildings b
    {$where_clause}
    ORDER BY b.building_name
    LIMIT {$per_page} OFFSET {$offset}
";

$buildings = Database::getInstance()->fetchAll($sql, $params);
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
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="px-4 py-3">
                <!-- หัวข้อและปุ่มเพิ่ม -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">จัดการข้อมูลอาคาร</h2>
                        <p class="text-muted">จัดการข้อมูลอาคารและสถานที่ภายในหอพัก</p>
                    </div>
                    <div>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>เพิ่มอาคาร
                        </a>
                    </div>
                </div>

                <!-- ค้นหา -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">ค้นหา</label>
                                <input type="text" class="form-control" name="search"
                                    value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหาจากชื่ออาคาร...">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="d-flex gap-2 w-100">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="fas fa-search me-2"></i>ค้นหา
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="list.php" class="btn btn-secondary">
                                            <i class="fas fa-redo"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- แสดงผลการค้นหา -->
                <?php if (!empty($search)): ?>
                    <div class="alert alert-info mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                ผลการค้นหาสำหรับ: <strong><?php echo htmlspecialchars($search); ?></strong>
                                (พบ <?php echo number_format($total_items); ?> รายการ)
                            </div>
                            <a href="list.php" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-times me-2"></i>ล้างการค้นหา
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- แสดงรายการอาคาร -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    <?php if (empty($buildings)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                ไม่พบข้อมูลอาคาร
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($buildings as $building): ?>
                            <div class="col">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($building['building_name']); ?></h5>
                                        <div class="row g-3 mt-2">
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <div class="text-primary">
                                                        <i class="fas fa-door-open fa-fw"></i>
                                                    </div>
                                                    <div class="ms-2">
                                                        <div class="small text-muted">จำนวนห้อง</div>
                                                        <div class="fw-bold"><?php echo $building['total_rooms']; ?> ห้อง</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <div class="text-success">
                                                        <i class="fas fa-bed fa-fw"></i>
                                                    </div>
                                                    <div class="ms-2">
                                                        <div class="small text-muted">ห้องที่มีผู้พัก</div>
                                                        <div class="fw-bold"><?php echo $building['occupied_rooms']; ?> ห้อง
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <div class="text-info">
                                                        <i class="fas fa-users fa-fw"></i>
                                                    </div>
                                                    <div class="ms-2">
                                                        <div class="small text-muted">จำนวนผู้พัก</div>
                                                        <div class="fw-bold"><?php echo $building['total_residents'] ?? 0; ?> คน
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <div class="text-warning">
                                                        <i class="fas fa-user-friends fa-fw"></i>
                                                    </div>
                                                    <div class="ms-2">
                                                        <div class="small text-muted">ความจุสูงสุด</div>
                                                        <div class="fw-bold"><?php echo $building['max_capacity'] ?? 0; ?> คน
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                                            <a href="edit.php?id=<?php echo $building['building_id']; ?>"
                                                class="btn btn-sm btn-info me-2">
                                                <i class="fas fa-edit me-1"></i>แก้ไข
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete(<?php echo $building['building_id']; ?>, '<?php echo htmlspecialchars($building['building_name']); ?>')">
                                                <i class="fas fa-trash me-1"></i>ลบ
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- การแบ่งหน้า -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            แสดง <?php echo $offset + 1; ?> ถึง <?php echo min($offset + $per_page, $total_items); ?>
                            จากทั้งหมด <?php echo $total_items; ?> รายการ
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }

                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                                    echo '<a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '">' . $i . '</a>';
                                    echo '</li>';
                                }

                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search) . '">' . $total_pages . '</a></li>';
                                }
                                ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal ยืนยันการลบ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">คุณต้องการลบอาคาร <span id="deleteBuildingName" class="fw-bold"></span> ใช่หรือไม่?</p>
                <p class="text-danger mt-2 mb-0"><small>* การลบอาคารจะลบข้อมูลห้องพักทั้งหมดในอาคารนี้ด้วย</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form action="delete.php" method="POST" class="d-inline">
                    <input type="hidden" name="building_id" id="deleteBuildingId">
                    <button type="submit" class="btn btn-danger">ลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(buildingId, buildingName) {
        document.getElementById('deleteBuildingId').value = buildingId;
        document.getElementById('deleteBuildingName').textContent = buildingName;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>