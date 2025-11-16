<?php
$pageTitle = "จัดการข้อมูลผู้ใช้";
require_once __DIR__ . '/../auth_check.php';

// การค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_time';
$order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';

// ตรวจสอบ sort field ที่อนุญาต
$allowed_sort_fields = ['username', 'full_name', 'email', 'role', 'created_time'];
if (!in_array($sort, $allowed_sort_fields)) {
    $sort = 'created_time';
}

// ตรวจสอบ order ที่อนุญาต
if (!in_array($order, ['ASC', 'DESC'])) {
    $order = 'DESC';
}

// สร้าง WHERE clause
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(username LIKE :search1 OR full_name LIKE :search2 OR email LIKE :search3 OR phone_number LIKE :search4)";
    $search_param = "%{$search}%";
    $params[':search1'] = $search_param;
    $params[':search2'] = $search_param;
    $params[':search3'] = $search_param;
    $params[':search4'] = $search_param;
}

if (!empty($role_filter)) {
    $where_clauses[] = "role = :role";
    $params[':role'] = $role_filter;
}

$where_clause = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// การแบ่งหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// ดึงสถิติผู้ใช้
$stats = Database::getInstance()->fetch("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'ผู้ดูแลระบบ' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'นักศึกษา' THEN 1 ELSE 0 END) as student_count,
        SUM(CASE WHEN room_id IS NOT NULL THEN 1 ELSE 0 END) as room_assigned_count
    FROM users
");

// ดึงจำนวนรายการทั้งหมด
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
$total_items = Database::getInstance()->fetch($count_sql, $params)['total'];
$total_pages = ceil($total_items / $per_page);

// ดึงข้อมูลผู้ใช้
$sql = "
    SELECT 
        u.*,
        r.room_number,
        b.building_name,
        (SELECT COUNT(*) FROM repair_requests rr WHERE rr.user_id = u.user_id) as repair_count
    FROM users u
    LEFT JOIN rooms r ON u.room_id = r.room_id
    LEFT JOIN buildings b ON r.building_id = b.building_id
    $where_clause
    ORDER BY $sort $order
    LIMIT $per_page OFFSET $offset
";
$users = Database::getInstance()->fetchAll($sql, $params);

// สร้าง URL สำหรับการเรียงลำดับ
function getSortUrl($field)
{
    global $sort, $order, $search, $role_filter;
    $new_order = ($sort === $field && $order === 'ASC') ? 'DESC' : 'ASC';
    $params = [
        'sort' => $field,
        'order' => strtolower($new_order)
    ];
    if (!empty($search)) $params['search'] = $search;
    if (!empty($role_filter)) $params['role'] = $role_filter;
    return '?' . http_build_query($params);
}

// สร้าง icon สำหรับการเรียงลำดับ
function getSortIcon($field)
{
    global $sort, $order;
    if ($sort !== $field) {
        return '<i class="fas fa-sort text-muted ms-1"></i>';
    }
    return $order === 'ASC'
        ? '<i class="fas fa-sort-up ms-1"></i>'
        : '<i class="fas fa-sort-down ms-1"></i>';
}

// กำหนด current_page สำหรับ sidebar
$current_page = 'users';
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
                <!-- หัวข้อหน้า -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">จัดการข้อมูลผู้ใช้</h2>
                        <p class="text-muted">จัดการข้อมูลผู้ใช้ทั้งหมดในระบบ</p>
                    </div>
                    <div>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>เพิ่มผู้ใช้
                        </a>
                    </div>
                </div>

                <!-- สถิติผู้ใช้ -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 bg-primary bg-opacity-10">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-primary">ผู้ใช้ทั้งหมด</h6>
                                <h3 class="card-title mb-0"><?php echo number_format($stats['total_users']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-success bg-opacity-10">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-success">ผู้ดูแลระบบ</h6>
                                <h3 class="card-title mb-0"><?php echo number_format($stats['admin_count']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-info bg-opacity-10">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-info">นักศึกษา</h6>
                                <h3 class="card-title mb-0"><?php echo number_format($stats['student_count']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-warning bg-opacity-10">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-warning">มีห้องพักแล้ว</h6>
                                <h3 class="card-title mb-0"><?php echo number_format($stats['room_assigned_count']); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ค้นหาและกรอง -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" name="search"
                                        value="<?php echo htmlspecialchars($search); ?>"
                                        placeholder="ค้นหาชื่อ, อีเมล, เบอร์โทร...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="role">
                                    <option value="">ทุกบทบาท</option>
                                    <option value="ผู้ดูแลระบบ"
                                        <?php echo $role_filter === 'ผู้ดูแลระบบ' ? 'selected' : ''; ?>>ผู้ดูแลระบบ
                                    </option>
                                    <option value="นักศึกษา"
                                        <?php echo $role_filter === 'นักศึกษา' ? 'selected' : ''; ?>>นักศึกษา</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>ค้นหา
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ตารางผู้ใช้ -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="<?php echo getSortUrl('username'); ?>"
                                                class="text-decoration-none text-dark">
                                                ชื่อผู้ใช้ <?php echo getSortIcon('username'); ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?php echo getSortUrl('full_name'); ?>"
                                                class="text-decoration-none text-dark">
                                                ชื่อ-นามสกุล <?php echo getSortIcon('full_name'); ?>
                                            </a>
                                        </th>
                                        <th>อีเมล</th>
                                        <th>เบอร์โทร</th>
                                        <th>
                                            <a href="<?php echo getSortUrl('role'); ?>"
                                                class="text-decoration-none text-dark">
                                                บทบาท <?php echo getSortIcon('role'); ?>
                                            </a>
                                        </th>
                                        <th>ห้องพัก</th>
                                        <th>การแจ้งซ่อม</th>
                                        <th>
                                            <a href="<?php echo getSortUrl('created_time'); ?>"
                                                class="text-decoration-none text-dark">
                                                วันที่สร้าง <?php echo getSortIcon('created_time'); ?>
                                            </a>
                                        </th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-search fa-2x mb-3"></i>
                                                <p class="mb-0">ไม่พบข้อมูลผู้ใช้</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo $user['role'] === 'ผู้ดูแลระบบ' ? 'primary' : 'info'; ?>">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['room_number']): ?>
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars($user['building_name'] . ' ห้อง ' . $user['room_number']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">ไม่มีห้องพัก</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo $user['repair_count'] > 0 ? 'warning' : 'secondary'; ?>">
                                                <?php echo number_format($user['repair_count']); ?> ครั้ง
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_time'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit.php?id=<?php echo $user['user_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDelete(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&sort=<?php echo $sort; ?>&order=<?php echo strtolower($order); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&sort=<?php echo $sort; ?>&order=<?php echo strtolower($order); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&sort=<?php echo $sort; ?>&order=<?php echo strtolower($order); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
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
                <p>คุณต้องการลบบัญชีผู้ใช้ <span id="deleteUsername" class="fw-bold"></span> ใช่หรือไม่?</p>
                <p class="text-danger mt-2 mb-0"><small>* การลบบัญชีผู้ใช้จะไม่สามารถกู้คืนได้</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form action="delete.php" method="POST" class="d-inline">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">ลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(userId, username) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>