<?php
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$request_id = '';
$repair = null;
$history = null;
$error = null;

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query conditions
$conditions = [];
$params = [];

if ($status) {
    $conditions[] = "r.status = ?";
    $params[] = $status;
}

if ($date_from) {
    $conditions[] = "DATE(r.created_time) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $conditions[] = "DATE(r.created_time) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get summary statistics
$summary = $db->fetchAll("
    SELECT 
        r.status,
        COUNT(*) as count,
        MIN(r.created_time) as earliest_date,
        MAX(r.created_time) as latest_date
    FROM repair_requests r
    GROUP BY r.status
");

$status_counts = [
    'รอดำเนินการ' => 0,
    'กำลังดำเนินการ' => 0,
    'เสร็จสิ้น' => 0,
    'ยกเลิก' => 0
];

foreach ($summary as $stat) {
    $status_counts[$stat['status']] = $stat['count'];
}

// Get category summary
$category_summary = $db->fetchAll("
    SELECT 
        r.title as category_name,
        COUNT(*) as count
    FROM repair_requests r
    GROUP BY r.title
    ORDER BY count DESC
    LIMIT 5
");

// Get total count for pagination
$count_result = $db->fetch("
    SELECT COUNT(*) as total
    FROM repair_requests r 
    LEFT JOIN public_repair_contacts c ON r.contact_id = c.contact_id
    LEFT JOIN users u ON r.user_id = u.user_id
    $where_clause
", $params);

$total_count = $count_result['total'];

$total_pages = ceil($total_count / $per_page);

// Get repair requests with pagination
$repairs = $db->fetchAll("
    SELECT r.*, 
           COALESCE(c.full_name, u.full_name) as contact_name,
           COALESCE(c.phone_number, u.phone_number) as contact_phone,
           COALESCE(c.email, u.email) as contact_email,
           l.location_name_th
    FROM repair_requests r
    LEFT JOIN public_repair_contacts c ON r.contact_id = c.contact_id
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN repair_locations l ON r.location_id = l.location_id
    $where_clause
    ORDER BY r.created_time DESC
    LIMIT ? OFFSET ?
", array_merge($params, [$per_page, $offset]));

// Process tracking code search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    try {
        $request_id = filter_input(INPUT_POST, 'request_id', FILTER_SANITIZE_STRING);
        if (empty($request_id)) {
            throw new Exception('กรุณากรอกรหัสติดตามการซ่อม');
        }

        // Search for specific repair request
        $repair = $db->fetch("
            SELECT r.*, 
                   COALESCE(c.full_name, u.full_name) as contact_name,
                   COALESCE(c.phone_number, u.phone_number) as contact_phone,
                   COALESCE(c.email, u.email) as contact_email,
                   l.location_name_th,
                   cat.category_name
            FROM repair_requests r
            LEFT JOIN public_repair_contacts c ON r.contact_id = c.contact_id
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN repair_locations l ON r.location_id = l.location_id
            LEFT JOIN repair_categories cat ON r.category_id = cat.category_id
            WHERE r.request_id = ?
        ", [$request_id]);

        if (!$repair) {
            throw new Exception('ไม่พบข้อมูลการซ่อมจากรหัสติดตามที่ระบุ');
        }

        // Get repair history
        $history = $db->fetchAll("
            SELECT h.*, u.full_name as admin_name
            FROM repair_history h
            LEFT JOIN users u ON h.admin_id = u.user_id
            WHERE h.request_id = ?
            ORDER BY h.created_time DESC
        ", [$repair['request_id']]);

        // Get repair images
        $images = $db->fetchAll("
            SELECT *
            FROM repair_images
            WHERE request_id = ?
            ORDER BY created_time ASC
        ", [$repair['request_id']]);

        $repair['images'] = $images;
        $repair['history'] = $history;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container py-2">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0"><i class="fas fa-search me-2 text-primary"></i>ติดตามสถานะการซ่อม</h2>
                <a href="<?php echo Config::$baseUrl; ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
                </a>
            </div>
        </div>
    </div>
    <!-- แสดงรายการแจ้งซ่อมทั้งหมด -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>รายการแจ้งซ่อมทั้งหมด</h5>
        </div>
        <div class="card-body">
            <!-- ฟิลเตอร์ -->
            <form method="get" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">สถานะ</label>
                    <select name="status" class="form-select">
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
                <div class="col-md-3">
                    <label class="form-label">วันที่เริ่มต้น</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">วันที่สิ้นสุด</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>กรอง
                        </button>
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i>รีเซ็ต
                        </a>
                    </div>
                </div>
            </form>

            <!-- ตารางแสดงรายการ -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>รหัสติดตาม</th>
                            <th>วันที่แจ้ง</th>
                            <th>ประเภท</th>
                            <th>สถานที่</th>
                            <th>ผู้แจ้ง</th>
                            <th>สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repairs as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['request_id']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($item['created_time'])); ?></td>
                                <td><?php echo htmlspecialchars($item['category_name'] ?? $item['title']); ?></td>
                                <td><?php echo htmlspecialchars($item['location_name_th'] ?? $item['location']); ?></td>
                                <td><?php echo htmlspecialchars($item['contact_name']); ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'รอดำเนินการ' => 'warning',
                                        'กำลังดำเนินการ' => 'info',
                                        'เสร็จสิ้น' => 'success',
                                        'ยกเลิก' => 'danger'
                                    ];
                                    $badge_class = $status_class[$item['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="repair_detail.php?id=<?php echo htmlspecialchars($item['request_id']); ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-search me-1"></i>ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($repairs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle me-2"></i>ไม่พบข้อมูลการแจ้งซ่อม
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($repair): ?>
        <!-- แสดงรายละเอียดการซ่อมที่ค้นหา -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>รายละเอียดการซ่อม</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">ข้อมูลการแจ้งซ่อม</h6>
                        <p><strong>รหัสติดตาม:</strong> <?php echo htmlspecialchars($repair['request_id']); ?></p>
                        <p><strong>วันที่แจ้ง:</strong> <?php echo date('d/m/Y H:i', strtotime($repair['created_time'])); ?>
                        </p>
                        <p><strong>ประเภท:</strong>
                            <?php echo htmlspecialchars($repair['category_name'] ?? $repair['title']); ?></p>
                        <p><strong>สถานที่:</strong>
                            <?php echo htmlspecialchars($repair['location_name_th'] ?? $repair['location']); ?></p>
                        <p><strong>รายละเอียด:</strong> <?php echo nl2br(htmlspecialchars($repair['description'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">ข้อมูลผู้แจ้ง</h6>
                        <p><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($repair['contact_name']); ?></p>
                        <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($repair['contact_phone']); ?></p>
                        <?php if (!empty($repair['contact_email'])): ?>
                            <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($repair['contact_email']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($repair['images'])): ?>
                    <h6 class="border-bottom pb-2 mb-3 mt-4">รูปภาพประกอบ</h6>
                    <div class="row">
                        <?php foreach ($repair['images'] as $image): ?>
                            <div class="col-md-3 mb-3">
                                <a href="<?php echo Config::$baseUrl . '/' . $image['image_path']; ?>" target="_blank">
                                    <img src="<?php echo Config::$baseUrl . '/' . $image['image_path']; ?>" alt="รูปภาพประกอบ"
                                        class="img-fluid rounded">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($repair['history'])): ?>
                    <h6 class="border-bottom pb-2 mb-3 mt-4">ประวัติการดำเนินการ</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>วันที่/เวลา</th>
                                    <th>การดำเนินการ</th>
                                    <th>ผู้ดำเนินการ</th>
                                    <th>หมายเหตุ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($repair['history'] as $history): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($history['created_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($history['action']); ?></td>
                                        <td><?php echo htmlspecialchars($history['admin_name'] ?? '-'); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($history['notes'] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>