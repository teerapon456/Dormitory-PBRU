<?php
require_once __DIR__ . '/../../../includes/header.php';

// แก้ไข SQL query เพื่อรองรับการค้นหา
$params = [];
$where = [];

if (!empty($_GET['room'])) {
    $where[] = "r.room_number LIKE ?";
    $params[] = "%" . $_GET['room'] . "%";
}

if (!empty($_GET['month'])) {
    $where[] = "DATE_FORMAT(ub.reading_time, '%Y-%m') = ?";
    $params[] = $_GET['month'];
}

if (!empty($_GET['building'])) {
    $where[] = "b.building_name LIKE ?";
    $params[] = "%" . $_GET['building'] . "%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// ดึงรายการบิล
$bills = Database::getInstance()->fetchAll("
    SELECT ub.*, 
           r.room_number,
           b.building_name
    FROM utility_bills ub
    LEFT JOIN rooms r ON ub.room_id = r.room_id
    LEFT JOIN buildings b ON r.building_id = b.building_id
    $whereClause
    ORDER BY ub.reading_time DESC
", $params);

// คำนวณยอดรวม
$total_amount = 0;
$total_paid = 0;
$total_pending = 0;

foreach ($bills as $bill) {
    $total_amount += $bill['amount'];
    if ($bill['status'] === 'ชำระแล้ว') {
        $total_paid += $bill['amount'];
    } else {
        $total_pending += $bill['amount'];
    }
}
?>
<!-- รายการบิล -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0">บิลค่าน้ำค่าไฟ</h2>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="building" class="form-control" placeholder="ค้นหาอาคาร"
                        aria-label="Search Building">
                </div>
                <div class="col-md-4">
                    <input type="text" name="room" class="form-control" placeholder="ค้นหาหมายเลขห้อง"
                        aria-label="Search Room">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="20%">วันที่อ่านมิเตอร์</th>
                        <th width="20%">กำหนดชำระ</th>
                        <th width="20%">ห้อง</th>
                        <th width="20%">อาคาร</th>
                        <th width="20%">ดาวน์โหลด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bills)): ?>
                        <tr>
                            <td colspan="8" class="text-center">ไม่พบข้อมูลบิล</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($bill['reading_time'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($bill['due_date'])); ?></td>
                                <td><?php echo htmlspecialchars($bill['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($bill['building_name']); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="combined_receipt.php?room_id=<?php echo $bill['room_id']; ?>&month=<?php echo date('m', strtotime($bill['reading_time'])); ?>&year=<?php echo date('Y', strtotime($bill['reading_time'])); ?>"
                                            class="btn btn-sm btn-outline-danger" target="_blank"
                                            title="ดาวน์โหลดใบเสร็จรวมค่าน้ำค่าไฟ">
                                            <i class="fas fa-file-pdf fa-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ปุ่มกด -->
<button class="btn btn-primary">บันทึก</button>

<!-- การ์ด -->
<div class="card">
    <div class="card-header">หัวข้อ</div>
    <div class="card-body">เนื้อหา</div>
</div>

<!-- ฟอร์ม -->
<div class="form-group">
    <label class="form-label">ชื่อ</label>
    <input type="text" class="form-control">
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>