<?php
require_once __DIR__ . '/../../../includes/header.php';

// ตรวจสอบสิทธิ์การเข้าถึง
if (!Auth::hasPermission('admin')) {
    header('Location: ' . Config::$baseUrl . '/modules/users/login.php');
    exit;
}

// รับพารามิเตอร์สำหรับกรองข้อมูล
$building_id = $_GET['building_id'] ?? null;
$floor = $_GET['floor'] ?? null;
$year = $_GET['year'] ?? date('Y');

// ดึงรายการอาคาร
$buildings = Database::getInstance()->fetchAll("
    SELECT b.*, 
           COUNT(DISTINCT r.room_id) as total_rooms,
           COUNT(DISTINCT CASE WHEN r.status = 'occupied' THEN r.room_id END) as occupied_rooms
    FROM buildings b
    LEFT JOIN rooms r ON b.building_id = r.building_id
    GROUP BY b.building_id
    ORDER BY b.name
");

// ดึงข้อมูลสถิติการเข้าพัก
$occupancy_stats = Database::getInstance()->fetchAll("
    SELECT 
        b.name as building_name,
        r.floor_number,
        COUNT(DISTINCT r.room_id) as total_rooms,
        COUNT(DISTINCT CASE WHEN r.status = 'occupied' THEN r.room_id END) as occupied_rooms,
        COUNT(DISTINCT s.student_id) as total_students,
        COUNT(DISTINCT CASE WHEN s.gender = 'male' THEN s.student_id END) as male_students,
        COUNT(DISTINCT CASE WHEN s.gender = 'female' THEN s.student_id END) as female_students
    FROM buildings b
    LEFT JOIN rooms r ON b.building_id = r.building_id
    LEFT JOIN students s ON r.room_id = s.room_id
    WHERE 1=1
    " . ($building_id ? "AND b.building_id = ?" : "") . "
    " . ($floor ? "AND r.floor_number = ?" : "") . "
    GROUP BY b.building_id, r.floor_number
    ORDER BY b.name, r.floor_number
", array_filter([$building_id, $floor]));

// ดึงข้อมูลแนวโน้มการเข้าพักรายเดือน
$monthly_trends = Database::getInstance()->fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(DISTINCT s.student_id) as total_students,
        COUNT(DISTINCT r.room_id) as occupied_rooms
    FROM students s
    JOIN rooms r ON s.room_id = r.room_id
    WHERE YEAR(created_at) = ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
", [$year]);

// คำนวณสถิติรวม
$total_stats = [
    'total_rooms' => 0,
    'occupied_rooms' => 0,
    'total_students' => 0,
    'male_students' => 0,
    'female_students' => 0
];

foreach ($occupancy_stats as $stat) {
    $total_stats['total_rooms'] += $stat['total_rooms'];
    $total_stats['occupied_rooms'] += $stat['occupied_rooms'];
    $total_stats['total_students'] += $stat['total_students'];
    $total_stats['male_students'] += $stat['male_students'];
    $total_stats['female_students'] += $stat['female_students'];
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>รายงานการเข้าพัก</h2>
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
            <div class="col-md-4">
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
                <label for="floor" class="form-label">ชั้น</label>
                <select class="form-select" id="floor" name="floor">
                    <option value="">ทั้งหมด</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $floor == $i ? 'selected' : ''; ?>>
                            ชั้น <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
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
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
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
                <h5 class="card-title">ห้องพักทั้งหมด</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['total_rooms']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">ห้องพักที่มีผู้เข้าพัก</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['occupied_rooms']); ?></h2>
                <small>
                    <?php echo $total_stats['total_rooms'] > 0
                        ? round(($total_stats['occupied_rooms'] / $total_stats['total_rooms']) * 100, 1)
                        : 0; ?>%
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">นักศึกษาทั้งหมด</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['total_students']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">ห้องพักว่าง</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['total_rooms'] - $total_stats['occupied_rooms']); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- ตารางสถิติรายอาคารและชั้น -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">สถิติรายอาคารและชั้น</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>อาคาร</th>
                        <th>ชั้น</th>
                        <th>ห้องทั้งหมด</th>
                        <th>ห้องที่มีผู้เข้าพัก</th>
                        <th>นักศึกษาทั้งหมด</th>
                        <th>นักศึกษาชาย</th>
                        <th>นักศึกษาหญิง</th>
                        <th>อัตราการเข้าพัก</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($occupancy_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['building_name']); ?></td>
                            <td><?php echo $stat['floor_number']; ?></td>
                            <td><?php echo number_format($stat['total_rooms']); ?></td>
                            <td><?php echo number_format($stat['occupied_rooms']); ?></td>
                            <td><?php echo number_format($stat['total_students']); ?></td>
                            <td><?php echo number_format($stat['male_students']); ?></td>
                            <td><?php echo number_format($stat['female_students']); ?></td>
                            <td>
                                <?php echo $stat['total_rooms'] > 0
                                    ? round(($stat['occupied_rooms'] / $stat['total_rooms']) * 100, 1)
                                    : 0; ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- กราฟแนวโน้มการเข้าพัก -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">แนวโน้มการเข้าพักรายเดือน</h5>
    </div>
    <div class="card-body">
        <canvas id="occupancyTrendChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('occupancyTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function ($item) {
                            return date('M Y', strtotime($item['month'] . '-01'));
                        }, $monthly_trends)); ?>,
                datasets: [{
                    label: 'จำนวนนักศึกษา',
                    data: <?php echo json_encode(array_column($monthly_trends, 'total_students')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'จำนวนห้องที่มีผู้เข้าพัก',
                    data: <?php echo json_encode(array_column($monthly_trends, 'occupied_rooms')); ?>,
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