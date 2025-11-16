<?php
$pageTitle = "จัดการการแจ้งซ่อม";
require_once __DIR__ . '/../../includes/header.php';

// ตรวจสอบสิทธิ์การเข้าถึง
if (!Auth::isAdmin()) {
    $_SESSION['error'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    header('Location: ' . Config::$baseUrl . '/login.php');
    exit;
}

// จัดการการกรองข้อมูล
$where_conditions = [];
$params = [];

// กรองตามสถานะ
$status = isset($_GET['status']) ? $_GET['status'] : '';
if ($status !== '') {
    $where_conditions[] = "r.status = :status";
    $params[':status'] = $status;
}

// กรองตามประเภท
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
if ($category_id > 0) {
    $where_conditions[] = "i.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

// กรองตามอาคาร
$building_id = isset($_GET['building_id']) ? (int)$_GET['building_id'] : 0;
if ($building_id > 0) {
    $where_conditions[] = "rm.building_id = :building_id";
    $params[':building_id'] = $building_id;
}

// การค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $where_conditions[] = "(r.title LIKE :search OR r.description LIKE :search OR u.full_name LIKE :search OR rm.room_number LIKE :search)";
    $params[':search'] = "%{$search}%";
}

// สร้าง WHERE clause
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// จัดการการเรียงลำดับ
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_time';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$allowed_sort_fields = ['created_time', 'status', 'title', 'room_number'];
$sort = in_array($sort, $allowed_sort_fields) ? $sort : 'created_time';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// การแบ่งหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// ดึงจำนวนรายการทั้งหมด
$count_sql = "
    SELECT COUNT(*) as total 
    FROM repair_requests r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN rooms rm ON r.room_id = rm.room_id
    LEFT JOIN repair_items i ON r.item_id = i.item_id
    LEFT JOIN repair_categories c ON i.category_id = c.category_id
    LEFT JOIN public_repair_contacts pc ON r.contact_id = pc.contact_id
    $where_clause
";
$total_items = Database::getInstance()->fetch($count_sql, $params)['total'];
$total_pages = ceil($total_items / $per_page);

// ดึงข้อมูลการแจ้งซ่อม
$sql = "
    SELECT 
        r.*,
        u.full_name as user_name,
        rm.room_number,
        b.building_name,
        i.item_name,
        c.category_name,
        pc.full_name as contact_name,
        (SELECT COUNT(*) FROM repair_images WHERE request_id = r.request_id) as image_count,
        (SELECT MAX(created_time) FROM repair_history WHERE request_id = r.request_id) as last_updated
    FROM repair_requests r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN rooms rm ON r.room_id = rm.room_id
    LEFT JOIN buildings b ON rm.building_id = b.building_id
    LEFT JOIN repair_items i ON r.item_id = i.item_id
    LEFT JOIN repair_categories c ON i.category_id = c.category_id
    LEFT JOIN public_repair_contacts pc ON r.contact_id = pc.contact_id
    $where_clause
    ORDER BY $sort $order
    LIMIT $per_page OFFSET $offset
";
$repairs = Database::getInstance()->fetchAll($sql, $params);

// ดึงข้อมูลสำหรับตัวกรอง
$categories = Database::getInstance()->fetchAll("SELECT * FROM repair_categories ORDER BY category_name");
$buildings = Database::getInstance()->fetchAll("SELECT * FROM buildings ORDER BY building_name");

// กำหนด current_page สำหรับ sidebar
$current_page = 'repairs';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="px-4 py-3">
                <!-- หัวข้อและปุ่มกรอง -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">จัดการการแจ้งซ่อม</h2>
                        <p class="text-muted">จัดการรายการแจ้งซ่อมทั้งหมด</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>พิมพ์รายงาน
                        </button>
                    </div>
                </div>

                <!-- ฟิลเตอร์และการค้นหา -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <!-- ค้นหา -->
                            <div class="col-md-4">
                                <label class="form-label">ค้นหา</label>
                                <input type="text" class="form-control" name="search"
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="ค้นหาจากชื่อเรื่อง, รายละเอียด, ผู้แจ้ง...">
                            </div>

                            <!-- กรองตามสถานะ -->
                            <div class="col-md-2">
                                <label class="form-label">สถานะ</label>
                                <select class="form-select" name="status">
                                    <option value="">ทั้งหมด</option>
                                    <option value="รอดำเนินการ" <?php echo $status === 'รอดำเนินการ' ? 'selected' : ''; ?>>
                                        รอดำเนินการ</option>
                                    <option value="กำลังดำเนินการ" <?php echo $status === 'กำลังดำเนินการ' ? 'selected' : ''; ?>>
                                        กำลังดำเนินการ</option>
                                    <option value="เสร็จสิ้น" <?php echo $status === 'เสร็จสิ้น' ? 'selected' : ''; ?>>เสร็จสิ้น
                                    </option>
                                    <option value="ยกเลิก" <?php echo $status === 'ยกเลิก' ? 'selected' : ''; ?>>ยกเลิก</option>
                                </select>
                            </div>

                            <!-- กรองตามประเภท -->
                            <div class="col-md-2">
                                <label class="form-label">ประเภท</label>
                                <select class="form-select" name="category_id">
                                    <option value="">ทั้งหมด</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>"
                                            <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
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
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>ค้นหา
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
                                        <th>วันที่แจ้ง</th>
                                        <th>รายการ</th>
                                        <th>สถานที่</th>
                                        <th>ผู้แจ้ง</th>
                                        <th>สถานะ</th>
                                        <th>การอัปเดตล่าสุด</th>
                                        <th class="text-end">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($repairs)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <p class="text-muted mb-0">ไม่พบรายการแจ้งซ่อม</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($repairs as $repair): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $date = new DateTime($repair['created_time']);
                                                    echo $date->format('d/m/Y H:i');
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($repair['image_count'] > 0): ?>
                                                            <i class="fas fa-image text-info me-2"
                                                                title="มีรูปภาพแนบ <?php echo $repair['image_count']; ?> รูป"></i>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($repair['title']); ?></div>
                                                            <small class="text-muted">
                                                                <?php
                                                                if ($repair['item_name']) {
                                                                    echo htmlspecialchars($repair['item_name']);
                                                                    if ($repair['category_name']) {
                                                                        echo ' (' . htmlspecialchars($repair['category_name']) . ')';
                                                                    }
                                                                }
                                                                ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($repair['room_number']): ?>
                                                        <?php echo htmlspecialchars($repair['building_name'] . ' ห้อง ' . $repair['room_number']); ?>
                                                    <?php else: ?>
                                                        <?php echo 'พื้นที่ส่วนกลาง'; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($repair['user_name']) {
                                                        echo htmlspecialchars($repair['user_name']);
                                                    } elseif ($repair['contact_name']) {
                                                        echo htmlspecialchars($repair['contact_name']) . ' (บุคคลภายนอก)';
                                                    } else {
                                                        echo 'ไม่ระบุ';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'รอดำเนินการ' => 'warning',
                                                        'กำลังดำเนินการ' => 'info',
                                                        'เสร็จสิ้น' => 'success',
                                                        'ยกเลิก' => 'danger'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class[$repair['status']]; ?>">
                                                        <?php echo htmlspecialchars($repair['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($repair['last_updated']) {
                                                        $last_updated = new DateTime($repair['last_updated']);
                                                        echo $last_updated->format('d/m/Y H:i');
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="repair_detail.php?id=<?php echo $repair['request_id']; ?>"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
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
                                            href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status; ?>&category_id=<?php echo $category_id; ?>&building_id=<?php echo $building_id; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1&status=' . $status . '&category_id=' . $category_id . '&building_id=' . $building_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&order=' . $order . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }

                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                                    echo '<a class="page-link" href="?page=' . $i . '&status=' . $status . '&category_id=' . $category_id . '&building_id=' . $building_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&order=' . $order . '">' . $i . '</a>';
                                    echo '</li>';
                                }

                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&status=' . $status . '&category_id=' . $category_id . '&building_id=' . $building_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&order=' . $order . '">' . $total_pages . '</a></li>';
                                }
                                ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status; ?>&category_id=<?php echo $category_id; ?>&building_id=<?php echo $building_id; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>