<?php
$pageTitle = "จัดการข้อมูลห้องพัก";
require_once __DIR__ . '/../auth_check.php';

// การค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$building_filter = isset($_GET['building']) ? $_GET['building'] : '';

// สร้าง WHERE clause
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(r.room_number LIKE :search1 
        OR u.username LIKE :search2 
        OR u.full_name LIKE :search3)";
    $params[':search1'] = "%{$search}%";
    $params[':search2'] = "%{$search}%";
    $params[':search3'] = "%{$search}%";
}

if (!empty($building_filter)) {
    $where_clauses[] = "r.building_id = :building_id";
    $params[':building_id'] = $building_filter;
}

$where_clause = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// การแบ่งหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// ดึงจำนวนรายการทั้งหมด
$count_sql = "
    SELECT COUNT(*) as total 
    FROM rooms r
    LEFT JOIN users u ON r.room_id = u.room_id
    $where_clause
";
$total_items = Database::getInstance()->fetch($count_sql, $params)['total'];
$total_pages = ceil($total_items / $per_page);

// ดึงข้อมูลอาคารทั้งหมด
$buildings = Database::getInstance()->fetchAll("SELECT * FROM buildings ORDER BY building_name");

// ดึงข้อมูลห้องพัก
$sql = "
    SELECT 
        r.*,
        b.building_name,
        u.username,
        u.full_name,
        u.phone_number
    FROM rooms r
    LEFT JOIN buildings b ON r.building_id = b.building_id
    LEFT JOIN users u ON r.room_id = u.room_id
    $where_clause
    ORDER BY b.building_name, r.room_number
    LIMIT $per_page OFFSET $offset
";
$rooms = Database::getInstance()->fetchAll($sql, $params);
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <div class="col-12">
            <div class="px-4 py-3">
                <!-- หัวข้อและปุ่มเพิ่ม -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">จัดการข้อมูลห้องพัก</h2>
                        <p class="text-muted">จัดการข้อมูลห้องพักและผู้พักอาศัย</p>
                    </div>
                    <div>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>เพิ่มห้องพัก
                        </a>
                    </div>
                </div>

                <!-- ค้นหาและตัวกรอง -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">ค้นหา</label>
                                <input type="text" class="form-control" name="search"
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="ค้นหาจากเลขห้อง, รหัสนักศึกษา หรือชื่อนักศึกษา...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">อาคาร</label>
                                <select class="form-select" name="building">
                                    <option value="">ทั้งหมด</option>
                                    <?php foreach ($buildings as $building): ?>
                                    <option value="<?php echo $building['building_id']; ?>"
                                        <?php echo $building_filter == $building['building_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($building['building_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="d-flex gap-2 w-100">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="fas fa-search me-2"></i>ค้นหา
                                    </button>
                                    <a href="view.php" class="btn btn-secondary">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ตารางแสดงข้อมูล -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>เลขห้อง</th>
                                        <th>อาคาร</th>
                                        <th>รหัสนักศึกษา</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>เบอร์โทร</th>
                                        <th>สถานะ</th>
                                        <th class="text-center">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rooms)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">ไม่พบข้อมูลห้องพัก</div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($room['building_name']); ?></td>
                                        <td><?php echo $room['username'] ? htmlspecialchars($room['username']) : '<span class="text-muted">-</span>'; ?>
                                        </td>
                                        <td><?php echo $room['full_name'] ? htmlspecialchars($room['full_name']) : '<span class="text-muted">-</span>'; ?>
                                        </td>
                                        <td><?php echo $room['phone_number'] ? htmlspecialchars($room['phone_number']) : '<span class="text-muted">-</span>'; ?>
                                        </td>
                                        <td>
                                            <?php if ($room['username']): ?>
                                            <span class="badge bg-success">มีผู้พัก</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">ว่าง</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="edit.php?id=<?php echo $room['room_id']; ?>"
                                                class="btn btn-sm btn-info me-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete(<?php echo $room['room_id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- การแบ่งหน้า -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                แสดง <?php echo $offset + 1; ?> ถึง
                                <?php echo min($offset + $per_page, $total_items); ?>
                                จากทั้งหมด <?php echo $total_items; ?> รายการ
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination mb-0">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&building=<?php echo urlencode($building_filter); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>

                                    <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);

                                        if ($start_page > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&building=' . urlencode($building_filter) . '">1</a></li>';
                                            if ($start_page > 2) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                        }

                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                                            echo '<a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '&building=' . urlencode($building_filter) . '">' . $i . '</a>';
                                            echo '</li>';
                                        }

                                        if ($end_page < $total_pages) {
                                            if ($end_page < $total_pages - 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search) . '&building=' . urlencode($building_filter) . '">' . $total_pages . '</a></li>';
                                        }
                                        ?>

                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&building=<?php echo urlencode($building_filter); ?>">
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
                <p class="mb-0">คุณต้องการลบห้องพัก <span id="deleteRoomNumber" class="fw-bold"></span> ใช่หรือไม่?</p>
                <p class="text-danger mt-2 mb-0"><small>* การลบห้องพักจะไม่สามารถกู้คืนได้</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form action="delete.php" method="POST" class="d-inline">
                    <input type="hidden" name="room_id" id="deleteRoomId">
                    <button type="submit" class="btn btn-danger">ลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(roomId, roomNumber) {
    document.getElementById('deleteRoomId').value = roomId;
    document.getElementById('deleteRoomNumber').textContent = roomNumber;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>