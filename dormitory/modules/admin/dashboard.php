<?php
$pageTitle = "แดชบอร์ดผู้ดูแลระบบ";
require_once __DIR__ . '/../../includes/header.php';

// ตรวจสอบสิทธิ์การเข้าถึง
if (!Auth::isAdmin()) {
    $_SESSION['error'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    header('Location: ' . Config::$baseUrl . '/login.php');
    exit;
}

// ดึงข้อมูลสถิติต่างๆ
$stats = Database::getInstance()->fetch("
    SELECT 
        -- สถิติห้องพัก
        (SELECT COUNT(*) FROM rooms) as total_rooms,
        (SELECT COUNT(*) FROM rooms WHERE current_occupancy < max_capacity) as available_rooms,
        (SELECT COUNT(*) FROM rooms WHERE current_occupancy = max_capacity) as occupied_rooms,
        (SELECT COUNT(*) FROM rooms WHERE current_occupancy = 0) as maintenance_rooms,
        
        -- สถิตินักศึกษา
        (SELECT COUNT(*) FROM users WHERE role = 'นักศึกษา') as total_students,
        (SELECT SUM(current_occupancy) FROM rooms) as total_occupants,
        
        -- สถิติการแจ้งซ่อม
        (SELECT COUNT(*) FROM repair_requests WHERE status = 'รอดำเนินการ') as pending_repairs,
        (SELECT COUNT(*) FROM repair_requests WHERE status = 'กำลังดำเนินการ') as ongoing_repairs,
        (SELECT COUNT(*) FROM repair_requests WHERE status = 'เสร็จสิ้น') as completed_repairs,
        (SELECT COUNT(*) FROM repair_requests WHERE status = 'ยกเลิก') as cancelled_repairs,
        
        -- สถิติค่าสาธารณูปโภค
        (SELECT COUNT(*) FROM utility_bills WHERE status = 'รอดำเนินการ') as pending_bills,
        (SELECT COUNT(*) FROM utility_bills WHERE status = 'ชำระแล้ว') as paid_bills,
        (SELECT COUNT(*) FROM utility_bills WHERE status = 'เลยกำหนด') as overdue_bills,
        
        -- สถิติอาคาร
        (SELECT COUNT(*) FROM buildings) as total_buildings,
        (SELECT COUNT(DISTINCT floor_number) FROM rooms) as total_floors
");

// ดึงการแจ้งซ่อมล่าสุด
$latest_repairs = Database::getInstance()->fetchAll("
    SELECT 
        r.*,
        u.full_name,
        rm.room_number,
        b.building_name,
        i.item_name,
        c.category_name,
        CASE 
            WHEN r.user_id IS NOT NULL THEN u.full_name
            WHEN r.contact_id IS NOT NULL THEN pc.full_name
            ELSE 'ไม่ระบุ'
        END as requester_name
    FROM repair_requests r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN rooms rm ON r.room_id = rm.room_id
    LEFT JOIN buildings b ON rm.building_id = b.building_id
    LEFT JOIN repair_items i ON r.item_id = i.item_id
    LEFT JOIN repair_categories c ON i.category_id = c.category_id
    LEFT JOIN public_repair_contacts pc ON r.contact_id = pc.contact_id
    ORDER BY r.created_time DESC 
    LIMIT 5
");

// ดึงสถิติการแจ้งซ่อมรายเดือน
$monthly_repairs = Database::getInstance()->fetchAll("
    SELECT 
        DATE_FORMAT(created_time, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'เสร็จสิ้น' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'รอดำเนินการ' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'กำลังดำเนินการ' THEN 1 ELSE 0 END) as in_progress
    FROM repair_requests
    WHERE created_time >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_time, '%Y-%m')
    ORDER BY month DESC
");

// ดึงสถิติประเภทการซ่อม
$repair_categories = Database::getInstance()->fetchAll("
    SELECT 
        c.category_name,
        COUNT(r.request_id) as total_repairs,
        SUM(CASE WHEN r.status = 'เสร็จสิ้น' THEN 1 ELSE 0 END) as completed_repairs
    FROM repair_categories c
    LEFT JOIN repair_items i ON c.category_id = i.category_id
    LEFT JOIN repair_requests r ON i.item_id = r.item_id
    GROUP BY c.category_id, c.category_name
    ORDER BY total_repairs DESC
");

// กำหนด current_page สำหรับ sidebar
$current_page = 'dashboard';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="px-4 py-3">
                <!-- หัวข้อหน้า -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">แดชบอร์ดผู้ดูแลระบบ</h2>
                        <p class="text-muted">ภาพรวมและสถิติการดำเนินงาน</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>พิมพ์รายงาน
                        </button>
                    </div>
                </div>

                <!-- สถิติภาพรวม -->
                <div class="row g-4 mb-4">
                    <!-- สถิติห้องพัก -->
                    <div class="col-md-6 col-xl-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-door-open text-primary fa-2x"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">ห้องพักทั้งหมด</h6>
                                        <h3 class="mb-0"><?php echo number_format($stats['total_rooms']); ?></h3>
                                    </div>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <?php
                                    $occupied_percent = ($stats['total_rooms'] > 0) ? ($stats['occupied_rooms'] / $stats['total_rooms'] * 100) : 0;
                                    $available_percent = ($stats['total_rooms'] > 0) ? ($stats['available_rooms'] / $stats['total_rooms'] * 100) : 0;
                                    $maintenance_percent = ($stats['total_rooms'] > 0) ? ($stats['maintenance_rooms'] / $stats['total_rooms'] * 100) : 0;
                                    ?>
                                    <div class="progress-bar bg-success"
                                        style="width: <?php echo $occupied_percent; ?>%" title="เต็ม"></div>
                                    <div class="progress-bar bg-warning"
                                        style="width: <?php echo $available_percent; ?>%" title="ว่าง"></div>
                                    <div class="progress-bar bg-danger"
                                        style="width: <?php echo $maintenance_percent; ?>%" title="ปิดปรับปรุง"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>เต็ม: <?php echo $stats['occupied_rooms']; ?></span>
                                    <span>ว่าง: <?php echo $stats['available_rooms']; ?></span>
                                    <span>ปิดปรับปรุง: <?php echo $stats['maintenance_rooms']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- สถิตินักศึกษา -->
                    <div class="col-md-6 col-xl-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-success bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-users text-success fa-2x"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">นักศึกษาทั้งหมด</h6>
                                        <h3 class="mb-0"><?php echo number_format($stats['total_students']); ?></h3>
                                    </div>
                                </div>
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-bed me-1"></i>
                                    อัตราการเข้าพัก: <?php
                                                        $total_capacity = $stats['total_rooms'] * 4; // สมมติว่าห้องละ 4 คน
                                                        $occupancy_rate = ($total_capacity > 0) ?
                                                            round(($stats['total_occupants'] / $total_capacity) * 100, 1) : 0;
                                                        echo $occupancy_rate;
                                                        ?>%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- สถิติการแจ้งซ่อม -->
                    <div class="col-md-6 col-xl-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-tools text-warning fa-2x"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">การแจ้งซ่อมที่รอดำเนินการ</h6>
                                        <h3 class="mb-0"><?php echo number_format($stats['pending_repairs']); ?></h3>
                                    </div>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <?php
                                    $total_repairs = $stats['pending_repairs'] + $stats['ongoing_repairs'] + $stats['completed_repairs'] + $stats['cancelled_repairs'];
                                    $pending_percent = ($total_repairs > 0) ? ($stats['pending_repairs'] / $total_repairs * 100) : 0;
                                    $ongoing_percent = ($total_repairs > 0) ? ($stats['ongoing_repairs'] / $total_repairs * 100) : 0;
                                    $completed_percent = ($total_repairs > 0) ? ($stats['completed_repairs'] / $total_repairs * 100) : 0;
                                    ?>
                                    <div class="progress-bar bg-warning" style="width: <?php echo $pending_percent; ?>%"
                                        title="รอดำเนินการ"></div>
                                    <div class="progress-bar bg-info" style="width: <?php echo $ongoing_percent; ?>%"
                                        title="กำลังดำเนินการ"></div>
                                    <div class="progress-bar bg-success"
                                        style="width: <?php echo $completed_percent; ?>%" title="เสร็จสิ้น"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>รอดำเนินการ: <?php echo $stats['pending_repairs']; ?></span>
                                    <span>กำลังดำเนินการ: <?php echo $stats['ongoing_repairs']; ?></span>
                                    <span>เสร็จสิ้น: <?php echo $stats['completed_repairs']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- แผนภูมิและตาราง -->
                <div class="row g-4">
                    <!-- กราฟแสดงสถิติรายเดือน -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">สถิติการแจ้งซ่อมรายเดือน</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="repairChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- รายการแจ้งซ่อมล่าสุด -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">การแจ้งซ่อมล่าสุด</h5>
                                    <a href="/dormitory/modules/admin/repairs.php"
                                        class="btn btn-sm btn-primary">ดูทั้งหมด</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ห้อง</th>
                                                <th>รายการ</th>
                                                <th>สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latest_repairs as $repair): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($repair['room_number']): ?>
                                                            <?php echo htmlspecialchars($repair['building_name'] . ' ' . $repair['room_number']); ?>
                                                        <?php else: ?>
                                                            <?php echo 'พื้นที่ส่วนกลาง'; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 150px;"
                                                            title="<?php echo htmlspecialchars($repair['description']); ?>">
                                                            <?php
                                                            echo htmlspecialchars($repair['item_name'] ? $repair['item_name'] : $repair['title']);
                                                            ?>
                                                        </div>
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
                                                        <span
                                                            class="badge bg-<?php echo $status_class[$repair['status']]; ?>">
                                                            <?php echo htmlspecialchars($repair['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ข้อมูลสำหรับกราฟ
            const monthlyData = <?php echo json_encode($monthly_repairs); ?>;

            // เตรียมข้อมูลสำหรับ Chart.js
            const labels = monthlyData.map(item => {
                const [year, month] = item.month.split('-');
                return `${month}/${year}`;
            }).reverse();

            const totalRepairs = monthlyData.map(item => item.total).reverse();
            const completedRepairs = monthlyData.map(item => item.completed).reverse();
            const pendingRepairs = monthlyData.map(item => item.pending).reverse();
            const inProgressRepairs = monthlyData.map(item => item.in_progress).reverse();

            // สร้างกราฟ
            const ctx = document.getElementById('repairChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'การแจ้งซ่อมทั้งหมด',
                        data: totalRepairs,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true
                    }, {
                        label: 'ดำเนินการเสร็จสิ้น',
                        data: completedRepairs,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true
                    }, {
                        label: 'รอดำเนินการ',
                        data: pendingRepairs,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true
                    }, {
                        label: 'กำลังดำเนินการ',
                        data: inProgressRepairs,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
    </script>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>