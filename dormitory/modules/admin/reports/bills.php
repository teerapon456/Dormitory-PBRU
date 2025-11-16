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
$payment_status = $_GET['payment_status'] ?? null;

// ดึงรายการอาคาร
$buildings = Database::getInstance()->fetchAll("
    SELECT b.*, 
           COUNT(DISTINCT b.bill_id) as total_bills
    FROM buildings b
    LEFT JOIN rooms r ON b.building_id = r.building_id
    LEFT JOIN utility_bills b ON r.room_id = b.room_id
    GROUP BY b.building_id
    ORDER BY b.name
");

// ดึงข้อมูลสถิติบิล
$bill_stats = Database::getInstance()->fetchAll("
    SELECT 
        b.name as building_name,
        COUNT(DISTINCT ub.bill_id) as total_bills,
        COUNT(DISTINCT CASE WHEN ub.payment_status = 'pending' THEN ub.bill_id END) as pending_bills,
        COUNT(DISTINCT CASE WHEN ub.payment_status = 'paid' THEN ub.bill_id END) as paid_bills,
        COUNT(DISTINCT CASE WHEN ub.payment_status = 'overdue' THEN ub.bill_id END) as overdue_bills,
        SUM(CASE WHEN ub.payment_status = 'paid' THEN ub.total_amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN ub.payment_status = 'pending' THEN ub.total_amount ELSE 0 END) as total_pending,
        SUM(CASE WHEN ub.payment_status = 'overdue' THEN ub.total_amount ELSE 0 END) as total_overdue,
        AVG(CASE 
            WHEN ub.payment_status = 'paid' 
            THEN TIMESTAMPDIFF(DAY, ub.due_date, ub.payment_date)
            ELSE NULL 
        END) as avg_payment_days
    FROM utility_bills ub
    LEFT JOIN rooms r ON ub.room_id = r.room_id
    LEFT JOIN buildings b ON r.building_id = b.building_id
    WHERE YEAR(ub.bill_date) = ? AND MONTH(ub.bill_date) = ?
    " . ($building_id ? "AND b.building_id = ?" : "") . "
    " . ($payment_status ? "AND ub.payment_status = ?" : "") . "
    GROUP BY b.building_id
    ORDER BY b.name
", array_filter([$year, $month, $building_id, $payment_status]));

// ดึงข้อมูลแนวโน้มการชำระรายเดือน
$monthly_trends = Database::getInstance()->fetchAll("
    SELECT 
        DATE_FORMAT(bill_date, '%Y-%m') as month,
        COUNT(*) as total_bills,
        COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_bills,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_bills,
        COUNT(CASE WHEN payment_status = 'overdue' THEN 1 END) as overdue_bills,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) as total_pending,
        SUM(CASE WHEN payment_status = 'overdue' THEN total_amount ELSE 0 END) as total_overdue
    FROM utility_bills
    WHERE YEAR(bill_date) = ?
    GROUP BY DATE_FORMAT(bill_date, '%Y-%m')
    ORDER BY month
", [$year]);

// คำนวณสถิติรวม
$total_stats = [
    'total_bills' => 0,
    'pending_bills' => 0,
    'paid_bills' => 0,
    'overdue_bills' => 0,
    'total_paid' => 0,
    'total_pending' => 0,
    'total_overdue' => 0,
    'avg_payment_days' => 0
];

$total_payment_days = 0;
$paid_count = 0;

foreach ($bill_stats as $stat) {
    $total_stats['total_bills'] += $stat['total_bills'];
    $total_stats['pending_bills'] += $stat['pending_bills'];
    $total_stats['paid_bills'] += $stat['paid_bills'];
    $total_stats['overdue_bills'] += $stat['overdue_bills'];
    $total_stats['total_paid'] += $stat['total_paid'];
    $total_stats['total_pending'] += $stat['total_pending'];
    $total_stats['total_overdue'] += $stat['total_overdue'];

    if ($stat['avg_payment_days'] !== null) {
        $total_payment_days += $stat['avg_payment_days'];
        $paid_count++;
    }
}

$total_stats['avg_payment_days'] = $paid_count > 0
    ? round($total_payment_days / $paid_count, 1)
    : 0;
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>รายงานบิลค่าใช้จ่าย</h2>
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
                <label for="payment_status" class="form-label">สถานะการชำระ</label>
                <select class="form-select" id="payment_status" name="payment_status">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" <?php echo $payment_status == 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                    <option value="paid" <?php echo $payment_status == 'paid' ? 'selected' : ''; ?>>ชำระแล้ว</option>
                    <option value="overdue" <?php echo $payment_status == 'overdue' ? 'selected' : ''; ?>>เกินกำหนด</option>
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
                <h5 class="card-title">บิลทั้งหมด</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['total_bills']); ?></h2>
                <small>มูลค่ารวม <?php echo number_format($total_stats['total_paid'] + $total_stats['total_pending'] + $total_stats['total_overdue'], 2); ?> บาท</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">รอดำเนินการ</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['pending_bills']); ?></h2>
                <small>มูลค่า <?php echo number_format($total_stats['total_pending'], 2); ?> บาท</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">เกินกำหนด</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['overdue_bills']); ?></h2>
                <small>มูลค่า <?php echo number_format($total_stats['total_overdue'], 2); ?> บาท</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">ชำระแล้ว</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['paid_bills']); ?></h2>
                <small>มูลค่า <?php echo number_format($total_stats['total_paid'], 2); ?> บาท</small>
                <br>
                <small>เฉลี่ย <?php echo $total_stats['avg_payment_days']; ?> วัน</small>
            </div>
        </div>
    </div>
</div>

<!-- กราฟแนวโน้มการชำระ -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">แนวโน้มการชำระรายเดือน</h5>
    </div>
    <div class="card-body">
        <canvas id="billTrendChart"></canvas>
    </div>
</div>

<!-- ตารางสถิติรายอาคาร -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">สถิติรายอาคาร</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>อาคาร</th>
                        <th>บิลทั้งหมด</th>
                        <th>รอดำเนินการ</th>
                        <th>ชำระแล้ว</th>
                        <th>เกินกำหนด</th>
                        <th>มูลค่าชำระแล้ว</th>
                        <th>มูลค่ารอดำเนินการ</th>
                        <th>มูลค่าเกินกำหนด</th>
                        <th>เฉลี่ยวันชำระ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bill_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['building_name']); ?></td>
                            <td><?php echo number_format($stat['total_bills']); ?></td>
                            <td><?php echo number_format($stat['pending_bills']); ?></td>
                            <td><?php echo number_format($stat['paid_bills']); ?></td>
                            <td><?php echo number_format($stat['overdue_bills']); ?></td>
                            <td><?php echo number_format($stat['total_paid'], 2); ?></td>
                            <td><?php echo number_format($stat['total_pending'], 2); ?></td>
                            <td><?php echo number_format($stat['total_overdue'], 2); ?></td>
                            <td><?php echo $stat['avg_payment_days'] ? round($stat['avg_payment_days'], 1) : '-'; ?></td>
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
        const ctx = document.getElementById('billTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function ($item) {
                            return date('M Y', strtotime($item['month'] . '-01'));
                        }, $monthly_trends)); ?>,
                datasets: [{
                    label: 'รอดำเนินการ',
                    data: <?php echo json_encode(array_column($monthly_trends, 'pending_bills')); ?>,
                    borderColor: 'rgb(255, 205, 86)',
                    tension: 0.1
                }, {
                    label: 'ชำระแล้ว',
                    data: <?php echo json_encode(array_column($monthly_trends, 'paid_bills')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'เกินกำหนด',
                    data: <?php echo json_encode(array_column($monthly_trends, 'overdue_bills')); ?>,
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