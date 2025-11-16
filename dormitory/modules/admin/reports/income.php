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
$income_type = $_GET['income_type'] ?? null;

// ดึงรายการอาคาร
$buildings = Database::getInstance()->fetchAll("
    SELECT b.*, 
           COUNT(DISTINCT p.payment_id) as total_payments
    FROM buildings b
    LEFT JOIN rooms r ON b.building_id = r.building_id
    LEFT JOIN payments p ON r.room_id = p.room_id
    GROUP BY b.building_id
    ORDER BY b.name
");

// ดึงข้อมูลสถิติรายได้
$income_stats = Database::getInstance()->fetchAll("
    SELECT 
        b.name as building_name,
        COUNT(DISTINCT p.payment_id) as total_payments,
        SUM(CASE WHEN p.payment_type = 'room' THEN p.amount ELSE 0 END) as room_income,
        SUM(CASE WHEN p.payment_type = 'utility' THEN p.amount ELSE 0 END) as utility_income,
        SUM(CASE WHEN p.payment_type = 'deposit' THEN p.amount ELSE 0 END) as deposit_income,
        SUM(CASE WHEN p.payment_type = 'other' THEN p.amount ELSE 0 END) as other_income,
        SUM(p.amount) as total_income
    FROM payments p
    LEFT JOIN rooms r ON p.room_id = r.room_id
    LEFT JOIN buildings b ON r.building_id = b.building_id
    WHERE YEAR(p.payment_date) = ? AND MONTH(p.payment_date) = ?
    " . ($building_id ? "AND b.building_id = ?" : "") . "
    " . ($income_type ? "AND p.payment_type = ?" : "") . "
    GROUP BY b.building_id
    ORDER BY b.name
", array_filter([$year, $month, $building_id, $income_type]));

// ดึงข้อมูลแนวโน้มรายได้รายเดือน
$monthly_trends = Database::getInstance()->fetchAll("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        COUNT(*) as total_payments,
        SUM(CASE WHEN payment_type = 'room' THEN amount ELSE 0 END) as room_income,
        SUM(CASE WHEN payment_type = 'utility' THEN amount ELSE 0 END) as utility_income,
        SUM(CASE WHEN payment_type = 'deposit' THEN amount ELSE 0 END) as deposit_income,
        SUM(CASE WHEN payment_type = 'other' THEN amount ELSE 0 END) as other_income,
        SUM(amount) as total_income
    FROM payments
    WHERE YEAR(payment_date) = ?
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month
", [$year]);

// คำนวณสถิติรวม
$total_stats = [
    'total_payments' => 0,
    'room_income' => 0,
    'utility_income' => 0,
    'deposit_income' => 0,
    'other_income' => 0,
    'total_income' => 0
];

foreach ($income_stats as $stat) {
    $total_stats['total_payments'] += $stat['total_payments'];
    $total_stats['room_income'] += $stat['room_income'];
    $total_stats['utility_income'] += $stat['utility_income'];
    $total_stats['deposit_income'] += $stat['deposit_income'];
    $total_stats['other_income'] += $stat['other_income'];
    $total_stats['total_income'] += $stat['total_income'];
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>รายงานรายได้</h2>
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
                <label for="income_type" class="form-label">ประเภทรายได้</label>
                <select class="form-select" id="income_type" name="income_type">
                    <option value="">ทั้งหมด</option>
                    <option value="room" <?php echo $income_type == 'room' ? 'selected' : ''; ?>>ค่าเช่าห้อง</option>
                    <option value="utility" <?php echo $income_type == 'utility' ? 'selected' : ''; ?>>ค่าใช้จ่ายสาธารณูปโภค</option>
                    <option value="deposit" <?php echo $income_type == 'deposit' ? 'selected' : ''; ?>>เงินมัดจำ</option>
                    <option value="other" <?php echo $income_type == 'other' ? 'selected' : ''; ?>>อื่นๆ</option>
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
                <h5 class="card-title">รายได้ทั้งหมด</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['total_income'], 2); ?> บาท</h2>
                <small><?php echo number_format($total_stats['total_payments']); ?> ธุรกรรม</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">ค่าเช่าห้อง</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['room_income'], 2); ?> บาท</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">ค่าใช้จ่ายสาธารณูปโภค</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['utility_income'], 2); ?> บาท</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">เงินมัดจำ</h5>
                <h2 class="mb-0"><?php echo number_format($total_stats['deposit_income'], 2); ?> บาท</h2>
            </div>
        </div>
    </div>
</div>

<!-- กราฟแนวโน้มรายได้ -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">แนวโน้มรายได้รายเดือน</h5>
    </div>
    <div class="card-body">
        <canvas id="incomeTrendChart"></canvas>
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
                        <th>ธุรกรรม</th>
                        <th>ค่าเช่าห้อง</th>
                        <th>ค่าใช้จ่ายสาธารณูปโภค</th>
                        <th>เงินมัดจำ</th>
                        <th>อื่นๆ</th>
                        <th>รวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($income_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['building_name']); ?></td>
                            <td><?php echo number_format($stat['total_payments']); ?></td>
                            <td><?php echo number_format($stat['room_income'], 2); ?></td>
                            <td><?php echo number_format($stat['utility_income'], 2); ?></td>
                            <td><?php echo number_format($stat['deposit_income'], 2); ?></td>
                            <td><?php echo number_format($stat['other_income'], 2); ?></td>
                            <td><?php echo number_format($stat['total_income'], 2); ?></td>
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
        const ctx = document.getElementById('incomeTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function ($item) {
                            return date('M Y', strtotime($item['month'] . '-01'));
                        }, $monthly_trends)); ?>,
                datasets: [{
                    label: 'ค่าเช่าห้อง',
                    data: <?php echo json_encode(array_column($monthly_trends, 'room_income')); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }, {
                    label: 'ค่าใช้จ่ายสาธารณูปโภค',
                    data: <?php echo json_encode(array_column($monthly_trends, 'utility_income')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }, {
                    label: 'เงินมัดจำ',
                    data: <?php echo json_encode(array_column($monthly_trends, 'deposit_income')); ?>,
                    backgroundColor: 'rgba(255, 205, 86, 0.5)',
                    borderColor: 'rgb(255, 205, 86)',
                    borderWidth: 1
                }, {
                    label: 'อื่นๆ',
                    data: <?php echo json_encode(array_column($monthly_trends, 'other_income')); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.5)',
                    borderColor: 'rgb(153, 102, 255)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('th-TH', {
                                    style: 'currency',
                                    currency: 'THB'
                                });
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw.toLocaleString('th-TH', {
                                    style: 'currency',
                                    currency: 'THB'
                                });
                            }
                        }
                    }
                }
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>