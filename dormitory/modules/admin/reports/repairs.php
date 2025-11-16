<?php
require_once __DIR__ . '/../../../includes/header.php';

// ตรวจสอบสิทธิ์การเข้าถึง
if (!Auth::hasPermission('admin')) {
    header('Location: ' . Config::$baseUrl . '/modules/users/login.php');
    exit;
}

// รับพารามิเตอร์สำหรับกรองข้อมูล
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$building_id = $_GET['building_id'] ?? null;
$request_type = $_GET['request_type'] ?? null;

// ดึงรายการอาคาร
$buildings = Database::getInstance()->fetchAll("
    SELECT b.*, 
           COUNT(DISTINCT r.request_id) as total_requests
    FROM buildings b
    LEFT JOIN repair_locations l ON b.building_id = l.building_id
    LEFT JOIN repair_requests r ON l.location_id = r.location_id
    GROUP BY b.building_id
    ORDER BY b.name
");

// ดึงข้อมูลสถิติการซ่อมบำรุง
$repair_stats = Database::getInstance()->fetchAll("
    SELECT 
        b.name as building_name,
        c.name as category_name,
        COUNT(DISTINCT r.request_id) as total_requests,
        COUNT(DISTINCT CASE WHEN r.status = 'pending' THEN r.request_id END) as pending_requests,
        COUNT(DISTINCT CASE WHEN r.status = 'in_progress' THEN r.request_id END) as in_progress_requests,
        COUNT(DISTINCT CASE WHEN r.status = 'completed' THEN r.request_id END) as completed_requests,
        COUNT(DISTINCT CASE WHEN r.status = 'cancelled' THEN r.request_id END) as cancelled_requests,
        AVG(CASE 
            WHEN r.status = 'completed' 
            THEN TIMESTAMPDIFF(HOUR, r.created_at, r.updated_at)
            ELSE NULL 
        END) as avg_completion_time
    FROM repair_requests r
    LEFT JOIN repair_locations l ON r.location_id = l.location_id
    LEFT JOIN buildings b ON l.building_id = b.building_id
    LEFT JOIN repair_items i ON r.item_id = i.item_id
    LEFT JOIN repair_categories c ON i.category_id = c.category_id
    WHERE YEAR(r.created_at) = ? AND MONTH(r.created_at) = ?
    " . ($building_id ? "AND b.building_id = ?" : "") . "
    " . ($request_type ? "AND r.request_type = ?" : "") . "
    GROUP BY b.building_id, c.category_id
    ORDER BY b.name, c.name
", array_filter([$year, $month, $building_id, $request_type]));

// ดึงข้อมูลแนวโน้มการแจ้งซ่อมรายเดือน
$monthly_trends = Database::getInstance()->fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_requests,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_requests,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_requests,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_requests
    FROM repair_requests
    WHERE YEAR(created_at) = ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
", [$year]);

// คำนวณสถิติรวม
$total_stats = [
    'total_requests' => 0,
    'pending_requests' => 0,
    'in_progress_requests' => 0,
    'completed_requests' => 0,
    'cancelled_requests' => 0,
    'avg_completion_time' => 0
];

$total_completion_time = 0;
$completed_count = 0;

foreach ($repair_stats as $stat) {
    $total_stats['total_requests'] += $stat['total_requests'];
    $total_stats['pending_requests'] += $stat['pending_requests'];
    $total_stats['in_progress_requests'] += $stat['in_progress_requests'];
    $total_stats['completed_requests'] += $stat['completed_requests'];
    $total_stats['cancelled_requests'] += $stat['cancelled_requests'];
    
    if ($stat['avg_completion_time'] !== null) {
        $total_completion_time += $stat['avg_completion_time'];
        $completed_count++;
    }
}

$total_stats['avg_completion_time'] = $completed_count > 0 
    ? round($total_completion_time / $completed_count, 1) 
    : 0;
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>รายงานการซ่อมบำรุง</h2>
            <div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ฟอร์มกรองข้อมูล -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label for="year" class="form-label">ปี</label>
                <select class="form-select" id="year" name="year">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo $y + 543; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="month" class="form-label">เดือน</label>
                <select class="form-select" id="month" name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="building_id" class="form-label">อาคาร</label>
                <select class="form-select" id="building_id" name="building_id">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($buildings as $building): ?>
                        <option value="<?php echo $building['building_id']; ?>" 
                                <?php echo $building_id == $building['building_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($building['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="request_type" class="form-label">ประเภทคำขอ</label>
                <select class="form-select" id="request_type" name="request_type">
                    <option value="">ทั้งหมด</option>
                    <option value="student" <?php echo $request_type == 'student' ? 'selected' : ''; ?>>นักศึกษา</option>
                    <option value="public" <?php echo $request_type == 'public' ? 'selected' : ''; ?>>สาธารณะ</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
            </div>
        </form>
    </div>
</div>

<!-- สถิติรวม -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">คำขอซ่อมทั้งหมด</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['total_requests']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">รอดำเนินการ</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['pending_requests']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">กำลังดำเนินการ</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['in_progress_requests']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">เสร็จสิ้น</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['completed_requests']); ?></h2>
                <small>เฉลี่ย <?php echo $total_stats['avg_completion_time']; ?> ชั่วโมง</small>
            </div>
        </div>
    </div>
</div>

<!-- กราฟแนวโน้มการแจ้งซ่อม -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">แนวโน้มการแจ้งซ่อมรายเดือน</h5>
    </div>
    <div class="card-body">
        <canvas id="repairTrendChart"></canvas>
    </div>
</div>

<!-- ตารางสถิติรายอาคารและหมวดหมู่ -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">สถิติรายอาคารและหมวดหมู่</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>อาคาร</th>
                        <th>หมวดหมู่</th>
                        <th>ทั้งหมด</th>
                        <th>รอดำเนินการ</th>
                        <th>กำลังดำเนินการ</th>
                        <th>เสร็จสิ้น</th>
                        <th>ยกเลิก</th>
                        <th>เฉลี่ยเวลาแก้ไข (ชม.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repair_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['building_name']); ?></td>
                            <td><?php echo htmlspecialchars($stat['category_name']); ?></td>
                            <td><?php echo number_format($stat['total_requests']); ?></td>
                            <td><?php echo number_format($stat['pending_requests']); ?></td>
                            <td><?php echo number_format($stat['in_progress_requests']); ?></td>
                            <td><?php echo number_format($stat['completed_requests']); ?></td>
                            <td><?php echo number_format($stat['cancelled_requests']); ?></td>
                            <td><?php echo $stat['avg_completion_time'] ? round($stat['avg_completion_time'], 1) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('repairTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($item) {
                return date('M Y', strtotime($item['month'] . '-01'));
            }, $monthly_trends)); ?>,
            datasets: [{
                label: 'รอดำเนินการ',
                data: <?php echo json_encode(array_column($monthly_trends, 'pending_requests')); ?>,
                borderColor: 'rgb(255, 205, 86)',
                tension: 0.1
            }, {
                label: 'กำลังดำเนินการ',
                data: <?php echo json_encode(array_column($monthly_trends, 'in_progress_requests')); ?>,
                borderColor: 'rgb(54, 162, 235)',
                tension: 0.1
            }, {
                label: 'เสร็จสิ้น',
                data: <?php echo json_encode(array_column($monthly_trends, 'completed_requests')); ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'ยกเลิก',
                data: <?php echo json_encode(array_column($monthly_trends, 'cancelled_requests')); ?>,
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
