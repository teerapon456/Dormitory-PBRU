<?php
$pageTitle = "จัดการค่าใช้จ่าย";
require_once __DIR__ . '/../auth_check.php';

// จัดการการกรองข้อมูล
$where_conditions = [];
$params = [];

// กรองตามสถานะ
$status = isset($_GET['status']) ? $_GET['status'] : '';
if ($status !== '') {
    $where_conditions[] = "b.status = :status";
    $params[':status'] = $status;
}

// กรองตามเดือน/ปี
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
if ($month > 0 && $year > 0) {
    $where_conditions[] = "MONTH(b.reading_time) = :month AND YEAR(b.reading_time) = :year";
    $params[':month'] = $month;
    $params[':year'] = $year;
}

// กรองตามอาคาร
$building_id = isset($_GET['building_id']) ? (int)$_GET['building_id'] : 0;
if ($building_id > 0) {
    $where_conditions[] = "r.building_id = :building_id";
    $params[':building_id'] = $building_id;
}

// การค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $where_conditions[] = "(r.room_number LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = "%{$search}%";
}

// สร้าง WHERE clause
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// จัดการการเรียงลำดับ
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'reading_time';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$allowed_sort_fields = ['reading_time', 'due_date', 'amount', 'status'];
$sort = in_array($sort, $allowed_sort_fields) ? $sort : 'reading_time';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// การแบ่งหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// ดึงจำนวนรายการทั้งหมด
$count_sql = "
    SELECT COUNT(*) as total 
    FROM utility_bills b
    LEFT JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN users u ON r.room_id = u.room_id
    LEFT JOIN buildings bd ON r.building_id = bd.building_id
    $where_clause
";
$total_items = Database::getInstance()->fetch($count_sql, $params)['total'];
$total_pages = ceil($total_items / $per_page);

// ดึงข้อมูลค่าใช้จ่าย
$sql = "
    SELECT 
        b.*,
        r.room_number,
        bd.building_name,
        u.full_name,
        u.username as student_id
    FROM utility_bills b
    LEFT JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN users u ON r.room_id = u.room_id
    LEFT JOIN buildings bd ON r.building_id = bd.building_id
    $where_clause
    ORDER BY b.$sort $order
    LIMIT $per_page OFFSET $offset
";
$bills = Database::getInstance()->fetchAll($sql, $params);

// ดึงข้อมูลอาคารสำหรับตัวกรอง
$buildings = Database::getInstance()->fetchAll("SELECT * FROM buildings ORDER BY building_name");
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
                        <h2 class="mb-1">จัดการค่าใช้จ่าย</h2>
                        <p class="text-muted">จัดการค่าสาธารณูปโภคและค่าใช้จ่ายอื่นๆ</p>
                    </div>
                    <div>
                        <a href="generate_pdf_form.php" class="btn btn-success me-2">
                            <i class="fas fa-file-invoice me-2"></i>สร้างบิลประจำเดือน
                        </a>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>เพิ่มค่าใช้จ่าย
                        </a>
                    </div>
                </div>

                <!-- ฟิลเตอร์และการค้นหา -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <!-- ค้นหา -->
                            <div class="col-md-3">
                                <label class="form-label">ค้นหา</label>
                                <input type="text" class="form-control" name="search"
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="ค้นหาจากเลขห้อง, ชื่อนักศึกษา...">
                            </div>

                            <!-- กรองตามสถานะ -->
                            <div class="col-md-2">
                                <label class="form-label">สถานะ</label>
                                <select class="form-select" name="status">
                                    <option value="">ทั้งหมด</option>
                                    <option value="รอดำเนินการ"
                                        <?php echo $status === 'รอดำเนินการ' ? 'selected' : ''; ?>>
                                        รอดำเนินการ</option>
                                    <option value="ชำระแล้ว" <?php echo $status === 'ชำระแล้ว' ? 'selected' : ''; ?>>
                                        ชำระแล้ว
                                    </option>
                                    <option value="เลยกำหนด" <?php echo $status === 'เลยกำหนด' ? 'selected' : ''; ?>>
                                        เลยกำหนด
                                    </option>
                                </select>
                            </div>

                            <!-- กรองตามเดือน/ปี -->
                            <div class="col-md-2">
                                <label class="form-label">เดือน</label>
                                <select class="form-select" name="month">
                                    <option value="">ทั้งหมด</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $month === $i ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">ปี</label>
                                <select class="form-select" name="year">
                                    <option value="">ทั้งหมด</option>
                                    <?php
                                    $current_year = (int)date('Y');
                                    for ($i = $current_year; $i >= $current_year - 2; $i--):
                                    ?>
                                    <option value="<?php echo $i; ?>" <?php echo $year === $i ? 'selected' : ''; ?>>
                                        <?php echo $i + 543; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- กรองตามอาคาร -->
                            <div class="col-md-2">
                                <label class="form-label">อาคาร</label>
                                <select class="form-select" name="building_id">
                                    <option value="">ทั้งหมด</option>
                                    <?php foreach ($buildings as $building): ?>
                                    <option value="<?php echo $building['building_id']; ?>"
                                        <?php echo $building_id == $building['building_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($building['building_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- ปุ่มค้นหา -->
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ตารางแสดงรายการ -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>วันที่</th>
                                        <th>ห้อง</th>
                                        <th>นักศึกษา</th>
                                        <th>รายการ</th>
                                        <th class="text-end">จำนวนเงิน</th>
                                        <th>กำหนดชำระ</th>
                                        <th>สถานะ</th>
                                        <th class="text-end">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($bills)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <p class="text-muted mb-0">ไม่พบรายการค่าใช้จ่าย</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($bills as $bill): ?>
                                    <tr>
                                        <td>
                                            <?php
                                                    $bill_date = new DateTime($bill['reading_time']);
                                                    echo $bill_date->format('d/m/Y');
                                                    ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($bill['building_name'] . ' ห้อง ' . $bill['room_number']); ?>
                                        </td>
                                        <td>
                                            <div>
                                                <?php echo htmlspecialchars($bill['full_name']); ?>
                                                <?php if ($bill['student_id']): ?>
                                                <small class="text-muted d-block">
                                                    รหัสนักศึกษา: <?php echo htmlspecialchars($bill['student_id']); ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?php echo htmlspecialchars($bill['bill_type']); ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <?php echo number_format($bill['amount'], 2); ?> บาท
                                        </td>
                                        <td>
                                            <?php
                                                    $due_date = new DateTime($bill['due_date']);
                                                    echo $due_date->format('d/m/Y');
                                                    ?>
                                        </td>
                                        <td>
                                            <?php
                                                    $status_class = [
                                                        'รอดำเนินการ' => 'warning',
                                                        'ชำระแล้ว' => 'success',
                                                        'เลยกำหนด' => 'danger'
                                                    ];
                                                    ?>
                                            <span class="badge bg-<?php echo $status_class[$bill['status']]; ?>">
                                                <?php echo htmlspecialchars($bill['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="edit.php?id=<?php echo $bill['bill_id']; ?>"
                                                class="btn btn-sm btn-info me-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete(<?php echo $bill['bill_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
                                    href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&building_id=<?php echo $building_id; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1&status=' . $status . '&month=' . $month . '&year=' . $year . '&building_id=' . $building_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&order=' . $order . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }

                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                                    echo '<a class="page-link" href="?page=' . $i . '&status=' . $status . '&month=' . $month . '&year=' . $year . '&building_id=' . $building_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&order=' . $order . '">' . $i . '</a>';
                                    echo '</li>';
                                }

                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&status=' . $status . '&month=' . $month . '&year=' . $year . '&building_id=' . $building_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&order=' . $order . '">' . $total_pages . '</a></li>';
                                }
                                ?>

                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&building_id=<?php echo $building_id; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
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
                <p class="mb-0">คุณต้องการลบรายการค่าใช้จ่ายนี้ใช่หรือไม่?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form action="delete.php" method="POST" class="d-inline">
                    <input type="hidden" name="bill_id" id="deleteBillId">
                    <button type="submit" class="btn btn-danger">ลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(billId) {
    document.getElementById('deleteBillId').value = billId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>